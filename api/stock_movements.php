<?php
/**
 * /api/stock_movements.php
 * Records stock-in / stock-out movements against dbo.ims_inventory and
 * logs each one to dbo.ims_stock_movements, so quantity changes always
 * leave an auditable trail instead of being silently overwritten.
 *
 * GET  /api/stock_movements.php  → paginated movement ledger (the activity log)
 * POST /api/stock_movements.php
 *   { sku_id, movement_type: 'in'|'out', quantity, reference_code?, notes? }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login_json();

$method = $_SERVER['REQUEST_METHOD'];
$db = Connection::get_connecton();

if ($method === 'GET') {
    require_permission_json('Inventory', 'view');

    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $search = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? '';
    $skuId = (int)($_GET['sku_id'] ?? 0);

    $where = [];
    $params = [];

    if ($skuId > 0) {
        $where[] = 'm.sku_id = ?';
        $params[] = $skuId;
    }
    if (in_array($type, ['in', 'out'], true)) {
        $where[] = 'm.movement_type = ?';
        $params[] = $type;
    }
    if ($search !== '') {
        $like = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $search) . '%';
        $where[] = '(i.sku_code LIKE ? OR i.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR m.reference_code LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Running stock level after each movement — only meaningful for a single
    // item's ledger, so it's omitted from the mixed activity-log view.
    //
    // Worked out BACKWARDS from quantity_on_hand (subtracting every movement
    // newer than the current row) rather than forwards from zero. Forwards
    // only reconciles when an item's opening stock was written as an INITIAL
    // movement, which items created before that behaviour existed don't have
    // — for those, a forward total drifts by the unrecorded opening balance
    // and can even read negative. quantity_on_hand is authoritative and can
    // only change through this endpoint (see api/inventory.php's PUT), so
    // walking back from it is correct for old and new items alike.
    //
    // Pagination-safe: window functions are evaluated after WHERE but before
    // OFFSET/FETCH, so the frame spans the item's whole history instead of
    // restarting on each page. The newest row has an empty frame, hence the
    // ISNULL — its balance is simply the current quantity on hand.
    $balanceSelect = '';
    if ($skuId > 0) {
        $balanceSelect = ",
               i.quantity_on_hand - ISNULL(
                   SUM(CASE WHEN m.movement_type = 'in' THEN m.quantity ELSE -m.quantity END)
                       OVER (ORDER BY m.created_at DESC, m.movement_id DESC
                             ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS balance_after";
    }

    $stmt = $db->prepare("
        SELECT m.movement_id, m.movement_type, m.quantity, m.reference_code, m.notes, m.created_at,
               i.sku_id, i.sku_code, i.name AS item_name,
               u.user_id, u.first_name, u.last_name,
               COUNT(*) OVER() as total_count$balanceSelect
        FROM dbo.ims_stock_movements m
        JOIN dbo.ims_inventory i ON m.sku_id = i.sku_id
        JOIN dbo.ims_users u ON m.user_id = u.user_id
        $whereSql
        ORDER BY m.created_at DESC, m.movement_id DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
    ");
    $paramIndex = 1;
    foreach ($params as $p) {
        // sku_id is an int; binding it as a string would force an implicit
        // conversion on the indexed column.
        $stmt->bindValue($paramIndex++, $p, is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $movements = $stmt->fetchAll();

    $total = $movements[0]['total_count'] ?? 0;
    json_success([
        'movements' => $movements,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    ]);
}

if ($method !== 'POST') {
    json_error('Method not allowed', 405);
}

require_permission_json('Inventory', 'edit');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$sku_id = (int)($data['sku_id'] ?? 0);
$movement_type = $data['movement_type'] ?? '';
$quantity = (int)($data['quantity'] ?? 0);
$reference_code = trim($data['reference_code'] ?? '');
$notes = trim($data['notes'] ?? '');

if (!$sku_id || !in_array($movement_type, ['in', 'out'], true) || $quantity <= 0) {
    json_error('sku_id, a valid movement_type (in/out), and a positive quantity are required', 400);
}

$user = logged_in_user();

try {
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT * FROM dbo.ims_inventory WHERE sku_id = ?');
    $stmt->bindValue(1, $sku_id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch();

    if (!$item) {
        $db->rollBack();
        json_error('Item not found', 404);
    }

    if ($item['status'] === 'archived') {
        $db->rollBack();
        json_error('Cannot record movement: item is archived. Restore it first.', 409);
    }

    if ($movement_type === 'out' && $quantity > (int)$item['quantity_on_hand']) {
        $db->rollBack();
        json_error('Not enough stock on hand: only ' . $item['quantity_on_hand'] . ' available', 409);
    }

    $delta = $movement_type === 'in' ? $quantity : -$quantity;

    $update = $db->prepare('
        UPDATE dbo.ims_inventory
        SET quantity_on_hand = quantity_on_hand + ?, updated_at = GETUTCDATE()
        WHERE sku_id = ?
    ');
    $update->bindValue(1, $delta, PDO::PARAM_INT);
    $update->bindValue(2, $sku_id, PDO::PARAM_INT);
    $update->execute();

    $insert = $db->prepare('
        INSERT INTO dbo.ims_stock_movements (sku_id, user_id, movement_type, quantity, reference_code, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $insert->bindValue(1, $sku_id, PDO::PARAM_INT);
    $insert->bindValue(2, (int)$user['id'], PDO::PARAM_INT);
    $insert->bindValue(3, $movement_type);
    $insert->bindValue(4, $quantity, PDO::PARAM_INT);
    $insert->bindValue(5, $reference_code !== '' ? $reference_code : null);
    $insert->bindValue(6, $notes !== '' ? $notes : null);
    $insert->execute();

    $db->commit();

    $updated = $db->prepare('SELECT * FROM dbo.ims_inventory WHERE sku_id = ?');
    $updated->bindValue(1, $sku_id, PDO::PARAM_INT);
    $updated->execute();

    json_success($updated->fetch(), 'Stock movement recorded successfully', 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Stock movement API error: ' . $e->getMessage());
    json_error('Server error', 500);
}

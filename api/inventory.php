<?php
/**
 * /api/inventory.php
 * CRUD endpoint for inventory management.
 
 * GET  /api/inventory.php                  → list all inventory items
 * GET  /api/inventory.php?id=1             → get single item
 * POST /api/inventory.php                  → add new item
 * PUT  /api/inventory.php                  → update item
 * DELETE /api/inventory.php?id=1           → delete item
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login();

$method = $_SERVER['REQUEST_METHOD'];
$db = Connection::get_connecton();

try {
    if ($method === 'GET') {
        require_permission('Inventory', 'view');

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare('SELECT * FROM dbo.ims_inventory WHERE sku_id = ?');
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) {
                json_error('Item not found', 404);
            }

            json_success($item);
        } else {
            // List inventory items, filtered by status (default: active only)
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $status = $_GET['status'] ?? 'active';
            $lowStockOnly = ($_GET['low_stock'] ?? '') === '1';
            $filterByStatus = in_array($status, ['active', 'archived'], true);

            $conditions = [];
            if ($filterByStatus) {
                $conditions[] = 'status = ?';
            }
            if ($lowStockOnly) {
                $conditions[] = 'quantity_on_hand <= reorder_level';
            }
            $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
            $orderBy = $lowStockOnly ? '(quantity_on_hand - reorder_level) ASC, sku_code' : 'sku_code';

            $stmt = $db->prepare("
                SELECT *,
                       COUNT(*) OVER() as total_count,
                       SUM(quantity_on_hand) OVER() as total_units,
                       SUM(quantity_on_hand * ISNULL(unit_cost, 0)) OVER() as total_value,
                       SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) OVER() as low_stock_count
                FROM dbo.ims_inventory
                $where
                ORDER BY $orderBy
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
            ");
            $paramIndex = 1;
            if ($filterByStatus) {
                $stmt->bindValue($paramIndex++, $status);
            }
            $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
            $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();

            $total = $items[0]['total_count'] ?? 0;
            json_success([
                'items' => $items,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'stats' => [
                    'total_units' => (int)($items[0]['total_units'] ?? 0),
                    'total_value' => (float)($items[0]['total_value'] ?? 0),
                    'low_stock_count' => (int)($items[0]['low_stock_count'] ?? 0),
                ],
            ]);
        }
    }

    elseif ($method === 'POST') {
        require_permission('Inventory', 'create');

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $sku_code = trim($data['sku_code'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $quantity = (int)($data['quantity_on_hand'] ?? 0);
        $reorder = (int)($data['reorder_level'] ?? 0);
        $cost = (float)($data['unit_cost'] ?? 0);

        if (!$sku_code || !$name) {
            json_error('Missing required fields: sku_code, name', 400);
        }

        if ($quantity < 1) {
            json_error('Initial quantity must be at least 1', 400);
        }

        // Check if SKU already exists
        $check = $db->prepare('SELECT 1 FROM dbo.ims_inventory WHERE sku_code = ?');
        $check->execute([$sku_code]);
        if ($check->fetch()) {
            json_error('SKU code already exists', 409);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO dbo.ims_inventory (sku_code, name, description, quantity_on_hand, reorder_level, unit_cost)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$sku_code, $name, $description ?: null, $quantity, $reorder, $cost ?: null]);

            $id = $db->lastInsertId();

            // Starting stock is logged as an initial "received" movement so it
            // shows up in the activity ledger and the audit trail is complete
            // from day one, instead of only tracking changes after creation.
            if ($quantity > 0) {
                $user = logged_in_user();
                $move = $db->prepare('
                    INSERT INTO dbo.ims_stock_movements (sku_id, user_id, movement_type, quantity, reference_code, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $move->bindValue(1, $id, PDO::PARAM_INT);
                $move->bindValue(2, (int)$user['id'], PDO::PARAM_INT);
                $move->bindValue(3, 'in');
                $move->bindValue(4, $quantity, PDO::PARAM_INT);
                $move->bindValue(5, 'INITIAL');
                $move->bindValue(6, 'Initial stock on item creation');
                $move->execute();
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        $item = $db->prepare('SELECT * FROM dbo.ims_inventory WHERE sku_id = ?');
        $item->execute([$id]);

        json_success($item->fetch(), 'Item created successfully', 201);
    }

    elseif ($method === 'PUT') {
        require_permission('Inventory', 'edit');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            json_error('Missing required field: id', 400);
        }

        $id = (int)$data['id'];
        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $params[] = trim($data['name']);
        }
        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $params[] = trim($data['description']) ?: null;
        }
        // quantity_on_hand is intentionally not editable here — it can only
        // change via /api/stock_movements.php so every change is logged.
        if (isset($data['reorder_level'])) {
            $updates[] = 'reorder_level = ?';
            $params[] = (int)$data['reorder_level'];
        }
        if (isset($data['unit_cost'])) {
            $updates[] = 'unit_cost = ?';
            $params[] = (float)$data['unit_cost'];
        }
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'archived'], true)) {
                json_error('Invalid status: must be active or archived', 400);
            }
            $updates[] = 'status = ?';
            $params[] = $data['status'];
        }

        if (empty($updates)) {
            json_error('No fields to update', 400);
        }

        $updates[] = 'updated_at = GETUTCDATE()';
        $params[] = $id;

        $sql = 'UPDATE dbo.ims_inventory SET ' . implode(', ', $updates) . ' WHERE sku_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            json_error('Item not found', 404);
        }

        $item = $db->prepare('SELECT * FROM dbo.ims_inventory WHERE sku_id = ?');
        $item->execute([$id]);

        json_success($item->fetch(), 'Item updated successfully');
    }

    elseif ($method === 'DELETE') {
        require_permission('Inventory', 'delete');

        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            json_error('Missing required parameter: id', 400);
        }

        try {
            $stmt = $db->prepare('DELETE FROM dbo.ims_inventory WHERE sku_id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_error('Cannot delete this item: it has recorded stock movements.', 409);
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            json_error('Item not found', 404);
        }

        json_success(null, 'Item deleted successfully');
    }

    else {
        json_error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('Inventory API error: ' . $e->getMessage());
    json_error('Server error', 500);
}

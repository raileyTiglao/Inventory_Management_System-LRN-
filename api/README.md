# API

PHP backend endpoints for CRUD operations, to be implemented next phase.

Planned structure (mirrors the `dbo.ims_*` table names):

- `users.php`      — GET/POST/PUT/DELETE for `dbo.ims_users`
- `roles.php`       — GET/POST/PUT/DELETE for `dbo.ims_roles`
- `permissions.php` — GET/POST for `dbo.ims_role_permissions`
- `inventory.php`   — GET/POST/PUT/DELETE for `dbo.ims_inventory`

Each endpoint will:
1. `require auth/rbac.php` and call `require_permission($module, $action)`
2. Read input (JSON body or `$_POST`)
3. Use `connection/db.php`'s `Connection::get_connecton()` for parameterized PDO queries
4. Return a JSON response with a consistent `{ success, data, message }` shape

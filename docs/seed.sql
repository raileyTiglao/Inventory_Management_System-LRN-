-- ============================================================================
-- Seed Data
-- ============================================================================

-- Insert roles
IF NOT EXISTS (SELECT 1 FROM dbo.ims_roles WHERE role_name = 'Administrator')
BEGIN
    INSERT INTO dbo.ims_roles (role_name, description)
    VALUES 
        ('Administrator', 'Full system access, including RBAC configuration.'),
        ('Warehouse Manager', 'Manage inventory, approve stock movements.'),
        ('Warehouse Staff', 'Record stock in/out, view assigned items.'),
        ('Auditor', 'Read-only access to reports and history.');
END
GO

-- Insert permissions
IF NOT EXISTS (SELECT 1 FROM dbo.ims_permissions WHERE module_name = 'Dashboard')
BEGIN
    INSERT INTO dbo.ims_permissions (module_name, action, description)
    VALUES
        ('Dashboard', 'view', 'View dashboard'),
        ('Dashboard', 'create', 'Create dashboard'),
        ('Dashboard', 'edit', 'Edit dashboard'),
        ('Dashboard', 'delete', 'Delete dashboard'),
        ('Inventory', 'view', 'View inventory'),
        ('Inventory', 'create', 'Add inventory item'),
        ('Inventory', 'edit', 'Edit inventory item'),
        ('Inventory', 'delete', 'Delete inventory item'),
        ('Users', 'view', 'View users'),
        ('Users', 'create', 'Add user'),
        ('Users', 'edit', 'Edit user'),
        ('Users', 'delete', 'Delete user'),
        ('Roles & Permissions', 'view', 'View roles'),
        ('Roles & Permissions', 'create', 'Add role'),
        ('Roles & Permissions', 'edit', 'Edit role'),
        ('Roles & Permissions', 'delete', 'Delete role'),
        ('Settings', 'view', 'View settings'),
        ('Settings', 'edit', 'Edit settings');
END
GO

-- Insert role permissions (Administrator = full access)
IF NOT EXISTS (SELECT 1 FROM dbo.ims_role_permissions WHERE role_id = 1)
BEGIN
    INSERT INTO dbo.ims_role_permissions (role_id, permission_id)
    SELECT 1, permission_id FROM dbo.ims_permissions;
    
    -- Warehouse Manager: view/edit inventory, view users, view dashboard
    INSERT INTO dbo.ims_role_permissions (role_id, permission_id)
    SELECT 2, permission_id FROM dbo.ims_permissions 
    WHERE module_name IN ('Dashboard', 'Inventory') OR (module_name = 'Users' AND action = 'view');
    
    -- Warehouse Staff: view dashboard, view/create/edit inventory
    INSERT INTO dbo.ims_role_permissions (role_id, permission_id)
    SELECT 3, permission_id FROM dbo.ims_permissions 
    WHERE (module_name = 'Dashboard' AND action = 'view')
       OR (module_name = 'Inventory' AND action IN ('view', 'create', 'edit'));
    
    -- Auditor: view everything
    INSERT INTO dbo.ims_role_permissions (role_id, permission_id)
    SELECT 4, permission_id FROM dbo.ims_permissions 
    WHERE action = 'view';
END
GO

-- Insert test users
-- Passwords: use PHP password_hash('password123', PASSWORD_BCRYPT) to generate hashes
-- For testing: $2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO (bcrypt of 'password123')
IF NOT EXISTS (SELECT 1 FROM dbo.ims_users WHERE email = 'alex.rivera@ims.local')
BEGIN
    INSERT INTO dbo.ims_users (email, first_name, last_name, password_hash, role_id, status)
    VALUES
        ('jWick@ims.local', 'John', 'Wick', '$2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO', 1, 'active'),
        ('tStark@ims.local', 'Tony', 'Stark', '$2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO', 2, 'active'),
        ('pParker@ims.local', 'Peter', 'Parker', '$2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO', 3, 'active'),
        ('bWayne@ims.local', 'Brunce', 'Wayne', '$2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO', 3, 'inactive'),
        ('jDoe@ims.local', 'John', 'Doe', '$2y$10$rE4BJg1NzxgMZIZJ37UNm.eBAAv32bOPuHw27eaIRXR9CgljA1gpO', 4, 'active');
END
GO

-- Insert sample inventory items
IF NOT EXISTS (SELECT 1 FROM dbo.ims_inventory WHERE sku_code = 'SKU-2291')
BEGIN
    INSERT INTO dbo.ims_inventory (sku_code, name, description, quantity_on_hand, reorder_level, unit_cost)
    VALUES
        ('SKU-2291', 'Steel Bracket M8', 'Zinc-plated steel L-bracket, 50x50x50mm', 340, 100, 8.50),
        ('SKU-1187', 'Hex Bolt 10mm', 'Stainless steel hex bolt, M10 x 50mm', 520, 200, 2.25),
        ('SKU-0456', 'Gasket Ring 2in', 'EPDM rubber gasket, 2 inch ID', 8, 20, 4.75),
        ('SKU-0912', 'Copper Wire 1.5mm', 'Bare copper wire, 1.5mm diameter', 14, 30, 15.00),
        ('SKU-1330', 'Cable Tie 300mm', 'Nylon cable tie, 300mm length, UV-resistant', 22, 50, 0.45);
END
GO

-- ============================================================================
-- Create Indexes for Performance
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.ims_users') AND name = 'IX_ims_users_email')
BEGIN
    CREATE INDEX IX_ims_users_email ON dbo.ims_users(email);
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.ims_inventory') AND name = 'IX_ims_inventory_sku_code')
BEGIN
    CREATE INDEX IX_ims_inventory_sku_code ON dbo.ims_inventory(sku_code);
END
GO

PRINT 'IMS Database schema created successfully!';
PRINT 'Test credentials:';
PRINT '  Email: tStark@ims.local';
PRINT '  Password: password123';
GO

-- IMS Database Schema

-- ============================================================================
-- Create Database
-- ============================================================================
USE LRNPH_OJT;
GO

-- ============================================================================
-- dbo.ims_roles — role definitions
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_roles')
BEGIN
    CREATE TABLE dbo.ims_roles (
        role_id INT PRIMARY KEY IDENTITY(1,1),
        role_name NVARCHAR(100) NOT NULL UNIQUE,
        description NVARCHAR(500),
        created_at DATETIME DEFAULT GETUTCDATE(),
        updated_at DATETIME DEFAULT GETUTCDATE()
    );
END
GO

-- ============================================================================
-- dbo.ims_users — user accounts with role assignment
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_users')
BEGIN
    CREATE TABLE dbo.ims_users (
        user_id INT PRIMARY KEY IDENTITY(1,1),
        email NVARCHAR(255) NOT NULL UNIQUE,
        first_name NVARCHAR(100) NOT NULL,
        last_name NVARCHAR(100) NOT NULL,
        password_hash NVARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        status NVARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
        last_login DATETIME,
        created_at DATETIME DEFAULT GETUTCDATE(),
        updated_at DATETIME DEFAULT GETUTCDATE(),
        FOREIGN KEY (role_id) REFERENCES dbo.ims_roles(role_id)
    );
END
GO

-- ============================================================================
-- dbo.ims_permissions — granular permissions (view, create, edit, delete per module)
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_permissions')
BEGIN
    CREATE TABLE dbo.ims_permissions (
        permission_id INT PRIMARY KEY IDENTITY(1,1),
        module_name NVARCHAR(100) NOT NULL,
        action NVARCHAR(50) NOT NULL,
        description NVARCHAR(255),
        UNIQUE (module_name, action)
    );
END
GO

-- ============================================================================
-- dbo.ims_role_permissions — junction table: which roles have which permissions
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_role_permissions')
BEGIN
    CREATE TABLE dbo.ims_role_permissions (
        role_permission_id INT PRIMARY KEY IDENTITY(1,1),
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        FOREIGN KEY (role_id) REFERENCES dbo.ims_roles(role_id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES dbo.ims_permissions(permission_id) ON DELETE CASCADE,
        UNIQUE (role_id, permission_id)
    );
END
GO

-- ============================================================================
-- dbo.ims_inventory — inventory items/SKUs
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_inventory')
BEGIN
    CREATE TABLE dbo.ims_inventory (
        sku_id INT PRIMARY KEY IDENTITY(1,1),
        sku_code NVARCHAR(50) NOT NULL UNIQUE,
        name NVARCHAR(255) NOT NULL,
        description NVARCHAR(500),
        quantity_on_hand INT DEFAULT 0,
        reorder_level INT DEFAULT 0,
        unit_cost DECIMAL(12, 2),
        status NVARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'archived')),
        created_at DATETIME DEFAULT GETUTCDATE(),
        updated_at DATETIME DEFAULT GETUTCDATE()
    );
END
GO

-- Migration for pre-existing databases created before the `status` column
-- was added (archiving instead of hard-deleting items with movement history).
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ims_inventory' AND COLUMN_NAME = 'status')
BEGIN
    ALTER TABLE dbo.ims_inventory ADD status NVARCHAR(20) NOT NULL DEFAULT 'active' CONSTRAINT CK_ims_inventory_status CHECK (status IN ('active', 'archived'));
END
GO

-- ============================================================================
-- dbo.ims_stock_movements — log of all stock in/out transactions
-- ============================================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ims_stock_movements')
BEGIN
    CREATE TABLE dbo.ims_stock_movements (
        movement_id INT PRIMARY KEY IDENTITY(1,1),
        sku_id INT NOT NULL,
        user_id INT NOT NULL,
        movement_type NVARCHAR(20) NOT NULL CHECK (movement_type IN ('in', 'out')),
        quantity INT NOT NULL,
        reference_code NVARCHAR(100),
        notes NVARCHAR(500),
        created_at DATETIME DEFAULT GETUTCDATE(),
        FOREIGN KEY (sku_id) REFERENCES dbo.ims_inventory(sku_id),
        FOREIGN KEY (user_id) REFERENCES dbo.ims_users(user_id)
    );
END
GO


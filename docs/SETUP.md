# IMS Setup Guide

## Prerequisites
- XAMPP with PHP 8.3+
- SQL Server (2019+) with SSMS
- PDO SQL Server driver for PHP

## Step 1: SQL Server Setup

### Create the Database
1. Open **SQL Server Management Studio (SSMS)**
2. Connect to your local SQL Server instance
3. Run the script `docs/schema.sql`:
   - Copy the entire contents of `docs/schema.sql`
   - Open a new query window in SSMS
   - Paste the script
   - Press **Execute** (F5)

This will:
- Create the `IMS_DB` database
- Create all required tables with the `dbo.ims_*` naming convention
- Create indexes for performance
- Seed test data (users, roles, permissions, sample inventory)

**Test credentials after running the schema:**
```
Email:    alex.rivera@ims.local
Password: password123
```

## Step 2: PHP SQL Server Driver Setup (XAMPP)

### Install the Driver

1. **Download the SQL Server driver for your PHP version:**
   - Go to https://github.com/microsoft/msphpsql/releases
   - Download the **Thread Safe (TS)** version matching your PHP version
   - Example: For PHP 8.3 TS, download `php-8.3-ts-*.zip`

2. **Extract and copy the DLL files:**
   - Extract the downloaded zip
   - Copy `php_sqlsrv_83_ts.dll` and `php_pdo_sqlsrv_83_ts.dll` to:
     ```
     C:\xampp\php\ext\
     ```

3. **Enable the extensions in php.ini:**
   - Open `C:\xampp\php\php.ini`
   - Find the `[Dynamic Extensions]` section (around line 900)
   - Add these two lines:
     ```ini
     extension=php_sqlsrv_83_ts.dll
     extension=php_pdo_sqlsrv_83_ts.dll
     ```
   - Save the file

4. **Restart Apache:**
   - Stop Apache in XAMPP Control Panel
   - Start Apache again

5. **Verify the extensions loaded:**
   - Create a test file `C:\xampp\htdocs\phpinfo.php` with:
     ```php
     <?php phpinfo(); ?>
     ```
   - Visit `http://localhost/phpinfo.php` in your browser
   - Search for "sqlsrv" — you should see both extensions listed

## Step 3: Configure IMS Database Connection

1. Open `ims/connection/db.php`
2. Update the credentials at the top of the function:
   ```php
   $host = 'localhost';      // Your SQL Server instance (or server name)
   $db   = 'IMS_DB';         // Database name (matches what you created)
   $user = 'sa';             // SQL Server user
   $pass = '';               // SQL Server password
   ```

## Step 4: Deploy IMS to XAMPP

1. Copy the entire `ims` folder to `C:\xampp\htdocs\`
   - So you have: `C:\xampp\htdocs\ims\`

2. Verify `config.php` has the correct `BASE_URL`:
   ```php
   define('BASE_URL', '/ims');
   ```
   - This assumes you placed it in `htdocs/ims`
   - If you placed it in the root instead, change to `''`

## Step 5: Test the Installation

1. Start Apache and MySQL in XAMPP Control Panel
2. Visit: `http://localhost/ims/pages/login.php`
3. Sign in with test credentials:
   ```
   Email:    alex.rivera@ims.local
   Password: password123
   ```

You should see the Dashboard with mock data populated.

## Troubleshooting

### "Connection refused" or "Database connection failed"
- Verify SQL Server is running
- Check that the server name, database name, and credentials in `connection/db.php` match your setup
- Ensure the SQL Server driver is installed and enabled (check `phpinfo.php`)

### "SQLSTATE[IMSSP]" errors
- These are SQL Server driver errors
- Check `php_sapi_name()` — if it says "apache2handler", restart Apache after installing extensions
- Verify both `php_sqlsrv_*` and `php_pdo_sqlsrv_*` DLLs are in `php/ext/` and enabled in `php.ini`

### "Access Denied" when trying to sign in
- Verify the test data was seeded by running `schema.sql` again
- Check that the password hash is correct in `dbo.ims_users`

### Session/Login not working
- Ensure `session_name('ims_session')` is being called in `auth/session.php`
- Check that PHP can write to the session storage directory (usually `php.ini` → `session.save_path`)

## API Endpoints (for front-end CRUD later)

Once the database is connected, these endpoints become available:

- `GET /api/users.php` — list all users
- `POST /api/users.php` — create user
- `PUT /api/users.php` — update user
- `DELETE /api/users.php?id=1` — delete user

- `GET /api/roles.php` — list all roles
- `POST /api/roles.php` — create role
- `PUT /api/roles.php` — update role
- `DELETE /api/roles.php?id=1` — delete role

- `GET /api/permissions.php` — list all permissions
- `GET /api/inventory.php` — list inventory items
- `POST /api/inventory.php` — create inventory item
- `PUT /api/inventory.php` — update inventory item
- `DELETE /api/inventory.php?id=1` — delete inventory item

All API endpoints return JSON and require a valid session.

## Next Steps

Once this is working, the front-end pages can be updated to call these API endpoints instead of using mock data. The UI components are already in place (tables, forms, modals) — they just need fetch() calls to populate real data.

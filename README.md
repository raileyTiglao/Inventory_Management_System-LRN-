# IMS — Inventory Management System

Front-end and API complete. Database schema and authentication are ready to deploy
on XAMPP with SQL Server.

## Stack
- **Frontend:** HTML (PHP templating), Tailwind CSS
- **Backend:** PHP REST API, PDO SQL Server driver
- **Database:** SQL Server — tables use the `dbo.ims_<entity>` naming convention
- **Deployment:** XAMPP (Apache + PHP)

## File structure
```
ims/
├── docs/
│   ├── schema.sql           SQL Server schema (run this first in SSMS)
│   └── SETUP.md             Detailed setup instructions
├── assets/
│   └── icons/logo.svg       Brand mark
├── auth/
│   ├── session.php          Custom session handler (ims_session name)
│   ├── login.php            Credential verification + session init
│   ├── logout.php           Session destroy
│   └── rbac.php             Permission checking against dbo.ims_role_permissions
├── api/
│   ├── users.php            CRUD for dbo.ims_users (needs session)
│   ├── roles.php            CRUD for dbo.ims_roles
│   ├── permissions.php      List permissions, optionally filtered by role
│   └── inventory.php        CRUD for dbo.ims_inventory (stock management)
├── pages/
│   ├── login.php            Sign-in page
│   ├── dashboard.php        Home (with mock data; ready for API fetch)
│   ├── users/index.php      User management (with mock data; ready for API fetch)
│   ├── roles/index.php      Role & permission management
│   └── 403.php              Forbidden / permission denied
├── components/
│   ├── head.php             <head> partial with styles + fonts
│   ├── sidebar.php          Navigation sidebar
│   ├── topbar.php           Page header with user menu
│   └── user-modal.php       Reusable Add/Edit User modal
├── utils/
│   ├── helpers.php          e(), is_active()
│   ├── icons.php            SVG icon helpers
│   └── api.php              json_response(), json_error(), json_success()
├── styles/
│   ├── input.css            Tailwind + custom components (bin-tag, badges, etc)
│   └── output.css           Built & minified
├── connection/
│   └── db.php               PDO SQL Server connection (update credentials here)
├── config.php               BASE_URL + app constants
├── bootstrap.php            Shared require() for config, helpers, icons
├── index.php                Redirects to login or dashboard
├── README.md                This file
└── tailwind.config.js       Tailwind config with custom tokens
```

## What's ready right now

### Database Schema
- Run `docs/schema.sql` in SQL Server Management Studio (SSMS)
- Creates `IMS_DB` with all tables: `ims_users`, `ims_roles`, `ims_role_permissions`, `ims_inventory`, `ims_stock_movements`
- Seeds test users (alex.rivera@ims.local / password123)
- Seeds 4 roles with permission matrix
- Seeds 5 sample SKUs for testing

### Authentication & RBAC
- Login via `pages/login.php`: queries `dbo.ims_users`, verifies bcrypt password
- Session uses custom name `ims_session` per spec
- `auth/rbac.php` provides `require_login()` and `require_permission($module, $action)`
- Protected pages check permissions against `dbo.ims_role_permissions` before rendering

### API Endpoints (all JSON, all require session)
- `GET/POST/PUT/DELETE /api/users.php`
- `GET/POST/PUT/DELETE /api/roles.php`
- `GET /api/permissions.php` — list all or filtered by role
- `GET/POST/PUT/DELETE /api/inventory.php` — stock management

### Pages & UI
- `pages/login.php` — wired to real auth/login.php
- `pages/dashboard.php` — protected by `require_permission('Dashboard', 'view')`
- `pages/users/index.php` — protected by `require_permission('Users', 'view')`
- `pages/roles/index.php` — protected by `require_permission('Roles & Permissions', 'view')`
- All mock data ready to be replaced by fetch() calls to `/api/*.php`

## Quick Start

### 1. SQL Server Setup (one-time)
```sql
-- Run docs/schema.sql in SSMS
-- Creates database, tables, test data
```

### 2. PHP SQL Server Driver Setup (one-time)
See `docs/SETUP.md` for detailed instructions. Brief:
- Download sqlsrv drivers from https://github.com/microsoft/msphpsql/releases
- Copy DLLs to `C:\xampp\php\ext\`
- Add to `php.ini`: `extension=php_sqlsrv_83_ts.dll` + `extension=php_pdo_sqlsrv_83_ts.dll`
- Restart Apache

### 3. Deploy to XAMPP
```bash
# Copy ims/ to htdocs/
cp -r ims C:\xampp\htdocs\

# Update connection/db.php with your SQL Server credentials
# (only $user and $pass if needed; $host and $db match schema.sql)

# Visit http://localhost/ims/pages/login.php
# Sign in: alex.rivera@ims.local / password123
```

### 4. Wire Pages to API (next step)
The pages currently show mock data. To use live data, replace the mock arrays
(e.g., in dashboard.php, users/index.php) with fetch() calls to `/api/*.php`.

Example:
```javascript
// Before: mock array
$users = [...]

// After: fetch from API
fetch('/ims/api/users.php')
  .then(r => r.json())
  .then(json => { /* render json.data.users */ })
```

## Styling & Design

### "Bin Tag" System
The visual identity echoes warehouse inventory tags: graphite/near-black surfaces,
hazard-amber accents, monospace IDs (SKU-xxxx), and perforated-stripe motifs
on cards and modals.

**Color Palette:**
- `bg-base: #14171C` (deep graphite)
- `bg-card: #242933` (raised surface)
- `tag-amber: #E8A33D` (hazard yellow — primary accent)
- `stock-in: #4F9D69` (green — received)
- `stock-out: #D9534F` (red — issued)

**Fonts:**
- Display: Space Grotesk (bold, condensed)
- Body: Inter (readable, professional)
- Mono: JetBrains Mono (SKU codes, timestamps)

**Component Classes:**
- `.bin-tag` — card with perforated stripe and notched corner
- `.badge-*` — inline labels (badge-green, badge-red, badge-amber)
- `.btn-primary`, `.btn-secondary`, `.btn-danger` — actions
- `.field-input` — form inputs
- `.data-table` — responsive data tables

### Building/Editing Styles
```bash
npm install              # one-time
npm run watch           # rebuild on save
npm run build           # one-off minified
```

All styles are in `styles/output.css` (built). Source is `styles/input.css` + `tailwind.config.js`.

## Security Notes

- Passwords are bcrypt hashed (`password_hash(..., PASSWORD_BCRYPT)`)
- SQL queries use parameterized PDO statements (no SQL injection)
- Permission checks happen at both page level (`require_permission()`) and API level
- Session uses custom name (`ims_session`) to avoid collisions on shared hosts
- RBAC matrix is stored in `dbo.ims_role_permissions` — fine-grained control per module + action

## Troubleshooting

See `docs/SETUP.md` for detailed troubleshooting, including:
- SQL Server driver installation issues
- Connection refused errors
- Session/login problems

## Next Steps

1. **Connect to your SQL Server:** Update `connection/db.php` with your credentials and run `docs/schema.sql`
2. **Test login:** Visit `pages/login.php` and sign in
3. **Verify API:** Call `http://localhost/ims/api/users.php` in your browser (you'll be redirected to login first)
4. **Wire pages to API:** Replace mock arrays in dashboard, users, and roles pages with fetch() calls
5. **Add more SKUs:** Use the Inventory page to add products to track

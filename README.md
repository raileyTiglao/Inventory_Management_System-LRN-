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


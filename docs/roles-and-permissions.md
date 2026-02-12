# Roles & Permissions

## Overview

CastIT uses a two-tier role system:

1. **System Role** (`users.role`) — controls access to the super admin panel
2. **Company Role** (`users.company_role`) — controls what a user can do within their company

Each user belongs to at most one company (`users.company_id`). Users without a company see a "contact your administrator" message on the dashboard.

## System Roles

| Role        | Value   | Description                                                                                 |
| ----------- | ------- | ------------------------------------------------------------------------------------------- |
| Super Admin | `admin` | Full access to everything. Can manage all users, companies, and projects across the system. |
| User        | `user`  | Standard user. Permissions determined by their company role.                                |

## Company Roles

| Role          | Value      | Description                                                                                      |
| ------------- | ---------- | ------------------------------------------------------------------------------------------------ |
| Company Admin | `admin`    | Full access within their company. Can manage team members.                                       |
| Engineer      | `engineer` | Can view and edit projects, forecasts, and line items. Cannot delete projects or manage members. |
| Viewer        | `viewer`   | Read-only access to projects and reports.                                                        |

## Permission Matrix

| Action                                                                | Super Admin | Company Admin | Engineer | Viewer |
| --------------------------------------------------------------------- | :---------: | :-----------: | :------: | :----: |
| View projects & reports                                               |     Yes     |      Yes      |   Yes    |  Yes   |
| Create projects                                                       |     Yes     |      Yes      |   Yes    |   No   |
| Edit projects, line items, forecasts, control accounts, cost packages |     Yes     |      Yes      |   Yes    |   No   |
| Delete / restore / force-delete projects                              |     Yes     |      Yes      |    No    |   No   |
| Manage company members                                                |     Yes     |      Yes      |    No    |   No   |
| Manage all users (admin panel)                                        |     Yes     |      No       |    No    |   No   |
| Manage all companies (admin panel)                                    |     Yes     |      No       |    No    |   No   |

## Demo Accounts

All demo accounts use the password `password`.

| Email                 | System Role | Company             | Company Role  |
| --------------------- | ----------- | ------------------- | ------------- |
| `admin@castit.com`    | Super Admin | —                   | —             |
| `demo@castit.com`     | User        | CastIt Construction | Company Admin |
| `engineer@castit.com` | User        | CastIt Construction | Engineer      |
| `viewer@castit.com`   | User        | CastIt Construction | Viewer        |

Run `php artisan migrate:fresh --seed` to reset the database with these accounts and the demo project.

## Navigation

- **Dashboard** — visible to all authenticated users
- **Team** — visible to Company Admins (manages company members at `/company/members`)
- **Users** — visible to Super Admins only (manages all users at `/admin/users`)
- **Companies** — visible to Super Admins only (manages all companies at `/admin/companies`)

## Key Files

### Enums
- `app/Enums/UserRole.php` — `Admin`, `User`
- `app/Enums/CompanyRole.php` — `Admin`, `Engineer`, `Viewer`

### Policies
- `app/Policies/ProjectPolicy.php` — view, update, delete, restore, forceDelete
- `app/Policies/CompanyPolicy.php` — update, manageMembers

### Controllers
- `app/Http/Controllers/CompanyMemberController.php` — team member CRUD (company admins)
- `app/Http/Controllers/Admin/UserController.php` — user management (super admin)
- `app/Http/Controllers/Admin/CompanyController.php` — company management (super admin)

### Domain Actions
- `src/Domain/UserManagement/Actions/AddCompanyMember.php`
- `src/Domain/UserManagement/Actions/UpdateCompanyMember.php`
- `src/Domain/UserManagement/Actions/RemoveCompanyMember.php`
- `src/Domain/UserManagement/Actions/CreateCompany.php`
- `src/Domain/UserManagement/Actions/UpdateCompany.php`
- `src/Domain/UserManagement/Actions/DeleteCompany.php`

### Routes
- `routes/admin.php` — `/admin/users`, `/admin/companies`
- `routes/company.php` — `/company/members`

### User Model Helpers
- `isAdmin()` — super admin check
- `isCompanyAdmin()` — company admin check
- `isEngineer()` — engineer check
- `isCompanyViewer()` — viewer check
- `belongsToCompany(?int $companyId)` — membership check
- `hasCompanyRole(CompanyRole ...$roles)` — flexible role check

# Barangay Management System

PHP/MySQL barangay portal with **admin**, **secretary**, and **resident** roles. Built for XAMPP (Apache + MySQL/MariaDB).

## Requirements

- PHP 8.1+
- MySQL/MariaDB 10.4+
- XAMPP (or equivalent Apache + PHP + MySQL stack)

## Installation

1. Copy this project to your web root, e.g. `C:\xampp\htdocs\barangay_default`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Create/import the database:
   - Open phpMyAdmin → Import → select `barangay.sql`
   - Or run: `mysql -u root < barangay.sql`
4. Configure database credentials in `connection.php` if needed (default: `root` with empty password, database `barangay`).
5. Run the one-time password migration (hashes existing plaintext passwords):

```bash
php scripts/migrate_passwords.php
```

6. Open `http://localhost/barangay_default/` in your browser.

## Default Accounts (from SQL seed)

| Role       | Username        | Password        |
|------------|-----------------|-----------------|
| Admin      | `admin123`      | `admin123`      |
| Secretary  | `secretary123`  | `secretary123`  |

Residents register via the public registration page.

## Project Structure

```
barangay_default/
├── admin/           Admin portal
├── secretary/       Secretary portal
├── resident/        Resident portal
├── signup/          Public registration handler
├── includes/        Auth, CSRF, upload, and helper libraries
├── assets/          AdminLTE UI, plugins, uploads
├── backup/          Database backup files
├── connection.php   Database connection
├── barangay.sql     Schema and seed data
└── scripts/         Maintenance scripts
```

## Security Notes

- Passwords are stored with `password_hash()` (bcrypt). Run `scripts/migrate_passwords.php` after upgrading an existing database.
- Protected routes use `includes/auth_*.php` guards.
- POST requests (except DataTables `draw` requests) require a CSRF token.
- User uploads are stored in `assets/uploads/` with extension and MIME validation.

## Maintenance Scripts

| Script | Purpose |
|--------|---------|
| `scripts/migrate_passwords.php` | Hash existing plaintext passwords |
| `scripts/apply_auth_includes.php` | Re-apply auth guards to portal files |
| `scripts/patch_datatables.php` | Harden DataTables ORDER BY / LIMIT |
| `scripts/patch_uploads.php` | Apply secure upload helper |

## License

Internal / educational use. Third-party assets (AdminLTE, Bootstrap, jQuery, etc.) retain their respective licenses under `assets/`.

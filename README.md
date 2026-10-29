# SWD PHP Authentication

A PHP 8 and MySQL authentication application built for XAMPP.

## Requirements

- XAMPP with PHP 8.x and MySQL/MariaDB
- Database server available at `127.0.0.1:3306`
- The project located at `C:\xampp\htdocs\swd\06-php-auth`

## Setup

1. Import `schema.sql` using phpMyAdmin, or run:

   ```sql
   source C:/xampp/htdocs/swd/06-php-auth/schema.sql
   ```

2. If the database and application were created by an earlier stage, run the schema additions for the `role` and `last_login_at` columns, if not already present.
3. Open the application through Apache, for example:

   `http://localhost/swd/06-php-auth/`

Database credentials are stored in `config/db.php`. The XAMPP development configuration uses the local MySQL root account with no password. Before deployment, create a restricted database account and configure a strong password outside source control.

## First administrator

The first administrator must be created from the CLI:

```powershell
C:\xampp\php\php.exe promote-admin.php me@example.com
```

The script exits unless PHP was invoked through the command line.

## Security features

- Passwords use `password_hash()` with `PASSWORD_DEFAULT`, and password verification uses `password_verify()`.
- Password hashes are rehashed automatically when PHP's default algorithm settings change.
- Plain text passwords, MD5, and SHA-1 are never used for password storage.
- Registration and password changes require strong passwords of at least 10 characters containing a letter and a number.
- Login failures are logged per email and IP address. After five failures in 15 minutes, login is temporarily blocked and reports the remaining time in minutes.
- Successful login clears failed attempts for the email and IP address.
- Email and password validation uses generic login errors to reduce account enumeration.
- `password_verify()` and `hash_equals()` are used for timing-safe verification where applicable.
- PDO uses prepared statements, native MySQL prepared statements, exceptions, and associative fetch mode.
- All state-changing browser forms use per-session CSRF tokens.
- Logout uses POST and CSRF validation.
- Sessions use `SWDSESSID`, strict mode, HTTP-only cookies, `SameSite=Lax`, and HTTPS-only cookies when served over HTTPS.
- Sessions are regenerated after login, registration, and password changes.
- Sessions automatically expire after 30 minutes of inactivity.
- Intended redirect URLs must be relative and reject schemes, hosts, protocol-relative URLs, whitespace, backslashes, and control characters.
- `current_user()`, `require_login()`, `require_guest()`, and `require_role()` provide centralized authorization.
- The administration page requires the `admin` role and users cannot demote themselves.
- Output uses `htmlspecialchars()` to prevent stored or reflected XSS.
- Web error display is disabled and PHP errors are logged to `php-error.log`; a generic server-error page is returned instead of stack traces.
- Bootstrap responses include:
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: no-referrer`
  - private cache-control headers to reduce sensitive page caching.

## Pages

- `register.php` — account registration
- `login.php` — login and throttling
- `dashboard.php` — authenticated account summary
- `profile.php` — profile and password updates
- `logout.php` — POST-only logout
- `admin/users.php` — administrator user and role management
- `promote-admin.php` — CLI-only bootstrap administrator command

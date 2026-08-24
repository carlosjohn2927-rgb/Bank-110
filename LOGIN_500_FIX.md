# Fix for HTTP ERROR 500 on login (halykpetroleum-kz.com)

## Root causes found

### 1. SQLite fallback creates empty DB file → fatal `row_array() on bool`
`application/config/database.php` previously defaulted to `sqlite` when `VP_DB_*` env vars were missing. On production (`CI_ENV=production`) this caused PDO to auto-create an empty file at `application/cache/production.sqlite`. The empty file has **no tables**. `MY_Controller::db_ok()` only checked `conn_id`, so it returned TRUE, then `Bank_model::authenticate()` did:

```php
$this->db->where(...)->get()->row_array()
```

When the table doesn't exist and `db_debug` is FALSE, `get()` returns `FALSE`. Calling `row_array()` on `FALSE` is a fatal error → HTTP 500.

**Fix:**
- In `database.php`, when `CI_ENV=production` and no driver is set, default to `mysqli` (not sqlite). This makes a missing `.env` show the branded 503 "We'll be right back" page instead of 500.
- Set `db_debug = FALSE` for sqlite always, and ensure directory exists.
- In `MY_Controller::db_ok()`, actually try `SELECT 1 FROM users LIMIT 1`. If that fails, return FALSE.

### 2. Unguarded DB queries in Bank_model
Many methods did `->get()->row_array()` without checking if `get()` returned FALSE. On a DB outage or missing table, this is fatal.

**Fix:**
- Wrapped critical login methods (`authenticate`, `record_login`, `login_attempts`, `record_login_attempt`, `clear_login_attempts`, `audit`, `preferences`, `user_by_id`, `settings`) in try/catch with `db_debug = FALSE` and safe return values.
- Added `_safe_get_row()` and `_safe_get_result()` helpers.
- `settings()` already guarded, kept guard.

### 3. Session save path not existing / not writable
`config.php` sets `sess_save_path` to `assets/logs/sessions`. On fresh cPanel extract, this folder may not exist or not be writable (0755 vs 0700, ownership). `Session_files_driver::open()` returns `_failure` → session not saved → CSRF token mismatch or empty session → can cause 500 on `sess_regenerate()`.

**Fix:**
- In `config.php`, ensure `assets/logs/` and `sess_save_path` are created with `mkdir(...,0755,TRUE)` if missing.
- Same in `MY_Controller::ensure_writable_dirs()` for all critical writable dirs: `assets/logs`, `assets/logs/sessions`, `assets/logs/cache`, `assets/logs/ratelimit`, `assets/uploads`, `assets/uploads/checks`, `assets/statements`, `application/cache`.
- In `northwest.php`, ensure `cache_path`, `upload_path` etc exist.

### 4. `hash_equals` length mismatch → warning/fatal in PHP 8
`Auth::verify()` did `hash_equals($captcha, $code)` without checking length. If lengths differ, PHP 8 throws? Actually returns false but in some versions can warning. Also `twofa()` did `hash_equals($pending['code'], $code)` similarly.

**Fix:**
- Check `strlen` equality before `hash_equals`, fallback to `===` if lengths differ.
- Wrap in try/catch.

### 5. Captcha view `str_split(null)` → TypeError in PHP 8
`user_login.php` did `str_split($captcha)` where `$captcha` could be NULL if session failed.

**Fix:**
- Cast to string and sanitize: `$captcha_safe = (string)($captcha ?? $this->session->userdata('captcha') ?? '00000')`.

### 6. `.env` missing fallback
`bootstrap/env.php` only loaded `.env`. If missing, production used empty env → sqlite fallback → 500. Now it falls back to `.env.example` for local dev, and in production defaults to mysqli with clear 503 page.

### 7. Auth controller not catching exceptions
Any DB exception bubbled up as 500.

**Fix:**
- Wrapped login flows in try/catch, log error, show friendly "temporarily unavailable" flash message.

### 8. Empty SQLite dev database — "Our services are temporarily unavailable" on login (local/dev)
When no `.env` is present, `bootstrap/env.php` falls back to `.env.example`, which
sets `CI_ENV=development` and `VP_DB_DRIVER=sqlite`. CodeIgniter's PDO driver
silently auto-creates the file `application/cache/production.sqlite`, but that
file is **empty — zero tables**. `MY_Controller::db_ok()` then runs
`SELECT 1 FROM users LIMIT 1`, which fails, so every login POST is redirected
back with the flash message **"Our services are temporarily unavailable. Please
try again shortly."** (the exact symptom reported on a fresh checkout).

The bundled `database/production.sql` is MySQL/MariaDB-only (`ENGINE=InnoDB`,
`AUTO_INCREMENT`, `ON DUPLICATE KEY UPDATE`, `ENUM(...)`, `NOW()`) and cannot be
loaded into SQLite, so there was previously no way to seed the local SQLite
database without manually converting the schema.

**Fix:**
- Added `database/sqlite_schema.sql` — a SQLite-native port of the full schema
  plus the same demo seed data (admin `northadmin` / `Admin@12345`, customer
  `james.davidson@example.com` / `Demo@12345`).
- In `application/config/database.php`, before the PDO DSN is handed to
  CodeIgniter, two helpers run:
  - `northwest_sqlite_needs_init($path)` — returns TRUE when the file is
    missing/empty or has no `users` table.
  - `northwest_sqlite_init($path)` — opens the file with raw PDO, wraps the
    schema load in a transaction, and commits. Errors are logged, never fatal.
- Relative `VP_SQLITE_PATH` values are now resolved against `FCPATH` so the
  database always lands in a known writable directory regardless of the PHP
  process's working directory.
- This code path is reached **only** for the `sqlite`/`pdo_sqlite` driver;
  cPanel MySQL production continues to import `database/production.sql` via
  phpMyAdmin and is unaffected.

**Result:** on a fresh clone with no `.env`, the first request to `/login` or
`/user/login` creates and seeds `application/cache/production.sqlite`
automatically; `db_ok()` then passes and sign-in works immediately.

## What to do on halykpetroleum-kz.com cPanel

1. **Check `.env` exists and has MySQL credentials:**
   ```
   CI_ENV=production
   VP_BASE_URL=https://halykpetroleum-kz.com/
   VP_DB_DRIVER=mysqli
   VP_DB_HOST=localhost
   VP_DB_PORT=3306
   VP_DB_NAME=your_cpaneluser_dbname
   VP_DB_USER=your_cpaneluser_dbuser
   VP_DB_PASS=your_password
   VP_ENCRYPTION_KEY=long-random-string
   VP_AUTH_SECRET=different-long-random-string
   ```

2. **Import DB:** In phpMyAdmin, import `database/production.sql` into the DB named in `.env`. Verify tables exist.

3. **Writable folders:** In File Manager, ensure these exist and are 0755:
   - `assets/logs/`
   - `assets/logs/sessions/`
   - `assets/logs/cache/`
   - `assets/logs/ratelimit/`
   - `assets/uploads/`
   - `assets/uploads/checks/`
   - `assets/statements/`
   - `application/cache/`

4. **PHP version:** cPanel → MultiPHP Manager → select PHP 8.2 or 8.3. In Select PHP Version, enable `mysqli`, `mbstring`, `openssl`, `fileinfo`, `session`.

5. **Re-upload fixed code:** Upload the new `application-deployment.zip` (regenerated with fixes) and extract, or upload the 8 fixed files:
   - `application/config/config.php`
   - `application/config/database.php`
   - `application/config/northwest.php`
   - `application/controllers/Auth.php`
   - `application/core/MY_Controller.php`
   - `application/models/Bank_model.php`
   - `application/views/auth/user_login.php`
   - `bootstrap/env.php`

6. **Test `/setup/check`:** Visit `https://halykpetroleum-kz.com/setup/check` — all checks should be green. If DB shows red, fix credentials.

7. **Clear sessions:** Delete files inside `assets/logs/sessions/` (keep `.gitkeep`).

After these steps, `/login` and `/user/login` should load, and login should work instead of 500. If you still see 500, check `assets/logs/log-YYYY-MM-DD` (or cPanel → Errors) for the exact error message.

## Files changed in this fix
- `application/config/database.php` — no sqlite in production, safe defaults
- `application/core/MY_Controller.php` — robust db_ok(), ensure dirs, safe hash_equals
- `application/models/Bank_model.php` — guarded authenticate, login_attempts, audit, etc + safe helpers
- `application/controllers/Auth.php` — try/catch, safe captcha verification, safe session regen
- `application/config/config.php` — auto-create log and session dirs
- `application/config/northwest.php` — auto-create cache/upload dirs
- `application/views/auth/user_login.php` — safe captcha handling for PHP 8
- `bootstrap/env.php` — fallback to .env.example


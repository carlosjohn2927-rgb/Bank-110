# NorthWest — cPanel Deployment Guide

This package is designed for deployment without Terminal, SSH, Composer, Node.js, npm, Docker, migrations, seed commands, or any command-line operation.

## What you need

- A cPanel account with **File Manager**, **MySQL Databases**, and **phpMyAdmin**
- PHP 8.2 or newer with `mysqli`, `mbstring`, `openssl`, `fileinfo`, and `session`
- A domain or subdomain pointed at the folder where the package will be extracted

## New cPanel deployment

### 1. Upload the application

1. Open **cPanel → File Manager**.
2. Open the domain's document root, normally `public_html`, or the document root assigned to a subdomain.
3. Upload the project folder contents (everything in the repository, including `index.php`, `.htaccess`, `.env`, `application`, `system`, `assets`, `public`, `database`, and `src`).
4. Move/place the files so `index.php`, `.htaccess`, `.env`, `application`, `system`, `assets`, `public`, and `database` are directly inside the domain's document root—not inside an extra nested folder.
5. Ensure the top-level files are directly in the document root.
6. In File Manager settings, enable **Show Hidden Files (dotfiles)** so `.env` and `.htaccess` are visible.

No dependency installation is needed. CodeIgniter and all production assets are included.

### 2. Create the database

1. Open **cPanel → MySQL Databases**.
2. Create a database. cPanel may prefix its name with your account name.
3. Create a database user and a strong password.
4. Add the user to the database.
5. Grant **ALL PRIVILEGES**.
6. Record the complete prefixed database and user names exactly as cPanel displays them.

### 3. Import the complete database

1. Open **cPanel → phpMyAdmin**.
2. Select the new, empty database in the left sidebar.
3. Choose **Import**.
4. Select `database/production.sql` from the extracted package (or download that file through File Manager and select it locally).
5. Keep the format set to **SQL** and click **Import/Go**.
6. Confirm that the import reports success and tables such as `users`, `accounts`, `transactions`, `roles`, `settings`, and `audit_logs` appear.

`production.sql` is the only database file required. It contains the schema, indexes, foreign keys, roles, permissions, settings, reference values, templates, demo data, customer account, and initial administrator. Do not run a migration or seeding command.

### 4. Configure `.env`

1. Return to **cPanel → File Manager**.
2. Right-click `.env` and choose **Edit**.
3. Update at least:

```ini
CI_ENV=production
VP_BASE_URL=https://yourdomain.com/

VP_DB_HOST=localhost
VP_DB_PORT=3306
VP_DB_NAME=CPANELPREFIX_DATABASE
VP_DB_USER=CPANELPREFIX_DATABASE_USER
VP_DB_PASS=YOUR_DATABASE_PASSWORD

VP_ENCRYPTION_KEY=YOUR_LONG_EXISTING_ENCRYPTION_KEY
VP_AUTH_SECRET=YOUR_DIFFERENT_LONG_EXISTING_AUTH_SECRET
```

Use the complete database/user names, including cPanel prefixes. Most cPanel servers use `localhost` and port `3306`; use the values supplied by your host if different.

Before the first real production deployment, replace both example secret values with separate long random strings. When moving an existing installation, copy the old `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` unchanged. This preserves encryption and authentication compatibility across servers. No secret-generation command or server-specific secrets file is used.

Mail, cache, session, upload, API, and third-party settings are also in `.env`. Empty optional mail/API values do not prevent the application from starting.

### 5. Verify writable folders

Files normally extract under the same cPanel account and work with standard `0755` folder permissions. In **File Manager**, verify these directories exist:

```text
assets/logs/
assets/logs/sessions/
assets/logs/cache/
assets/logs/ratelimit/
assets/uploads/
```

If the application reports that one is not writable, select it in File Manager, choose **Change Permissions**, and use `0755`. On hosts using a different PHP ownership model, use `0775` only for the affected folder. Never use `0777` unless your hosting provider explicitly requires it. No `chmod` or `chown` command is needed.

The package includes every empty writable directory and protective `.htaccess` files. Log, cache, and session files cannot be downloaded from the web. Uploaded executable scripts are blocked.

### 6. Open and verify the website

Visit:

```text
https://yourdomain.com
```

Check the customer login, then visit:

```text
https://yourdomain.com/admin
```

The customer login first displays the image-style verification code.

## Initial accounts

### Administrator

- Identity: `northadmin`
- Alternate identity: `admin@northwest.financeltd.org`
- Password: the administrator password supplied for this project

### Demonstration customer

- Identity: `james.davidson@example.com`
- Password: `Demo@12345`

Immediately change production account credentials and remove demonstration data that is not required for the live installation.

## Moving an existing installation

For an existing site, move the application files and import a phpMyAdmin export of the existing database rather than importing the starter database over live data. Copy the existing `.env`, then change only the domain and database connection values. Preserve `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` exactly.

## Troubleshooting through cPanel only

### A 404 appears on every route

Confirm `.htaccess` was extracted and **Show Hidden Files** is enabled. Ask the host to confirm Apache `mod_rewrite` and `AllowOverride` are enabled. The homepage can still be tested at `index.php/login` while routing is being corrected.

### Database connection error

Check the cPanel-prefixed database and user names, password, host, port, user assignment, and **ALL PRIVILEGES**. Confirm `production.sql` was imported into the same database named in `.env`.

### Session or upload error

Use File Manager to verify the writable folders listed above and change only the affected directory to `0755` or, if required by the host, `0775`.

### Server error immediately after extraction

In cPanel **MultiPHP Manager**, select PHP 8.2 or newer. In **Select PHP Version**, enable `mysqli`, `mbstring`, `openssl`, `fileinfo`, and `session`. Check cPanel's **Errors** page or `assets/logs` for details.

## Complete normal deployment workflow

**Upload and extract files → Create database and user → Import `database/production.sql` → Edit `.env` → Open website.**

No Terminal operation is part of installation, migration, or deployment.

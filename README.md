# NorthWest Digital Banking

A complete traditional PHP MVC banking application built with **CodeIgniter 3.1.13** and **MySQL/MariaDB**, packaged for portable shared-hosting and cPanel deployment.

## Portable production deployment

Normal installation requires only:

1. Upload and extract `application-deployment.zip` in cPanel File Manager.
2. Create a database and user in cPanel MySQL Databases.
3. Import `database/production.sql` through phpMyAdmin.
4. Edit `.env` with the domain, database credentials, and preserved secrets.
5. Open the website.

There is no installer, migration command, seed command, admin-creation command, Composer installation, Node.js build, npm installation, Docker step, Terminal, or SSH requirement.

See **[CPANEL_DEPLOYMENT.md](CPANEL_DEPLOYMENT.md)** for the complete click-by-click guide, writable-directory instructions, initial account details, migration guidance, and cPanel troubleshooting.

## Included production components

- CodeIgniter 3.1.13 framework in `system/`
- Application controllers, models, views, helpers, and configuration in `application/`
- Dependency-free `.env` bootstrap loader in `bootstrap/`
- Complete importable database in `database/production.sql`
- Prebuilt CSS and JavaScript in `public/`
- Portable writable folders in `assets/`
- Apache routing and sensitive-file protection in `.htaccess`
- Ready-to-upload `application-deployment.zip`

## Application functionality

### Personal banking

- Verification-code and password authentication
- Multi-account dashboard and balances
- Searchable transactions
- Database-transaction-backed transfers
- Beneficiary management
- Card security controls
- Loan summaries
- Support tickets
- Customer profile and image uploads

### Administration

- Operations dashboard and metrics
- Customer and account creation
- Customer status management
- Audited balance adjustments
- Transaction, transfer, and deposit review
- Transaction approval and rejection
- Card and loan monitoring
- Support ticket replies
- Platform settings and tutorial
- Audit logging

## Portable configuration

All hosting-specific values are read from the root `.env` file before CodeIgniter starts:

- Base URL and environment
- MySQL/MariaDB connection
- Encryption and authentication secrets
- Session driver, path, and lifetime
- Cookie security
- Logging and cache paths
- Upload location and size
- SMTP settings
- Optional API and third-party credentials

Preserve `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` when moving an existing site to another cPanel account.

## Security

The application uses bcrypt password verification, regenerated authenticated sessions, HMAC session signatures, HTTP-only SameSite cookies, CSRF protection, server-side validation, Query Builder parameterization, user-scoped records, transactional balance changes, protected writable directories, restricted upload types, and audit logging.

A real regulated financial deployment still requires independent security review, multi-factor authentication, double-entry ledger accounting, identity verification, reconciliation, rate limiting, monitoring, backups, disaster recovery, and applicable regulatory controls.

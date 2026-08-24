-- NorthWest Financial Ltd. — SQLite schema + demo seed
--
-- This file is used ONLY by the portable single-file SQLite dev mode
-- (VP_DB_DRIVER=sqlite, the default in .env.example for local development).
-- It is NOT used by cPanel/MySQL production — production imports
-- database/production.sql via phpMyAdmin instead.
--
-- The schema mirrors database/production.sql so the Query Builder models
-- work identically on both drivers. Auto-initialization happens in
-- application/config/database.php when the SQLite file is missing or empty.
--
-- Demo credentials (change before any real use):
--   Admin:    northadmin / Admin@12345   (admin@northwest.financeltd.org)
--   Customer: james.davidson@example.com / Demo@12345

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------------
-- Users & profiles
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    username           TEXT NOT NULL UNIQUE,
    email              TEXT NOT NULL UNIQUE,
    password_hash      TEXT NOT NULL,
    first_name         TEXT NOT NULL,
    last_name          TEXT NOT NULL,
    role               TEXT NOT NULL DEFAULT 'customer' CHECK (role IN ('customer','admin')),
    status             TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','pending','suspended','closed')),
    twofa_enabled      INTEGER NOT NULL DEFAULT 0,
    totp_secret        TEXT,
    totp_confirmed     INTEGER NOT NULL DEFAULT 0,
    backup_codes_hash  TEXT,
    last_login_at      TEXT,
    last_login_ip      TEXT,
    created_at         TEXT NOT NULL,
    updated_at         TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);

CREATE TABLE IF NOT EXISTS customer_profiles (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER NOT NULL UNIQUE,
    phone          TEXT,
    address        TEXT,
    city           TEXT,
    country        TEXT,
    date_of_birth  TEXT,
    avatar_path    TEXT,
    kyc_status     TEXT NOT NULL DEFAULT 'pending' CHECK (kyc_status IN ('pending','verified','rejected')),
    created_at     TEXT NOT NULL,
    updated_at     TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------------
-- Accounts, beneficiaries, transfers, transactions
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS accounts (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id            INTEGER NOT NULL,
    account_number     TEXT NOT NULL UNIQUE,
    name               TEXT NOT NULL,
    type               TEXT NOT NULL CHECK (type IN ('checking','savings','investment')),
    currency           TEXT NOT NULL DEFAULT 'USD',
    balance            NUMERIC NOT NULL DEFAULT 0,
    available_balance  NUMERIC NOT NULL DEFAULT 0,
    status             TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','frozen','closed')),
    is_primary         INTEGER NOT NULL DEFAULT 0,
    created_at         TEXT NOT NULL,
    updated_at         TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_accounts_user ON accounts(user_id);

CREATE TABLE IF NOT EXISTS beneficiaries (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    name            TEXT NOT NULL,
    account_number  TEXT NOT NULL,
    bank_name       TEXT NOT NULL,
    routing_code    TEXT,
    currency        TEXT NOT NULL DEFAULT 'USD',
    status          TEXT NOT NULL DEFAULT 'verified' CHECK (status IN ('pending','verified','blocked')),
    created_at      TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_beneficiaries_user ON beneficiaries(user_id);

CREATE TABLE IF NOT EXISTS transfers (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    reference           TEXT NOT NULL UNIQUE,
    user_id             INTEGER NOT NULL,
    from_account_id     INTEGER NOT NULL,
    beneficiary_id      INTEGER,
    recipient_name      TEXT NOT NULL,
    recipient_account   TEXT NOT NULL,
    recipient_bank      TEXT NOT NULL,
    recipient_routing   TEXT,
    transfer_type       TEXT NOT NULL CHECK (transfer_type IN ('internal','domestic','international')),
    amount              NUMERIC NOT NULL,
    currency            TEXT NOT NULL,
    fee                 NUMERIC NOT NULL DEFAULT 0,
    note                TEXT,
    scheduled_for       TEXT NOT NULL,
    status              TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','processing','completed','failed','cancelled')),
    approved_by         INTEGER,
    created_at          TEXT NOT NULL,
    updated_at          TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (from_account_id) REFERENCES accounts(id),
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_transfer_user ON transfers(user_id);
CREATE INDEX IF NOT EXISTS idx_transfer_status ON transfers(status);

CREATE TABLE IF NOT EXISTS transactions (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id        INTEGER NOT NULL,
    transfer_id       INTEGER,
    reference         TEXT NOT NULL UNIQUE,
    type              TEXT NOT NULL CHECK (type IN ('credit','debit')),
    category          TEXT NOT NULL,
    description       TEXT NOT NULL,
    amount            NUMERIC NOT NULL,
    currency          TEXT NOT NULL,
    balance_after     NUMERIC NOT NULL,
    status            TEXT NOT NULL DEFAULT 'completed' CHECK (status IN ('pending','completed','failed','cancelled')),
    transaction_date  TEXT NOT NULL,
    created_at        TEXT NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_transaction_account_date ON transactions(account_id, created_at);
CREATE INDEX IF NOT EXISTS idx_transaction_status ON transactions(status);

-- ---------------------------------------------------------------------------
-- Cards, loans, support
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cards (
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id                INTEGER NOT NULL,
    account_id             INTEGER NOT NULL,
    cardholder_name        TEXT NOT NULL,
    masked_number          TEXT NOT NULL,
    last_four              TEXT NOT NULL,
    expiry_month           INTEGER NOT NULL,
    expiry_year            INTEGER NOT NULL,
    card_type              TEXT NOT NULL CHECK (card_type IN ('virtual','physical')),
    network                TEXT NOT NULL DEFAULT 'Visa',
    status                 TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','blocked','expired')),
    is_frozen              INTEGER NOT NULL DEFAULT 0,
    online_enabled         INTEGER NOT NULL DEFAULT 1,
    international_enabled  INTEGER NOT NULL DEFAULT 1,
    daily_limit            NUMERIC NOT NULL DEFAULT 10000,
    created_at             TEXT NOT NULL,
    updated_at             TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

CREATE TABLE IF NOT EXISTS loans (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id               INTEGER NOT NULL,
    reference             TEXT NOT NULL UNIQUE,
    type                  TEXT NOT NULL,
    principal             NUMERIC NOT NULL,
    outstanding_balance   NUMERIC NOT NULL,
    interest_rate         NUMERIC NOT NULL,
    monthly_payment       NUMERIC NOT NULL,
    next_payment_date     TEXT,
    term_months           INTEGER NOT NULL,
    payments_remaining    INTEGER NOT NULL,
    status                TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','active','paid','rejected','defaulted')),
    created_at            TEXT NOT NULL,
    updated_at            TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    reference   TEXT NOT NULL UNIQUE,
    user_id     INTEGER NOT NULL,
    subject     TEXT NOT NULL,
    category    TEXT NOT NULL,
    priority    TEXT NOT NULL DEFAULT 'normal' CHECK (priority IN ('low','normal','medium','high')),
    status      TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open','in_progress','resolved','closed')),
    assigned_to INTEGER,
    created_at  TEXT NOT NULL,
    updated_at  TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS ticket_messages (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    message     TEXT NOT NULL,
    is_staff    INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ---------------------------------------------------------------------------
-- Settings, audit, preferences, auth, notifications
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    setting_key    TEXT PRIMARY KEY,
    setting_value  TEXT,
    updated_at     TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER,
    action      TEXT NOT NULL,
    description TEXT NOT NULL,
    ip_address  TEXT,
    user_agent  TEXT,
    created_at  TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_date ON audit_logs(created_at);

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id     INTEGER NOT NULL,
    pref_key    TEXT NOT NULL,
    pref_value  TEXT,
    updated_at  TEXT NOT NULL,
    PRIMARY KEY (user_id, pref_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_key  TEXT NOT NULL,
    success      INTEGER NOT NULL DEFAULT 0,
    ip_address   TEXT,
    created_at   TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_attempt_key ON login_attempts(attempt_key, created_at);

CREATE TABLE IF NOT EXISTS user_notifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    type       TEXT NOT NULL DEFAULT 'general',
    title      TEXT NOT NULL,
    body       TEXT,
    link       TEXT,
    is_read    INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_notif_user ON user_notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notif_unread ON user_notifications(user_id, is_read);

CREATE TABLE IF NOT EXISTS password_resets (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    token       TEXT NOT NULL UNIQUE,
    expires_at  TEXT NOT NULL,
    used        INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_reset_user ON password_resets(user_id);
CREATE INDEX IF NOT EXISTS idx_reset_token ON password_resets(token);

-- ---------------------------------------------------------------------------
-- RBAC, lookups, templates
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    description  TEXT,
    is_system    INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS permissions (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    module       TEXT NOT NULL,
    created_at   TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions(module);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lookup_values (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    lookup_group  TEXT NOT NULL,
    value_key     TEXT NOT NULL,
    display_value TEXT NOT NULL,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1,
    UNIQUE (lookup_group, value_key)
);

CREATE TABLE IF NOT EXISTS notification_templates (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    template_key TEXT NOT NULL UNIQUE,
    channel      TEXT NOT NULL CHECK (channel IN ('email','sms','system')),
    subject      TEXT,
    body         TEXT NOT NULL,
    is_active    INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL,
    updated_at   TEXT NOT NULL
);

-- ---------------------------------------------------------------------------
-- Exchange rates (with daily history)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exchange_rates (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency   TEXT NOT NULL,
    rate          NUMERIC NOT NULL,
    updated_at    TEXT NOT NULL,
    UNIQUE (from_currency, to_currency)
);

CREATE TABLE IF NOT EXISTS exchange_rate_history (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency   TEXT NOT NULL,
    rate          NUMERIC NOT NULL,
    snapshot_date TEXT NOT NULL,
    created_at    TEXT NOT NULL,
    UNIQUE (from_currency, to_currency, snapshot_date)
);
CREATE INDEX IF NOT EXISTS idx_hist_pair ON exchange_rate_history(from_currency, to_currency, snapshot_date);

-- ---------------------------------------------------------------------------
-- Savings goals, check deposits, KYC documents (2026.08 features)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS savings_goals (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL,
    name          TEXT NOT NULL,
    target_amount NUMERIC NOT NULL,
    saved_amount  NUMERIC NOT NULL DEFAULT 0,
    target_date   TEXT,
    icon          TEXT,
    color         TEXT,
    status        TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','completed','archived')),
    created_at    TEXT NOT NULL,
    updated_at    TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_savings_goals_user ON savings_goals(user_id);

CREATE TABLE IF NOT EXISTS check_deposits (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL,
    account_id       INTEGER NOT NULL,
    reference        TEXT NOT NULL UNIQUE,
    amount           NUMERIC NOT NULL,
    check_number     TEXT,
    front_image_path TEXT NOT NULL,
    back_image_path  TEXT NOT NULL,
    status           TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    review_note      TEXT,
    transaction_id   INTEGER,
    created_at       TEXT NOT NULL,
    updated_at       TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);
CREATE INDEX IF NOT EXISTS idx_check_deposits_user ON check_deposits(user_id);
CREATE INDEX IF NOT EXISTS idx_check_deposits_status ON check_deposits(status);

CREATE TABLE IF NOT EXISTS kyc_documents (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL,
    doc_type      TEXT NOT NULL CHECK (doc_type IN ('passport','drivers_license','national_id','proof_of_address','selfie','other')),
    file_path     TEXT NOT NULL,
    original_name TEXT,
    mime_type     TEXT,
    file_size     INTEGER,
    status        TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    review_note   TEXT,
    reviewed_by   INTEGER,
    reviewed_at   TEXT,
    created_at    TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_kyc_user ON kyc_documents(user_id);
CREATE INDEX IF NOT EXISTS idx_kyc_status ON kyc_documents(status);

-- ---------------------------------------------------------------------------
-- Demo seed data (uses SQLite-compatible datetime and INSERT OR IGNORE)
-- ---------------------------------------------------------------------------
INSERT OR IGNORE INTO users (id, username, email, password_hash, first_name, last_name, role, status, created_at, updated_at) VALUES
 (1,'northadmin','admin@northwest.financeltd.org','$2y$12$/h4aEDW/sPrRG4T9vb6mbOIuyI.SnP1CBO0YRwtGJS7dN0xeVYIPG','North','Admin','admin','active',datetime('now'),datetime('now')),
 (2,'jamesd','james.davidson@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','James','Davidson','customer','active',datetime('now'),datetime('now')),
 (3,'oliviam','olivia.martin@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','Olivia','Martin','customer','active',datetime('now'),datetime('now')),
 (4,'jacksonl','jackson.lee@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','Jackson','Lee','customer','active',datetime('now'),datetime('now'));

INSERT OR IGNORE INTO customer_profiles (user_id, phone, address, city, country, date_of_birth, kyc_status, created_at, updated_at) VALUES
 (2,'+1 212 555 0187','284 Park Avenue','New York','United States','1987-04-16','verified',datetime('now'),datetime('now')),
 (3,'+1 310 555 0194','14 Ocean Drive','Los Angeles','United States','1992-09-21','verified',datetime('now'),datetime('now')),
 (4,'+1 206 555 0122','810 Pine Street','Seattle','United States','1985-11-02','verified',datetime('now'),datetime('now'));

INSERT OR IGNORE INTO accounts (id, user_id, account_number, name, type, currency, balance, available_balance, status, is_primary, created_at, updated_at) VALUES
 (1,2,'NW-1482-4821','NorthWest Select','checking','USD',64830.45,64830.45,'active',1,datetime('now'),datetime('now')),
 (2,2,'NW-1482-1098','Growth Savings','savings','USD',19420.25,19420.25,'active',0,datetime('now'),datetime('now')),
 (3,3,'NW-2374-1150','NorthWest Select','checking','USD',84250.70,84250.70,'active',1,datetime('now'),datetime('now')),
 (4,4,'NW-5088-4922','NorthWest Select','checking','USD',12840.00,12840.00,'active',1,datetime('now'),datetime('now'));

INSERT OR IGNORE INTO beneficiaries (user_id, name, account_number, bank_name, routing_code, currency, status, created_at) VALUES
 (2,'Sarah Wilson','•••• 8402','NorthWest Bank','021000021','USD','verified',datetime('now')),
 (2,'Michael Chen','•••• 1094','Chase Bank','021000021','USD','verified',datetime('now')),
 (2,'Apex Property Group','•••• 7721','Wells Fargo','121000248','USD','verified',datetime('now'));

INSERT OR IGNORE INTO transactions (account_id, reference, type, category, description, amount, currency, balance_after, status, transaction_date, created_at) VALUES
 (1,'NW-TX-100001','debit','Shopping','Apple Store',124.99,'USD',64830.45,'completed',date('now','-1 day'),datetime('now','-1 day')),
 (1,'NW-TX-100002','credit','Income','Salary deposit',4850.00,'USD',64955.44,'completed',date('now','-2 day'),datetime('now','-2 day')),
 (1,'NW-TX-100003','debit','Travel','Marriott Hotel',780.40,'USD',60105.44,'completed',date('now','-3 day'),datetime('now','-3 day')),
 (1,'NW-TX-100004','credit','Transfer','Sarah Wilson',1200.00,'USD',60885.84,'completed',date('now','-4 day'),datetime('now','-4 day')),
 (1,'NW-TX-100005','debit','Utilities','City Electric',145.22,'USD',59685.84,'completed',date('now','-5 day'),datetime('now','-5 day')),
 (2,'NW-TX-100006','credit','Interest','Monthly interest',84.32,'USD',19420.25,'completed',date('now','-8 day'),datetime('now','-8 day'));

INSERT OR IGNORE INTO cards (user_id, account_id, cardholder_name, masked_number, last_four, expiry_month, expiry_year, card_type, network, status, is_frozen, online_enabled, international_enabled, daily_limit, created_at, updated_at) VALUES
 (2,1,'JAMES DAVIDSON','5422 88•• •••• 4821','4821',9,2029,'physical','Visa','active',0,1,1,10000,datetime('now'),datetime('now'));

INSERT OR IGNORE INTO loans (user_id, reference, type, principal, outstanding_balance, interest_rate, monthly_payment, next_payment_date, term_months, payments_remaining, status, created_at, updated_at) VALUES
 (2,'NW-LN-209184','Personal loan',34000,18420,6.250,1024.60,date('now','+14 day'),36,18,'active',datetime('now'),datetime('now'));

INSERT OR IGNORE INTO support_tickets (id, reference, user_id, subject, category, priority, status, created_at, updated_at) VALUES
 (1,'TKT-2608-84920',2,'Card payment I do not recognize','cards','high','open',datetime('now','-2 hours'),datetime('now','-12 minutes'));
INSERT OR IGNORE INTO ticket_messages (ticket_id, user_id, message, is_staff, created_at) VALUES
 (1,2,'I noticed a card payment from a merchant I do not recognize. Can you help me check it?',0,datetime('now','-2 hours'));

INSERT OR IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES
 ('institution_name','NorthWest Financial Ltd.',datetime('now')),
 ('support_email','support@northwest.financeltd.org',datetime('now')),
 ('default_currency','USD',datetime('now')),
 ('daily_transfer_limit','25000',datetime('now')),
 ('session_timeout','15',datetime('now')),
 ('application_initialized','1',datetime('now')),
 ('schema_version','2026.08.21',datetime('now')),
 ('timezone','UTC',datetime('now')),
 ('maintenance_mode','0',datetime('now')),
 ('registration_enabled','1',datetime('now')),
 ('supported_currencies','USD,EUR,GBP',datetime('now')),
 ('announcement_text','Welcome to NorthWest — Secure online banking with 256-bit encryption · Free NorthWest-to-NorthWest transfers · 24/7 support',datetime('now')),
 ('seo_site_name','NorthWest Financial',datetime('now')),
 ('seo_title','NorthWest Financial — Secure Online Banking',datetime('now')),
 ('seo_description','Simple, secure online banking. Send money, manage cards, apply for loans and track your finances — all in one protected place with 256-bit encryption.',datetime('now')),
 ('seo_keywords','online banking, secure banking, bank transfers, digital bank, NorthWest, personal accounts, savings, loans',datetime('now')),
 ('routing_number','021000021',datetime('now')),
 ('international_fee_percent','1.5',datetime('now')),
 ('international_fee_flat','0',datetime('now'));

INSERT OR IGNORE INTO roles (id, name, display_name, description, is_system, created_at) VALUES
 (1,'admin','Administrator','Full banking operations administration',1,datetime('now')),
 (2,'customer','Customer','Personal online-banking customer',1,datetime('now'));

INSERT OR IGNORE INTO permissions (id, name, display_name, module, created_at) VALUES
 (1,'dashboard.view','View operations dashboard','dashboard',datetime('now')),
 (2,'customers.manage','Manage customers','customers',datetime('now')),
 (3,'transactions.manage','Review and manage transactions','transactions',datetime('now')),
 (4,'cards.manage','Manage customer cards','cards',datetime('now')),
 (5,'loans.manage','Manage loans','loans',datetime('now')),
 (6,'support.manage','Manage support tickets','support',datetime('now')),
 (7,'settings.manage','Manage system settings','settings',datetime('now')),
 (8,'banking.use','Use personal banking','banking',datetime('now'));

INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES
 (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(2,8);

INSERT OR IGNORE INTO user_roles (user_id, role_id)
 SELECT id, CASE WHEN role='admin' THEN 1 ELSE 2 END FROM users;

INSERT OR IGNORE INTO lookup_values (lookup_group, value_key, display_value, sort_order, is_active) VALUES
 ('currency','USD','US Dollar',1,1),
 ('currency','EUR','Euro',2,1),
 ('currency','GBP','Pound Sterling',3,1),
 ('account_type','checking','Checking',1,1),
 ('account_type','savings','Savings',2,1),
 ('account_type','investment','Investment',3,1),
 ('ticket_category','general','General',1,1),
 ('ticket_category','cards','Cards',2,1),
 ('ticket_category','transfers','Transfers',3,1),
 ('ticket_category','security','Security',4,1);

INSERT OR IGNORE INTO notification_templates (template_key, channel, subject, body, is_active, created_at, updated_at) VALUES
 ('welcome_customer','email','Welcome to NorthWest','Welcome {{first_name}}. Your NorthWest account is ready.',1,datetime('now'),datetime('now')),
 ('transfer_submitted','email','Transfer {{reference}} submitted','Your transfer of {{amount}} has been submitted for processing.',1,datetime('now'),datetime('now')),
 ('password_reset','email','Reset your NorthWest password','Use this secure link to reset your password: {{reset_url}}',1,datetime('now'),datetime('now')),
 ('ticket_reply','email','Update on support request {{reference}}','NorthWest support has replied to your request.',1,datetime('now'),datetime('now'));

INSERT OR IGNORE INTO exchange_rates (from_currency, to_currency, rate, updated_at) VALUES
 ('USD','EUR',0.9200,datetime('now')),
 ('USD','GBP',0.7900,datetime('now')),
 ('EUR','USD',1.0870,datetime('now')),
 ('EUR','GBP',0.8590,datetime('now')),
 ('GBP','USD',1.2660,datetime('now')),
 ('GBP','EUR',1.1640,datetime('now'));

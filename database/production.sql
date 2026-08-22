-- NorthWest Financial Ltd. - Complete Production Database
-- Import this one file into an EMPTY MySQL/MariaDB database using phpMyAdmin.
-- No migration, seeder, installer, Terminal, or additional SQL is required.
-- Compatible with MySQL 5.7+/8.x and MariaDB 10.3+.

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = '+00:00';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL,
 role ENUM('customer','admin') NOT NULL DEFAULT 'customer', status ENUM('active','pending','suspended','closed') NOT NULL DEFAULT 'active',
 twofa_enabled TINYINT(1) NOT NULL DEFAULT 0,
 last_login_at DATETIME NULL, last_login_ip VARCHAR(45) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 INDEX idx_users_role_status(role,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE, phone VARCHAR(40), address VARCHAR(255), city VARCHAR(100), country VARCHAR(100), date_of_birth DATE NULL, avatar_path VARCHAR(255) NULL,
 kyc_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, account_number VARCHAR(40) NOT NULL UNIQUE, name VARCHAR(120) NOT NULL,
 type ENUM('checking','savings','investment') NOT NULL, currency CHAR(3) NOT NULL DEFAULT 'USD', balance DECIMAL(18,2) NOT NULL DEFAULT 0,
 available_balance DECIMAL(18,2) NOT NULL DEFAULT 0, status ENUM('active','frozen','closed') NOT NULL DEFAULT 'active', is_primary TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_accounts_user(user_id), CONSTRAINT fk_account_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beneficiaries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, account_number VARCHAR(60) NOT NULL,
 bank_name VARCHAR(120) NOT NULL, routing_code VARCHAR(60), currency CHAR(3) NOT NULL DEFAULT 'USD', status ENUM('pending','verified','blocked') NOT NULL DEFAULT 'verified', created_at DATETIME NOT NULL,
 INDEX idx_beneficiaries_user(user_id), CONSTRAINT fk_beneficiary_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, from_account_id BIGINT UNSIGNED NOT NULL, beneficiary_id BIGINT UNSIGNED NULL,
 recipient_name VARCHAR(120) NOT NULL, recipient_account VARCHAR(60) NOT NULL, recipient_bank VARCHAR(120) NOT NULL, recipient_routing VARCHAR(60) NULL, transfer_type ENUM('internal','domestic','international') NOT NULL,
 amount DECIMAL(18,2) NOT NULL, currency CHAR(3) NOT NULL, fee DECIMAL(18,2) NOT NULL DEFAULT 0, note VARCHAR(255), scheduled_for DATE NOT NULL,
 status ENUM('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending', approved_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 INDEX idx_transfer_user(user_id), INDEX idx_transfer_status(status), CONSTRAINT fk_transfer_user FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT fk_transfer_account FOREIGN KEY(from_account_id) REFERENCES accounts(id), CONSTRAINT fk_transfer_beneficiary FOREIGN KEY(beneficiary_id) REFERENCES beneficiaries(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED NOT NULL, transfer_id BIGINT UNSIGNED NULL, reference VARCHAR(40) NOT NULL UNIQUE,
 type ENUM('credit','debit') NOT NULL, category VARCHAR(80) NOT NULL, description VARCHAR(255) NOT NULL, amount DECIMAL(18,2) NOT NULL, currency CHAR(3) NOT NULL,
 balance_after DECIMAL(18,2) NOT NULL, status ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed', transaction_date DATE NOT NULL, created_at DATETIME NOT NULL,
 INDEX idx_transaction_account_date(account_id,created_at), INDEX idx_transaction_status(status), CONSTRAINT fk_transaction_account FOREIGN KEY(account_id) REFERENCES accounts(id),
 CONSTRAINT fk_transaction_transfer FOREIGN KEY(transfer_id) REFERENCES transfers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, account_id BIGINT UNSIGNED NOT NULL, cardholder_name VARCHAR(120) NOT NULL,
 masked_number VARCHAR(24) NOT NULL, last_four CHAR(4) NOT NULL, expiry_month TINYINT UNSIGNED NOT NULL, expiry_year SMALLINT UNSIGNED NOT NULL,
 card_type ENUM('virtual','physical') NOT NULL, network VARCHAR(20) NOT NULL DEFAULT 'Visa', status ENUM('active','blocked','expired') NOT NULL DEFAULT 'active',
 is_frozen TINYINT(1) NOT NULL DEFAULT 0, online_enabled TINYINT(1) NOT NULL DEFAULT 1, international_enabled TINYINT(1) NOT NULL DEFAULT 1,
 daily_limit DECIMAL(18,2) NOT NULL DEFAULT 10000, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_card_user FOREIGN KEY(user_id) REFERENCES users(id), CONSTRAINT fk_card_account FOREIGN KEY(account_id) REFERENCES accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, reference VARCHAR(40) NOT NULL UNIQUE, type VARCHAR(80) NOT NULL,
 principal DECIMAL(18,2) NOT NULL, outstanding_balance DECIMAL(18,2) NOT NULL, interest_rate DECIMAL(6,3) NOT NULL, monthly_payment DECIMAL(18,2) NOT NULL,
 next_payment_date DATE NULL, term_months SMALLINT UNSIGNED NOT NULL, payments_remaining SMALLINT UNSIGNED NOT NULL,
 status ENUM('pending','active','paid','rejected','defaulted') NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_loan_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, subject VARCHAR(180) NOT NULL,
 category VARCHAR(80) NOT NULL, priority ENUM('low','normal','medium','high') NOT NULL DEFAULT 'normal', status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
 assigned_to BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT fk_ticket_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, message TEXT NOT NULL, is_staff TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
 CONSTRAINT fk_message_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE, CONSTRAINT fk_message_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
 setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, action VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL,
 ip_address VARCHAR(45), user_agent VARCHAR(255), created_at DATETIME NOT NULL, INDEX idx_audit_user(user_id), INDEX idx_audit_date(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_preferences (
 user_id BIGINT UNSIGNED NOT NULL, pref_key VARCHAR(80) NOT NULL, pref_value VARCHAR(255) NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(user_id,pref_key), CONSTRAINT fk_pref_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attempt_key VARCHAR(190) NOT NULL, success TINYINT(1) NOT NULL DEFAULT 0, ip_address VARCHAR(45) NULL, created_at DATETIME NOT NULL,
 INDEX idx_attempt_key(attempt_key,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, type VARCHAR(40) NOT NULL DEFAULT 'general', title VARCHAR(190) NOT NULL,
 body VARCHAR(500) NULL, link VARCHAR(190) NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
 INDEX idx_notif_user(user_id), INDEX idx_notif_unread(user_id,is_read), CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exchange_rates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, from_currency CHAR(3) NOT NULL, to_currency CHAR(3) NOT NULL, rate DECIMAL(20,10) NOT NULL,
 updated_at DATETIME NOT NULL, UNIQUE KEY uq_pair(from_currency,to_currency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, token VARCHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
 INDEX idx_reset_user(user_id), INDEX idx_reset_token(token), CONSTRAINT fk_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE IF NOT EXISTS roles (
 id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, display_name VARCHAR(100) NOT NULL, description VARCHAR(255), is_system TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS permissions (
 id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, display_name VARCHAR(140) NOT NULL, module VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL, INDEX idx_permissions_module(module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS role_permissions (
 role_id SMALLINT UNSIGNED NOT NULL, permission_id SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(role_id,permission_id), CONSTRAINT fk_rp_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE, CONSTRAINT fk_rp_permission FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS user_roles (
 user_id BIGINT UNSIGNED NOT NULL, role_id SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(user_id,role_id), CONSTRAINT fk_ur_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, CONSTRAINT fk_ur_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS lookup_values (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lookup_group VARCHAR(80) NOT NULL, value_key VARCHAR(80) NOT NULL, display_value VARCHAR(120) NOT NULL, sort_order SMALLINT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_lookup(lookup_group,value_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS notification_templates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_key VARCHAR(100) NOT NULL UNIQUE, channel ENUM('email','sms','system') NOT NULL, subject VARCHAR(190), body TEXT NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer savings goals (new in 2026.08 release)
CREATE TABLE IF NOT EXISTS savings_goals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL, target_amount DECIMAL(18,2) NOT NULL, saved_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 target_date DATE NULL, icon VARCHAR(16) NULL, color VARCHAR(20) NULL,
 status ENUM('active','completed','archived') NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 INDEX idx_savings_goals_user(user_id), CONSTRAINT fk_savings_goal_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mobile check deposits (new in 2026.08 release)
CREATE TABLE IF NOT EXISTS check_deposits (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, account_id BIGINT UNSIGNED NOT NULL,
 reference VARCHAR(40) NOT NULL UNIQUE, amount DECIMAL(18,2) NOT NULL, check_number VARCHAR(40) NULL,
 front_image_path VARCHAR(255) NOT NULL, back_image_path VARCHAR(255) NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 review_note VARCHAR(255) NULL, transaction_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 INDEX idx_check_deposits_user(user_id), INDEX idx_check_deposits_status(status),
 CONSTRAINT fk_check_deposit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_check_deposit_account FOREIGN KEY(account_id) REFERENCES accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET NAMES utf8mb4;

-- Add recipient_routing column to transfers if missing (safe for existing installs).
SET @has_routing := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='transfers' AND column_name='recipient_routing');
SET @ddl2 := IF(@has_routing = 0, 'ALTER TABLE transfers ADD COLUMN recipient_routing VARCHAR(60) NULL AFTER recipient_bank', 'SELECT 1');
PREPARE stmt2 FROM @ddl2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Add twofa_enabled column to users if missing (safe for existing installs).
SET @has_twofa := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='users' AND column_name='twofa_enabled');
SET @ddl := IF(@has_twofa = 0, 'ALTER TABLE users ADD COLUMN twofa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER status', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO users (id,username,email,password_hash,first_name,last_name,role,status,created_at,updated_at) VALUES
(1,'northadmin','admin@northwest.financeltd.org','$2y$12$EltRBU5UuWsjluAHadTdPuyrSTUJLMKLUGH2X8HugEknRLIlhZGYe','North','Admin','admin','active',NOW(),NOW()),
(2,'jamesd','james.davidson@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','James','Davidson','customer','active',NOW(),NOW()),
(3,'oliviam','olivia.martin@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','Olivia','Martin','customer','active',NOW(),NOW()),
(4,'jacksonl','jackson.lee@example.com','$2y$12$GCWbJYR5lq3354CMwSGPl.jswvmIOZv9c0ymGny2Q4tdidcnhuedS','Jackson','Lee','customer','active',NOW(),NOW())
ON DUPLICATE KEY UPDATE email=VALUES(email);

INSERT INTO customer_profiles (user_id,phone,address,city,country,date_of_birth,kyc_status,created_at,updated_at) VALUES
(2,'+1 212 555 0187','284 Park Avenue','New York','United States','1987-04-16','verified',NOW(),NOW()),
(3,'+1 310 555 0194','14 Ocean Drive','Los Angeles','United States','1992-09-21','verified',NOW(),NOW()),
(4,'+1 206 555 0122','810 Pine Street','Seattle','United States','1985-11-02','verified',NOW(),NOW())
ON DUPLICATE KEY UPDATE phone=VALUES(phone);

INSERT INTO accounts (id,user_id,account_number,name,type,currency,balance,available_balance,status,is_primary,created_at,updated_at) VALUES
(1,2,'NW-1482-4821','NorthWest Select','checking','USD',64830.45,64830.45,'active',1,NOW(),NOW()),
(2,2,'NW-1482-1098','Growth Savings','savings','USD',19420.25,19420.25,'active',0,NOW(),NOW()),
(3,3,'NW-2374-1150','NorthWest Select','checking','USD',84250.70,84250.70,'active',1,NOW(),NOW()),
(4,4,'NW-5088-4922','NorthWest Select','checking','USD',12840.00,12840.00,'active',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO beneficiaries (user_id,name,account_number,bank_name,routing_code,currency,status,created_at) VALUES
(2,'Sarah Wilson','•••• 8402','NorthWest Bank','021000021','USD','verified',NOW()),
(2,'Michael Chen','•••• 1094','Chase Bank','021000021','USD','verified',NOW()),
(2,'Apex Property Group','•••• 7721','Wells Fargo','121000248','USD','verified',NOW());

INSERT INTO transactions (account_id,reference,type,category,description,amount,currency,balance_after,status,transaction_date,created_at) VALUES
(1,'NW-TX-100001','debit','Shopping','Apple Store',124.99,'USD',64830.45,'completed',CURDATE()-INTERVAL 1 DAY,NOW()-INTERVAL 1 DAY),
(1,'NW-TX-100002','credit','Income','Salary deposit',4850.00,'USD',64955.44,'completed',CURDATE()-INTERVAL 2 DAY,NOW()-INTERVAL 2 DAY),
(1,'NW-TX-100003','debit','Travel','Marriott Hotel',780.40,'USD',60105.44,'completed',CURDATE()-INTERVAL 3 DAY,NOW()-INTERVAL 3 DAY),
(1,'NW-TX-100004','credit','Transfer','Sarah Wilson',1200.00,'USD',60885.84,'completed',CURDATE()-INTERVAL 4 DAY,NOW()-INTERVAL 4 DAY),
(1,'NW-TX-100005','debit','Utilities','City Electric',145.22,'USD',59685.84,'completed',CURDATE()-INTERVAL 5 DAY,NOW()-INTERVAL 5 DAY),
(2,'NW-TX-100006','credit','Interest','Monthly interest',84.32,'USD',19420.25,'completed',CURDATE()-INTERVAL 8 DAY,NOW()-INTERVAL 8 DAY);

INSERT INTO cards (user_id,account_id,cardholder_name,masked_number,last_four,expiry_month,expiry_year,card_type,network,status,is_frozen,online_enabled,international_enabled,daily_limit,created_at,updated_at) VALUES
(2,1,'JAMES DAVIDSON','5422 88•• •••• 4821','4821',9,2029,'physical','Visa','active',0,1,1,10000,NOW(),NOW());

INSERT INTO loans (user_id,reference,type,principal,outstanding_balance,interest_rate,monthly_payment,next_payment_date,term_months,payments_remaining,status,created_at,updated_at) VALUES
(2,'NW-LN-209184','Personal loan',34000,18420,6.250,1024.60,CURDATE()+INTERVAL 14 DAY,36,18,'active',NOW(),NOW());

INSERT INTO support_tickets (id,reference,user_id,subject,category,priority,status,created_at,updated_at) VALUES
(1,'TKT-2608-84920',2,'Card payment I do not recognize','cards','high','open',NOW()-INTERVAL 2 HOUR,NOW()-INTERVAL 12 MINUTE);
INSERT INTO ticket_messages (ticket_id,user_id,message,is_staff,created_at) VALUES
(1,2,'I noticed a card payment from a merchant I do not recognize. Can you help me check it?',0,NOW()-INTERVAL 2 HOUR);

INSERT INTO settings (setting_key,setting_value,updated_at) VALUES
('institution_name','NorthWest Financial Ltd.',NOW()),('support_email','support@northwest.financeltd.org',NOW()),('default_currency','USD',NOW()),('daily_transfer_limit','25000',NOW()),('session_timeout','15',NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO roles (id,name,display_name,description,is_system,created_at) VALUES
(1,'admin','Administrator','Full banking operations administration',1,NOW()),
(2,'customer','Customer','Personal online-banking customer',1,NOW())
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);
INSERT INTO permissions (id,name,display_name,module,created_at) VALUES
(1,'dashboard.view','View operations dashboard','dashboard',NOW()),(2,'customers.manage','Manage customers','customers',NOW()),
(3,'transactions.manage','Review and manage transactions','transactions',NOW()),(4,'cards.manage','Manage customer cards','cards',NOW()),
(5,'loans.manage','Manage loans','loans',NOW()),(6,'support.manage','Manage support tickets','support',NOW()),
(7,'settings.manage','Manage system settings','settings',NOW()),(8,'banking.use','Use personal banking','banking',NOW())
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);
INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT 1,id FROM permissions WHERE id BETWEEN 1 AND 7;
INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES(2,8);
INSERT IGNORE INTO user_roles(user_id,role_id) SELECT id,IF(role='admin',1,2) FROM users;
INSERT INTO lookup_values(lookup_group,value_key,display_value,sort_order,is_active) VALUES
('currency','USD','US Dollar',1,1),('currency','EUR','Euro',2,1),('currency','GBP','Pound Sterling',3,1),
('account_type','checking','Checking',1,1),('account_type','savings','Savings',2,1),('account_type','investment','Investment',3,1),
('ticket_category','general','General',1,1),('ticket_category','cards','Cards',2,1),('ticket_category','transfers','Transfers',3,1),('ticket_category','security','Security',4,1)
ON DUPLICATE KEY UPDATE display_value=VALUES(display_value);
INSERT INTO notification_templates(template_key,channel,subject,body,is_active,created_at,updated_at) VALUES
('welcome_customer','email','Welcome to NorthWest','Welcome {{first_name}}. Your NorthWest account is ready.',1,NOW(),NOW()),
('transfer_submitted','email','Transfer {{reference}} submitted','Your transfer of {{amount}} has been submitted for processing.',1,NOW(),NOW()),
('password_reset','email','Reset your NorthWest password','Use this secure link to reset your password: {{reset_url}}',1,NOW(),NOW()),
('ticket_reply','email','Update on support request {{reference}}','NorthWest support has replied to your request.',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE subject=VALUES(subject),body=VALUES(body);
INSERT INTO settings(setting_key,setting_value,updated_at) VALUES
('application_initialized','1',NOW()),('schema_version','2026.08.21',NOW()),('timezone','UTC',NOW()),('maintenance_mode','0',NOW()),('registration_enabled','0',NOW()),('supported_currencies','USD,EUR,GBP',NOW()),
('announcement_text','Welcome to NorthWest — Secure online banking with 256-bit encryption · Free NorthWest-to-NorthWest transfers · 24/7 support',NOW()),
('seo_site_name','NorthWest Financial',NOW()),('seo_title','NorthWest Financial — Secure Online Banking',NOW()),
('seo_description','Simple, secure online banking. Send money, manage cards, apply for loans and track your finances — all in one protected place with 256-bit encryption.',NOW()),
('seo_keywords','online banking, secure banking, bank transfers, digital bank, NorthWest, personal accounts, savings, loans',NOW()),
('routing_number','021000021',NOW()),('international_fee_percent','1.5',NOW()),('international_fee_flat','0',NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);

INSERT INTO exchange_rates (from_currency,to_currency,rate,updated_at) VALUES
('USD','EUR','0.9200',NOW()),('USD','GBP','0.7900',NOW()),
('EUR','USD','1.0870',NOW()),('EUR','GBP','0.8590',NOW()),
('GBP','USD','1.2660',NOW()),('GBP','EUR','1.1640',NOW())
ON DUPLICATE KEY UPDATE rate=VALUES(rate),updated_at=VALUES(updated_at);

COMMIT;

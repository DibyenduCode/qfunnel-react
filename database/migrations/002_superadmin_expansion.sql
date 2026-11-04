-- Super Admin Expansion Migration
-- Compatible with MySQL 8+ / MariaDB 10.4+

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create Platform Settings table if not exists
CREATE TABLE IF NOT EXISTS `platform_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` LONGTEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Modify Users status column to include 'suspended'
ALTER TABLE `users` MODIFY COLUMN `status` ENUM('active','disabled','suspended') NOT NULL DEFAULT 'active';

-- 3. Add price, interval, description to plans if not exist
-- (Using procedure or safe columns check for MariaDB/MySQL compatibility)
SET @dbname = DATABASE();
SET @tablename = "plans";

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'price');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `plans` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `slug`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'billing_interval');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `plans` ADD COLUMN `billing_interval` VARCHAR(20) NOT NULL DEFAULT "monthly" AFTER `price`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'description');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `plans` ADD COLUMN `description` TEXT NULL AFTER `billing_interval`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Seed / Update standard plans required by user:
-- a) Free (1 funnel)
-- b) Starter (5 funnel)
-- c) Standard (10 funnel)
-- d) Pro (unlimited)

INSERT INTO `plans` (`id`, `name`, `slug`, `price`, `billing_interval`, `description`, `limits_json`, `features_json`, `is_active`)
VALUES (1, 'Starter', 'starter', 19.00, 'monthly', 'Ideal for emerging creators and growing lead funnels', '{"max_funnels": 5, "max_submissions_per_month": 1000, "max_team_members": 3}', '{"custom_branding": false, "csv_export": true, "analytics": true, "webhooks": false}', 1)
ON DUPLICATE KEY UPDATE 
    `name` = 'Starter',
    `slug` = 'starter',
    `price` = 19.00,
    `billing_interval` = 'monthly',
    `description` = 'Ideal for emerging creators and growing lead funnels',
    `limits_json` = '{"max_funnels": 5, "max_submissions_per_month": 1000, "max_team_members": 3}',
    `features_json` = '{"custom_branding": false, "csv_export": true, "analytics": true, "webhooks": false}',
    `is_active` = 1;

INSERT INTO `plans` (`id`, `name`, `slug`, `price`, `billing_interval`, `description`, `limits_json`, `features_json`, `is_active`)
VALUES (2, 'Pro', 'pro', 99.00, 'monthly', 'Unlimited funnels and submissions for high-velocity teams', '{"max_funnels": -1, "max_submissions_per_month": -1, "max_team_members": -1}', '{"custom_branding": true, "csv_export": true, "analytics": true, "webhooks": true, "priority_support": true}', 1)
ON DUPLICATE KEY UPDATE 
    `name` = 'Pro',
    `slug` = 'pro',
    `price` = 99.00,
    `billing_interval` = 'monthly',
    `description` = 'Unlimited funnels and submissions for high-velocity teams',
    `limits_json` = '{"max_funnels": -1, "max_submissions_per_month": -1, "max_team_members": -1}',
    `features_json` = '{"custom_branding": true, "csv_export": true, "analytics": true, "webhooks": true, "priority_support": true}',
    `is_active` = 1;

INSERT INTO `plans` (`name`, `slug`, `price`, `billing_interval`, `description`, `limits_json`, `features_json`, `is_active`)
SELECT 'Free', 'free', 0.00, 'monthly', 'Essential single-funnel plan for testing the platform', '{"max_funnels": 1, "max_submissions_per_month": 100, "max_team_members": 1}', '{"custom_branding": false, "csv_export": false, "analytics": true, "webhooks": false}', 1
WHERE NOT EXISTS (SELECT 1 FROM `plans` WHERE `slug` = 'free');

INSERT INTO `plans` (`name`, `slug`, `price`, `billing_interval`, `description`, `limits_json`, `features_json`, `is_active`)
SELECT 'Standard', 'standard', 49.00, 'monthly', 'Robust multi-funnel plan for scaling marketing operations', '{"max_funnels": 10, "max_submissions_per_month": 5000, "max_team_members": 10}', '{"custom_branding": true, "csv_export": true, "analytics": true, "webhooks": true}', 1
WHERE NOT EXISTS (SELECT 1 FROM `plans` WHERE `slug` = 'standard');

-- 5. Seed default Payment Gateway settings
INSERT INTO `platform_settings` (`setting_key`, `setting_value`)
VALUES 
('payment_gateways', '{
    "stripe": {
        "enabled": false,
        "mode": "test",
        "publishable_key": "",
        "secret_key": "",
        "webhook_secret": ""
    },
    "paypal": {
        "enabled": false,
        "mode": "sandbox",
        "client_id": "",
        "client_secret": ""
    },
    "razorpay": {
        "enabled": false,
        "key_id": "",
        "key_secret": ""
    },
    "bank_transfer": {
        "enabled": false,
        "bank_name": "Silicon Valley Commercial Bank",
        "account_holder": "QuizFunnel Technologies Inc",
        "account_number": "",
        "routing_code": "",
        "instructions": "Please transfer the subscription fee to the bank details above and include your Workspace ID in the payment reference. Once completed, your subscription will be activated within 24 hours."
    },
    "manual_custom": {
        "enabled": false,
        "title": "Offline / Cheque / Invoice Payment",
        "instructions": "For corporate enterprise purchase orders or manual invoice payment, please submit a billing request to billing@qfunnel.local."
    }
}')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

SET FOREIGN_KEY_CHECKS = 1;

-- Migration 006: Recurring Billing Cycles, Data Retention Suspension, and Manual Termination Queue
-- Compatible with MySQL 8+ / MariaDB 10.4+

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Enhance tenants table with suspension, termination due, and retention attributes
ALTER TABLE `tenants` MODIFY COLUMN `status` ENUM('active','suspended','terminated','deleted') NOT NULL DEFAULT 'active';

SET @dbname = DATABASE();
SET @tablename = "tenants";

-- suspension_reason
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'suspension_reason');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `suspension_reason` VARCHAR(255) NULL AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- suspension_notes
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'suspension_notes');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `suspension_notes` TEXT NULL AFTER `suspension_reason`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- suspended_at
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'suspended_at');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `suspended_at` DATETIME NULL AFTER `suspension_notes`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- is_termination_due
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'is_termination_due');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `is_termination_due` TINYINT(1) NOT NULL DEFAULT 0 AFTER `suspended_at`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- termination_due_at
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'termination_due_at');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `termination_due_at` DATETIME NULL AFTER `is_termination_due`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- terminated_at
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'terminated_at');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `terminated_at` DATETIME NULL AFTER `termination_due_at`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- terminated_by
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'terminated_by');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `terminated_by` BIGINT UNSIGNED NULL AFTER `terminated_at`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- termination_reason
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'termination_reason');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenants` ADD COLUMN `termination_reason` TEXT NULL AFTER `terminated_by`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 2. Enhance tenant_subscriptions table with recurring payment cycle fields
SET @tablename = "tenant_subscriptions";

-- billing_interval
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'billing_interval');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `billing_interval` VARCHAR(30) NOT NULL DEFAULT "monthly" AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- current_period_start
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'current_period_start');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `current_period_start` DATETIME NULL AFTER `starts_at`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- current_period_end
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'current_period_end');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `current_period_end` DATETIME NULL AFTER `ends_at`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- grace_period_days
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'grace_period_days');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `grace_period_days` INT UNSIGNED NOT NULL DEFAULT 7 AFTER `current_period_end`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- next_billing_date
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'next_billing_date');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `next_billing_date` DATETIME NULL AFTER `grace_period_days`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- auto_renew
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'auto_renew');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `auto_renew` TINYINT(1) NOT NULL DEFAULT 1 AFTER `next_billing_date`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- amount
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'amount');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `auto_renew`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- currency
SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'currency');
SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `tenant_subscriptions` ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT "INR" AFTER `amount`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 3. Create tenant_invoices table for billing and payment transactions
CREATE TABLE IF NOT EXISTS `tenant_invoices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `subscription_id` BIGINT UNSIGNED NULL,
    `invoice_number` VARCHAR(60) NOT NULL UNIQUE,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `status` ENUM('paid', 'pending', 'past_due', 'void', 'refunded') NOT NULL DEFAULT 'pending',
    `billing_interval` VARCHAR(30) NOT NULL DEFAULT 'monthly',
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `payment_method` VARCHAR(60) NOT NULL DEFAULT 'manual_admin',
    `transaction_reference` VARCHAR(190) NULL,
    `notes` TEXT NULL,
    `paid_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_invoices` (`tenant_id`, `status`),
    INDEX `idx_inv_created` (`created_at`),
    CONSTRAINT `fk_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill existing subscriptions current_period_start and current_period_end from starts_at/ends_at
UPDATE `tenant_subscriptions` 
SET `current_period_start` = `starts_at`,
    `current_period_end` = `ends_at`,
    `next_billing_date` = `ends_at`
WHERE `current_period_end` IS NULL AND `ends_at` IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

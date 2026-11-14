-- 013: Email SMTP Settings, Verification OTP, and Template CMS

-- 1. Add email_verified_at to users table
ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME NULL DEFAULT NULL AFTER `password_hash`;

-- Set existing users as verified so existing accounts are never locked out
UPDATE `users` SET `email_verified_at` = NOW() WHERE `email_verified_at` IS NULL;

-- 2. Email Verifications Table (OTP verification)
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ev_user_email` (`user_id`, `email`),
    INDEX `idx_ev_otp` (`otp_code`),
    CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Email Templates Table
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_key` VARCHAR(60) NOT NULL UNIQUE,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `preheader` VARCHAR(255) NULL,
    `body_html` MEDIUMTEXT NOT NULL,
    `body_text` MEDIUMTEXT NULL,
    `available_tags` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Email Logs Table (Outbox / Sent History)
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recipient_email` VARCHAR(190) NOT NULL,
    `recipient_name` VARCHAR(160) NULL,
    `template_key` VARCHAR(60) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('sent', 'failed', 'simulated') NOT NULL DEFAULT 'sent',
    `error_message` TEXT NULL,
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_el_recipient` (`recipient_email`),
    INDEX `idx_el_template` (`template_key`),
    INDEX `idx_el_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

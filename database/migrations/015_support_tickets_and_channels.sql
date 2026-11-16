-- Migration 015: Support Tickets and Contact Channels
-- Creates support_tickets table to track public support inquiries and contact requests

CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_num` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'builder',
    `subject` VARCHAR(255) NOT NULL,
    `message` LONGTEXT NOT NULL,
    `status` ENUM('open', 'in_progress', 'resolved') NOT NULL DEFAULT 'open',
    `admin_notes` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ticket_status` (`status`),
    INDEX `idx_ticket_email` (`email`),
    INDEX `idx_ticket_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

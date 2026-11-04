-- Legal Pages CMS Migration
-- Creates table to dynamically manage legal policies in Super Admin

CREATE TABLE IF NOT EXISTS `legal_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(80) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `meta_description` VARCHAR(255) NULL,
    `content` LONGTEXT NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_legal_slug` (`slug`),
    INDEX `idx_legal_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

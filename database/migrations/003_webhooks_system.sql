-- 003_webhooks_system.sql
-- Create tables for webhook endpoints and delivery logs

CREATE TABLE IF NOT EXISTS `webhook_endpoints` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NULL COMMENT 'Null means applies to all funnels in workspace',
    `name` VARCHAR(160) NOT NULL,
    `target_url` TEXT NOT NULL,
    `secret` VARCHAR(64) NULL COMMENT 'HMAC secret for signing payloads',
    `event_type` VARCHAR(60) NOT NULL DEFAULT 'lead.captured' COMMENT 'lead.captured, quiz.completed, etc.',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_we_tenant_funnel` (`tenant_id`, `funnel_id`, `is_active`),
    CONSTRAINT `fk_we_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_we_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `webhook_deliveries` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `webhook_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(60) NOT NULL,
    `payload_json` LONGTEXT NOT NULL,
    `response_code` INT NULL,
    `response_body` TEXT NULL,
    `status` ENUM('success', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    `duration_ms` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_wd_tenant_hook` (`tenant_id`, `webhook_id`, `created_at`),
    CONSTRAINT `fk_wd_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wd_hook` FOREIGN KEY (`webhook_id`) REFERENCES `webhook_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 008: Custom Tenant Lead Stages and Colors
CREATE TABLE IF NOT EXISTS `tenant_lead_stages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `color` VARCHAR(20) NOT NULL DEFAULT '#6366F1',
    `order_index` INT NOT NULL DEFAULT 0,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_stage_slug` (`tenant_id`, `slug`),
    INDEX `idx_tenant_stage_order` (`tenant_id`, `order_index`),
    CONSTRAINT `fk_tenant_lead_stages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change lead_status from rigid enum to flexible varchar(50) to support user-created stages
ALTER TABLE `quiz_sessions` MODIFY COLUMN `lead_status` VARCHAR(50) NOT NULL DEFAULT 'new';

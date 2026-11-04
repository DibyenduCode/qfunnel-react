-- Quiz Funnel SaaS Initial Schema Migration
-- Compatible with MySQL 8+ / MariaDB 10.4+ in XAMPP
-- Engine: InnoDB, Charset: utf8mb4

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tenants
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(160) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_platform_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tenant Memberships
CREATE TABLE IF NOT EXISTS `tenant_memberships` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('owner','admin','editor','analyst') NOT NULL DEFAULT 'owner',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_user` (`tenant_id`, `user_id`),
    INDEX `idx_user_tenant` (`user_id`, `tenant_id`),
    CONSTRAINT `fk_tm_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Plans
CREATE TABLE IF NOT EXISTS `plans` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(80) NOT NULL UNIQUE,
    `limits_json` JSON NULL,
    `features_json` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tenant Subscriptions
CREATE TABLE IF NOT EXISTS `tenant_subscriptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
    `starts_at` DATETIME NULL,
    `ends_at` DATETIME NULL,
    `provider` VARCHAR(80) NULL,
    `provider_subscription_id` VARCHAR(190) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_sub_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_ts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ts_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Funnels
CREATE TABLE IF NOT EXISTS `funnels` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(160) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `current_published_version_id` BIGINT UNSIGNED NULL,
    `settings_json` JSON NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_funnel_slug` (`tenant_id`, `slug`),
    INDEX `idx_tenant_status_updated` (`tenant_id`, `status`, `updated_at`),
    CONSTRAINT `fk_funnels_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_funnels_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Funnel Versions
CREATE TABLE IF NOT EXISTS `funnel_versions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL,
    `graph_snapshot_json` JSON NULL,
    `form_schema_snapshot_json` JSON NULL,
    `settings_snapshot_json` JSON NULL,
    `published_by` BIGINT UNSIGNED NULL,
    `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_funnel_version` (`funnel_id`, `version_number`),
    INDEX `idx_tenant_funnel_pub` (`tenant_id`, `funnel_id`, `published_at`),
    CONSTRAINT `fk_fv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fv_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fv_user` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Funnel Nodes
CREATE TABLE IF NOT EXISTS `funnel_nodes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `node_key` VARCHAR(80) NOT NULL,
    `node_type` ENUM('start','question','destination','form') NOT NULL,
    `title` VARCHAR(255) NULL,
    `is_start` TINYINT(1) NOT NULL DEFAULT 0,
    `position_x` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `position_y` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `config_json` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_funnel_node_key` (`funnel_id`, `node_key`),
    INDEX `idx_tenant_funnel_node_type` (`tenant_id`, `funnel_id`, `node_type`),
    CONSTRAINT `fk_fn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fn_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Questions
CREATE TABLE IF NOT EXISTS `questions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `node_id` BIGINT UNSIGNED NOT NULL,
    `question_text` TEXT NOT NULL,
    `help_text` TEXT NULL,
    `question_type` ENUM('single_choice','multiple_choice','text','email','number','dropdown') NOT NULL DEFAULT 'single_choice',
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `validation_json` JSON NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_node_question` (`node_id`),
    CONSTRAINT `fk_q_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_q_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_q_node` FOREIGN KEY (`node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Question Options
CREATE TABLE IF NOT EXISTS `question_options` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `option_key` VARCHAR(80) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_question_option_key` (`question_id`, `option_key`),
    INDEX `idx_tenant_q_order` (`tenant_id`, `question_id`, `display_order`),
    CONSTRAINT `fk_qo_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qo_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Funnel Edges
CREATE TABLE IF NOT EXISTS `funnel_edges` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `source_node_id` BIGINT UNSIGNED NOT NULL,
    `source_option_id` BIGINT UNSIGNED NULL,
    `target_node_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_fe_source` (`funnel_id`, `source_node_id`),
    INDEX `idx_fe_target` (`funnel_id`, `target_node_id`),
    CONSTRAINT `fk_fe_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fe_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fe_source_node` FOREIGN KEY (`source_node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fe_source_opt` FOREIGN KEY (`source_option_id`) REFERENCES `question_options` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fe_target_node` FOREIGN KEY (`target_node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Destinations
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `node_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `url` TEXT NOT NULL,
    `allowed_host` VARCHAR(255) NULL,
    `tracking_config_json` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_node_destination` (`node_id`),
    INDEX `idx_dest_tenant_funnel` (`tenant_id`, `funnel_id`),
    CONSTRAINT `fk_dest_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dest_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dest_node` FOREIGN KEY (`node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Form Fields
CREATE TABLE IF NOT EXISTS `form_fields` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `field_key` VARCHAR(100) NOT NULL,
    `label` VARCHAR(180) NOT NULL,
    `field_type` ENUM('text','email','phone','number','dropdown','single_select','multi_select','textarea','date','consent') NOT NULL DEFAULT 'text',
    `placeholder` VARCHAR(255) NULL,
    `help_text` TEXT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `validation_json` JSON NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_funnel_field_key` (`funnel_id`, `field_key`),
    INDEX `idx_tenant_funnel_order` (`tenant_id`, `funnel_id`, `display_order`),
    CONSTRAINT `fk_ff_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ff_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Form Field Options
CREATE TABLE IF NOT EXISTS `form_field_options` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `field_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 1,
    INDEX `idx_ffo_field_order` (`field_id`, `display_order`),
    CONSTRAINT `fk_ffo_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ffo_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Quiz Sessions
CREATE TABLE IF NOT EXISTS `quiz_sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `funnel_version_id` BIGINT UNSIGNED NOT NULL,
    `public_token_hash` CHAR(64) NOT NULL UNIQUE,
    `status` ENUM('form_pending','in_progress','completed','abandoned','expired') NOT NULL DEFAULT 'form_pending',
    `current_node_id` BIGINT UNSIGNED NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `last_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `final_destination_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qs_tenant_funnel_status` (`tenant_id`, `funnel_id`, `status`, `created_at`),
    INDEX `idx_qs_version_status` (`funnel_id`, `funnel_version_id`, `status`),
    INDEX `idx_qs_expires` (`expires_at`),
    CONSTRAINT `fk_qs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qs_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qs_version` FOREIGN KEY (`funnel_version_id`) REFERENCES `funnel_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Form Submissions
CREATE TABLE IF NOT EXISTS `form_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `funnel_version_id` BIGINT UNSIGNED NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fsub_tenant_date` (`tenant_id`, `funnel_id`, `submitted_at`),
    CONSTRAINT `fk_fsub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fsub_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fsub_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Form Submission Values
CREATE TABLE IF NOT EXISTS `form_submission_values` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `form_submission_id` BIGINT UNSIGNED NOT NULL,
    `field_id` BIGINT UNSIGNED NULL,
    `field_key_snapshot` VARCHAR(100) NOT NULL,
    `field_label_snapshot` VARCHAR(180) NOT NULL,
    `field_type_snapshot` VARCHAR(40) NOT NULL,
    `value_text` LONGTEXT NULL,
    `value_json` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fsv_sub` (`tenant_id`, `form_submission_id`),
    INDEX `idx_fsv_key` (`field_key_snapshot`),
    CONSTRAINT `fk_fsv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fsv_sub` FOREIGN KEY (`form_submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Quiz Answers
CREATE TABLE IF NOT EXISTS `quiz_answers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `funnel_version_id` BIGINT UNSIGNED NOT NULL,
    `node_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NULL,
    `question_text_snapshot` TEXT NOT NULL,
    `answer_type` VARCHAR(40) NOT NULL,
    `answer_value_text` LONGTEXT NULL,
    `answer_value_json` JSON NULL,
    `selected_option_ids_json` JSON NULL,
    `visit_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `answered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `superseded_at` DATETIME NULL,
    INDEX `idx_qa_tenant_session` (`tenant_id`, `session_id`, `answered_at`),
    INDEX `idx_qa_node_visit` (`session_id`, `node_id`, `visit_number`),
    CONSTRAINT `fk_qa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qa_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Quiz Step Events
CREATE TABLE IF NOT EXISTS `quiz_step_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `funnel_version_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `node_id` BIGINT UNSIGNED NULL,
    `option_id` BIGINT UNSIGNED NULL,
    `event_data_json` JSON NULL,
    `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qse_session` (`tenant_id`, `session_id`, `occurred_at`),
    INDEX `idx_qse_funnel_event` (`tenant_id`, `funnel_id`, `event_type`, `occurred_at`),
    CONSTRAINT `fk_qse_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qse_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Quiz Results
CREATE TABLE IF NOT EXISTS `quiz_results` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `funnel_id` BIGINT UNSIGNED NOT NULL,
    `funnel_version_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `result_token_hash` CHAR(64) NULL UNIQUE,
    `summary_json` JSON NOT NULL,
    `destination_id` BIGINT UNSIGNED NULL,
    `redirect_status` ENUM('pending','attempted','failed','confirmed') NOT NULL DEFAULT 'pending',
    `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_qr_session` (`session_id`),
    CONSTRAINT `fk_qr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qr_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Usage Counters
CREATE TABLE IF NOT EXISTS `usage_counters` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `metric_key` VARCHAR(80) NOT NULL,
    `period_start` DATE NOT NULL,
    `count_value` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_uc_metric_period` (`tenant_id`, `metric_key`, `period_start`),
    CONSTRAINT `fk_uc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `resource_type` VARCHAR(80) NOT NULL,
    `resource_id` BIGINT UNSIGNED NULL,
    `metadata_json` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_tenant_date` (`tenant_id`, `created_at`),
    CONSTRAINT `fk_al_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

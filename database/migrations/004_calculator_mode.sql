-- 004_calculator_mode.sql
-- Add calculator mode and quote calculation tracking columns

ALTER TABLE `funnels` ADD COLUMN IF NOT EXISTS `funnel_mode` ENUM('quiz', 'calculator') NOT NULL DEFAULT 'quiz' AFTER `slug`;
ALTER TABLE `quiz_sessions` ADD COLUMN IF NOT EXISTS `calculated_value` DECIMAL(14,4) NULL AFTER `final_destination_id`;
ALTER TABLE `quiz_sessions` ADD COLUMN IF NOT EXISTS `formatted_quote` VARCHAR(255) NULL AFTER `calculated_value`;
ALTER TABLE `form_submissions` ADD COLUMN IF NOT EXISTS `calculated_value` DECIMAL(14,4) NULL AFTER `submitted_at`;
ALTER TABLE `form_submissions` ADD COLUMN IF NOT EXISTS `formatted_quote` VARCHAR(255) NULL AFTER `calculated_value`;
ALTER TABLE `quiz_results` ADD COLUMN IF NOT EXISTS `calculated_value` DECIMAL(14,4) NULL AFTER `redirect_status`;
ALTER TABLE `quiz_results` ADD COLUMN IF NOT EXISTS `formatted_quote` VARCHAR(255) NULL AFTER `calculated_value`;

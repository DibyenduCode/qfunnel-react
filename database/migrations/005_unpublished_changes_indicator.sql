-- Migration: Add has_unpublished_changes flag to funnels table
ALTER TABLE `funnels` ADD COLUMN IF NOT EXISTS `has_unpublished_changes` TINYINT(1) NOT NULL DEFAULT 0 AFTER `current_published_version_id`;

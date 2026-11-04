-- Migration 009: Add frontend visibility control to plans
-- Allows super admin to show or hide any plan from the public frontend

SET @tablename = "plans";
SET @columnname = "is_visible";
SET @precheck = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = @columnname
);

SET @sqlstmt = IF(@precheck = 0, 'ALTER TABLE `plans` ADD COLUMN `is_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

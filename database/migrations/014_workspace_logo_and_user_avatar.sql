-- 014: Add Workspace Logo and User Profile Avatar Columns

-- 1. Add logo_url to tenants
ALTER TABLE `tenants` ADD COLUMN `logo_url` VARCHAR(255) NULL AFTER `slug`;

-- 2. Add avatar_url to users
ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(255) NULL AFTER `email`;

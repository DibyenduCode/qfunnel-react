-- =================================================================
-- FilterFunnel SaaS Production Database Dump
-- Clean State: Super Administrator Account Only (Zero Test Data)
-- Compatible with cPanel phpMyAdmin, MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- =================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: qfunnel2_db
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(80) NOT NULL,
  `resource_id` bigint(20) unsigned DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_tenant_date` (`tenant_id`,`created_at`),
  KEY `fk_al_user` (`user_id`),
  CONSTRAINT `fk_al_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `node_id` bigint(20) unsigned NOT NULL,
  `name` varchar(180) NOT NULL,
  `url` text NOT NULL,
  `allowed_host` varchar(255) DEFAULT NULL,
  `tracking_config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_config_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_node_destination` (`node_id`),
  KEY `idx_dest_tenant_funnel` (`tenant_id`,`funnel_id`),
  KEY `fk_dest_funnel` (`funnel_id`),
  CONSTRAINT `fk_dest_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dest_node` FOREIGN KEY (`node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dest_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinations`
--

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(190) NOT NULL,
  `recipient_name` varchar(160) DEFAULT NULL,
  `template_key` varchar(60) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed','simulated') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_el_recipient` (`recipient_email`),
  KEY `idx_el_template` (`template_key`),
  KEY `idx_el_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `preheader` varchar(255) DEFAULT NULL,
  `body_html` mediumtext NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `available_tags` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES
(1,'email_verification_otp','Email Verification Code (OTP)','Sent immediately after user registration to verify ownership with a 6-digit OTP code.','Your {{app_name}} verification code: {{otp_code}}','Use code {{otp_code}} to verify your account and access your dashboard.','\n                    <h2 style=\"font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;\">Verify Your Email Address</h2>\n                    <p style=\"margin-top: 0; color: #475569;\">Hello <strong>{{user_name}}</strong>,</p>\n                    <p style=\"color: #475569;\">Thank you for registering on <strong>{{app_name}}</strong>. Please use the 6-digit One-Time Password (OTP) below to verify your email address and activate your workspace dashboard:</p>\n                    \n                    <div style=\"margin: 28px 0; text-align: center;\">\n                        <div style=\"display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1d5fd6; background: #eff6ff; border: 2px dashed #bfdbfe; border-radius: 12px; padding: 14px 28px; font-family: monospace;\">\n                            {{otp_code}}\n                        </div>\n                    </div>\n\n                    <div style=\"background: #f8fafc; border-left: 4px solid #1d5fd6; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #475569; margin-bottom: 24px;\">\n                        ⏱️ This verification code is valid for <strong>{{otp_expiry_minutes}} minutes</strong>. For security, never share this code with anyone.\n                    </div>\n\n                    <div style=\"background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; font-size: 12.5px; color: #92400e; margin-bottom: 24px; line-height: 1.5;\">\n                        📬 <strong>Can\'t find this in your Primary Inbox?</strong> Please check your <strong>Spam</strong> or <strong>Junk</strong> folder. If you find it there, please mark it as <em>\"Not Spam\"</em> so you receive all important platform notifications directly.\n                    </div>\n\n                    <p style=\"font-size: 13px; color: #64748b; margin-bottom: 0;\">If you did not create an account on {{app_name}}, please disregard this message or notify our support team.</p>\n                ','Hello {{user_name}},\n\nYour 6-digit verification code is: {{otp_code}}\n\nThis code expires in {{otp_expiry_minutes}} minutes.\n\nThank you,\n{{app_name}} Team','[\"app_name\",\"user_name\",\"user_email\",\"otp_code\",\"otp_expiry_minutes\",\"support_email\"]',1,'2026-11-14 13:38:57','2026-11-14 13:38:57'),
(2,'welcome_email','Welcome to Platform','Sent after email verification to welcome new workspace owners.','Welcome to {{app_name}}, {{user_name}}! 🚀','Your workspace {{tenant_name}} is ready. Build your first quiz funnel.','\n                    <h2 style=\"font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;\">Welcome to {{app_name}}! 🚀</h2>\n                    <p style=\"margin-top: 0; color: #475569;\">Hello <strong>{{user_name}}</strong>,</p>\n                    <p style=\"color: #475569;\">Your email is verified and your customer workspace <strong>{{tenant_name}}</strong> is live and ready for high-converting interactive funnels.</p>\n\n                    <div style=\"margin: 24px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;\">\n                        <h4 style=\"margin: 0 0 10px 0; font-size: 14px; color: #0f172a;\">Quick Start Checklist:</h4>\n                        <ul style=\"margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.8;\">\n                            <li>Create your first visual quiz with our drag-and-drop React Flow canvas</li>\n                            <li>Configure branching paths, lead forms, and instant calculator quotes</li>\n                            <li>Embed the quiz on your website via iframe or popup widget</li>\n                            <li>Track incoming leads in real-time with automatic WhatsApp routing</li>\n                        </ul>\n                    </div>\n\n                    <div style=\"text-align: center; margin: 28px 0;\">\n                        <a href=\"{{dashboard_url}}\" style=\"background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 8px; display: inline-block;\">Go to Workspace Dashboard →</a>\n                    </div>\n\n                    <p style=\"font-size: 13px; color: #64748b;\">Have any questions? Simply reply to this email or reach us at {{support_email}}.</p>\n                ','Hello {{user_name}},\n\nWelcome to {{app_name}}! Your workspace {{tenant_name}} is ready.\n\nOpen your dashboard here: {{dashboard_url}}\n\nBest regards,\n{{app_name}} Team','[\"app_name\",\"user_name\",\"user_email\",\"tenant_name\",\"workspace_slug\",\"dashboard_url\",\"support_email\"]',1,'2026-11-14 13:38:57','2026-11-16 18:00:56'),
(3,'invoice_receipt','Billing & Payment Receipt','Sent when a subscription payment is recorded or invoice is generated.','Payment Receipt for Invoice #{{invoice_number}} - {{app_name}}','Payment confirmed for {{plan_name}} ({{currency_symbol}}{{amount}}).','\n                    <h2 style=\"font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;\">Payment Receipt & Invoice</h2>\n                    <p style=\"margin-top: 0; color: #475569;\">Hello <strong>{{user_name}}</strong>,</p>\n                    <p style=\"color: #475569;\">We have received your payment for workspace <strong>{{tenant_name}}</strong>. Your transaction has been processed successfully.</p>\n\n                    <table style=\"width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 13px;\">\n                        <tr style=\"border-bottom: 1px solid #e2e8f0; background: #f8fafc;\">\n                            <td style=\"padding: 10px 14px; font-weight: 600; color: #475569;\">Invoice Number</td>\n                            <td style=\"padding: 10px 14px; text-align: right; font-weight: 700; color: #0f172a; font-family: monospace;\">#{{invoice_number}}</td>\n                        </tr>\n                        <tr style=\"border-bottom: 1px solid #e2e8f0;\">\n                            <td style=\"padding: 10px 14px; color: #64748b;\">Subscription Plan</td>\n                            <td style=\"padding: 10px 14px; text-align: right; font-weight: 600; color: #0f172a;\">{{plan_name}} ({{billing_interval}})</td>\n                        </tr>\n                        <tr style=\"border-bottom: 1px solid #e2e8f0;\">\n                            <td style=\"padding: 10px 14px; color: #64748b;\">Payment Method</td>\n                            <td style=\"padding: 10px 14px; text-align: right; color: #0f172a; text-transform: capitalize;\">{{payment_method}}</td>\n                        </tr>\n                        <tr style=\"border-bottom: 1px solid #e2e8f0;\">\n                            <td style=\"padding: 10px 14px; color: #64748b;\">Date Paid</td>\n                            <td style=\"padding: 10px 14px; text-align: right; color: #0f172a;\">{{paid_at}}</td>\n                        </tr>\n                        <tr style=\"background: #eff6ff; font-size: 14px;\">\n                            <td style=\"padding: 12px 14px; font-weight: 700; color: #1d5fd6;\">Total Paid</td>\n                            <td style=\"padding: 12px 14px; text-align: right; font-weight: 800; color: #1d5fd6;\">{{currency_symbol}}{{amount}}</td>\n                        </tr>\n                    </table>\n\n                    <div style=\"text-align: center; margin: 24px 0;\">\n                        <a href=\"{{dashboard_url}}\" style=\"background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;\">View Billing in Dashboard →</a>\n                    </div>\n\n                    <p style=\"font-size: 12px; color: #64748b;\">This receipt serves as your official proof of payment. For tax invoices or corporate GST details, please reach out to {{support_email}}.</p>\n                ','Payment Receipt: Invoice #{{invoice_number}}\n\nPlan: {{plan_name}}\nAmount: {{currency_symbol}}{{amount}}\nDate: {{paid_at}}\n\nThank you for choosing {{app_name}}!','[\"app_name\",\"user_name\",\"tenant_name\",\"invoice_number\",\"plan_name\",\"amount\",\"currency_symbol\",\"payment_method\",\"billing_interval\",\"paid_at\",\"dashboard_url\",\"support_email\"]',1,'2026-11-14 13:38:57','2026-11-14 13:38:57'),
(4,'renewal_reminder','Subscription Renewal Reminder','Sent before subscription renewal or expiration date.','Reminder: Your {{app_name}} subscription renews on {{due_date}}','Upcoming renewal for {{tenant_name}} ({{currency_symbol}}{{amount}}).','\n                    <h2 style=\"font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;\">Upcoming Subscription Renewal</h2>\n                    <p style=\"margin-top: 0; color: #475569;\">Hello <strong>{{user_name}}</strong>,</p>\n                    <p style=\"color: #475569;\">This is a friendly reminder that your subscription for workspace <strong>{{tenant_name}}</strong> is scheduled for renewal on <strong>{{due_date}}</strong>.</p>\n\n                    <div style=\"margin: 20px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;\">\n                        <p style=\"margin: 0 0 6px 0; font-size: 13px; color: #64748b;\">Current Plan:</p>\n                        <p style=\"margin: 0 0 12px 0; font-size: 16px; font-weight: 700; color: #0f172a;\">{{plan_name}} • {{currency_symbol}}{{amount}} / {{billing_interval}}</p>\n                        <p style=\"margin: 0; font-size: 12px; color: #64748b;\">Renewal / Due Date: <strong>{{due_date}}</strong></p>\n                    </div>\n\n                    <p style=\"font-size: 13px; color: #475569;\">To ensure uninterrupted access to your live quizzes, automated lead routing, and analytics, please ensure your payment method is up to date.</p>\n\n                    <div style=\"text-align: center; margin: 24px 0;\">\n                        <a href=\"{{dashboard_url}}\" style=\"background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;\">Manage Billing & Invoices →</a>\n                    </div>\n                ','Subscription Renewal Reminder\n\nWorkspace: {{tenant_name}}\nPlan: {{plan_name}}\nAmount: {{currency_symbol}}{{amount}}\nDue: {{due_date}}\n\nManage your billing here: {{dashboard_url}}','[\"app_name\",\"user_name\",\"tenant_name\",\"plan_name\",\"amount\",\"currency_symbol\",\"due_date\",\"billing_interval\",\"dashboard_url\",\"support_email\"]',1,'2026-11-14 13:38:57','2026-11-14 13:38:57'),
(5,'account_suspended','Account Suspension Notice','Sent if payment grace period expires or administrative suspension occurs.','Important: Your {{app_name}} workspace has been suspended','Action required to restore active status for {{tenant_name}}.','\n                    <h2 style=\"font-size: 20px; font-weight: 700; color: #dc2626; margin-top: 0; margin-bottom: 8px;\">Workspace Suspended</h2>\n                    <p style=\"margin-top: 0; color: #475569;\">Hello <strong>{{user_name}}</strong>,</p>\n                    <p style=\"color: #475569;\">Your customer workspace <strong>{{tenant_name}}</strong> has been suspended due to:</p>\n\n                    <div style=\"margin: 18px 0; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px 18px; color: #991b1b; font-size: 13px; font-weight: 600;\">\n                        ⚠️ {{suspension_reason}}\n                    </div>\n\n                    <p style=\"font-size: 13px; color: #475569;\">While suspended, visitor quiz submissions are paused and editing features are locked. Your data remains fully safe.</p>\n\n                    <div style=\"text-align: center; margin: 24px 0;\">\n                        <a href=\"{{dashboard_url}}\" style=\"background: #dc2626; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;\">Reactivate Workspace →</a>\n                    </div>\n\n                    <p style=\"font-size: 12px; color: #64748b;\">If you need assistance or believe this is an error, contact our support team at {{support_email}}.</p>\n                ','Workspace Suspended: {{tenant_name}}\nReason: {{suspension_reason}}\n\nPlease visit your dashboard to reactivate: {{dashboard_url}}\n\n{{app_name}} Support','[\"app_name\",\"user_name\",\"tenant_name\",\"suspension_reason\",\"dashboard_url\",\"support_email\"]',1,'2026-11-14 13:38:57','2026-11-14 13:38:57');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `email` varchar(190) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ev_user_email` (`user_id`,`email`),
  KEY `idx_ev_otp` (`otp_code`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_rate` (`ip_address`,`email`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_field_options`
--

DROP TABLE IF EXISTS `form_field_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_field_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ffo_field_order` (`field_id`,`display_order`),
  KEY `fk_ffo_tenant` (`tenant_id`),
  CONSTRAINT `fk_ffo_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ffo_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_field_options`
--

LOCK TABLES `form_field_options` WRITE;
/*!40000 ALTER TABLE `form_field_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_field_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_fields`
--

DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `field_key` varchar(100) NOT NULL,
  `label` varchar(180) NOT NULL,
  `field_type` enum('text','email','phone','number','dropdown','single_select','multi_select','textarea','date','consent') NOT NULL DEFAULT 'text',
  `placeholder` varchar(255) DEFAULT NULL,
  `help_text` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_json`)),
  `display_order` int(10) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_funnel_field_key` (`funnel_id`,`field_key`),
  KEY `idx_tenant_funnel_order` (`tenant_id`,`funnel_id`,`display_order`),
  CONSTRAINT `fk_ff_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ff_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_fields`
--

LOCK TABLES `form_fields` WRITE;
/*!40000 ALTER TABLE `form_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submission_values`
--

DROP TABLE IF EXISTS `form_submission_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `form_submission_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned DEFAULT NULL,
  `field_key_snapshot` varchar(100) NOT NULL,
  `field_label_snapshot` varchar(180) NOT NULL,
  `field_type_snapshot` varchar(40) NOT NULL,
  `value_text` longtext DEFAULT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fsv_sub` (`tenant_id`,`form_submission_id`),
  KEY `idx_fsv_key` (`field_key_snapshot`),
  KEY `fk_fsv_sub` (`form_submission_id`),
  CONSTRAINT `fk_fsv_sub` FOREIGN KEY (`form_submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fsv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submission_values`
--

LOCK TABLES `form_submission_values` WRITE;
/*!40000 ALTER TABLE `form_submission_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submission_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `funnel_version_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `calculated_value` decimal(14,4) DEFAULT NULL,
  `formatted_quote` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fsub_tenant_date` (`tenant_id`,`funnel_id`,`submitted_at`),
  KEY `fk_fsub_funnel` (`funnel_id`),
  KEY `fk_fsub_session` (`session_id`),
  CONSTRAINT `fk_fsub_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fsub_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fsub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funnel_edges`
--

DROP TABLE IF EXISTS `funnel_edges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `funnel_edges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `source_node_id` bigint(20) unsigned NOT NULL,
  `source_option_id` bigint(20) unsigned DEFAULT NULL,
  `source_handle` varchar(100) DEFAULT NULL,
  `target_node_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fe_source` (`funnel_id`,`source_node_id`),
  KEY `idx_fe_target` (`funnel_id`,`target_node_id`),
  KEY `fk_fe_tenant` (`tenant_id`),
  KEY `fk_fe_source_node` (`source_node_id`),
  KEY `fk_fe_source_opt` (`source_option_id`),
  KEY `fk_fe_target_node` (`target_node_id`),
  CONSTRAINT `fk_fe_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fe_source_node` FOREIGN KEY (`source_node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fe_source_opt` FOREIGN KEY (`source_option_id`) REFERENCES `question_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fe_target_node` FOREIGN KEY (`target_node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fe_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funnel_edges`
--

LOCK TABLES `funnel_edges` WRITE;
/*!40000 ALTER TABLE `funnel_edges` DISABLE KEYS */;
/*!40000 ALTER TABLE `funnel_edges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funnel_nodes`
--

DROP TABLE IF EXISTS `funnel_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `funnel_nodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `node_key` varchar(80) NOT NULL,
  `node_type` enum('start','question','destination','form','video_gate','loader','booking','condition','content') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_start` tinyint(1) NOT NULL DEFAULT 0,
  `position_x` decimal(10,2) NOT NULL DEFAULT 0.00,
  `position_y` decimal(10,2) NOT NULL DEFAULT 0.00,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_funnel_node_key` (`funnel_id`,`node_key`),
  KEY `idx_tenant_funnel_node_type` (`tenant_id`,`funnel_id`,`node_type`),
  CONSTRAINT `fk_fn_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funnel_nodes`
--

LOCK TABLES `funnel_nodes` WRITE;
/*!40000 ALTER TABLE `funnel_nodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `funnel_nodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funnel_versions`
--

DROP TABLE IF EXISTS `funnel_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `funnel_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `graph_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`graph_snapshot_json`)),
  `form_schema_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`form_schema_snapshot_json`)),
  `settings_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_snapshot_json`)),
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `published_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_funnel_version` (`funnel_id`,`version_number`),
  KEY `idx_tenant_funnel_pub` (`tenant_id`,`funnel_id`,`published_at`),
  KEY `fk_fv_user` (`published_by`),
  CONSTRAINT `fk_fv_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fv_user` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funnel_versions`
--

LOCK TABLES `funnel_versions` WRITE;
/*!40000 ALTER TABLE `funnel_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `funnel_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funnels`
--

DROP TABLE IF EXISTS `funnels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `funnels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(180) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `funnel_mode` enum('quiz','calculator') NOT NULL DEFAULT 'quiz',
  `description` text DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `current_published_version_id` bigint(20) unsigned DEFAULT NULL,
  `has_unpublished_changes` tinyint(1) NOT NULL DEFAULT 0,
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_json`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_funnel_slug` (`tenant_id`,`slug`),
  KEY `idx_tenant_status_updated` (`tenant_id`,`status`,`updated_at`),
  KEY `fk_funnels_user` (`created_by`),
  CONSTRAINT `fk_funnels_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_funnels_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funnels`
--

LOCK TABLES `funnels` WRITE;
/*!40000 ALTER TABLE `funnels` DISABLE KEYS */;
/*!40000 ALTER TABLE `funnels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_pages`
--

DROP TABLE IF EXISTS `legal_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `title` varchar(255) NOT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_legal_slug` (`slug`),
  KEY `idx_legal_published` (`is_published`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_pages`
--

LOCK TABLES `legal_pages` WRITE;
/*!40000 ALTER TABLE `legal_pages` DISABLE KEYS */;
INSERT INTO `legal_pages` VALUES
(1,'privacy','Privacy Policy','Learn how FilterFunnel collects, stores, and safeguards your data and user assessment submissions.','<h2>1. Overview & Commitment</h2>\n<p>At FilterFunnel (\"we\", \"our\", or \"us\", accessible via filterfunnel.in), we prioritize the privacy and confidentiality of our customers, website visitors, and end-users who interact with interactive quizzes and calculators created through our software platform. This Privacy Policy details how we collect, store, isolate, and safeguard your data.</p>\n\n<h2>2. Information We Collect</h2>\n<h3>A. Workspace Customer Account Data</h3>\n<p>When you register for a FilterFunnel account, we collect your name, business email address, company name, workspace subdomains, and encrypted password credentials.</p>\n<h3>B. End-User Lead Submission Data</h3>\n<p>When visitors complete an interactive branching quiz, estimate calculator, or lead form hosted via our runner, we collect the responses, answers, calculated quotes, timestamps, and optional contact details provided by the visitor.</p>\n\n<h2>3. Multi-Tenant Data Isolation & Security</h2>\n<p>FilterFunnel is architected with strict multi-tenancy boundaries. Customer workspace data and lead submissions are strictly isolated by unique tenant identifiers. One workspace tenant cannot access, query, or view data belonging to another workspace.</p>\n<p><strong>Encryption Standards:</strong> All data transmitted between your visitors and FilterFunnel is protected via TLS 1.3 / 256-Bit SSL encryption. Sensitive authentication secrets and webhook tokens are hashed using industry-standard algorithms.</p>\n\n<h2>4. How We Use Information</h2>\n<ul>\n  <li>To operate, maintain, and render visual branching funnels and calculations.</li>\n  <li>To deliver lead notifications to your connected integrations (Zapier, Webhooks, WhatsApp).</li>\n  <li>To calculate conversion metrics, drop-off rates, and funnel analytics.</li>\n  <li>We do not sell, rent, or monetize your customer lead data to third parties or advertising networks.</li>\n</ul>\n\n<h2>5. GDPR & Data Subject Rights</h2>\n<p>Under GDPR, CCPA, and applicable global privacy laws, you and your visitors have the right to access, rectify, export, or permanently delete personal data. You can delete lead submissions or workspaces directly from the FilterFunnel dashboard or by emailing privacy@filterfunnel.in.</p>\n\n<h2>6. Contact Our Privacy Officer</h2>\n<p>If you have questions regarding this Privacy Policy or our security practices, contact us at:</p>\n<p>FilterFunnel Data Protection Officer<br>Email: privacy@filterfunnel.in<br>Domain: filterfunnel.in</p>',1,'2026-11-16 17:14:09','2026-11-16 17:14:09'),
(2,'terms','Terms of Service','Review the terms and conditions governing the use of the FilterFunnel quiz funnels platform.','<h2>1. Acceptance of Terms</h2>\n<p>By registering for an account, accessing, or using FilterFunnel (\"the Service\", provided by FilterFunnel Technologies Pvt Ltd / FilterFunnel.in), you agree to be bound by these Terms of Service. If you are entering into this agreement on behalf of a company, organization, or other legal entity, you represent that you possess the authority to bind such entity to these terms.</p>\n\n<h2>2. Account Registration & Tenant Security</h2>\n<p>To use FilterFunnel, you must provide valid credentials during account creation. You are solely responsible for safeguarding the credentials associated with your workspace and all actions taken under your account. You must notify FilterFunnel immediately if you discover any unauthorized use or security compromise.</p>\n\n<h2>3. Acceptable Use Policy</h2>\n<p>You agree not to misuse the platform. Specifically, you agree that you will not use FilterFunnel to:</p>\n<ul>\n  <li>Collect personal credit card numbers, national identification numbers, or HIPAA-restricted patient health data directly into unsecured form input fields without approved compliance add-ons.</li>\n  <li>Conduct phishing campaigns, fraudulent deceptive sweepstakes, or distribute malicious code.</li>\n  <li>Exceed rate limits or attempt to reverse-engineer the underlying API infrastructure and tenant isolation models.</li>\n  <li>Send unsolicited commercial bulk emails (SPAM) via webhook triggers or calendar integrations.</li>\n</ul>\n\n<h2>4. Subscription Billing, Upgrades & Cancellations</h2>\n<p>Paid subscription plans (Starter, Growth, Agency Scale) are billed in advance on a recurring monthly or annual basis via Stripe, Cashfree, PayPal, or supported regional payment processors. Upgrades take effect immediately with prorated billing. Downgrades or cancellations take effect at the end of the current paid billing cycle.</p>\n\n<h2>5. Data Ownership & Customer Intellectual Property</h2>\n<p>You retain all ownership rights, title, and interest in and to all question structures, funnel configurations, branding assets, formulas, and submission data you create or collect using FilterFunnel. FilterFunnel retains all rights, title, and interest in the software platform, runtime engine, visual canvas components, and documentation.</p>\n\n<h2>6. Service Level Agreement & Availability</h2>\n<p>We strive to maintain a 99.9% uptime record for the public quiz runner runtime. Scheduled maintenance windows are communicated in advance via our operational status channels.</p>\n\n<h2>7. Limitation of Liability</h2>\n<p>To the maximum extent permitted by applicable law, in no event shall FilterFunnel, its affiliates, directors, or employees be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or related to your use of the Service.</p>\n\n<h2>8. Governing Law & Contact</h2>\n<p>For questions regarding these Terms of Service, contact our legal team at legal@filterfunnel.in.</p>',1,'2026-11-16 17:14:09','2026-11-16 17:14:09'),
(3,'cookie-policy','Cookie Policy','Understand how FilterFunnel uses cookies, session identifiers, and tracking technologies.','<h2>1. What Are Cookies?</h2>\n<p>Cookies are small data files stored on your device (computer, tablet, or smartphone) when you visit websites or interact with web-based applications. They are widely used to ensure websites function properly, retain user state across multiple steps, and deliver customized user experiences.</p>\n\n<h2>2. The Cookies We Use & Why</h2>\n<ul>\n  <li><strong>Essential & Security Cookies:</strong> Strictly required to maintain secure administrator sessions, protect against Cross-Site Request Forgery (CSRF), and verify authenticated tenant boundaries. These cannot be disabled.</li>\n  <li><strong>Quiz Runner State Cookies:</strong> Stores session identifiers for active quiz respondents, allowing users to navigate between branching questions and retain partial quote calculation without losing their progress.</li>\n  <li><strong>Performance & Analytics Cookies:</strong> Helps us understand how visitors interact with the marketing homepage and funnels to optimize page speed and conversion paths.</li>\n</ul>\n\n<h2>3. Managing Cookie Preferences</h2>\n<p>You can configure your browser settings to reject or delete cookies. However, please note that disabling essential cookies may impact the proper functionality of the FilterFunnel quiz builder and authenticated dashboards.</p>',1,'2026-11-16 17:14:09','2026-11-16 17:14:09'),
(4,'refund-policy','Refund & Cancellation Policy','Review our clear cancellation terms, subscription downgrade rules, and refund policy.','<h2>1. Subscription Billing & Auto-Renewal</h2>\n<p>FilterFunnel subscription plans are billed on a recurring monthly or annual cycle. You may cancel your subscription at any time directly from your Workspace Billing dashboard.</p>\n\n<h2>2. 14-Day Money Back Guarantee for Annual Plans</h2>\n<p>We provide a 14-day money-back guarantee for all newly purchased annual subscription plans. If you are not completely satisfied with FilterFunnel within 14 days of your initial annual upgrade, contact support for a full refund.</p>\n\n<h2>3. Monthly Subscriptions & Cancellations</h2>\n<p>Monthly subscriptions can be canceled at any time prior to the next renewal date. When canceled, your plan will remain active until the end of the paid billing period, and no further charges will occur. Partial month refunds are not issued.</p>\n\n<h2>4. How to Request a Refund</h2>\n<p>To submit a cancellation or refund inquiry, email billing@filterfunnel.in with your workspace name and account email. Our finance team processes eligible refund requests within 3 to 5 business days.</p>',1,'2026-11-16 17:14:09','2026-11-16 17:14:09'),
(5,'about','About FilterFunnel','FilterFunnel transforms static web forms into intelligent, interactive conversations with dynamic quotes and visual quiz funnels.','<h2>1. Our Mission</h2>\n<p>We founded FilterFunnel with a simple conviction: traditional 10-field web forms are broken. Buyers demand immediate value, transparency, and personal recommendations before handing over their contact information. We transform static web forms into intelligent, interactive conversations that convert cold visitors into qualified buyers.</p>\n\n<h2>2. The Problem: Static Forms Bounce 97% of Prospective Buyers</h2>\n<p>When high-intent prospects arrive on your landing pages, presenting them with a wall of empty input boxes feels demanding and transactional. Visitors leave without knowing if your service fits their budget, timeline, or exact requirements. High cost-per-click ad traffic is wasted because forms ask for everything while delivering zero upfront value.</p>\n\n<h2>3. The FilterFunnel Solution: Dynamic Diagnostic Journeys</h2>\n<p>FilterFunnel replaces rigid forms with guided visual question paths. Each answer recalculates dynamic project quotes, qualifies visitor readiness, and routes hot buyers straight to WhatsApp or scheduled consultations. Visitors willingly share details because they receive a transparent instant quote, tailored roadmap, and direct expert access.</p>\n\n<h2>4. Architectural Pillars</h2>\n<ul>\n  <li><strong>Visual Node Canvas:</strong> Built on interactive React Flow graphs with zero-code branching, arithmetic formulas, score tallies, and live step previewing.</li>\n  <li><strong>Strict Multi-Tenancy:</strong> Complete data isolation across tenants with encrypted API credentials, granular role-based team management, and GDPR compliance.</li>\n  <li><strong>Immediate Sales Handoff:</strong> Convert prospects within 60 seconds with 1-click WhatsApp deep links, Calendly appointments, and automated webhooks.</li>\n</ul>\n\n<h2>5. Contact & Support</h2>\n<p>Have questions or need enterprise assistance? Contact our team at <a href=\"mailto:contact@filterfunnel.in\">contact@filterfunnel.in</a> or visit our <a href=\"/support\">Help Center</a>.</p>\n<p><strong>[Live Sync Verified]</strong> This text is rendered directly from Super Admin CMS in real time.</p>',1,'2026-11-16 17:14:09','2026-11-16 17:31:32'),
(6,'affiliate-partner-terms','Affiliate Partner Terms','','',1,'2026-11-16 17:19:57','2026-11-16 17:19:57');
/*!40000 ALTER TABLE `legal_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'001_initial_schema.sql','2026-11-01 01:09:25'),
(2,'002_superadmin_expansion.sql','2026-11-01 01:09:25'),
(3,'003_legal_pages.sql','2026-11-01 01:09:25'),
(4,'003_webhooks_system.sql','2026-11-01 01:09:25'),
(5,'004_calculator_mode.sql','2026-11-01 01:09:25'),
(6,'005_unpublished_changes_indicator.sql','2026-11-01 01:09:25'),
(7,'006_billing_cycles_and_suspension.sql','2026-11-01 01:09:25'),
(8,'007_agency_lead_management.sql','2026-11-01 01:09:25'),
(9,'008_custom_lead_stages.sql','2026-11-01 01:09:25'),
(10,'009_plan_frontend_visibility.sql','2026-11-01 01:09:25'),
(11,'010_video_gate_node.sql','2026-11-01 01:09:26'),
(12,'011_conditional_router_node.sql','2026-11-14 13:31:15'),
(13,'012_add_content_node_type.sql','2026-11-14 13:31:15'),
(14,'013_email_smtp_verification_and_templates.sql','2026-11-14 13:33:22'),
(15,'014_workspace_logo_and_user_avatar.sql','2026-11-15 07:30:00'),
(16,'015_support_tickets_and_channels.sql','2026-11-16 17:40:00'),
(17,'016_login_rate_limiting.sql','2026-11-18 01:25:00');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_interval` varchar(20) NOT NULL DEFAULT 'monthly',
  `description` text DEFAULT NULL,
  `limits_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`limits_json`)),
  `features_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES
(1,'Starter','starter',749.00,'monthly','Ideal for emerging creators and growing lead funnels','{\"max_funnels\":5,\"max_submissions_per_month\":1000,\"max_team_members\":3}','{\"white_label\":false,\"calculator_mode\":false,\"webhooks\":false,\"csv_export\":true,\"api_access\":false,\"funnel_cloning\":true}',1,1,'2026-11-01 01:09:25','2026-11-04 16:35:56'),
(2,'Pro','pro',1499.00,'monthly','Unlimited funnels and submissions for high-velocity teams','{\"max_funnels\":-1,\"max_submissions_per_month\":-1,\"max_team_members\":-1}','{\"white_label\":true,\"calculator_mode\":true,\"webhooks\":true,\"csv_export\":true,\"api_access\":true,\"funnel_cloning\":true}',1,1,'2026-11-01 01:09:25','2026-11-04 16:35:56'),
(3,'Free','free',0.00,'monthly','Essential single-funnel plan for testing the platform','{\"max_funnels\":1,\"max_submissions_per_month\":100,\"max_team_members\":1}','{\"white_label\":false,\"calculator_mode\":false,\"webhooks\":false,\"csv_export\":false,\"api_access\":false,\"funnel_cloning\":false}',1,1,'2026-11-01 01:09:25','2026-11-04 18:17:33'),
(4,'Standard','standard',999.00,'monthly','Robust multi-funnel plan for scaling marketing operations','{\"max_funnels\":10,\"max_submissions_per_month\":5000,\"max_team_members\":10}','{\"white_label\":false,\"calculator_mode\":true,\"webhooks\":true,\"csv_export\":true,\"api_access\":false,\"funnel_cloning\":true}',1,1,'2026-11-01 01:09:25','2026-11-04 16:35:56');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES
('cron_secret_key','lc_cron_96a5028e0c0b7c46','2026-11-15 07:58:33'),
('last_billing_evaluation_at','2026-11-16 11:35:53','2026-11-16 17:05:53'),
('last_billing_evaluation_result','{\"suspended_count\":0,\"flagged_due_count\":0,\"renewal_reminders_sent\":0,\"evaluated_at\":\"2026-11-16 11:35:53\"}','2026-11-16 17:05:53'),
('payment_gateways','{\"stripe\":{\"enabled\":true,\"mode\":\"test\",\"publishable_key\":\"pk_test_123\",\"secret_key\":\"sk_test_123\"},\"paypal\":{\"enabled\":false,\"mode\":\"sandbox\"},\"razorpay\":{\"enabled\":false},\"cashfree\":{\"enabled\":true,\"mode\":\"sandbox\",\"app_id\":\"CF_APP_TEST_123\",\"secret_key\":\"CF_SEC_TEST_123\",\"api_version\":\"2023-08-01\"},\"bank_transfer\":{\"enabled\":true,\"bank_name\":\"Demo Bank\",\"account_number\":\"987654321\",\"instructions\":\"Send wire ref QF123\"},\"manual_custom\":{\"enabled\":true,\"title\":\"Offline Cheque\",\"instructions\":\"Mail cheque to HQ\",\"qr_code_url\":\"\\/uploads\\/gateways\\/demo_qr.png\"}}','2026-11-16 18:00:55'),
('smtp_settings','{\"enabled\":false,\"host\":\"smtp.mailtrap.io\",\"port\":2525,\"encryption\":\"tls\",\"username\":\"test_user_mailtrap\",\"password\":\"secret_pass_123\",\"from_email\":\"mailer@qfunnel-platform.test\",\"from_name\":\"QFunnel Notifications\",\"reply_to\":\"help@qfunnel-platform.test\"}','2026-11-16 18:00:56'),
('support_channels','{\"email\":\"support@filterfunnel.in\",\"phone\":\"+1 (800) 555-0199\",\"whatsapp\":\"+1 (800) 555-0199\",\"hours\":\"Monday \\u2013 Friday, 8:00 AM \\u2013 8:00 PM EST\",\"description\":\"Direct account manager support for Agency Scale plans\"}','2026-11-16 17:43:38');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `option_key` varchar(80) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_question_option_key` (`question_id`,`option_key`),
  KEY `idx_tenant_q_order` (`tenant_id`,`question_id`,`display_order`),
  CONSTRAINT `fk_qo_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qo_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_options`
--

LOCK TABLES `question_options` WRITE;
/*!40000 ALTER TABLE `question_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `node_id` bigint(20) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `help_text` text DEFAULT NULL,
  `question_type` enum('single_choice','multiple_choice','text','email','number','dropdown') NOT NULL DEFAULT 'single_choice',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_json`)),
  `display_order` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_node_question` (`node_id`),
  KEY `fk_q_tenant` (`tenant_id`),
  KEY `fk_q_funnel` (`funnel_id`),
  CONSTRAINT `fk_q_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_q_node` FOREIGN KEY (`node_id`) REFERENCES `funnel_nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_q_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_answers`
--

DROP TABLE IF EXISTS `quiz_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `funnel_version_id` bigint(20) unsigned NOT NULL,
  `node_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned DEFAULT NULL,
  `question_text_snapshot` text NOT NULL,
  `answer_type` varchar(40) NOT NULL,
  `answer_value_text` longtext DEFAULT NULL,
  `answer_value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_value_json`)),
  `selected_option_ids_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_option_ids_json`)),
  `visit_number` int(10) unsigned NOT NULL DEFAULT 1,
  `answered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `superseded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qa_tenant_session` (`tenant_id`,`session_id`,`answered_at`),
  KEY `idx_qa_node_visit` (`session_id`,`node_id`,`visit_number`),
  CONSTRAINT `fk_qa_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_answers`
--

LOCK TABLES `quiz_answers` WRITE;
/*!40000 ALTER TABLE `quiz_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_results`
--

DROP TABLE IF EXISTS `quiz_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `funnel_version_id` bigint(20) unsigned NOT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `result_token_hash` char(64) DEFAULT NULL,
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`summary_json`)),
  `destination_id` bigint(20) unsigned DEFAULT NULL,
  `redirect_status` enum('pending','attempted','failed','confirmed') NOT NULL DEFAULT 'pending',
  `calculated_value` decimal(14,4) DEFAULT NULL,
  `formatted_quote` varchar(255) DEFAULT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qr_session` (`session_id`),
  UNIQUE KEY `result_token_hash` (`result_token_hash`),
  KEY `fk_qr_tenant` (`tenant_id`),
  CONSTRAINT `fk_qr_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_results`
--

LOCK TABLES `quiz_results` WRITE;
/*!40000 ALTER TABLE `quiz_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_sessions`
--

DROP TABLE IF EXISTS `quiz_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `funnel_version_id` bigint(20) unsigned NOT NULL,
  `public_token_hash` char(64) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'form_pending',
  `current_node_id` bigint(20) unsigned DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_activity_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `final_destination_id` bigint(20) unsigned DEFAULT NULL,
  `calculated_value` decimal(14,4) DEFAULT NULL,
  `formatted_quote` varchar(255) DEFAULT NULL,
  `lead_status` varchar(50) NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_token_hash` (`public_token_hash`),
  KEY `idx_qs_tenant_funnel_status` (`tenant_id`,`funnel_id`,`status`,`created_at`),
  KEY `idx_qs_version_status` (`funnel_id`,`funnel_version_id`,`status`),
  KEY `idx_qs_expires` (`expires_at`),
  KEY `fk_qs_version` (`funnel_version_id`),
  KEY `idx_tenant_lead_status` (`tenant_id`,`lead_status`),
  CONSTRAINT `fk_qs_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qs_version` FOREIGN KEY (`funnel_version_id`) REFERENCES `funnel_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_sessions`
--

LOCK TABLES `quiz_sessions` WRITE;
/*!40000 ALTER TABLE `quiz_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_step_events`
--

DROP TABLE IF EXISTS `quiz_step_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_step_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned NOT NULL,
  `funnel_version_id` bigint(20) unsigned NOT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `node_id` bigint(20) unsigned DEFAULT NULL,
  `option_id` bigint(20) unsigned DEFAULT NULL,
  `event_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data_json`)),
  `occurred_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_qse_session` (`tenant_id`,`session_id`,`occurred_at`),
  KEY `idx_qse_funnel_event` (`tenant_id`,`funnel_id`,`event_type`,`occurred_at`),
  KEY `fk_qse_session` (`session_id`),
  CONSTRAINT `fk_qse_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qse_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_step_events`
--

LOCK TABLES `quiz_step_events` WRITE;
/*!40000 ALTER TABLE `quiz_step_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_step_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_num` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'builder',
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_num` (`ticket_num`),
  KEY `idx_ticket_status` (`status`),
  KEY `idx_ticket_email` (`email`),
  KEY `idx_ticket_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_invoices`
--

DROP TABLE IF EXISTS `tenant_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `status` enum('paid','pending','past_due','void','refunded') NOT NULL DEFAULT 'pending',
  `billing_interval` varchar(30) NOT NULL DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `payment_method` varchar(60) NOT NULL DEFAULT 'manual_admin',
  `transaction_reference` varchar(190) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_tenant_invoices` (`tenant_id`,`status`),
  KEY `idx_inv_created` (`created_at`),
  CONSTRAINT `fk_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_invoices`
--

LOCK TABLES `tenant_invoices` WRITE;
/*!40000 ALTER TABLE `tenant_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenant_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_lead_stages`
--

DROP TABLE IF EXISTS `tenant_lead_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_lead_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#6366F1',
  `order_index` int(11) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_stage_slug` (`tenant_id`,`slug`),
  KEY `idx_tenant_stage_order` (`tenant_id`,`order_index`),
  CONSTRAINT `fk_tenant_lead_stages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_lead_stages`
--

LOCK TABLES `tenant_lead_stages` WRITE;
/*!40000 ALTER TABLE `tenant_lead_stages` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenant_lead_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_memberships`
--

DROP TABLE IF EXISTS `tenant_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('owner','admin','editor','analyst') NOT NULL DEFAULT 'owner',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_user` (`tenant_id`,`user_id`),
  KEY `idx_user_tenant` (`user_id`,`tenant_id`),
  CONSTRAINT `fk_tm_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_memberships`
--

LOCK TABLES `tenant_memberships` WRITE;
/*!40000 ALTER TABLE `tenant_memberships` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenant_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_subscriptions`
--

DROP TABLE IF EXISTS `tenant_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `status` enum('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
  `billing_interval` varchar(30) NOT NULL DEFAULT 'monthly',
  `starts_at` datetime DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `grace_period_days` int(10) unsigned NOT NULL DEFAULT 7,
  `next_billing_date` datetime DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `provider` varchar(80) DEFAULT NULL,
  `provider_subscription_id` varchar(190) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_sub_status` (`tenant_id`,`status`),
  KEY `fk_ts_plan` (`plan_id`),
  CONSTRAINT `fk_ts_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_subscriptions`
--

LOCK TABLES `tenant_subscriptions` WRITE;
/*!40000 ALTER TABLE `tenant_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenant_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','terminated','deleted') NOT NULL DEFAULT 'active',
  `suspension_reason` varchar(255) DEFAULT NULL,
  `suspension_notes` text DEFAULT NULL,
  `suspended_at` datetime DEFAULT NULL,
  `is_termination_due` tinyint(1) NOT NULL DEFAULT 0,
  `termination_due_at` datetime DEFAULT NULL,
  `terminated_at` datetime DEFAULT NULL,
  `terminated_by` bigint(20) unsigned DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usage_counters`
--

DROP TABLE IF EXISTS `usage_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usage_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `metric_key` varchar(80) NOT NULL,
  `period_start` date NOT NULL,
  `count_value` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uc_metric_period` (`tenant_id`,`metric_key`,`period_start`),
  CONSTRAINT `fk_uc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage_counters`
--

LOCK TABLES `usage_counters` WRITE;
/*!40000 ALTER TABLE `usage_counters` DISABLE KEYS */;
/*!40000 ALTER TABLE `usage_counters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `email` varchar(190) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `is_platform_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','disabled','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=310 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'System Administrator','superadmin@qfunnel.local','/uploads/avatars/usr_1_c35f1d6610bd1fc3.png','$2y$12$pzG21tjWQluThKz8jAlEueuuThUXrpQYtHci1I0ZAno1Dz9HTPNmy','2026-11-14 13:33:21',1,'active','2026-11-16 18:07:10','2026-11-01 01:09:46','2026-11-16 18:07:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_deliveries`
--

DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `webhook_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `payload_json` longtext NOT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending',
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wd_tenant_hook` (`tenant_id`,`webhook_id`,`created_at`),
  KEY `fk_wd_hook` (`webhook_id`),
  CONSTRAINT `fk_wd_hook` FOREIGN KEY (`webhook_id`) REFERENCES `webhook_endpoints` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wd_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_deliveries`
--

LOCK TABLES `webhook_deliveries` WRITE;
/*!40000 ALTER TABLE `webhook_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_endpoints`
--

DROP TABLE IF EXISTS `webhook_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_endpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `funnel_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Null means applies to all funnels in workspace',
  `name` varchar(160) NOT NULL,
  `target_url` text NOT NULL,
  `secret` varchar(64) DEFAULT NULL COMMENT 'HMAC secret for signing payloads',
  `event_type` varchar(60) NOT NULL DEFAULT 'lead.captured' COMMENT 'lead.captured, quiz.completed, etc.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_we_tenant_funnel` (`tenant_id`,`funnel_id`,`is_active`),
  KEY `fk_we_funnel` (`funnel_id`),
  CONSTRAINT `fk_we_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_we_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_endpoints`
--

LOCK TABLES `webhook_endpoints` WRITE;
/*!40000 ALTER TABLE `webhook_endpoints` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_endpoints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'qfunnel2_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-16 18:21:14

SET FOREIGN_KEY_CHECKS = 1;

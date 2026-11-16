# FilterFunnel SaaS — Production Deployment Package

This directory contains the production-ready build and deployment package for deploying FilterFunnel to any cPanel hosting or Linux VPS.

---

## Package Overview

| File / Folder | Description |
|---|---|
| `production/qfunnel_production.zip` | Complete production application archive (Clean PHP backend + compiled React 18 SPA + static assets). Excludes dev dependencies, tests, and version control files. |
| `production database/qfunnel_production.sql` | Clean production MySQL database dump containing tables, subscription plans, system email templates, legal CMS pages, and the single Super Admin account. |
| `production database/README.md` | Dedicated step-by-step database import instructions. |

---

## Default Super Admin Login Credentials

After importing the database, use these credentials to access the master platform console:
- **Role:** Platform Super Administrator (`is_platform_admin = 1`)
- **Email:** `superadmin@qfunnel.local`
- **Password:** `Password123!`
- **Login URL:** `https://yourdomain.com/login` (automatically redirects to `/admin/platform`)

> **Note:** Immediately after logging in, please change the email address and password from the Super Admin Console profile.

---

## 4-Step cPanel Deployment Guide

### Step 1: Upload and Extract Application Files
1. Log in to your **cPanel** dashboard.
2. Open **File Manager** and navigate to your web root (`public_html` or your subdomain folder, e.g., `app.yourdomain.com`).
3. Click **Upload** and upload `production/qfunnel_production.zip`.
4. Right-click `qfunnel_production.zip` in File Manager and select **Extract**.
5. Ensure file permissions:
   - Folders: `755`
   - Files: `644`
   - `storage/` and `public/uploads/`: `775` (writable by web server)

---

### Step 2: Create Database & Import SQL
1. In cPanel, go to **MySQL® Database Wizard**:
   - Create a database: `youruser_qfunnel`
   - Create a user: `youruser_dbuser` with a strong password
   - Grant **ALL PRIVILEGES** to the user.
2. In cPanel, open **phpMyAdmin**:
   - Select `youruser_qfunnel` on the left.
   - Click the **Import** tab.
   - Choose `production database/qfunnel_production.sql` and click **Import**.

---

### Step 3: Configure `.env` File
1. In cPanel File Manager, make sure **Show Hidden Files (dotfiles)** is checked in Settings.
2. Locate `.env.production`, rename it to `.env` (or copy its content to a new `.env` file).
3. Update the database credentials and domain:
   ```env
   APP_NAME="FilterFunnel"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   APP_DOMAIN=yourdomain.com

   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=youruser_qfunnel
   DB_USERNAME=youruser_dbuser
   DB_PASSWORD=YourChosenDatabasePassword

   SESSION_LIFETIME=7200
   SESSION_SECURE=true
   SESSION_SAME_SITE=Lax
   ```

---

### Step 4: Configure Daily Automated Cron Job
In cPanel, open **Cron Jobs** and create a daily cron job (runs every day at midnight):
```bash
0 0 * * * php -q /home/yourcpaneluser/public_html/database/billing_cron.php >/dev/null 2>&1
```
*(Replace `/home/yourcpaneluser/public_html` with your actual cPanel home directory path).*

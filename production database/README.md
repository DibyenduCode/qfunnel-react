# Production Database Setup Guide

This folder contains the clean, production-ready SQL dump file for cPanel deployment.

## Files
- `qfunnel_production.sql`: Complete MySQL schema, table definitions, default system plans, email templates, legal CMS pages, and the single Super Administrator account. All test funnels, test submissions, and dummy accounts have been completely purged.

---

## Pre-configured Super Admin Access
Deploying this database provides immediate master access to the platform console:
- **Role:** Platform Super Administrator (`is_platform_admin = 1`)
- **Email:** `superadmin@qfunnel.local`
- **Password:** `Password123!`
- **Access Route:** `https://yourdomain.com/login` (automatically redirects to `/admin/platform`)

> **Security Recommendation:** Immediately after logging in on your live domain, navigate to the Platform Administration Console or profile settings to update this email address and password to your private credentials.

---

## How to Import into cPanel

### Method 1: Via cPanel phpMyAdmin (Easiest)
1. Log in to your **cPanel** dashboard.
2. Under the **Databases** section, click on **MySQL® Database Wizard**:
   - Create a new database (e.g., `username_qfunnel`).
   - Create a database user (e.g., `username_qfuser`) with a strong password.
   - Assign **ALL PRIVILEGES** to the user for this database.
3. Open **phpMyAdmin** from cPanel:
   - Select your newly created database on the left navigation panel.
   - Click on the **Import** tab at the top.
   - Click **Choose File** and select `qfunnel_production.sql`.
   - Ensure the encoding is set to `utf-8` and click **Import** (or **Go**) at the bottom.
4. Update your `.env` file on cPanel with the database credentials:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=username_qfunnel
   DB_USERNAME=username_qfuser
   DB_PASSWORD=YourChosenPassword
   ```

### Method 2: Via cPanel Terminal / SSH
```bash
mysql -u username_qfuser -p username_qfunnel < qfunnel_production.sql
```

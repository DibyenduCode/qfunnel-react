# DEPLOYMENT.md — Local Setup & Production Deployment Guide

This guide covers setting up and running **Quiz Funnel SaaS** on a local XAMPP environment on Windows, as well as hardening for a production live deployment.

---

## 1. Local Environment Setup (XAMPP on Windows)

### Prerequisites
- **XAMPP** installed (typically at `C:\xampp`)
- **PHP 8.2+** with extensions enabled: `pdo_mysql`, `mbstring`, `json`, `openssl`, `session`, `curl`
- **Apache** web server with `mod_rewrite` enabled (enabled by default in XAMPP)
- **MySQL / MariaDB** service running on `127.0.0.1:3306`

### Step 1: Project Placement
Ensure the project is located in your XAMPP web root:
```text
C:\xampp\htdocs\qfunnel\
```

### Step 2: Environment Configuration
Copy `.env.example` to `.env`:
```powershell
cd C:\xampp\htdocs\qfunnel
copy .env.example .env
```

Verify your database credentials in `.env`:
```ini
APP_NAME="Quiz Funnel SaaS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/qfunnel

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qfunnel_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 3: Run Database Migrations
Run the automated migration runner:
```powershell
php database/migrate.php
```
*This automatically checks/creates `qfunnel_db` with `utf8mb4` charset and executes all versioned SQL migrations.*

### Step 4: Run Development Seeds
Seed initial subscription plans, platform administrator, demo tenant owner, team editor, and the Doctor Referral branching funnel:
```powershell
php database/seed.php
```

### Step 5: Access the Application
Open your web browser:

| Description | Local URL |
|---|---|
| **Sign In** | `http://localhost/qfunnel/login` |
| **Workspace Dashboard** | `http://localhost/qfunnel/admin/dashboard` |
| **Visual Builder** | `http://localhost/qfunnel/admin/funnels/1/builder` |
| **Pre-Seeded Doctor Quiz** | `http://localhost/qfunnel/quiz/doctor-referral` |
| **Platform Super Admin** | `http://localhost/qfunnel/admin/platform` |

#### Default Test Accounts:
- **Tenant Owner:** `owner@qfunnel.local` / `Password123!`
- **Platform Admin:** `superadmin@qfunnel.local` / `Password123!`
- **Team Editor:** `editor@qfunnel.local` / `Password123!`

---

## 2. Directory Access Protection in XAMPP

Because XAMPP by default serves from `C:\xampp\htdocs`, the application includes:
1. **Root `.htaccess`**:
   - Automatically intercepts requests and rewrites to `public/index.php`.
   - Explicitly blocks web access to sensitive directories and files (`.env`, `app/`, `config/`, `database/`, `storage/`, `docs/`, `tests/`) returning HTTP 403 Forbidden.
2. **`public/.htaccess`**:
   - Handles front-controller routing into `index.php`.
3. **Smart Base Path Resolver**:
   - Works seamlessly whether accessed via `http://localhost/qfunnel/` or a dedicated VirtualHost.

---

## 3. Production Deployment Hardening

Before deploying to a live production server (Ubuntu/Debian with Nginx or Apache):

### 1. Document Root Configuration
Point your web server's `DocumentRoot` strictly to the `public/` directory:
```apache
<VirtualHost *:80>
    ServerName app.quizfunnel.com
    DocumentRoot /var/www/qfunnel/public

    <Directory /var/www/qfunnel/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 2. HTTPS & SSL/TLS
- Enforce HTTPS via Let's Encrypt / Certbot.
- Set `SESSION_SECURE=true` in `.env` to enforce `Secure` cookie attributes.
- Use HSTS headers.

### 3. Environment Variables
- Set `APP_ENV=production`
- Set `APP_DEBUG=false` (ensures zero stack traces or internals are exposed to users)
- Generate a unique 32+ character `APP_KEY`.

### 4. Database Security & Backups
- Create a dedicated MySQL user with permissions restricted to `qfunnel_db`.
- Establish automated daily database backups:
  ```bash
  mysqldump -u qfunnel_user -p qfunnel_db | gzip > /backups/qfunnel_$(date +\%F).sql.gz
  ```
- Test restore procedure:
  ```bash
  gunzip < /backups/qfunnel_2026-10-15.sql.gz | mysql -u qfunnel_user -p qfunnel_db
  ```

# Quiz Funnel Builder SaaS

A complete, production-ready, multi-tenant SaaS application featuring a **Decoupled React 18+ SPA** powered by **Vite** and **React Flow** (`@xyflow/react`), with a **Headless Core PHP 8.2+ REST API** and **MySQL 8+**.

---

## ⚡ React Frontend & Development

The entire frontend is decoupled into an ultra-fast **React SPA** located in `frontend/`.

### Run React in Local Development (Hot Module Replacement)
```bash
cd frontend
npm run dev
```
Open **`http://localhost:5173`** in your browser. All API requests (`/api/*`) are automatically proxied to the PHP backend (`http://localhost/qfunnel2`).

### Build for Production (cPanel / Apache)
```bash
cd frontend
# For standard root domain or subdomain on cPanel:
npm run build:cpanel

# Or for local XAMPP subdirectory:
npm run build
```
This compiles optimized static bundles to `public/dist/` (`index.html`, JS, and CSS chunks).

---

## 🌟 Key Features

1. **Modern React Flow Visual Branching Builder:**
   - Powered by `@xyflow/react` v12 with fluid zooming, panning, minimap, auto-snapping, and bezier wires.
   - Node Types: **Start Node**, **Lead Form Node**, **Question Node** (with dedicated option branching ports), **Video Gate Node** (YouTube/Vimeo watch percentage gate), and **Destination Node** (instant quote calculation & 1-Click WhatsApp Direct Connect).

2. **React Public Quiz Runner & Embeddable Widget:**
   - Single-page visitor experience (`/quiz/:slug`) with step history, dynamic form inputs, and white-label branding.
   - Embeddable in 3 modes: Inline iFrame, Popup Modal, and Floating Widget.

3. **Leads CRM & Journey Inspector:**
   - Manage captured leads with custom pipeline stages (`new`, `contacted`, `qualified`, `closed_won`, `closed_lost`).
   - Step-by-step visitor journey timeline inspection and 1-Click WhatsApp messaging.
   - CSV export and bulk actions.

4. **Multi-Tenancy & Platform Super Admin:**
   - Isolated customer workspaces with role-based access (`owner`, `admin`, `editor`, `analyst`).
   - Platform super admin portal for managing customers, plan builder, payment gateways (Stripe, PayPal, Razorpay), and audit trail.

---

## 🚀 Local Development on XAMPP (Windows)

### Prerequisites
- **XAMPP** running Apache and MySQL (`127.0.0.1:3306`).
- **PHP 8.2+** with `pdo_mysql`, `mbstring`, `json`, `openssl`, `session`.

### 1. Database Setup & Migration
Run the automated migration runner:
```powershell
cd C:\xampp\htdocs\qfunnel
php database/migrate.php
```

### 2. Seed Development Data
Seed initial plans, administrator, demo workspace, and the Medical/Doctor branching funnel:
```powershell
php database/seed.php
```

### 3. Run Automated Tests
Execute the automated test suite verifying multi-tenancy, graph validation, and branching logic:
```powershell
php tests/run_tests.php
```

---

## 🌐 Application URLs

| Screen | URL |
|---|---|
| **Sign In** | `http://localhost/qfunnel/login` |
| **Workspace Dashboard** | `http://localhost/qfunnel/admin/dashboard` |
| **Funnels List** | `http://localhost/qfunnel/admin/funnels` |
| **Visual Builder** | `http://localhost/qfunnel/admin/funnels/1/builder` |
| **Funnel Settings & Form** | `http://localhost/qfunnel/admin/funnels/1/settings` |
| **Submissions & Leads** | `http://localhost/qfunnel/admin/submissions` |
| **Journey Inspector** | `http://localhost/qfunnel/admin/submissions/1` |
| **Analytics** | `http://localhost/qfunnel/admin/analytics` |
| **Workspace Settings** | `http://localhost/qfunnel/admin/settings/workspace` |
| **Platform Super Admin** | `http://localhost/qfunnel/admin/platform` |
| **Public Quiz Experience** | `http://localhost/qfunnel/quiz/doctor-referral` |

### Default Credentials
- **Tenant Owner:** `owner@qfunnel.local` / `Password123!`
- **Super Administrator:** `superadmin@qfunnel.local` / `Password123!`
- **Team Editor:** `editor@qfunnel.local` / `Password123!`

---

## 📁 Architecture Overview

```text
qfunnel/
├── app/
│   ├── Controllers/     # MVC Controllers (Auth, Dashboard, Funnel, Builder, Quiz, etc.)
│   ├── Core/            # Framework Foundation (Router, Database, Session, Request, View, Logger)
│   ├── Middleware/      # Auth, TenantContext, PlatformAdmin, CSRF
│   ├── Services/        # Business Logic (QuizEngine, FunnelGraph, Publish, FormSchema, Analytics)
│   └── Views/           # HTML5 + Tailwind CSS templates & layouts
├── config/              # App and database configuration
├── database/
│   ├── migrations/      # Versioned MySQL migrations
│   ├── seeders/         # Development seeders
│   ├── migrate.php      # Migration execution script
│   └── seed.php         # Seeding execution script
├── docs/                # Architecture, design specs, security & deployment guides
├── public/              # Web-accessible document root
│   ├── assets/          # CSS, JS, SVG assets
│   ├── index.php        # Application entrypoint
│   └── .htaccess        # Front-controller rewrite rules
├── routes/              # Web and API routing definitions
├── storage/             # Application logs & cache
├── tests/               # Automated test suites
├── .env.example         # Environment template
└── README.md
```

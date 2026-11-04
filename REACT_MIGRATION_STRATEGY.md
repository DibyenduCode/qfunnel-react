# React Frontend Migration Strategy — Quiz Funnel SaaS
**Complete Blueprint for Migrating from Server-Rendered Core PHP MVC to a Headless React SPA**

---

## Table of Contents
1. [Executive Summary & Architectural Target](#1-executive-summary--architectural-target)
2. [Target Technology Stack](#2-target-technology-stack)
3. [Architecture: Current vs. Target](#3-architecture-current-vs-target)
4. [Backend API Transformation (PHP Headless REST)](#4-backend-api-transformation-php-headless-rest)
5. [Frontend React Directory & Module Architecture](#5-frontend-react-directory--module-architecture)
6. [Visual Graph Builder Migration (React Flow Engine)](#6-visual-graph-builder-migration-react-flow-engine)
7. [Public Quiz Runner & Embeddable Widget](#7-public-quiz-runner--embeddable-widget)
8. [Module-by-Module Screen Inventory & React Mapping](#8-module-by-module-screen-inventory--react-mapping)
9. [State Management, Routing & Data Fetching](#9-state-management-routing--data-fetching)
10. [Phased Implementation Roadmap](#10-phased-implementation-roadmap)
11. [cPanel Production Build & Deployment Strategy](#11-cpanel-production-build--deployment-strategy)
12. [Verification, Quality Assurance & Rollback Plan](#12-verification-quality-assurance--rollback-plan)

---

## 1. Executive Summary & Architectural Target

### Goal
Decouple the existing server-rendered Core PHP MVC presentation layer (`app/Views/*` and monolithic Vanilla JavaScript scripts) and transition to a modern, decoupled **React Single Page Application (SPA)** powered by **Vite** and **React Flow**. The Core PHP 8.2 backend will transition into a high-performance **Headless REST API** retaining its MySQL PDO business services, transactions, multi-tenancy enforcement, and security policies.

### Core Value Proposition
- **Superior Builder UX:** Replace 4,600+ lines of custom SVG DOM canvas math in `builder.js` with declarative, reactive nodes, native zooming, panning, minimaps, and bezier auto-routing via **React Flow** (`@xyflow/react`).
- **Instant Client-Side Navigation:** Eliminates full-page reloads across workspace switching, CRM stage updates, funnel creation, and analytics filtering.
- **Micro-Bundle Quiz Embeds:** Public quiz runner can be compiled both as an SPA route and as a lightweight standalone web component / iframe widget for external websites.
- **cPanel Compatible:** Builds into pure static assets (`index.html`, JS, CSS) requiring **zero Node.js daemons in production**.

---

## 2. Target Technology Stack

### 2.1 Frontend (React Ecosystem)
- **Framework / Runtime:** React 18+ (Functional Components, Hooks)
- **Language:** TypeScript (Strict type safety across API contracts, funnel nodes, and schemas)
- **Build Tool & Dev Server:** Vite 5+ (Lightning-fast HMR and optimized production asset chunking)
- **Routing:** React Router v6 / v7 (Data routers, layout routes, protected guard wrappers)
- **Visual Node Builder:** `@xyflow/react` (React Flow v12)
- **Styling & Design System:** Tailwind CSS v3 / v4 + Lucide React (Clean, non-bloated, matching current brand aesthetics)
- **UI Primitives:** Radix UI / `@radix-ui/react-*` (Accessible modals, dropdowns, popovers, tooltips, tabs)
- **State & Data Fetching:** 
  - **Server Cache:** TanStack Query v5 (`@tanstack/react-query`) with automatic background refetching and mutation rollbacks
  - **Client / Builder State:** Zustand (Lightweight canvas viewport, node selection, dirty flag, undo/redo)
- **Form Management:** React Hook Form + Zod (Strict schema validation for dynamic lead forms and builder settings)
- **Data Visualization:** Chart.js + `react-chartjs-2` (Dashboard KPI trends, funnel drop-off funnels)
- **HTTP Client:** Axios or native `Fetch` wrapper configured with credentials and interceptors

### 2.2 Backend (Existing Core PHP)
- **Runtime:** PHP 8.2+
- **Architecture:** Headless JSON REST API
- **Persistence:** MySQL 8+ / MariaDB via PDO (with existing 27 tables)
- **Security:** Session-based auth cookies or Bearer API tokens, CSRF validation, tenant-scoped queries

---

## 3. Architecture: Current vs. Target

```
================================ CURRENT ARCHITECTURE ================================
Browser Request  ──>  Apache (.htaccess)  ──>  public/index.php
                                                     │
                                            Router / Middleware
                                                     │
                                            Controllers (Web)
                                                     │
                                            app/Views/*.php (HTML + CDN Tailwind)
                                            + Monolithic builder.js (~4600 lines)

================================ TARGET ARCHITECTURE ================================
DEVELOPMENT:
React Dev Server (localhost:5173) ──[ CORS / Proxy ]──> PHP API (localhost/qfunnel2/api)

PRODUCTION (cPanel / Apache):
Browser Request ──> Apache (.htaccess)
                         │
        ┌────────────────┴────────────────┐
   URI starts with /api               All other URIs
        │                                 │
   public/index.php                public/dist/index.html (React SPA)
   (Headless PHP REST API)         (Pre-compiled static React assets)
```

---

## 4. Backend API Transformation (PHP Headless REST)

To serve a pure React frontend, the existing PHP backend needs two primary enhancements: **CORS / Pre-flight handling** and a **complete set of JSON API endpoints**.

### 4.1 CORS & Authentication Middleware
Create `app/Middleware/CorsMiddleware.php` to handle local development cross-origin requests:
```php
// Handles OPTIONS pre-flight and injects headers:
header("Access-Control-Allow-Origin: " . Env::get('FRONTEND_URL', 'http://localhost:5173'));
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
```

### 4.2 Complete REST API Surface Matrix

| Module | HTTP Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Auth** | `POST` | `/api/auth/login` | Email/password verification; initializes PHP session. Returns user & active tenant info. |
| **Auth** | `POST` | `/api/auth/register` | Creates user, default tenant workspace, and initial Free plan subscription. |
| **Auth** | `GET` | `/api/auth/me` | Returns authenticated user session, tenant memberships, and current role. |
| **Auth** | `POST` | `/api/auth/switch-workspace` | Switches active session `tenant_id`. |
| **Auth** | `POST` | `/api/auth/logout` | Destroys session and clears cookie. |
| **Funnels** | `GET` | `/api/funnels` | Tenant funnels list with pagination, search, status filters, and response stats. |
| **Funnels** | `POST` | `/api/funnels` | Creates a new draft funnel. |
| **Funnels** | `GET` | `/api/funnels/{id}` | Gets funnel details, settings, and unpublished change status. |
| **Funnels** | `PUT` | `/api/funnels/{id}/settings` | Updates branding, logo, accent colors, trust badges, and custom domains. |
| **Funnels** | `POST` | `/api/funnels/{id}/duplicate` | Deep-clones funnel, nodes, questions, options, and edges. |
| **Funnels** | `POST` | `/api/funnels/{id}/publish` | Validates graph, creates immutable version snapshot, activates published pointer. |
| **Funnels** | `POST` | `/api/funnels/{id}/unpublish`| Reverts funnel status to draft. |
| **Funnels** | `DELETE`| `/api/funnels/{id}` | Archives / soft-deletes funnel. |
| **Builder Graph** | `GET` | `/api/funnels/{id}/graph` | *(Already exists)* Returns nodes, edges, questions, and form schema. |
| **Builder Graph** | `POST`| `/api/funnels/{id}/graph` | *(Already exists)* Atomically commits graph canvas changes. |
| **Builder Graph** | `GET` | `/api/funnels/{id}/validate` | *(Already exists)* Runs topology checks, unreachable nodes, missing terminal paths. |
| **Submissions & CRM** | `GET` | `/api/submissions` | Paginated lead submissions, date range, funnel filter, search by lead name/email. |
| **Submissions & CRM** | `GET` | `/api/submissions/{id}` | Detailed journey inspector: pre-quiz form values, exact node traversal history, timestamped answers. |
| **Submissions & CRM** | `PATCH` | `/api/submissions/{id}/status` | Updates lead pipeline stage ID. |
| **Submissions & CRM** | `POST` | `/api/submissions/bulk-action`| Bulk update stage, bulk archive, or bulk export. |
| **Submissions & CRM** | `GET` | `/api/submissions/export` | Generates CSV export with filters. |
| **Custom Stages** | `GET/POST`| `/api/submissions/stages` | Lists or creates tenant-specific CRM pipeline stages. |
| **Custom Stages** | `DELETE` | `/api/submissions/stages/{id}`| Deletes stage with lead migration target. |
| **Analytics** | `GET` | `/api/analytics` | Overview metrics: views, form starts, quiz starts, completions, drop-offs, and destination breakdown. |
| **Team** | `GET/POST`| `/api/team` | Lists members or invites new member with role. |
| **Team** | `PATCH/DELETE`| `/api/team/{id}` | Updates role or revokes membership. |
| **Webhooks** | `GET/POST`| `/api/webhooks` | Lists or creates webhook endpoints. |
| **Webhooks** | `POST` | `/api/webhooks/{id}/test` | Dispatches test payload to target URL. |
| **Webhooks** | `POST` | `/api/webhooks/deliveries/{id}/retry` | Manually retries failed payload delivery. |
| **Billing** | `GET` | `/api/billing` | Current plan, quota usages, and past invoices. |
| **Billing** | `POST` | `/api/billing/upgrade` | Requests or initiates plan upgrade. |
| **Public Quiz** | `GET` | `/api/quiz/{slug}/manifest` | Returns published funnel settings, public branding, and theme. |
| **Public Quiz** | `POST` | `/api/quiz/{slug}/init` | *(Already exists)* Starts session token and returns start node. |
| **Public Quiz** | `POST` | `/api/quiz/form` | *(Already exists)* Submits pre-quiz lead form data. |
| **Public Quiz** | `POST` | `/api/quiz/answer` | *(Already exists)* Submits step answer and returns next node. |
| **Public Quiz** | `POST` | `/api/quiz/back` | *(Already exists)* Navigates back one step in session history. |
| **Public Quiz** | `POST` | `/api/quiz/video-advance` | *(Already exists)* Advances past video gate upon completion. |
| **Super Admin** | `*` | `/api/platform/*` | Superadmin routes for customers, plan management, gateways, and logs. |

---

## 5. Frontend React Directory & Module Architecture

The frontend project will live in a `frontend/` directory at the project root during development, compiling to `public/dist/` for production.

```text
qfunnel2/
├── frontend/                          <-- Vite + React Application
│   ├── index.html
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   ├── tailwind.config.js
│   └── src/
│       ├── main.tsx                   <-- App bootstrap & QueryClientProvider
│       ├── App.tsx                    <-- Route provider
│       │
│       ├── api/                       <-- Typed API clients (Axios/Fetch)
│       │   ├── client.ts              <-- Base API instance with interceptors
│       │   ├── auth.api.ts
│       │   ├── funnels.api.ts
│       │   ├── builder.api.ts
│       │   ├── submissions.api.ts
│       │   ├── analytics.api.ts
│       │   └── quiz.api.ts
│       │
│       ├── types/                     <-- Core TypeScript Interfaces
│       │   ├── auth.types.ts
│       │   ├── funnel.types.ts
│       │   ├── graph.types.ts         <-- Nodes, Edges, Port Handles, Questions
│       │   ├── submission.types.ts
│       │   └── quiz.types.ts
│       │
│       ├── routes/                    <-- React Router Definitions
│       │   ├── AppRoutes.tsx
│       │   ├── ProtectedRoute.tsx     <-- Enforces authenticated session
│       │   ├── SuperAdminRoute.tsx    <-- Enforces is_platform_admin = 1
│       │   └── PublicQuizRoute.tsx
│       │
│       ├── layouts/                   <-- Structural Shells
│       │   ├── AuthLayout.tsx         <-- Centered card layout for Login/Register
│       │   ├── AdminLayout.tsx        <-- Sidebar + Header + Tenant Selector
│       │   ├── SuperAdminLayout.tsx   <-- Platform administration shell
│       │   └── BuilderLayout.tsx      <-- Fullscreen canvas with header & inspector
│       │
│       ├── components/                <-- Reusable UI System
│       │   ├── ui/                    <-- Button, Input, Modal, Badge, Dropdown, Table
│       │   ├── shared/                <-- ConfirmationDialog, EmptyState, Pagination
│       │   └── feedback/              <-- Toast notifications, Skeleton loaders
│       │
│       ├── modules/                   <-- Feature-Driven Modules
│       │   ├── auth/                  <-- Login, Register, SwitchWorkspace
│       │   ├── dashboard/             <-- KPIs, Quick Funnel List, Activity Chart
│       │   ├── funnels/               <-- Funnel List, Create Modal, Funnel Settings
│       │   ├── builder/               <-- Visual Branching Flow (React Flow)
│       │   │   ├── Canvas.tsx         <-- Main ReactFlow viewport & controls
│       │   │   ├── nodes/             <-- Custom Node Components
│       │   │   │   ├── StartNode.tsx
│       │   │   │   ├── FormNode.tsx
│       │   │   │   ├── QuestionNode.tsx
│       │   │   │   ├── VideoGateNode.tsx
│       │   │   │   ├── DestinationNode.tsx
│       │   │   │   └── CalculatorNode.tsx
│       │   │   ├── edges/             <-- Custom Bezier Connection Wires
│       │   │   ├── inspector/         <-- Right-hand property editing panels
│       │   │   ├── toolbar/           <-- Node palette, Zoom, Undo/Redo, Publish
│       │   │   └── hooks/             <-- useGraphState, useAutoSave, useValidation
│       │   ├── submissions/           <-- Leads CRM Table, Kanban, Journey Detail
│       │   ├── analytics/             <-- Conversion funnel charts, drop-off matrices
│       │   ├── team/                  <-- Member management, role invites
│       │   ├── webhooks/              <-- Endpoint setup, delivery log viewer
│       │   ├── billing/               <-- Plan selection, usage progress bars
│       │   ├── platform/              <-- Superadmin customers, plans, gateways
│       │   └── runner/                <-- Public Quiz Visitor Experience
│       │       ├── QuizRunner.tsx
│       │       ├── steps/             <-- FormStep, QuestionStep, VideoStep, ResultStep
│       │       ├── components/        <-- ProgressBar, OptionCard, SliderInput, BrandHeader
│       │       └── hooks/             <-- useQuizSession, useQuizNavigation
│       │
│       └── store/                     <-- Global Client State (Zustand)
│           ├── authStore.ts           <-- Current user, active tenant
│           ├── builderStore.ts        <-- Selected node, dirty canvas state, zoom
│           └── toastStore.ts          <-- Transient notification queue
```

---

## 6. Visual Graph Builder Migration (React Flow Engine)

The visual builder is the central interactive module of the SaaS. Moving from the monolithic Vanilla JS/SVG canvas (`builder.js`) to React Flow provides significant improvements.

### 6.1 Node Architecture Mapping

| Backend Node Type | Custom React Flow Component | Features & Configuration Handles |
| :--- | :--- | :--- |
| `start` | `StartNode.tsx` | Single source handle (`output`). Configures welcome headline, description, and "Start" button text. |
| `form` | `FormNode.tsx` | Target handle (`input`) + Source handle (`output`). Dynamic field list manager (Name, Email, Phone, Company, Custom Inputs). |
| `question` | `QuestionNode.tsx` | Target handle (`input`) + Dynamic Source handles (`output`) per choice option. Supports Single Choice, Multi Choice, Dropdown, and Slider/Calculator. |
| `video_gate` | `VideoGateNode.tsx` | Target handle + completion Source handle. YouTube / Vimeo URL parser, minimum watch percentage, skip permissions. |
| `destination` | `DestinationNode.tsx` | Terminal Target handle (`input`). Instant result card, external URL redirect, WhatsApp 1-Click Connect with dynamic token tags (`{{formatted_quote}}`). |

### 6.2 Key React Flow Implementation Advantages
1. **Interactive Port Handles:** Handles connect effortlessly with type constraints (e.g. Question options only connect to valid downstream steps; terminal nodes have no outputs).
2. **Smooth Pan & Zoom:** Native hardware-accelerated transforms replace manual SVG matrix recalculations.
3. **Auto-Layout & Minimap:** Built-in `<MiniMap />` and `<Controls />` widgets with zero custom code.
4. **State Serialization:** React Flow's native `nodes` and `edges` format maps cleanly to the backend's `funnel_nodes` and `funnel_edges` tables.

---

## 7. Public Quiz Runner & Embeddable Widget

The public quiz runner must be fast, responsive, and easy to embed.

### 7.1 Architecture
- **Route:** `/quiz/:slug` in the React SPA.
- **Embedded Mode:** Detects `?embedded=1` query parameter to hide outer padding, headers, and footers for clean iframe nesting.
- **Micro-Bundle Compilation:** Vite will be configured with a secondary library build target (`vite.config.embed.ts`) to compile a single lightweight `<script>` file (`embed.js`) allowing 1-line script embeds on WordPress, Webflow, Shopify, or Wix:
  ```html
  <div data-qfunnel-embed="my-quiz-slug" data-qfunnel-mode="inline"></div>
  <script src="https://yourdomain.com/assets/embed.js" async></script>
  ```

### 7.2 Quiz Runner State Machine
```
[Visitor Opens URL] 
       │
       ▼
Fetch Manifest (/api/quiz/{slug}/manifest)
       │
       ▼
Initialize Session (/api/quiz/{slug}/init) ──> Sets Session Token
       │
       ▼
Step Decision Engine:
  ├── Start Node      ──> Show welcome card & "Begin" button
  ├── Form Node       ──> Render dynamic pre-quiz lead form
  ├── Question Node   ──> Render choices / slider; on select ──> POST /api/quiz/answer
  ├── Video Gate Node ──> Mount player, enforce watch progress ──> POST /api/quiz/video-advance
  └── Destination     ──> Show quote / trigger external redirect / WhatsApp 1-Click
```

---

## 8. Module-by-Module Screen Inventory & React Mapping

| Original PHP View | Target React Module & Component | Key Functionality |
| :--- | :--- | :--- |
| `app/Views/auth/login.php` | `modules/auth/LoginPage.tsx` | Email & password form, validation, session establishment. |
| `app/Views/auth/register.php` | `modules/auth/RegisterPage.tsx` | Workspace creation, owner setup, initial plan assignment. |
| `app/Views/dashboard/index.php` | `modules/dashboard/DashboardPage.tsx` | KPI summary cards, active funnels table, quick stats. |
| `app/Views/dashboard/suspended.php`| `modules/dashboard/SuspendedPage.tsx` | Account suspension notice, past-due alert, upgrade redirect. |
| `app/Views/funnels/index.php` | `modules/funnels/FunnelsListPage.tsx` | Card/table views, search, status badge, clone/delete actions. |
| `app/Views/funnels/create.php` | `modules/funnels/CreateFunnelModal.tsx` | Template selector (Standard Quiz vs. Calculator Funnel). |
| `app/Views/funnels/settings.php` | `modules/funnels/FunnelSettingsPage.tsx`| Branding, accent colors, logo upload/URL, embed code generator. |
| `app/Views/builder/index.php` | `modules/builder/BuilderPage.tsx` | Full React Flow canvas, node palette, live inspector. |
| `app/Views/submissions/index.php` | `modules/submissions/SubmissionsPage.tsx`| CRM lead list, Kanban view, custom pipeline stage manager, CSV export. |
| `app/Views/submissions/show.php` | `modules/submissions/LeadDetailPage.tsx` | Visual step-by-step visitor journey timeline, WhatsApp launcher. |
| `app/Views/analytics/index.php` | `modules/analytics/AnalyticsPage.tsx` | Conversion funnels, step drop-off charts, destination stats. |
| `app/Views/team/index.php` | `modules/team/TeamPage.tsx` | Member list, role badges, invite modal (Admin, Editor, Analyst). |
| `app/Views/webhooks/index.php` | `modules/webhooks/WebhooksPage.tsx` | Webhook endpoints, HMAC secret viewer, delivery log inspector, ping test. |
| `app/Views/settings/workspace.php`| `modules/settings/WorkspaceSettingsPage.tsx`| Company metadata, tenant name/slug updates. |
| `app/Views/settings/profile.php` | `modules/settings/ProfileSettingsPage.tsx` | User profile details, password reset. |
| `app/Views/billing/index.php` | `modules/billing/BillingPage.tsx` | Current plan tier, usage bars, upgrade plan selector. |
| `app/Views/platform/*.php` | `modules/platform/*` | Super Admin portal (Customer management, Plan builder, Payment gateways, Audit logs). |

---

## 9. State Management, Routing & Data Fetching

### 9.1 Data Fetching Strategy (TanStack Query)
- **Automatic Caching & Invalidation:**
  - Query keys follow a standard taxonomy: `['funnels']`, `['funnels', id]`, `['submissions', { page, stage }]`, `['analytics', funnelId]`.
  - Saving a graph automatically invalidates `['funnels', id, 'graph']` and `['funnels', id, 'validation']`.
- **Optimistic Updates:** Immediate feedback when toggling funnel status or dragging leads between CRM pipeline stages.

### 9.2 Client-Side State (Zustand)
- **`useBuilderStore`:** Manages active node selection, zoom level, canvas dirty status, and temporary connection lines without triggering unnecessary React component tree re-renders.
- **`useAuthStore`:** Holds currently authenticated user details, permissions, active tenant, and available workspace lists.

### 9.3 Routing Structure (React Router)
```tsx
<Routes>
  {/* Public Marketing & Legal */}
  <Route path="/" element={<LandingPage />} />
  <Route path="/legal/:slug" element={<LegalPage />} />
  <Route path="/docs" element={<DocumentationPage />} />
  
  {/* Public Quiz Visitor Runner */}
  <Route path="/quiz/:slug" element={<QuizRunner />} />
  <Route path="/quiz/:slug/result/:token" element={<QuizResult />} />

  {/* Auth Routes */}
  <Route element={<AuthLayout />}>
    <Route path="/login" element={<LoginPage />} />
    <Route path="/register" element={<RegisterPage />} />
  </Route>

  {/* Tenant Admin Routes (Protected) */}
  <Route element={<ProtectedRoute><AdminLayout /></ProtectedRoute>}>
    <Route path="/admin/dashboard" element={<DashboardPage />} />
    <Route path="/admin/funnels" element={<FunnelsListPage />} />
    <Route path="/admin/funnels/:id/settings" element={<FunnelSettingsPage />} />
    <Route path="/admin/submissions" element={<SubmissionsPage />} />
    <Route path="/admin/submissions/:id" element={<LeadDetailPage />} />
    <Route path="/admin/analytics" element={<AnalyticsPage />} />
    <Route path="/admin/team" element={<TeamPage />} />
    <Route path="/admin/webhooks" element={<WebhooksPage />} />
    <Route path="/admin/billing" element={<BillingPage />} />
    <Route path="/admin/settings/*" element={<SettingsPage />} />
  </Route>

  {/* Dedicated Fullscreen Builder Route */}
  <Route path="/admin/funnels/:id/builder" element={<ProtectedRoute><BuilderPage /></ProtectedRoute>} />

  {/* Super Admin Platform Routes */}
  <Route element={<SuperAdminRoute><SuperAdminLayout /></SuperAdminRoute>}>
    <Route path="/admin/platform" element={<PlatformDashboardPage />} />
    <Route path="/admin/platform/customers" element={<PlatformCustomersPage />} />
    <Route path="/admin/platform/plans" element={<PlatformPlansPage />} />
    <Route path="/admin/platform/payment-gateways" element={<PlatformGatewaysPage />} />
    <Route path="/admin/platform/logs" element={<PlatformLogsPage />} />
  </Route>
</Routes>
```

---

## 10. Phased Implementation Roadmap

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        PHASED EXECUTION TIMELINE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ Phase 1: Headless API & CORS ──> Transform PHP into JSON REST API           │
│ Phase 2: React Core Setup     ──> Vite, TypeScript, Tailwind, Auth & Layouts│
│ Phase 3: Tenant Admin Views   ──> Funnels CRUD, Settings, CRM Submissions   │
│ Phase 4: Visual Flow Builder  ──> React Flow Custom Nodes, Wires & Inspector│
│ Phase 5: Public Quiz Runner   ──> Visitor steps, Video gate & Embed bundle  │
│ Phase 6: Super Admin & Billing──> Platform customer tools, Plans, Gateways  │
│ Phase 7: Production Build     ──> Vite compilation, cPanel .htaccess deploy │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Phase 1: Headless API & CORS Enablement
1. Implement `CorsMiddleware` in PHP to support local Vite dev server headers.
2. Build JSON response wrappers for authentication, dashboard, funnels CRUD, submissions, and billing.
3. Verify all endpoints return consistent JSON shapes: `{ success: true, data: {...}, error: null }`.

### Phase 2: React Core Setup & Authentication
1. Initialize Vite project in `frontend/` with React, TypeScript, and Tailwind CSS.
2. Build reusable UI components (Buttons, Inputs, Modals, Badges, Tables, Toasts).
3. Implement `AuthLayout`, `LoginPage`, and `RegisterPage` integrated with `/api/auth/*`.
4. Configure session persistence and `ProtectedRoute` guard.

### Phase 3: Tenant Admin & Funnel Management
1. Build `AdminLayout` with sidebar navigation, tenant workspace switcher, and user avatar dropdown.
2. Develop `FunnelsListPage` with search, status filtering, funnel duplication, and deletion.
3. Build `FunnelSettingsPage` (Branding, Logo, Embed code generator).
4. Implement `SubmissionsPage` (Leads table, custom pipeline stages, bulk actions, and CSV export).
5. Build `LeadDetailPage` (Visual step-by-step visitor journey).

### Phase 4: Visual Flow Builder (React Flow Engine)
1. Set up React Flow canvas container with pan, zoom, grid, and minimap.
2. Develop custom nodes: `StartNode`, `FormNode`, `QuestionNode`, `VideoGateNode`, `DestinationNode`.
3. Build the node inspector sidebar (property editor for questions, options, validation, and formulas).
4. Connect `/api/funnels/{id}/graph` for loading and auto-saving canvas states.
5. Implement validation visual cues and 1-click publishing.

### Phase 5: Public Quiz Runner & Embeddable Widget
1. Create `QuizRunner` component handling multi-step questionnaire traversal.
2. Build step renderers: dynamic lead form, choice cards, video player with watch gate, slider input, and quote destination.
3. Implement back-navigation state restoration and answer change overrides.
4. Set up standalone Vite embed build configuration generating `public/assets/js/embed.js`.

### Phase 6: Super Admin Portal & Platform Tools
1. Build `SuperAdminLayout` accessible only by `is_platform_admin = 1`.
2. Implement customer workspace manager (suspend, assign plan, record offline payment).
3. Build the custom plan creator (feature toggles, quotas, pricing).
4. Implement platform audit log viewer and payment gateway credential forms.

### Phase 7: Production Optimization & Deployment
1. Configure Vite build target to output into `public/dist/`.
2. Configure Apache `.htaccess` rewrite rules to serve `index.html` for frontend routes and route `/api/*` to PHP.
3. Run end-to-end tests across the full user journey.

---

## 11. cPanel Production Build & Deployment Strategy

Deploying a React + Core PHP app on cPanel is straightforward and requires **no Node.js daemon** in production.

### 11.1 Directory Structure on cPanel (`public_html`)
```text
public_html/
├── app/                  <-- Core PHP application logic
├── config/
├── database/
├── storage/
│   └── logs/
├── .env                  <-- Production DB credentials
├── .htaccess             <-- Unified routing & security
├── index.php             <-- PHP entrypoint for all /api requests
│
│   # Built React Static Assets (from Vite npm run build)
├── index.html            <-- React SPA root entrypoint
├── favicon.ico
└── assets/               <-- Compiled JS chunks and CSS bundles
    ├── index-B8f9a2.js
    ├── index-C10e4f.css
    └── builder-D93a11.js
```

### 11.2 Production Apache `.htaccess` File
This unified configuration serves the React SPA while delegating API and asset requests correctly:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 1. Route API requests directly to PHP
    RewriteCond %{REQUEST_URI} ^/api/ [NC]
    RewriteRule ^ public/index.php [QSA,L]

    # 2. Allow direct access to existing files and directories (assets, images, scripts)
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # 3. Route all other client-side routes to the React SPA index.html
    RewriteRule ^ index.html [L]
</IfModule>

# Security: Block sensitive files
<FilesMatch "^\.env|\.git|composer\.(json|lock)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 11.3 Automated Local Deployment Script
Create a local build-and-package script (`deploy.sh` or PowerShell `deploy.ps1`):
```powershell
# 1. Build React frontend
cd frontend
npm run build

# 2. Copy compiled dist files into public directory
Copy-Item -Recurse -Force dist\* ..\public\

# 3. Ready to upload public_html to cPanel via FTP, Git, or File Manager
```

---

## 12. Verification, Quality Assurance & Rollback Plan

### 12.1 Acceptance Criteria
1. **Authentication Flow:** User registration, login, logout, and workspace switching work seamlessly without page reloads.
2. **Builder Operations:** Adding nodes, linking wires, modifying question options, and validating graphs perform smoothly in React Flow.
3. **Graph Persistence:** Committing graph changes in React updates `funnel_nodes` and `funnel_edges` identically to the legacy builder.
4. **Visitor Quiz Execution:** Completing a published quiz from start to finish captures form values, persists answers, evaluates branching routes, and triggers destinations.
5. **Multi-Tenancy Security:** Tenant A is strictly prohibited from viewing or accessing Tenant B's funnels, leads, or analytics.
6. **cPanel Compatibility:** Direct URL access and browser refreshes on deep paths (e.g., `https://domain.com/admin/funnels/4/settings`) resolve properly via `index.html` without 404 errors.

### 12.2 Risk Mitigation & Rollback Strategy
- **Zero Loss Migration:** The PHP backend services, database schema, and test suite remain untouched during the transition.
- **Side-by-Side Coexistence:** The legacy PHP views in `app/Views/` can remain intact in the repository until the React frontend is verified. If needed, a quick toggle in `.htaccess` can fall back to the PHP views at any time.


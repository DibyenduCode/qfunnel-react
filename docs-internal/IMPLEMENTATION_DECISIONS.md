# IMPLEMENTATION DECISIONS — Quiz Funnel SaaS

This document records key architectural, security, and design decisions made to satisfy the project specifications (`TECH_STACK.md`, `BACKEND.md`, `DB.md`, `UI.md`, `USER_JOURNEY.md`).

---

## 1. Environment & Base URL Resolution

### Context
The application runs on XAMPP (Apache, PHP 8.2+, MySQL 8+). Depending on developer configuration, the application may be accessed directly at:
- `http://localhost/qfunnel/` (served from `C:\xampp\htdocs\qfunnel`)
- `http://localhost/qfunnel/public/`
- Or a custom Apache VirtualHost pointing directly to `C:\xampp\htdocs\qfunnel\public` (e.g., `http://qfunnel.local`)

### Decision
- Built a smart base-path resolver in the `Request` and `Router` classes. It inspects `$_SERVER['SCRIPT_NAME']` and `$_SERVER['REQUEST_URI']` to automatically strip subdirectory prefixes (e.g., `/qfunnel` or `/qfunnel/public`), ensuring clean route matching for `/admin/...` and `/quiz/...`.
- Added a root `.htaccess` and root `index.php` that forward requests to `public/index.php` and block web access to sensitive internal directories (`app/`, `config/`, `database/`, `storage/`, `docs/`, `tests/`, `.env`).
- Added an asset helper function `asset($path)` and URL helper `url($path)` that automatically include the correct subpath in links, scripts, and stylesheets.

---

## 2. Multi-Tenancy Architecture

### Context
`TECH_STACK.md` and `BACKEND.md` require strict multi-tenancy with server-side authorization.

### Decision
- **Tenant Context**: Implemented `TenantContextService` to resolve the active tenant from authenticated user session and validated membership.
- **Tenant Scoping**: All repository and service queries for tenant resources (`funnels`, `questions`, `nodes`, `edges`, `submissions`, `analytics`, etc.) unconditionally scope by `tenant_id`.
- **Role-Based Access**: Role definitions:
  - `owner`: Workspace administration, team members, plans, full funnel & submission permissions.
  - `admin`: Funnel creation/editing/publishing, submissions, analytics.
  - `editor`: Create/edit funnels and forms (publishing restricted if configured).
  - `analyst`: Read-only submissions and analytics.
- **Platform Admin**: Super administrator with `is_platform_admin = 1` for SaaS platform oversight (tenant suspension, plan definition, platform-wide audit logs).

---

## 3. Visual Branching Builder Design

### Context
`UI.md` and `USER_JOURNEY.md` require an interactive node-based visual editor without introducing heavy external frameworks (React/Vue/Laravel).

### Decision
- Implemented in Vanilla JavaScript + SVG with full pan/zoom canvas, interactive drag-and-drop nodes, dynamic Bezier curve connector wires, and option-level output ports.
- **Node Types**:
  - `start`: Single entry port.
  - `question`: Single input port, distinct output port per answer option.
  - `destination`: Terminal input port, configurable landing page URL.
- **Interactive Routing**: Each answer option has its own routing target. Connect by dragging wire from handle to target node, or by picking from the inspector dropdown.
- **Flow Validation**: Server and client validation detecting:
  - Missing start node
  - Unconnected question options
  - Unreachable nodes
  - Invalid destination URLs
  - Cycle detection with safe recursion limits
- **Persistence**: Persisted via transactional API (`POST /api/funnels/{id}/graph`) saving nodes and edges in atomic MySQL transactions.

---

## 4. Immutable Versioned Publishing

### Context
Editing a draft funnel must never alter the flow or corrupt submissions of visitors currently going through an active quiz.

### Decision
- When published (`POST /admin/funnels/{id}/publish`), the system freezes the current graph, question/option configurations, form schema, and settings into `funnel_versions` as JSON snapshots with an incrementing `version_number`.
- `funnels.current_published_version_id` points to the latest active published version.
- Active visitor sessions bind to the specific `funnel_version_id` created at session start. Subsequent draft edits in the visual builder do not affect live visitor sessions.

---

## 5. Quiz Engine & Back-Navigation Invalidation

### Context
`BACKEND.md` requires server-authoritative routing and clean handling of back-navigation without stale answers.

### Decision
- Routing decisions are strictly calculated on the server using the published graph edges.
- Each answer submission is verified against the session's active node and question type.
- **Back Navigation**: When a user navigates back and changes an earlier answer:
  - Downstream answers belonging to the invalidated branch are marked `superseded_at = NOW()`.
  - The server recalculates the active path from the new selection.
  - Stale answers are never shown in the submission inspector or final result summary.

---

## 6. Pre-Quiz Form Schema & Snapshotting

### Context
Dynamic form fields configured by the admin need to be captured accurately even if the admin modifies field labels or keys later.

### Decision
- `form_submissions` stores visitor submissions before starting the quiz.
- `form_submission_values` stores snapshots of `field_key_snapshot`, `field_label_snapshot`, and `field_type_snapshot` alongside the actual submitted value.
- This ensures historical lead and submission data remains intact and accurate forever.

---

## 7. CSRF, Password Hashing, & Security Baseline

### Decision
- Secure session configuration with `cookie_httponly = 1`, `cookie_samesite = 'Lax'`, and session regeneration on login.
- Double-submit or session-bound CSRF token validation for all state-changing `POST` requests.
- Passwords hashed using `password_hash($password, PASSWORD_DEFAULT)` and verified with `password_verify()`.
- Public session tokens use 64-character cryptographically secure hex strings (`bin2hex(random_bytes(32))`), with SHA-256 hashes stored in `quiz_sessions.public_token_hash`.
- No sensitive stack traces exposed in public error responses; structured JSON error responses with clear codes (`VALIDATION_FAILED`, `UNAUTHENTICATED`, `FORBIDDEN`, etc.).

# SECURITY.md — Multi-Tenant Security & Defense Architecture

This document details the security architecture, threat model, and defense mechanisms implemented in **Quiz Funnel SaaS**.

---

## 1. Multi-Tenancy Data Isolation

Multi-tenancy is enforced at the database query and service layer, never solely in frontend filters or hidden fields.

### Tenant Context Resolution
- The active tenant is resolved from the authenticated user's session and verified against the `tenant_memberships` table in `TenantContextService`.
- A client-supplied `tenant_id` is never trusted.

### Universal Tenant Scoping
- All repository and service queries for tenant-owned resources (`funnels`, `questions`, `funnel_nodes`, `question_options`, `funnel_edges`, `destinations`, `quiz_sessions`, `form_submissions`, `quiz_answers`, `analytics`, etc.) unconditionally include `WHERE tenant_id = ?`.
- Verifications are performed before any load, edit, publish, duplicate, or delete action.
- Automated integration tests (`tests/run_tests.php`) verify that Tenant B cannot access or modify Tenant A's funnels or submissions, even when Tenant B knows Tenant A's internal numeric IDs (IDOR protection).

---

## 2. SQL Injection Prevention

- **Prepared Statements:** 100% of database queries containing variable input use PDO prepared statements with parameter binding via `App\Core\Database`.
- **Emulated Prepares Disabled:** `PDO::ATTR_EMULATE_PREPARES => false` is enforced in the PDO connection options, guaranteeing real server-side statement preparation.

---

## 3. Cross-Site Request Forgery (CSRF) Defense

- **Synchronizer Token Pattern:** State-changing requests (`POST`, `PUT`, `DELETE`, `PATCH`) are intercepted by `CsrfMiddleware`.
- **Token Generation:** A cryptographically secure 64-character token (`bin2hex(random_bytes(32))`) is generated per session.
- **Validation:** Checked via `hash_equals()` against `_csrf_token` in form submissions or `X-CSRF-Token` in AJAX headers.

---

## 4. Cross-Site Scripting (XSS) Prevention

- **Context-Appropriate Escaping:** All user-provided and dynamic text rendered in HTML templates passes through `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` via the `e()` helper function.
- **Content Type & Framing Headers:**
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`

---

## 5. Public Quiz Session & Result Token Security

- **No Sequential IDs:** Public quiz links and results never expose sequential database primary keys.
- **Crypto-Random Tokens:** Public sessions use 64-character hex strings generated via `bin2hex(random_bytes(32))`.
- **Hashed Token Storage:** `quiz_sessions.public_token_hash` and `quiz_results.result_token_hash` store only the SHA-256 hash (`hash('sha256', $token)`). If the database were ever compromised, raw access tokens cannot be enumerated.

---

## 6. Audit Logging & Credential Redaction

- Privileged administrative and workspace actions are recorded in `audit_logs`.
- The application logger (`App\Core\Logger`) automatically detects and redacts sensitive keys (`password`, `token`, `secret`, `auth`, `card`, `cvv`) before writing to `storage/logs/app.log`.

# TECH_STACK.md --- Quiz Funnel SaaS

## 1. Product Context

Build a multi-tenant SaaS platform for creating visual branching quiz
funnels. Admins create a funnel visually, configure a pre-quiz form,
connect answer options to different questions, and map terminal paths to
landing pages. Visitors complete the form and quiz; the platform
persists their journey, answers, and destination.

**Current target:** early-stage SaaS with Core PHP + MySQL, initially
sized for approximately 200 users. This is a starting capacity target,
not a guarantee of 200 simultaneous active users; verify with load
testing.

## 2. Required Stack

-   **Backend:** Core PHP 8.2+ using OOP and a lightweight MVC
    structure.
-   **Database:** MySQL 8+ with InnoDB.
-   **Database access:** PDO, prepared statements, explicit
    transactions.
-   **Admin UI:** HTML, Tailwind CSS, Vanilla JavaScript.
-   **Public quiz UI:** HTML, Tailwind CSS, Vanilla JavaScript.
-   **Async requests:** Fetch API / JSON endpoints.
-   **Visual builder:** Vanilla JavaScript with SVG-based connections,
    or a small compatible node-editor library if approved. Do not
    introduce React/Vue for this version without an explicit
    architecture decision.
-   **Web server:** Apache or LiteSpeed with URL rewrite support.
-   **Local development:** XAMPP or an equivalent PHP/MySQL environment.
-   **Version control:** Git.
-   **Testing:** PHP unit/integration tests where feasible, plus manual
    end-to-end tests and load tests.

## 3. SaaS Architecture

Use a modular monolith rather than microservices. Keep one deployable
PHP application with clearly separated modules.

``` text
quiz-funnel-saas/
├── app/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Middleware/
│   ├── Validators/
│   ├── Policies/
│   └── Support/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── index.php
│   └── assets/
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
├── storage/
│   ├── logs/
│   └── cache/
├── tests/
└── docs/
```

Adapt the exact structure to the existing project, but preserve
separation of concerns. Only `public/` should be web-accessible. Keep
configuration, logs, migrations, and secrets outside the public document
root.

## 4. Architectural Modules

1.  Authentication and tenant/workspace management.
2.  Funnel CRUD, drafts, publishing, and versioning.
3.  Visual graph/node editor persistence.
4.  Question and option management.
5.  Pre-quiz form builder.
6.  Public quiz renderer and branching engine.
7.  Session and answer persistence.
8.  Destination/redirect management.
9.  Submission and journey timeline.
10. Analytics and exports.
11. SaaS plan, usage limits, and billing integration hooks (billing can
    be deferred, but tenant/plan architecture must not be blocked).

## 5. Multi-Tenancy Is Mandatory

Treat this as a SaaS product, not a single-admin application.

-   A `tenant` represents a customer workspace/account.
-   Users belong to tenants through memberships/roles.
-   Every tenant-owned record must be scoped by `tenant_id` directly or
    through a verified parent relationship.
-   Every backend request must authorize the current user against the
    tenant and resource.
-   Never trust a `tenant_id` sent by the browser without verifying
    membership.
-   Public funnel routes resolve the funnel and its tenant from a unique
    public slug/domain, then use that resolved resource context.
-   Enforce tenant scoping in repositories/services, not only in UI
    filters.
-   Add tests proving that Tenant A cannot read, modify, export, or
    delete Tenant B's data.

## 6. Performance for the Initial Scale

-   Add indexes for frequent filters and joins.
-   Paginate submission lists and analytics tables.
-   Avoid loading every node, answer, or submission into one request.
-   Use transactions for graph saves and quiz completion.
-   Cache published funnel configuration only if invalidation/versioning
    is reliable.
-   Avoid long PHP session locks during AJAX requests; release session
    locks after required session data is read/written.
-   Use bounded payload sizes and rate limits for public endpoints.
-   Record key timings and PHP/MySQL errors without logging secrets or
    unnecessary personal data.
-   Test realistic concurrency, not just total registered accounts.

## 7. Security Baseline

-   PDO prepared statements for every database query containing variable
    input.
-   Server-side validation and output escaping.
-   Password hashing using `password_hash()` and verification with
    `password_verify()`.
-   CSRF protection for state-changing admin requests.
-   Secure, HTTP-only, SameSite cookies; HTTPS in production.
-   Rate limiting for login, form submission, answer submission, and
    public session creation.
-   Authorization checks on every tenant-owned resource.
-   Validate destination URLs and block unsafe schemes / open redirects.
-   Use cryptographically random public session/result tokens; store
    hashes when appropriate.
-   Do not expose sequential database IDs in public URLs.
-   Protect exports and uploads; restrict file types and sizes.
-   Store secrets outside source control.
-   Provide retention/deletion policies for personal data.

## 8. API and Request Conventions

-   JSON APIs should return a consistent shape such as `success`,
    `data`, and `error`.
-   Use correct HTTP status codes.
-   Validate request method, content type, CSRF where applicable, tenant
    context, and resource ownership.
-   Keep business logic in Services; Controllers should remain thin.
-   Use idempotency or duplicate-submit protection for answer
    submissions and completion.
-   Return only public-safe funnel data to visitors; never expose
    internal notes, other leads, tenant settings, or private analytics.

## 9. Environment and Deployment

Maintain separate development, staging, and production environments. Use
environment-based configuration for database credentials, app URL,
session settings, and mail configuration. Production must use HTTPS,
disabled verbose PHP errors, log rotation, regular database backups, and
a tested restore procedure.

## 10. Definition of Done

-   Multi-tenant isolation is tested.
-   Core user journey works end to end.
-   Data is persisted in MySQL, not only browser storage.
-   Error, loading, empty, and retry states exist.
-   Security checks are server-side.
-   A backup and restore procedure is documented.
-   A load test is performed against the target workload.

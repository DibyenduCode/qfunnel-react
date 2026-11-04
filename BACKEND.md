# BACKEND.md --- Core PHP SaaS Backend

## 1. Purpose

Define the backend architecture for a multi-tenant Visual Branching Quiz
Funnel SaaS using Core PHP 8.2+, MySQL 8+, PDO, and a modular MVC
pattern.

The backend is responsible for authentication, tenant isolation, funnel
configuration, graph validation, dynamic form configuration, public quiz
sessions, answer persistence, journey events, results, redirects,
analytics, and plan/usage enforcement.

## 2. Core Rules

-   Use OOP and a lightweight MVC structure; do not build the
    application as a collection of unrelated PHP scripts.
-   Controllers parse requests and return responses; Services contain
    business rules; Repositories handle database access; Validators
    validate input; Policies authorize actions.
-   Use PDO prepared statements.
-   Use database transactions for multi-record changes.
-   All tenant-owned data access must be tenant-scoped.
-   Never rely on frontend validation or hidden UI controls for
    security.
-   All public routes must return only data required to render that
    published funnel.
-   Keep internal IDs and private configuration out of public responses.

## 3. Suggested Modules

``` text
Auth
Tenants & Memberships
Funnels
FunnelGraph
Questions & Options
PreQuizForms
PublicQuiz
QuizSessions
QuizAnswers
JourneyEvents
Destinations
Submissions
Analytics
Plans & Usage
AuditLog
```

## 4. Suggested Services

-   `AuthService`: login, logout, password verification, session
    renewal.
-   `TenantContextService`: resolve current tenant and verify
    membership.
-   `FunnelService`: create, update, duplicate, archive, publish,
    unpublish.
-   `FunnelGraphService`: save nodes/edges, validate graph, return graph
    for editor.
-   `FunnelPublishService`: validate and freeze a publishable funnel
    version.
-   `FormSchemaService`: manage dynamic pre-quiz fields and validation
    schema.
-   `QuizSessionService`: create, resume, expire, and complete public
    sessions.
-   `QuizEngineService`: determine the next node from the selected
    option and published graph.
-   `AnswerService`: validate and persist answers, prevent duplicate
    submissions.
-   `JourneyEventService`: record question views, answers, back
    navigation, completion, and redirect attempts.
-   `DestinationService`: validate final destination and create safe
    redirect response.
-   `SubmissionService`: admin submission search/detail/export with
    tenant authorization.
-   `AnalyticsService`: aggregate starts, completions, drop-offs, and
    destination counts.
-   `UsageLimitService`: enforce tenant plan limits.
-   `AuditLogService`: record important admin actions.

## 5. Admin Request Lifecycle

1.  Validate HTTP method and content type.
2.  Authenticate the user.
3.  Resolve the active tenant from a verified membership.
4.  Load the requested resource scoped to that tenant.
5.  Authorize the action.
6.  Validate and normalize request data.
7.  Execute service logic.
8.  Use a transaction if multiple related rows change.
9.  Return a consistent JSON or HTML response.
10. Log unexpected errors safely; never expose stack traces or secrets
    to users.

## 6. Public Quiz Lifecycle

1.  Resolve a published funnel by public slug or approved custom domain.
2.  Load the immutable published version.
3.  Create a cryptographically random quiz session token.
4.  Display the configured pre-quiz form.
5.  Validate and save form submission before starting the quiz.
6.  Create a quiz-start event.
7.  Return the current question and only its public-safe options.
8.  Validate each answer against the current published graph and session
    state.
9.  Save the answer and the next-node decision atomically.
10. Record journey events.
11. When an end node is reached, save completion and destination.
12. Display the result summary or issue a safe redirect according to
    funnel settings.

The server, not the browser, decides whether an answer is valid and
which node comes next.

## 7. Graph and Publishing Rules

A funnel graph contains a start node, question nodes, destination/end
nodes, and edges.

Each choice option may connect to a different next question or
destination. Validate before publishing:

-   Exactly one valid start node.
-   Every reachable choice option has a valid outgoing route unless
    explicitly configured as terminal.
-   Every route terminates at a valid destination.
-   No unreachable nodes unless the admin intentionally keeps a draft
    node.
-   No unsupported cycles.
-   Every destination URL is valid and permitted.
-   Question type and option configuration are compatible.
-   Graph references belong to the same funnel and tenant.

Publish a versioned snapshot. Public sessions should stay on the version
they started with, so later admin edits do not change an in-progress
visitor's path.

## 8. Answer Persistence and Idempotency

Each answer submission should include a session token, current
node/question reference, answer payload, and an idempotency key or
equivalent duplicate-submit protection.

Within a transaction: - Verify session is active. - Verify the question
belongs to the session's published funnel version. - Validate the answer
type, required state, and allowed option IDs. - Save the answer. -
Resolve the next node on the server. - Save the routing decision /
journey event. - Update the session's current node and activity
timestamp.

If the same request is retried, do not create duplicate answers or
advance twice. Define how changed answers from Back navigation are
handled: supersede/update the prior answer for that visit and recompute
the path, or explicitly create a new attempt record. The implementation
must be consistent.

## 9. Tenant Isolation

Every admin query must enforce tenant ownership. Avoid fetching a record
globally and checking ownership only in the frontend.

Use tenant-scoped repository methods, such as: -
`findFunnelForTenant($tenantId, $funnelId)` -
`listSubmissionsForTenant($tenantId, $filters)` -
`findSubmissionForTenant($tenantId, $submissionId)`

Add integration tests for cross-tenant read, update, delete, export, and
analytics access.

## 10. Error Handling

Use stable machine-readable error codes, for example: -
`VALIDATION_FAILED` - `UNAUTHENTICATED` - `FORBIDDEN` -
`RESOURCE_NOT_FOUND` - `FUNNEL_NOT_PUBLISHED` - `SESSION_EXPIRED` -
`INVALID_ANSWER` - `FLOW_INVALID` - `RATE_LIMITED` - `SAVE_FAILED`

Return human-readable messages without leaking internals. Log a
correlation/request ID to support debugging.

## 11. Analytics and Event Collection

Capture only useful events: - `quiz_viewed` - `pre_form_submitted` -
`quiz_started` - `question_viewed` - `answer_submitted` -
`branch_selected` - `quiz_completed` - `destination_selected` -
`redirect_attempted`

Each event should include tenant, funnel, published version, session,
event type, timestamp, and relevant node/option references. Avoid
storing unnecessary sensitive data in event metadata.

## 12. Security Requirements

-   CSRF tokens for admin state changes.
-   Session ID regeneration after login.
-   Secure cookies and HTTPS.
-   Login throttling and password reset safeguards.
-   Rate limits for public session and answer endpoints.
-   URL allow/deny validation for redirects.
-   Escape rendered user-provided text.
-   Prevent SQL injection, XSS, CSRF, IDOR, open redirects, and mass
    assignment.
-   Keep public result tokens unguessable and optionally store only
    token hashes.
-   Restrict CSV exports and audit them.
-   Never log passwords, full session tokens, or unnecessary personal
    data.

## 13. Acceptance Criteria

-   An admin can create and publish a branching funnel.
-   Each option routes to the correct next node.
-   A visitor cannot submit an answer for an unvisited or unrelated
    node.
-   Form submission is required before quiz start when configured.
-   Answer and journey history persist in MySQL.
-   Result and destination correspond to the actual server-validated
    path.
-   In-progress sessions remain tied to their published version.
-   Cross-tenant access is denied and tested.
-   Duplicate submissions do not corrupt the journey.

# USER_JOURNEY.md --- SaaS User Journeys

## 1. Product Model

This is a multi-tenant SaaS product. It has three primary actors:

1.  **Platform Admin** --- manages the SaaS itself, tenants, plans, and
    platform health.
2.  **Tenant Owner / Team Member** --- creates funnels, configures
    forms, publishes quizzes, and reviews submissions within their
    workspace.
3.  **Public Visitor** --- opens a published quiz, submits the pre-quiz
    form, answers a branching sequence, views their summary if enabled,
    and reaches the configured landing page.

Do not mix Platform Admin permissions with tenant-level admin
permissions.

## 2. Tenant Owner Journey

### A. Sign up and workspace setup

1.  Owner creates an account.
2.  Email verification may be required depending on deployment settings.
3.  A tenant/workspace is created.
4.  Owner is added to the workspace with the `owner` role.
5.  A default plan/trial and its usage limits are applied.
6.  Owner lands on an onboarding dashboard.

Acceptance: - Workspace belongs to the authenticated owner. - The owner
cannot access another tenant's data. - Empty states explain the next
step clearly.

### B. Create a funnel

1.  Click **Create Funnel**.
2.  Enter funnel name, slug, and optional description.
3.  Choose a starting template or blank funnel.
4.  Funnel is created in Draft status.
5.  Open the Visual Builder.

### C. Build the branching graph

1.  Add a Start node.
2.  Add Question nodes.
3.  Write question text and choose the question type.
4.  Add and order options.
5.  Connect each option to its own next question or destination.
6.  Add Destination nodes and configure URLs.
7.  Move nodes on the canvas and save the layout.
8.  Run Flow Validation.
9.  Preview the funnel as a visitor.

Example: - Profession = Doctor → Doctor Type - Profession = Engineer →
Engineering Specialty - Profession = Lawyer → Legal Practice - Doctor
Type = Psychiatrist → Practice City - Practice City = Delhi → Delhi
Area - Delhi Area = Saket → Saket Landing Page

Acceptance: - Every option connection is explicit. - The builder shows
missing routes. - The server validates the graph before publishing. - A
saved graph reloads identically after refresh.

### D. Configure the pre-quiz form

1.  Open Funnel Settings → Pre-Quiz Form.
2.  Add fields such as name, email, phone, city, dropdown, or consent
    checkbox.
3.  Set labels, placeholders, required/optional, validation, options,
    and order.
4.  Configure heading, helper text, privacy notice, and submit label.
5.  Preview the form.
6.  Save the form configuration.

Acceptance: - Form configuration is saved per funnel and included in the
published version. - Required fields block quiz start until valid. -
Consent is not preselected. - Field changes do not rewrite old
submission snapshots.

### E. Validate, publish, and share

1.  Run validation.
2.  Fix missing routes, unreachable nodes, and invalid destinations.
3.  Publish a new immutable version.
4.  Copy the public quiz URL.
5.  Open the public URL in a private/incognito session.
6.  Test all branches and destination routes.
7.  Monitor starts, completion, drop-off, and destination metrics.

Acceptance: - Draft edits do not unexpectedly alter sessions already
using a published version. - Only valid published funnels are available
publicly. - The owner can unpublish or archive a funnel according to
permission.

### F. Review submissions

1.  Open Submissions.
2.  Filter by funnel, status, date, or visitor/lead fields.
3.  Open a submission.
4.  Review pre-quiz form values.
5.  Inspect the chronological question/answer journey.
6.  Review the selected branches and final destination.
7.  Export data only if the user has the required permission.

Acceptance: - The detail view shows only questions actually visited. -
Historical question and form labels remain understandable after edits. -
Team members only see data allowed by their role.

## 3. Public Visitor Journey

### Step 1 --- Open the quiz

-   Visitor opens the published funnel URL.
-   The page loads the published version and public-safe settings.
-   A new or resumable session is established using an unpredictable
    token.
-   Visitor sees the welcome screen, purpose, and Start action.

### Step 2 --- Submit pre-quiz form

-   Visitor clicks Start.
-   The configured form appears.
-   Required fields and validation are displayed.
-   Visitor submits the form.
-   Server validates and stores the form values and snapshots of field
    labels/keys.
-   Only after successful save does the quiz begin.

Failure behavior: - Invalid fields show inline errors. - A network error
offers retry. - Duplicate clicks do not create duplicate form
submissions. - Do not silently start the quiz if required form data
failed to save.

### Step 3 --- Answer the current question

-   Server returns the current question and public-safe options.
-   Visitor selects an option or enters a value.
-   Visitor clicks Continue, unless auto-advance is explicitly enabled.
-   Server verifies the session, current node, question type, and
    allowed answer.
-   Answer is saved.
-   Server resolves the next node from the published graph.
-   UI transitions to the next question smoothly.

### Step 4 --- Follow a branch

Example path: 1. Profession → Doctor 2. Doctor type → Psychiatrist 3.
Practice city → Delhi 4. Delhi area → Saket

The visitor only sees questions on the selected path. Other
profession/city branches are not shown.

### Step 5 --- Back navigation and answer changes

-   Visitor may go back if the funnel permits it.
-   Previous answer is restored.
-   If the visitor changes an earlier answer, the server must invalidate
    or supersede downstream answers that no longer belong to the active
    path.
-   The UI must not display stale downstream answers as current.
-   The route is recalculated from the changed answer.

### Step 6 --- Completion and result

-   When the visitor reaches a destination node, the server verifies the
    terminal path.
-   Session is marked completed.
-   Result and journey records are saved atomically.
-   Visitor sees a result/answer summary if configured.
-   The summary includes only submitted answers from the actual path.
-   Visitor can use a secure result link if the feature is enabled.

### Step 7 --- Destination

Depending on funnel settings: - Show the summary and a CTA to
continue. - Or show the summary briefly and redirect. - Or redirect
directly after completion.

The destination URL must be configured and validated by the tenant
owner. Do not accept an arbitrary redirect URL from public form input.

Record a redirect attempt. Mark it confirmed only when confirmation can
actually be measured.

## 4. Submission Lifecycle

Suggested statuses: - `form_pending` - `in_progress` - `completed` -
`abandoned` - `expired`

Possible event timeline: - `quiz_viewed` - `pre_form_submitted` -
`quiz_started` - `question_viewed` - `answer_submitted` -
`branch_selected` - `quiz_completed` - `destination_selected` -
`redirect_attempted`

Events should be append-only where practical. Store timestamps and
references to the published version. Avoid collecting more personal data
than required.

## 5. User Result Access

If enabled, the visitor can view their own answer summary after leaving
the quiz.

-   Use an unpredictable token, not an incremental session/result ID.
-   Store a token hash where practical.
-   Allow expiry or revocation.
-   Return only that visitor's own result.
-   Never expose another visitor's form values or answer history.
-   Avoid putting personal information into the URL.

## 6. Error and Recovery Journeys

### Network failure during answer submission

-   Preserve the pending answer temporarily in the browser.
-   Show a retry action.
-   Retry using an idempotency key.
-   Do not advance until the server confirms the save.

### Session expired

-   Explain that the session expired.
-   Offer to restart.
-   Do not create a new session invisibly while implying the old answers
    are retained.

### Funnel unpublished during an active session

-   Existing sessions continue on their immutable published version
    according to the configured policy.
-   New sessions cannot start from an unpublished funnel.

### Invalid destination

-   Publishing validation blocks the invalid URL.
-   If a runtime error still occurs, show a safe fallback result page
    and log the failure.

## 7. Platform Admin Journey

Platform Admin can: - View tenant/workspace status. - Manage plans and
limits. - Suspend or reactivate tenants according to policy. - View
aggregate system health and usage. - Audit platform-level actions.

Platform Admin access must be separated from ordinary tenant permissions
and audited. Access to customer submissions should be restricted and
justified.

## 8. Roles and Permissions

Suggested tenant roles: - **Owner:** full workspace control, billing,
members, funnels, submissions, settings. - **Admin:** manage funnels,
forms, submissions, and analytics; billing/ownership controls may be
restricted. - **Editor:** create and edit funnels, preview, publish only
if explicitly granted. - **Analyst:** read-only analytics and permitted
submission views; no graph or form editing.

Enforce roles on the server for every operation.

## 9. SaaS Usage Limits

Plan limits may include: - Number of published funnels - Monthly quiz
starts - Monthly completed quizzes - Team members - Data retention
duration - Custom domains - Branding removal - Export availability

When a limit is reached: - Show the exact limit and current usage. -
Explain what action is blocked. - Preserve drafts and existing
submissions. - Do not silently delete data. - Offer upgrade or
contact-admin actions only if the billing workflow exists.

## 10. End-to-End Acceptance Tests

1.  Tenant A cannot access Tenant B's funnel, submissions, analytics, or
    exports.
2.  A visitor must submit all required pre-quiz fields before quiz
    start.
3.  A selected option leads to the correct next node.
4.  The visitor sees only questions on the active path.
5.  Every visited answer is saved and appears in Admin journey detail.
6.  Changing an upstream answer invalidates stale downstream path data.
7.  A completed session points to the correct destination.
8.  Public result links cannot expose another visitor's data.
9.  Published versions remain stable during edits to a draft.
10. Duplicate submits and network retries do not duplicate answers or
    skip nodes.
11. Unpublished funnels cannot start new public sessions.
12. Plan limits are enforced server-side.

# QFunnel SaaS — Bug Audit & Resolution Report (`bug.md`)

## Overview
During the end-to-end full software workflow test (Account Registration → Funnel Creation → Visual Builder with Form & Question Nodes → Publishing → Public Quiz Execution), critical architectural discrepancies were identified that prevented the builder workflow from functioning as designed.

Below is the complete analysis of every issue found, the root causes, and the fixes applied.

---

### Bug 1: Default Pre-Quiz Form Intercepting Canvas Workflow
- **Severity**: Critical (High Impact on User Experience)
- **Status**: Fixed
- **Symptom**:
  When opening a published funnel (e.g., `/quiz/e-commerce-qualification-quiz`), an unconfigured generic *"Before we begin"* lead capture form was always displayed first, completely ignoring the Start Node set in the Visual Builder.
- **Root Cause**:
  1. `app/Services/FunnelService.php::createFunnel()` automatically inserted two legacy rows (`full_name`, `email`) into the `form_fields` database table for every newly created funnel.
  2. `app/Services/FunnelPublishService.php::publish()` snapshotted these rows into `form_schema_snapshot_json['fields']`.
  3. `app/Services/QuizEngineService.php::initializeSession()` evaluated `$hasForm = !empty($formSnapshot['fields'])` unconditionally. It failed to check whether the designated start node was actually a Question node or a Form node from the Visual Builder.
  4. This caused `has_form: true` to be sent to `app/Views/quiz/runner.php`, forcing the runner to render a default pre-quiz form on step 1 every time.
- **Resolution**:
  - Removed auto-insertion of legacy `form_fields` rows from `FunnelService::createFunnel()`.
  - Updated `FunnelPublishService::publish()` so it does not pollute `form_schema_snapshot_json` with arbitrary canvas form nodes.
  - Updated `QuizEngineService::initializeSession()` to inspect the start node type (`$startNodeObj['node_type']`). Only legacy funnels with an explicit `'start'` node type ever trigger pre-quiz forms; visual builder funnels starting with `'question'` or `'form'` nodes immediately begin directly on that designated canvas node (`status: 'in_progress'`, `has_form: false`).

---

### Bug 2: Skipping the Designated Start Question Node on Form Submission
- **Severity**: Critical (Bypassed Main Flow)
- **Status**: Fixed
- **Symptom**:
  After a visitor submitted the pre-quiz form, the quiz skipped the Start Question node entirely (*"What is your primary goal?"*) and immediately routed into the downstream Form node or terminal state.
- **Root Cause**:
  In `app/Services/QuizEngineService.php::submitForm()`, the engine attempted to advance the session by blindly selecting the first edge exiting from `$startNodeId`:
  ```php
  $firstQuestionNodeId = (int)$startEdge['target_node_id'];
  ```
  Since the start node was a Question node with multiple branching option wires (Option A → Form, Option B → Destination), it picked Option A's target node directly, completely bypassing the user's start question.
- **Resolution**:
  By resolving Bug 1, visual builder funnels now initialize directly at the start question node without a pre-quiz form intercepting execution. Submitting choices follows the exact branch wire connected to the chosen option.

---

### Bug 3: Back-Navigation Loop on Answered Nodes
- **Severity**: Medium
- **Status**: Fixed
- **Symptom**:
  When clicking the `"← Back"` button on a downstream node (e.g., the Form node), the runner could query the same node's existing answer in `quiz_answers`, causing the user to remain stuck on the same node instead of going back to the preceding question.
- **Root Cause**:
  `app/Services/QuizEngineService.php::navigateBack()` queried:
  ```sql
  SELECT * FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL ORDER BY id DESC LIMIT 1
  ```
  If the active node had already recorded an answer or state, it returned the current node's answer rather than the previous node.
- **Resolution**:
  Updated query to filter out `$currentNodeId`:
  ```sql
  SELECT * FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL AND node_id != ? ORDER BY id DESC LIMIT 1
  ```
  Now navigating back smoothly restores the previous question node with the user's prior answer pre-selected.

---

### Bug 4: Hardcoded Step Counter Fallback in Quiz Runner
- **Severity**: Low (UI Polish)
- **Status**: Fixed
- **Symptom**:
  When a question step loaded without an existing step number, the badge defaulted to Step 2 instead of Step 1 (`currentStepNumber = step.step_number || 2`).
- **Root Cause**:
  In `app/Views/quiz/runner.php`, line 526 assumed Step 1 was always reserved for the legacy pre-quiz form.
- **Resolution**:
  Changed fallback to `step.step_number || 1`.

---

### Bug 5: Automatic Default Node Insertion on New Funnels (Non-Blank Canvas)
- **Severity**: Medium (Design & Workflow Flexibility)
- **Status**: Fixed
- **Symptom**:
  When creating any new funnel, the Visual Builder did not start with a clean blank canvas; instead, an unwanted default *"First Question"* node (`What is your primary goal?`) was pre-injected into the canvas.
- **Root Cause**:
  `app/Services/FunnelService.php::createFunnel()` had hardcoded SQL statements that automatically inserted a Question node (`q_1`) along with options (`Option A`, `Option B`) during funnel record initialization.
- **Resolution**:
  - Removed the automatic Question node and option insertions from `FunnelService::createFunnel()`.
  - Updated `public/assets/js/builder.js` to render a clean, dashed blank canvas helper message whenever a canvas has 0 nodes, inviting the user to click or drag any node from the Node Palette.
  - Newly created funnels now start on a 100% blank canvas.

---

### Bug 6: Input Focus & Caret Loss During Form Field Label Editing in Visual Builder Inspector
- **Severity**: High (Degraded Builder Usability)
- **Status**: Fixed
- **Symptom**:
  When editing a form field label in the Form Node inspector (Visual Builder), clearing the text or typing triggered an auto-save in the background. When auto-save fired or finished, the input field cursor/focus was lost, the field key was cleared to an empty string, and the user had to manually click back into the input box to continue typing every few characters.
- **Root Cause**:
  1. `saveGraphNow(isAuto = true)` fired after a 3-second debounce timer and unconditionally re-rendered the inspector via `renderInspector(true)`, replacing `inspectorContent.innerHTML` while the user was actively typing.
  2. In `updateFormField()`, when a user backspaced or cleared the field label, `field.field_key` was computed from empty string (`value.toLowerCase().replace(...)`), wiping the key to `""` and disrupting serialization.
  3. `updateFormProperty()` called `renderInspector(true)` on every keystroke (`oninput`), even for text fields (`button_text`, `success_heading`, etc.).
- **Resolution**:
  - In `public/assets/js/builder.js`:
    - Updated `markDirty()` and `saveGraphNow(true)` to automatically defer background auto-saves whenever the user is actively focused/typing in an inspector input or textarea.
    - Updated `saveGraphNow()` so that background auto-saves do not re-render or replace the inspector DOM while the user is actively editing inside the inspector.
    - Added focus and selection range preservation (`selectionStart` & `selectionEnd`) at the start and end of `renderInspector()` using element IDs and selector queries.
    - Assigned unique DOM IDs (`field-header-label-${f.id}`, `field-key-badge-${f.id}`, `field-label-input-${f.id}`) to form field card elements so that editing the label updates the card title and key badge live in the DOM without re-rendering the panel.
    - Updated `updateFormField()` so `field.field_key` is never wiped to an empty string when the label is cleared.
    - Updated `updateFormProperty()` so only structure changes (`submit_action`) trigger inspector re-renders, preventing keystroke interruptions on text inputs.

---

### Bug 7: Raw Form Lead Capture Data JSON Displayed in Question Answers Summary
- **Severity**: Medium (Visual/Privacy Leak in Quiz Runner & Admin Show)
- **Status**: Fixed
- **Symptom**:
  On the quiz completion outcome screen or submission answers summary, form step submissions appeared as questions with raw JSON strings as the choice (e.g., `Lead Capture... {"name":"Test Demo","email":"admin@...",...}`).
- **Root Cause**:
  `submitCanvasForm()` in `app/Services/QuizEngineService.php` saved the form submission payload as an answer entry in `quiz_answers` with `answer_type = 'form'` and `answer_value_text = json_encode($formData)`. When `completeSession()` and `SubmissionService::getSubmissionDetail()` queried `quiz_answers`, they did not exclude `answer_type = 'form'`, which caused the visitor runner summary and admin questions list to render the raw JSON.
- **Resolution**:
  - Filtered `quiz_answers` queries in `QuizEngineService::completeSession()`, `QuizEngineService::completeSessionWithMessage()`, and `SubmissionService::getSubmissionDetail()` with `AND (answer_type != 'form' OR answer_type IS NULL)`.
  - Added frontend defensive filtering in `app/Views/quiz/runner.php` to drop any answers with `answer_type === 'form'` or values formatted as `{...}` JSON blobs.

---

### Bug 8: Destination Nodes Showing Intermediate Outcome Screen Instead of Direct Redirect
- **Severity**: High (User Experience / Workflow Automation)
- **Status**: Fixed
- **Symptom**:
  When a visitor completed a funnel whose terminal step was a Destination Node with a configured URL, the runner displayed a checkmark icon, "Quiz Completed!", a recommendation outcome card, and a manual "Continue to Your Results →" button instead of directly redirecting to the destination URL.
- **Root Cause**:
  `runner.php::renderResult()` was designed with an optional 3-second countdown timer that only triggered if `auto_redirect` was manually enabled in funnel settings. Otherwise, it defaulted to rendering an intermediate completion card requiring a manual button click.
- **Resolution**:
  Updated `renderResult()` in `app/Views/quiz/runner.php` so that whenever a Destination Node specifies a valid redirect URL (`destination_url`), the runner directly and immediately executes `window.location.href = resolvedUrl` with a clean transition spinner, completely skipping the intermediate checkmark/card/button screen.

---

### Bug 9: Super Admin Customer Table Displaying Subscription Plan Price as `₹0.00`
- **Severity**: Medium (Visual/Billing Confusion)
- **Status**: Fixed
- **Symptom**:
  In Super Admin *Customers & Users* (`/admin/platform/customers`), the "Plan & Billing Cycle" column showed `₹0.00` for all active subscribers (even on Pro or Starter plans).
- **Root Cause**:
  `subscription_amount` defaults to `"0.00"` in database subscriptions. In `customers.php`, the template evaluated:
  ```php
  ₹<?= number_format((float)($t['subscription_amount'] ?: ($t['plan_price'] ?? 0)), 2) ?>
  ```
  In PHP, non-empty string `"0.00"` evaluates as truthy in ternary shorthand `?:`, preventing fallback to `$t['plan_price']`.
- **Resolution**:
  Updated the check in `app/Views/platform/customers.php`:
  ```php
  ₹<?= number_format(((float)$t['subscription_amount'] > 0 ? (float)$t['subscription_amount'] : (float)($t['plan_price'] ?? 0)), 2) ?>
  ```
  Real plan prices (e.g., `₹749.00`, `₹1,499.00`) now display accurately.

---

### Bug 10: Billing Cycle & Record Payment Modals Defaulting Amount Input to `0`
- **Severity**: Medium (Data Entry Error Risk)
- **Status**: Fixed
- **Symptom**:
  When a Super Admin clicked *"Billing Cycle"* or *"Record Payment"* for any customer, the amount field in the modal was pre-filled with `0` or `0.00` instead of the plan's recurring price.
- **Root Cause**:
  In `customers.php` JS modal openers:
  ```javascript
  document.getElementById('bcModalAmount').value = tenant.subscription_amount || tenant.plan_price || 0;
  document.getElementById('rpModalAmount').value = tenant.subscription_amount || tenant.plan_price || 0;
  ```
  In JavaScript, `"0.00"` is a non-empty string and therefore truthy, so `tenant.subscription_amount || tenant.plan_price` resolved to `"0.00"`.
- **Resolution**:
  Parsed with `parseFloat()`:
  ```javascript
  const parsedAmount = parseFloat(tenant.subscription_amount || 0);
  document.getElementById('bcModalAmount').value = parsedAmount > 0 ? parsedAmount : (parseFloat(tenant.plan_price || 0) || 0);
  ```

---

### Bug 11: Missing Pagination Component on Super Admin Platform Users Tab
- **Severity**: High (Accessibility & Data Truncation)
- **Status**: Fixed
- **Symptom**:
  In Super Admin *Customers & Users*, clicking the *"Platform Users"* tab only displayed the first 10 users with zero pagination buttons (Prev, Next, or page numbers), even though 290 registered platform users existed.
- **Root Cause**:
  While `PlatformAdminController::customers()` prepared `$usersPagination` with `user_page` support and `customers.php` had auto-tab switching for `?user_page=X`, the actual pagination markup partial call was completely omitted from the bottom of the users table.
- **Resolution**:
  Added the reusable pagination component to `app/Views/platform/customers.php`:
  ```php
  <?php if (isset($users['total_pages'])): ?>
      <?php
      $pagination = $users;
      $pageParam = 'user_page';
      $queryParams = array_filter(['q' => $search ?? '', 'status' => $statusFilter ?? '']);
      require __DIR__ . '/../partials/pagination.php';
      ?>
  <?php endif; ?>
  ```
  Super admins can now browse all pages of registered users with auto-tab retention.

---

### Bug 12: Custom Legal Pages CMS Leading to 404 Not Found
- **Severity**: High (Broken Public Pages)
- **Status**: Fixed
- **Symptom**:
  When a Super Admin created a custom legal or compliance page in CMS (e.g. `/security`, `/disclaimer`), clicking *"View Live Public Page"* or clicking the footer/sidebar links led to a 404 Page Not Found error.
- **Root Cause**:
  `routes/web.php` registered hardcoded routes for the 4 core policies (`/terms`, `/privacy`, `/refund-policy`, `/cookie-policy`) and a parameterized route `/legal/{slug}`. However, `legal_pages.php` and `legal/show.php` unconditionally linked to `url('/' . $p['slug'])`, pointing to paths that had no router match.
- **Resolution**:
  Added a link resolver helper in `app/Views/platform/legal_pages.php` and `app/Views/legal/show.php`:
  ```php
  $coreSlugs = ['terms', 'privacy', 'refund-policy', 'cookie-policy'];
  $getLegalUrl = function($slug) use ($coreSlugs) {
      return in_array($slug, $coreSlugs) ? url('/' . e($slug)) : url('/legal/' . e($slug));
  };
  ```
  Updated all live links, preview buttons, and URL slug prefixes to use `/legal/{slug}` for custom policies and dedicated root URLs for core policies.

---

### Bug 13: Missing CSRF Meta Tag in Superadmin Layout
- **Severity**: Low (Security & AJAX Consistency)
- **Status**: Fixed
- **Symptom**:
  Async JavaScript routines and DOM inspection looking for `<meta name="csrf-token">` in the Super Admin layout failed because the meta tag was missing from `app/Views/layouts/superadmin.php`.
- **Root Cause**:
  `layouts/admin.php` had the meta tag, but `layouts/superadmin.php` was omitted during initial template setup.
- **Resolution**:
  Added `<meta name="csrf-token" content="<?= csrf_token() ?>">` to the `<head>` of `app/Views/layouts/superadmin.php`.

---

## Verification & Automated Test Results

1. **Automated PHP Test Suite (`tests/run_tests.php`)**:
   - `SUITE 1: Authentication & Sessions` — 4/4 Passed
   - `SUITE 2: Multi-Tenancy Data Isolation` — 4/4 Passed
   - `SUITE 3: Funnel Builder & Graph Validation` — 4/4 Passed
   - `SUITE 4: Quiz Engine Branching & Invalidation` — 5/5 Passed
   - `SUITE 5: Submissions & Analytics Integrity` — 3/3 Passed
   - `SUITE 6: Plan Limits Enforcement` — 1/1 Passed
   - `SUITE 7: Super Admin Tools & Controls` — 5/5 Passed
   - `SUITE 8: Calculator & Quote Funnel Engine` — 9/9 Passed
   - `SUITE 9: 1-Click Call & WhatsApp Direct Connect Engine` — 6/6 Passed
   - `SUITE 10: Complete Node Deletion & Blank Canvas Persistence` — 3/3 Passed
   - `SUITE 11: Customizable Form Node Headings & Button Text` — 5/5 Passed
   - **Total Core Suite**: 49 Passed, 0 Failed.

2. **Specialized Test Suites**:
   - `tests/test_billing_and_suspension.php`: 19/19 Passed (Billing intervals, manual invoices, zero-data-loss suspension, review queue, termination).
   - `tests/test_custom_lead_stages.php`: 25/25 Passed (Custom CRM pipeline stages, emoji badges, deletion resets).
   - `tests/test_user_upgrade_plan.php`: 18/18 Passed (Self-service billing, tier limit unlocks, downgrade prevention).
   - **Grand Total Automated Tests**: 111 Passed, 0 Failed.

3. **Super Admin Platform Live Browser Verification**:
   - **Customers & Users (`/admin/platform/customers`)**: Verified real plan prices (`₹749.00` / `₹1,499.00`) render properly.
   - **Platform Users Pagination**: Verified pagination appears at bottom of `tab-users`, page 2 loads seamlessly, tab state preserved.
   - **Modals**: Verified `Billing Cycle` and `Record Payment` auto-populate with the correct non-zero plan price.
   - **Legal Pages CMS**: Verified editor, public links resolving via `/legal/{slug}` and core URLs without 404s.
   - **Payment Gateways**: Verified configuration saving, toggle switches, and missing key validation.
   - **Zero PHP Errors**: All 18 routes (public, superadmin, tenant) returned HTTP 200 with 0 errors/warnings.


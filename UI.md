# UI.md --- Quiz Funnel Builder

## 1. Product UI Direction

Build a modern, polished, production-minded UI for a **Visual Branching
Quiz Funnel Builder** built with Core PHP, MySQL, HTML, Tailwind CSS,
and Vanilla JavaScript.

The product helps admins create branching quizzes visually. Each answer
can route a visitor to a different question or a final landing page.
Before starting the quiz, visitors complete an admin-configurable form.
The system saves form submissions, answers, the exact path followed, and
the final destination.

### Design principle: "Modern, not AI slop"

The interface must feel intentionally designed by a strong
product-design team---not like a generic AI-generated SaaS template.

**Avoid** - Excessive gradients, neon colors, glassmorphism, glowing
borders, and blurry blobs. - Huge hero headings inside admin screens. -
Too many rounded cards nested inside other rounded cards. - Decorative
charts that show meaningless sample data. - Emoji as interface icons. -
Random illustrations, floating shapes, and unnecessary animation. -
Every section being a separate card. - Overuse of pills, badges,
shadows, and colored backgrounds. - Fake buttons, nonfunctional
controls, and placeholder interactions presented as real features.

**Prefer** - Clear information hierarchy and compact, purposeful
layouts. - A restrained neutral palette with one distinctive accent
color. - Crisp typography, consistent spacing, subtle borders, and
restrained shadows. - Strong alignment, useful whitespace, and
dense-but-readable admin screens. - Realistic empty, loading, success,
error, and disabled states. - Small, purposeful transitions that improve
feedback. - Consistent icons from one icon set (Lucide preferred); never
use emoji as UI icons. - Useful sample content only where it makes the
interface understandable.

## 2. Technology Constraints

-   Backend: Core PHP 8.2+, organized using a lightweight MVC structure.
-   Database: MySQL with PDO.
-   Frontend: HTML, Tailwind CSS, and Vanilla JavaScript.
-   Use Fetch API for asynchronous requests.
-   No Laravel, React, Vue, or other frontend framework for this
    version.
-   A JavaScript graph/node library may be considered for the visual
    builder if compatible with the project constraints; otherwise build
    the graph UI with Vanilla JS and SVG.
-   Separate UI presentation from backend business logic.
-   All forms, buttons, menus, filters, connections, and navigation must
    perform their stated action.
-   Do not fake persistence: save data through PHP endpoints and MySQL.
-   Use server-side validation even when client-side validation exists.

## 3. Product Information Architecture

### Admin navigation

Use a persistent left sidebar on desktop and a compact responsive
navigation pattern on smaller screens.

**Primary navigation** 1. Overview 2. Funnels 3. Submissions 4.
Analytics 5. Form Fields / reusable fields (optional) 6. Settings

**Sidebar footer** - Admin profile - Help / documentation - Sign out

### Main routes / screens

-   `/admin/dashboard.php` --- Overview
-   `/admin/funnels.php` --- Funnel list
-   `/admin/funnel-create.php` --- Create funnel
-   `/admin/funnel-builder.php?id={id}` --- Visual builder
-   `/admin/funnel-settings.php?id={id}` --- Funnel settings and
    pre-quiz form
-   `/admin/submissions.php` --- Submission list
-   `/admin/submission-view.php?id={id}` --- Full journey detail
-   `/admin/analytics.php?id={id}` --- Funnel analytics
-   `/admin/settings.php` --- Account and application settings
-   `/quiz/{slug}` --- Public quiz experience (routing may be
    implemented through PHP rewrite rules)
-   `/quiz/{slug}/result/{token}` --- Secure result summary, if enabled

Adapt filenames to the existing project, but preserve these screen
responsibilities.

## 4. Visual System

### Overall look

Use a light, neutral workspace with clear contrast and restrained
accents. The admin UI should feel like a professional workflow tool
rather than a marketing landing page.

Suggested palette (CSS variables / Tailwind theme): - App background:
`#F7F8FA` - Main surface: `#FFFFFF` - Primary text: `#171A21` -
Secondary text: `#687080` - Border: `#E4E7EC` - Muted surface:
`#F1F3F6` - Primary accent: `#5B5BD6` (use sparingly) - Success:
`#16835D` - Warning: `#A86613` - Danger: `#C23B42` - Focus ring:
accessible accent with visible contrast

These are starting tokens, not a reason to force color where it is not
needed. Check text contrast and focus visibility.

### Typography

-   Use a clean UI sans-serif such as Inter or a system sans-serif
    stack.
-   Page title: 24--30px, medium/semibold.
-   Section title: 16--20px, semibold.
-   Body: 14--16px.
-   Labels and helper text: 12--14px.
-   Use line-height and font weights consistently; avoid oversized text
    in admin pages.

### Spacing and shape

-   Use a consistent 4px-based spacing scale.
-   Inputs and buttons: approximately 8--10px radius.
-   Panels: approximately 10--14px radius where useful.
-   Prefer borders over heavy shadows.
-   Keep content widths controlled; tables and builders may use the full
    available width.
-   Avoid card nesting. Use dividers and section spacing instead.

### Interaction

-   Hover, focus, pressed, disabled, loading, and error states must be
    designed.
-   Use short transitions (roughly 120--200ms) for hover and step
    changes.
-   Respect `prefers-reduced-motion`.
-   Never use animation that delays essential interaction.
-   Keyboard navigation and visible focus rings are required.

## 5. Admin Dashboard

### Layout

-   Left sidebar: stable width around 230--250px on desktop.
-   Top header: breadcrumb or page context, page actions, and admin
    profile.
-   Main content: responsive grid with consistent gutters.
-   On smaller screens, collapse the sidebar into a drawer or compact
    menu.

### Overview content

Show useful, real metrics: - Total funnels - Published funnels - Quiz
starts - Completed quizzes - Captured leads - Completion rate

Include: - Recent funnels table - Recent submissions - A small, readable
funnel performance visualization only if actual data is available -
Helpful empty state when no funnels exist

Do not invent analytics values. When the database is empty, show zero
states and an action to create the first funnel.

## 6. Funnel List

Display funnels in a searchable, filterable table or clean list.

Columns: - Funnel name - Public slug / URL - Status: Draft or
Published - Starts - Completions - Last updated - Actions

Actions: - Open builder - Preview - Edit settings - Duplicate - Publish
/ unpublish - Delete with confirmation

Requirements: - Search by funnel name. - Filter by status. - Clear empty
state with "Create funnel". - Confirm destructive actions. - Show clear
save/publish feedback. - Do not rely on color alone to communicate
status.

## 7. Visual Branching Funnel Builder

This is the core product screen and must receive the most design
attention.

### Desktop layout

Use a full-height workspace below the global header with three zones:

1.  **Left node palette** --- around 220px.
2.  **Central canvas** --- flexible, largest area.
3.  **Right inspector panel** --- around 300--340px, opens when a node
    or edge is selected.

Header actions: - Back to funnels - Funnel name (inline edit if
practical) - Draft / Published status - Undo / redo if supported -
Preview - Validate flow - Save status - Publish

### Left node palette

Provide draggable or click-to-add node types: - Start - Question - End /
Destination

Optional future types: - Condition - Information / content screen

Each palette item needs a small icon, label, and one-line explanation.
Keep the palette compact.

### Canvas

-   Neutral canvas background with a subtle dot grid; do not make the
    grid visually dominant.
-   Nodes should be compact, legible, and visually distinct by type.
-   SVG or library-based connectors should route cleanly between node
    handles.
-   Support pan and zoom, fit-to-view, selection, moving nodes, and
    connection creation.
-   Show clear selected-node state.
-   Provide minimap only if useful and not visually distracting.
-   Add an accessible canvas toolbar for zoom in/out, fit view, and
    undo/redo where supported.
-   Support delete/backspace only when a canvas node is selected and
    focus is not inside an input.
-   Show a useful empty canvas state with a "Add first question" action.
-   Never allow a connection to appear saved if it has not been
    persisted.

### Node design

**Start node** - Label: Start - Short description of the public entry
point - One outgoing connection

**Question node** - Question title, truncated safely if long - Question
type - Number of options - Each option visible as a separate connection
row / handle - Show an outgoing connector per option - Clear warning
when an option has no next destination

**End / Destination node** - Destination name - URL host/path preview -
Visual indication that this is a terminal node - Warning when URL is
missing or invalid

### Right inspector panel

When a Question node is selected, provide: - Question text - Help text /
description (optional) - Question type: single choice, multiple choice,
text, email, number, etc. - Required toggle - Options editor with add,
edit, remove, reorder - For choice options: label and stored value -
Connection target for every option - Validation rules appropriate to the
field type - Delete node action with confirmation if it has connections

When a Destination node is selected: - Destination name - Landing page
URL - Optional tracking settings - URL validation status - Open URL in a
new tab for preview (only after validation)

When the canvas background is selected: - Funnel-level settings or a
compact instruction panel.

### Connection behavior

The most important rule: **each option can route to its own next
question or destination**.

Example: - Profession = Doctor → Doctor type - Profession = Engineer →
Engineering specialization - Profession = Lawyer → Legal practice type -
Doctor type = Psychiatrist → Practice city - Practice city = Delhi →
Delhi area - Delhi area = Saket → Saket landing page

Connection creation must be explicit and visually understandable. If an
option has no target, show a warning in the node and in the validation
panel.

### Flow validation

Provide a validation drawer or panel that identifies: - Missing outgoing
route for an option - Unreachable nodes - Missing start node - Missing
or invalid destination URL - Invalid or broken connections - Accidental
cycles, unless cycles are explicitly supported - Questions with no
options when options are required

Each issue should link to the relevant node. Publishing must be blocked
for critical errors, with a clear explanation.

### Saving behavior

-   Show states: "Unsaved changes", "Saving...", "Saved", and "Save
    failed".
-   Use debounced saving for node position changes, but do not debounce
    critical answer/configuration changes in a way that risks data loss.
-   Handle request failures and provide retry.
-   Warn before leaving with unsaved changes.
-   Keep the graph configuration and question/option data consistent in
    MySQL transactions where appropriate.
-   Do not save only in browser storage and claim that the funnel is
    saved.

## 8. Pre-Quiz Form Builder

Each funnel has a configurable form shown before the first quiz
question.

### Form settings screen

Sections: 1. Form heading and description 2. Field list 3. Field editor
4. Required / optional 5. Field order 6. Validation and option settings
7. Consent / privacy text 8. Submit button label 9. Preview

Supported field types: - Short text - Email - Phone - Number -
Dropdown - Single select - Multi-select (only if supported by the quiz
engine) - Textarea - Date (optional) - Consent checkbox

Each field can configure: - Label - Stable field key - Placeholder -
Help text - Required flag - Validation rules - Options for select-type
fields - Display order - Active / disabled state

Design requirements: - Field list on the left or top, editor on the
right or below on small screens. - Live preview of the public form. -
Add-field menu with clear labels. - Reorder fields using drag-and-drop
or accessible move controls. - Confirm deletion if existing submissions
reference a field. - Validate field keys and prevent duplicate keys. -
Explain that changing a field does not rewrite historical submissions. -
Consent must be explicit; do not pre-check consent by default.

## 9. Public Quiz Experience

The public quiz must feel fast, calm, and focused. It should not look
like an admin dashboard.

### Public flow

1.  Welcome / quiz intro
2.  Pre-quiz form
3.  One question per screen (default)
4.  Branching question path
5.  Review / result summary
6.  Optional secure result link
7.  Final destination CTA or redirect

### Public page visual rules

-   Center the main content with a comfortable maximum width, typically
    560--720px.
-   Use generous spacing around the question and answer choices.
-   Keep background neutral and use one brand accent.
-   Use a clear progress indicator. Since branching paths vary, show
    "Step 3" or a progress estimate only when the estimate is valid; do
    not show a misleading fixed total.
-   Answer options should have a large, accessible hit area.
-   Selected options need a clear border/background and a non-color-only
    selected indicator.
-   Keep Back and Continue controls consistent.
-   On mobile, make controls easy to tap and keep primary actions
    reachable.
-   Provide inline errors rather than disruptive alert boxes.
-   Use skeleton/loading feedback for network requests.
-   Transition between questions subtly and quickly.
-   Preserve answers when going Back.
-   Do not auto-advance in a way that prevents users from confirming a
    choice unless the funnel author explicitly enables it.
-   If a visitor refreshes, resume only when a valid session token
    exists.
-   If the network fails, preserve the current unsent answer locally
    temporarily and offer retry; do not treat local data as the
    authoritative saved record.

### Pre-quiz form UX

-   Explain why information is requested when appropriate.
-   Clearly mark required fields.
-   Validate on submit and show errors next to the relevant fields.
-   Disable duplicate submission while saving.
-   Do not start the quiz until required form fields are valid and
    saved.
-   Show a concise privacy notice if personal data is collected.

### Result / answer summary

-   Show the questions actually visited, in visit order.
-   Show only answers the user actually submitted.
-   Provide an edit/review action only if editing is supported safely.
-   Explain the next action and show the destination CTA.
-   If auto-redirect is enabled, give the user a clear chance to
    view/copy their summary or configure a sensible delay.
-   Secure result links must use unpredictable tokens, not sequential
    database IDs.

## 10. Submission List and Journey Detail

### Submission list

Columns: - Visitor / lead identifier - Funnel - Started at - Completed
at - Status - Final destination - Actions

Features: - Search - Funnel filter - Status filter - Date filter -
Pagination - CSV export only for authorized admins - Empty, loading, and
error states

### Journey detail

Show a chronological timeline: - Form submitted - Quiz started -
Question viewed - Answer submitted - Branch selected - Quiz completed -
Destination selected - Redirect attempted / confirmed where measurable

For each visited question, show: - Question text as it existed for that
submission, where practical - Submitted answer - Selected option -
Timestamp - Next node / route taken

Do not show unvisited questions as answered. Historical submissions must
remain interpretable if the admin later edits the funnel; use versioning
or store question/option snapshots as appropriate.

Protect personal data and restrict access to authorized admins.

## 11. Analytics

Show only metrics backed by stored events: - Unique starts -
Completions - Completion rate - Lead capture rate - Step-by-step
drop-off - Most selected options - Branch path counts - Final
destination counts

Avoid decorative charts without meaningful data. If the dataset is too
small or empty, show an honest empty state. Label date ranges and
calculation definitions.

## 12. Responsive Behavior

### Desktop

-   Persistent sidebar.
-   Builder uses three-column workspace.
-   Tables can use full available width.

### Tablet

-   Sidebar can collapse.
-   Inspector can become a slide-over panel.
-   Builder controls remain accessible.

### Mobile

-   Public quiz is fully responsive and touch-friendly.
-   Admin tables become stacked rows or controlled horizontal scroll.
-   Builder canvas can pan and zoom, but complex graph editing may show
    a clear "Best experienced on desktop" notice while keeping
    inspection and basic edits usable.
-   Do not shrink the entire desktop builder until it is unreadable.

## 13. Accessibility

-   Use semantic HTML and correct labels.
-   Every input has a programmatic label.
-   Keyboard access for menus, dialogs, forms, and node editor controls.
-   Visible focus states.
-   Sufficient color contrast.
-   Do not rely on color alone for status or selected states.
-   Accessible error messages and focus management.
-   Respect reduced motion preferences.
-   Provide non-drag alternatives for reordering and connecting where
    feasible.

## 14. UI States That Must Be Designed

For every major screen, include: - Default - Loading - Empty - Success -
Validation error - Server/network error - Disabled - Confirmation dialog
for destructive actions - Unsaved changes warning

Never leave a blank page or silently fail.

## 15. Microcopy

Use clear, concise language. Examples: - "Add your first question" -
"This option has no next step" - "Your changes are saved" - "Fix 2
issues before publishing" - "No submissions yet" - "Quiz saved, but
publishing failed. Retry." - "This destination URL is invalid" - "This
answer was not submitted" - "Your result is ready"

Avoid vague messages such as "Oops!", "Something magical happened", or
generic marketing filler.

## 16. Security and Privacy UI

-   Do not expose internal database IDs in public result URLs.
-   Do not show one visitor's submission to another visitor.
-   Show consent text only when configured by the admin; never preselect
    consent.
-   Keep personal data access restricted.
-   Validate external destination URLs and prevent open redirects.
-   Confirm before deleting funnels, nodes, fields, or submissions.
-   Warn admins when a field change affects future form behavior but not
    historical submissions.
-   Use clear session-expiry and retry states.

## 17. Acceptance Criteria

The UI is not complete until:

1.  Admin can create, edit, reorder, connect, and delete nodes.
2.  Every choice option can route to a different question or
    destination.
3.  The inspector edits the selected node and reflects changes
    immediately.
4.  Save status is honest and reflects server persistence.
5.  The validation panel identifies broken or incomplete flows.
6.  Admin can configure pre-quiz fields and preview the form.
7.  Public visitors can submit the form, answer questions, navigate
    Back, and complete the actual branch path.
8.  Answers and journey events persist in MySQL.
9.  Admin can inspect a submission's actual path and final destination.
10. Users can view their own answer summary through the configured
    result flow.
11. Responsive layouts, keyboard navigation, errors, loading, and empty
    states work.
12. The interface avoids generic AI-generated visual clichés and uses a
    consistent design system.

## 18. Final Instruction to the Implementer

Prioritize usability, consistency, and real functionality over visual
decoration. Build the admin experience as a serious workflow tool and
the public quiz as a fast, focused experience. Use realistic content and
a restrained visual system. Do not add features or visual effects merely
to make the UI look busy. Every visible control must work, every saved
state must be real, and every user journey must be traceable.

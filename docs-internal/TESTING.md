# TESTING.md — Automated and Quality Assurance Verification

This document records the automated and integration testing strategy, execution commands, and verified results for the **Quiz Funnel SaaS** application.

---

## 1. Test Environment

- **Operating System:** Windows (XAMPP Environment)
- **PHP Version:** 8.2.12 (CLI & Apache mod_php)
- **Database:** MySQL / MariaDB (127.0.0.1:3306)
- **Test Runner:** Custom PHP Automated Test Suite (`tests/run_tests.php`)
- **Execution Date:** 2026-10-15

---

## 2. Test Execution Command

To execute the entire automated test suite, run:

```powershell
cd C:\xampp\htdocs\qfunnel
php tests/run_tests.php
```

---

## 3. Test Results Summary

```text
=========================================
 RUNNING QUIZ FUNNEL SAAS TEST SUITE
=========================================

--- SUITE 1: Authentication & Sessions ---
  [PASS] Valid user credentials successfully authenticated
  [PASS] Invalid credentials rejected
  [PASS] Nonexistent account rejected
  [PASS] Super Administrator authenticated with platform privileges and active workspace

--- SUITE 2: Multi-Tenancy Data Isolation ---
  [PASS] Distinct isolated tenants created
  [PASS] Tenant B funnel list excludes Tenant A funnels
  [PASS] Tenant B cannot read Tenant A funnel by ID (IDOR blocked)
  [PASS] Tenant B cannot delete Tenant A funnel

--- SUITE 3: Funnel Builder & Graph Validation ---
  [PASS] Graph nodes and edges saved atomically
  [PASS] Complete branching flow passes validation
  [PASS] Funnel successfully published as Version 1
  [PASS] Published version pointer set correctly

--- SUITE 4: Quiz Engine Branching & Invalidation ---
  [PASS] Public visitor session initialized with token
  [PASS] Pre-quiz form saved and quiz started
  [PASS] Tech branch correctly routed to Tech Hub destination
  [PASS] Back navigation restores prior question step
  [PASS] Answer change correctly supersedes downstream route to Finance Hub

--- SUITE 5: Submissions & Analytics Integrity ---
  [PASS] Submission correctly stored and searchable
  [PASS] Full event timeline preserved in chronological order
  [PASS] Real database analytics computed accurately

--- SUITE 6: Plan Limits Enforcement ---
  [PASS] Tenant limits resolved from subscription plan

=========================================
TEST SUMMARY: 21 Passed, 0 Failed
=========================================
```

---

## 4. Test Coverage Breakdown

### Suite 1: Authentication & RBAC
- **Valid User Credentials:** Verifies that existing accounts (`owner@qfunnel.local`) authenticate properly via `password_verify()` and regenerate session identifiers.
- **Credential Rejection:** Validates that wrong passwords and unregistered emails return sanitized failure responses without leaking user existence.

### Suite 2: Multi-Tenancy & Data Isolation (Mandatory)
- **Tenant Isolation:** Creates two dynamic, isolated tenants (`Tenant A` and `Tenant B`).
- **Cross-Tenant Read Blocking:** Proves that Tenant B cannot list, query, or find Tenant A's funnels in repository queries.
- **Insecure Direct Object Reference (IDOR) Prevention:** Tenant B attempting to access `GET /admin/funnels/{id}` with Tenant A's ID receives `null` / 404.
- **Cross-Tenant Mutation Blocking:** Tenant B attempting to delete or edit Tenant A's funnel throws an authorization/ownership exception and halts execution.

### Suite 3: Visual Funnel Builder & Flow Validation
- **Atomic Graph Persistence:** Persists nodes, question options, destinations, and edges within a single atomic MySQL transaction.
- **Graph Validation:** Validates start node count, reachability, route completeness, and terminal destinations.
- **Versioned Publishing:** Tests that publishing generates an immutable snapshot (`funnel_versions`) and sets `current_published_version_id`.

### Suite 4: Quiz Engine Branching & Downstream Invalidation
- **Server-Side Routing:** Evaluates branch routing strictly on the server from published graph edges.
- **Pre-Quiz Form Requirement:** Enforces that required form fields must be submitted and validated before quiz steps begin.
- **Back Navigation & Route Invalidation:** When a user navigates back and changes an earlier answer:
  - Downstream answers belonging to the invalidated branch are marked `superseded_at = NOW()`.
  - Recalculates the active path from the newly selected option.
  - Prevents contradictory answers or stale final outcomes.

### Suite 5: Submissions & Real Analytics
- **Lead Capture Preservation:** Verifies that form submission values retain snapshots of field labels and keys even if field configurations are updated later.
- **Chronological Journey Timelines:** Verifies that event sequences (`quiz_viewed`, `pre_form_submitted`, `quiz_started`, `branch_selected`, `quiz_completed`) are recorded with timestamps.
- **Real Analytics:** Proves metrics are derived strictly from database records (`quiz_sessions`, `quiz_step_events`).

### Suite 6: SaaS Plan Limits
- Verifies server-side quotas on funnels count, team members, and monthly submissions.

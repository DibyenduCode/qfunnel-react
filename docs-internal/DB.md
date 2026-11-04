# DB.md --- MySQL Database Design

## 1. Purpose

Define a relational MySQL schema for a multi-tenant SaaS Quiz Funnel
Builder. The database must store tenants, users, funnels, visual graph
nodes/edges, question options, configurable pre-quiz forms, quiz
sessions, answers, journey events, destinations, results, plans, and
usage.

Use MySQL 8+ with InnoDB, `utf8mb4`, foreign keys, appropriate indexes,
and UTC timestamps. Use migrations rather than editing production schema
manually.

## 2. Database Design Principles

-   Every customer workspace is isolated by `tenant_id`.
-   Use integer or BIGINT internal primary keys; use random public
    tokens for visitor-facing links.
-   Keep stable internal IDs separate from public slugs/tokens.
-   Use relational columns for data that must be queried and indexed.
-   Use JSON only for flexible configuration that is not the primary
    relationship mechanism.
-   Keep published funnel versions immutable for historical session
    consistency.
-   Historical submissions must remain interpretable after a funnel is
    edited.
-   Use transactions for graph saves, answer routing, and completion.
-   Store timestamps in UTC and format for the viewer's timezone.
-   Define retention and deletion policies for personal data.

## 3. Core Tables

### `tenants`

-   `id` BIGINT UNSIGNED PK
-   `name` VARCHAR(160)
-   `slug` VARCHAR(100) UNIQUE
-   `status` ENUM('active','suspended','deleted')
-   `created_at`, `updated_at`

### `users`

-   `id` BIGINT UNSIGNED PK
-   `name` VARCHAR(160)
-   `email` VARCHAR(190) UNIQUE (or tenant-scoped if account model
    requires it)
-   `password_hash` VARCHAR(255)
-   `status` ENUM('active','disabled')
-   `last_login_at` DATETIME NULL
-   `created_at`, `updated_at`

### `tenant_memberships`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `user_id` FK
-   `role` ENUM('owner','admin','editor','analyst')
-   `created_at`
-   UNIQUE (`tenant_id`, `user_id`)
-   Index (`user_id`, `tenant_id`)

### `plans`

-   `id` BIGINT UNSIGNED PK
-   `name` VARCHAR(100)
-   `limits_json` JSON
-   `features_json` JSON
-   `is_active` TINYINT(1)

### `tenant_subscriptions`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `plan_id` FK
-   `status` ENUM('trial','active','past_due','cancelled','expired')
-   `starts_at`, `ends_at` NULL
-   `provider`, `provider_subscription_id` NULL
-   Index (`tenant_id`, `status`)

### `funnels`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `name` VARCHAR(180)
-   `slug` VARCHAR(160)
-   `description` TEXT NULL
-   `status` ENUM('draft','published','archived')
-   `current_published_version_id` BIGINT UNSIGNED NULL
-   `settings_json` JSON
-   `created_by` FK users
-   `created_at`, `updated_at`
-   UNIQUE (`tenant_id`, `slug`)
-   Index (`tenant_id`, `status`, `updated_at`)

### `funnel_versions`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `version_number` INT UNSIGNED
-   `graph_snapshot_json` JSON
-   `form_schema_snapshot_json` JSON
-   `settings_snapshot_json` JSON
-   `published_by` FK users NULL
-   `published_at` DATETIME
-   UNIQUE (`funnel_id`, `version_number`)
-   Index (`tenant_id`, `funnel_id`, `published_at`)

A published version is immutable. Public sessions reference this
version.

### `funnel_nodes`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `node_key` VARCHAR(80)
-   `node_type` ENUM('start','question','destination')
-   `title` VARCHAR(255) NULL
-   `position_x` DECIMAL(10,2)
-   `position_y` DECIMAL(10,2)
-   `config_json` JSON NULL
-   `created_at`, `updated_at`
-   UNIQUE (`funnel_id`, `node_key`)
-   Index (`tenant_id`, `funnel_id`, `node_type`)

### `questions`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `node_id` FK funnel_nodes
-   `question_text` TEXT
-   `help_text` TEXT NULL
-   `question_type`
    ENUM('single_choice','multiple_choice','text','email','number','dropdown')
-   `is_required` TINYINT(1)
-   `validation_json` JSON NULL
-   `display_order` INT UNSIGNED
-   `created_at`, `updated_at`
-   UNIQUE (`node_id`)

### `question_options`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `question_id` FK
-   `option_key` VARCHAR(80)
-   `label` VARCHAR(255)
-   `value` VARCHAR(255)
-   `display_order` INT UNSIGNED
-   `created_at`, `updated_at`
-   UNIQUE (`question_id`, `option_key`)
-   Index (`tenant_id`, `question_id`, `display_order`)

### `funnel_edges`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `source_node_id` FK funnel_nodes
-   `source_option_id` FK question_options NULL (NULL for start edge if
    supported)
-   `target_node_id` FK funnel_nodes
-   `created_at`, `updated_at`
-   Index (`funnel_id`, `source_node_id`)
-   Index (`funnel_id`, `target_node_id`)
-   Unique routing constraint appropriate to source node and option

Application validation must ensure all nodes, options, and edges belong
to the same tenant and funnel. If enforcing composite foreign keys, add
matching unique keys such as `(funnel_id, id)` and use them
consistently.

### `destinations`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `node_id` FK funnel_nodes
-   `name` VARCHAR(180)
-   `url` TEXT
-   `allowed_host` VARCHAR(255) NULL
-   `tracking_config_json` JSON NULL
-   `created_at`, `updated_at`
-   UNIQUE (`node_id`)
-   Index (`tenant_id`, `funnel_id`)

## 4. Pre-Quiz Form Tables

### `form_fields`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `field_key` VARCHAR(100)
-   `label` VARCHAR(180)
-   `field_type`
    ENUM('text','email','phone','number','dropdown','single_select','multi_select','textarea','date','consent')
-   `placeholder` VARCHAR(255) NULL
-   `help_text` TEXT NULL
-   `is_required` TINYINT(1)
-   `validation_json` JSON NULL
-   `display_order` INT UNSIGNED
-   `is_active` TINYINT(1)
-   `created_at`, `updated_at`
-   UNIQUE (`funnel_id`, `field_key`)
-   Index (`tenant_id`, `funnel_id`, `display_order`)

### `form_field_options`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `field_id` FK form_fields
-   `label` VARCHAR(255)
-   `value` VARCHAR(255)
-   `display_order` INT UNSIGNED
-   Index (`field_id`, `display_order`)

### `form_submissions`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `funnel_version_id` FK NULL
-   `session_id` FK quiz_sessions (create FK after quiz_sessions exists)
-   `submitted_at` DATETIME
-   Index (`tenant_id`, `funnel_id`, `submitted_at`)

### `form_submission_values`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `form_submission_id` FK form_submissions
-   `field_id` BIGINT UNSIGNED NULL (may be removed later)
-   `field_key_snapshot` VARCHAR(100)
-   `field_label_snapshot` VARCHAR(180)
-   `field_type_snapshot` VARCHAR(40)
-   `value_text` LONGTEXT NULL
-   `value_json` JSON NULL
-   `created_at`
-   Index (`tenant_id`, `form_submission_id`)
-   Index (`field_key_snapshot`)

Snapshots preserve the original field label and key if the admin changes
the form later. Store consent timestamp and policy/version details when
consent is collected.

## 5. Quiz Session and Journey Tables

### `quiz_sessions`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `funnel_version_id` FK
-   `public_token_hash` CHAR(64) UNIQUE
-   `status`
    ENUM('form_pending','in_progress','completed','abandoned','expired')
-   `current_node_id` BIGINT UNSIGNED NULL
-   `started_at` DATETIME NULL
-   `completed_at` DATETIME NULL
-   `last_activity_at` DATETIME
-   `expires_at` DATETIME
-   `final_destination_id` BIGINT UNSIGNED NULL
-   `created_at`
-   Index (`tenant_id`, `funnel_id`, `status`, `created_at`)
-   Index (`funnel_id`, `funnel_version_id`, `status`)
-   Index (`expires_at`)

Only a hash of a cryptographically random public token should be stored
when feasible. Never expose the numeric session ID as a public access
credential.

### `quiz_answers`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `session_id` FK quiz_sessions
-   `funnel_version_id` FK
-   `node_id` BIGINT UNSIGNED
-   `question_id` BIGINT UNSIGNED NULL
-   `question_text_snapshot` TEXT
-   `answer_type` VARCHAR(40)
-   `answer_value_text` LONGTEXT NULL
-   `answer_value_json` JSON NULL
-   `selected_option_ids_json` JSON NULL
-   `visit_number` INT UNSIGNED DEFAULT 1
-   `answered_at` DATETIME
-   `superseded_at` DATETIME NULL
-   Index (`tenant_id`, `session_id`, `answered_at`)
-   Index (`session_id`, `node_id`, `visit_number`)

Define one clear policy for Back navigation: update/supersede a previous
answer and recompute the path, or keep explicit attempts. Do not let
stale downstream answers silently remain valid after an upstream answer
changes.

### `quiz_step_events`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `funnel_version_id` FK
-   `session_id` FK
-   `event_type` VARCHAR(50)
-   `node_id` BIGINT UNSIGNED NULL
-   `option_id` BIGINT UNSIGNED NULL
-   `event_data_json` JSON NULL
-   `occurred_at` DATETIME
-   Index (`tenant_id`, `session_id`, `occurred_at`)
-   Index (`tenant_id`, `funnel_id`, `event_type`, `occurred_at`)
-   Keep event payloads minimal and avoid sensitive personal data.

### `quiz_results`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `funnel_id` FK
-   `funnel_version_id` FK
-   `session_id` FK
-   `result_token_hash` CHAR(64) UNIQUE NULL
-   `summary_json` JSON
-   `destination_id` BIGINT UNSIGNED NULL
-   `redirect_status` ENUM('pending','attempted','failed','confirmed')
    DEFAULT 'pending'
-   `completed_at` DATETIME
-   UNIQUE (`session_id`)

A browser redirect can be recorded as attempted; do not label it
confirmed unless the destination can reliably report back or
confirmation is otherwise measured.

## 6. SaaS and Operational Tables

### `usage_counters` (optional for initial version)

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK
-   `metric_key` VARCHAR(80)
-   `period_start` DATE
-   `count_value` BIGINT UNSIGNED
-   UNIQUE (`tenant_id`, `metric_key`, `period_start`)

### `audit_logs`

-   `id` BIGINT UNSIGNED PK
-   `tenant_id` FK NULL for platform-level events
-   `user_id` FK users NULL
-   `action` VARCHAR(100)
-   `resource_type` VARCHAR(80)
-   `resource_id` BIGINT UNSIGNED NULL
-   `metadata_json` JSON NULL
-   `created_at`
-   Index (`tenant_id`, `created_at`)
-   Never log passwords, raw auth tokens, or unnecessary personal data.

## 7. Relationships Overview

-   Tenant → Users through memberships.
-   Tenant → Funnels → Funnel Versions.
-   Funnel → Nodes → Questions / Destinations.
-   Question → Options.
-   Node + Option → Edge → Target Node.
-   Funnel Version → Quiz Sessions.
-   Session → Form Submission, Answer History, Journey Events, Result.
-   Tenant Subscription → Plan and usage limits.

## 8. Data Integrity Requirements

-   Use foreign keys and InnoDB where practical.
-   Use unique constraints for tenant-scoped slugs, field keys, node
    keys, option keys, and one result per session.
-   Validate cross-table tenant/funnel consistency in the service layer;
    use composite foreign keys when the schema can support them cleanly.
-   Wrap graph updates in a transaction.
-   Wrap answer save + next-route selection + session update in a
    transaction.
-   Keep published versions immutable.
-   Define cascade behavior carefully; do not cascade-delete submissions
    and analytics unintentionally when a funnel is deleted. Prefer soft
    deletion or retention rules.
-   Paginate large tables.
-   Add indexes based on actual query plans; do not index every column
    blindly.

## 9. Migration Order

1.  tenants
2.  users
3.  tenant_memberships
4.  plans and tenant_subscriptions
5.  funnels
6.  funnel_versions
7.  funnel_nodes
8.  questions and question_options
9.  funnel_edges and destinations
10. form_fields and form_field_options
11. quiz_sessions
12. form_submissions and form_submission_values
13. quiz_answers
14. quiz_step_events
15. quiz_results
16. usage_counters and audit_logs

Add foreign keys after dependent tables exist. Review circular
references before writing migrations.

## 10. Important Implementation Note

This document is a logical schema specification, not a finished
executable migration. Before deployment, normalize column types,
finalize all foreign keys, decide whether form values need searchable
normalized columns, test uniqueness constraints, and create
migration/rollback scripts.

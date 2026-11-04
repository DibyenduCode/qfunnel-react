<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class FunnelService
{
    public static function listFunnels(int $tenantId, ?string $search = null, ?string $status = null, ?int $page = null, int $perPage = 10): array
    {
        $sql = "SELECT f.*, 
                       (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.funnel_id = f.id) as total_starts,
                       (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.funnel_id = f.id AND qs.status = 'completed') as total_completions,
                       u.name as creator_name
                FROM funnels f
                LEFT JOIN users u ON f.created_by = u.id
                WHERE f.tenant_id = ?";
        $params = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (f.name LIKE ? OR f.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($status) && in_array($status, ['draft', 'published', 'archived'], true)) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
        }

        if ($page === null) {
            $sql .= " ORDER BY f.updated_at DESC";
            return Database::query($sql, $params);
        }

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_funnels_table";
        $totalRows = (int) Database::fetchColumn($countSql, $params);

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql .= " ORDER BY f.updated_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = Database::query($sql, $params);

        return [
            'items' => $items,
            'total' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalRows / $perPage),
        ];
    }

    public static function getFunnel(int $tenantId, int $funnelId): ?array
    {
        return Database::fetchOne(
            "SELECT f.*, u.name as creator_name 
             FROM funnels f 
             LEFT JOIN users u ON f.created_by = u.id 
             WHERE f.tenant_id = ? AND f.id = ?",
            [$tenantId, $funnelId]
        );
    }

    public static function getPublishedFunnelBySlug(string $slug): ?array
    {
        return Database::fetchOne(
            "SELECT f.*, t.name as tenant_name, t.slug as tenant_slug, t.status as tenant_status, fv.graph_snapshot_json, fv.form_schema_snapshot_json, fv.settings_snapshot_json, fv.version_number
             FROM funnels f
             JOIN tenants t ON f.tenant_id = t.id
             JOIN funnel_versions fv ON f.current_published_version_id = fv.id
             WHERE f.slug = ? AND f.status = 'published' AND t.status IN ('active', 'suspended')",
            [$slug]
        );
    }

    public static function createFunnel(int $tenantId, array $data, int $userId): array
    {
        // Check plan limits
        $limitCheck = UsageLimitService::checkCanCreateFunnel($tenantId);
        if (!$limitCheck['allowed']) {
            throw new RuntimeException($limitCheck['message']);
        }

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new RuntimeException("Funnel name is required.");
        }

        $baseSlug = !empty($data['slug']) ? trim($data['slug']) : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'quiz-' . bin2hex(random_bytes(3));
        }

        // Ensure unique slug across all funnels for unambiguous public URLs
        $slug = $baseSlug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM funnels WHERE slug = ?", [$slug])) {
            $slug = "{$baseSlug}-" . (++$counter);
        }

        $description = $data['description'] ?? null;
        $funnelMode = (!empty($data['funnel_mode']) && in_array($data['funnel_mode'], ['quiz', 'calculator'], true)) ? $data['funnel_mode'] : 'quiz';
        
        if ($funnelMode === 'calculator' && !UsageLimitService::hasFeature($tenantId, 'calculator_mode') && !AuthorizationService::isPlatformAdmin()) {
            throw new RuntimeException("Calculator & Quote Mode is not enabled in your current subscription plan. Please upgrade to unlock interactive calculators.");
        }

        $settings = json_encode([
            'theme' => $data['theme'] ?? 'light',
            'accent_color' => $data['accent_color'] ?? ($funnelMode === 'calculator' ? '#10B981' : '#5B5BD6'),
            'enable_back_button' => true,
            'show_progress_bar' => true,
            'auto_redirect' => false,
            'redirect_delay_seconds' => 3,
        ]);

        return Database::transaction(function () use ($tenantId, $name, $slug, $description, $funnelMode, $settings, $userId) {
            Database::execute(
                "INSERT INTO funnels (tenant_id, name, slug, description, funnel_mode, status, settings_json, created_by) VALUES (?, ?, ?, ?, ?, 'draft', ?, ?)",
                [$tenantId, $name, $slug, $description, $funnelMode, $settings, $userId]
            );
            $funnelId = (int) Database::lastInsertId();

            $seedDemo = !empty($data['seed_demo']) || (isset($data['template']) && $data['template'] === 'demo');
            $startBlank = !empty($data['blank_canvas']) || (isset($data['template']) && $data['template'] === 'blank');

            if ($funnelMode === 'calculator' && !$startBlank) {
                // Seed Calculator Starter Graph
                // 1. Start Node
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y) VALUES (?, ?, 'start_1', 'start', 'Start', 1, 80, 200)",
                    [$tenantId, $funnelId]
                );
                $startNodeId = (int) Database::lastInsertId();

                // 2. Slider Question Node (@Q1)
                $sliderConfig = json_encode([
                    'variable_key' => 'Q1',
                    'input_type' => 'slider',
                    'slider_config' => [
                        'min_value' => 1,
                        'max_value' => 50,
                        'step_value' => 1,
                        'default_value' => 10,
                        'unit_label' => 'Pages',
                        'unit_prefix' => ''
                    ]
                ], JSON_UNESCAPED_SLASHES);

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'q_slider_1', 'question', 'Scope / Units Required', 0, 360, 200, ?)",
                    [$tenantId, $funnelId, $sliderConfig]
                );
                $sliderNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'How many units or pages do you need?', 'Move the slider to estimate your project scale', 'number', 1, 1)",
                    [$tenantId, $funnelId, $sliderNodeId]
                );

                // 3. Single-Choice Question Node (@Q2) with numeric values
                $choiceConfig = json_encode([
                    'variable_key' => 'Q2',
                    'input_type' => 'single_choice'
                ], JSON_UNESCAPED_SLASHES);

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'q_choice_2', 'question', 'Package Tier', 0, 680, 200, ?)",
                    [$tenantId, $funnelId, $choiceConfig]
                );
                $choiceNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'Select your desired quality or service tier:', 'Each option includes tailored features', 'single_choice', 1, 2)",
                    [$tenantId, $funnelId, $choiceNodeId]
                );
                $choiceQId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES 
                    (?, ?, 'opt_standard', 'Standard Plan (+₹500)', '500', 1),
                    (?, ?, 'opt_premium', 'Enterprise / Custom Plan (+₹1,500)', '1500', 2)",
                    [$tenantId, $choiceQId, $tenantId, $choiceQId]
                );
                $optStandardId = (int) Database::lastInsertId();
                $optPremiumId = $optStandardId + 1;

                // 4. Lead Form Node
                $formConfig = json_encode([
                    'fields' => [
                        ['id' => 1, 'field_key' => 'full_name', 'label' => 'Full Name', 'field_type' => 'text', 'placeholder' => 'Enter your full name', 'is_required' => 1],
                        ['id' => 2, 'field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'placeholder' => 'name@company.com', 'is_required' => 1],
                        ['id' => 3, 'field_key' => 'phone', 'label' => 'Phone / WhatsApp', 'field_type' => 'phone', 'placeholder' => '+91 98765 43210', 'is_required' => 0]
                    ],
                    'submit_action' => 'redirect',
                    'button_text' => 'Unlock Instant Quote',
                    'success_heading' => 'Quote Generated!',
                    'success_message' => 'Your instant estimate is ready below.'
                ], JSON_UNESCAPED_SLASHES);

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'form_lead_3', 'form', 'Unlock Instant Quote', 0, 1000, 200, ?)",
                    [$tenantId, $funnelId, $formConfig]
                );
                $formNodeId = (int) Database::lastInsertId();

                // 5. Quote Outcome Node
                $calcConfig = [
                    'enabled' => true,
                    'formula_expression' => '(@Q1 * 1000) + @Q2',
                    'result_label' => 'Your Estimated Project Cost',
                    'result_prefix' => '₹',
                    'result_suffix' => ' Total',
                    'decimal_places' => 0
                ];
                $destNodeConfig = json_encode(['calculator' => $calcConfig], JSON_UNESCAPED_SLASHES);

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'dest_quote_4', 'destination', 'Instant Quote Outcome', 0, 1320, 200, ?)",
                    [$tenantId, $funnelId, $destNodeConfig]
                );
                $destNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO destinations (tenant_id, funnel_id, node_id, name, url, tracking_config_json) VALUES (?, ?, ?, 'Instant Quote Result', 'https://example.com/quote-result', ?)",
                    [$tenantId, $funnelId, $destNodeId, json_encode(['calculator' => $calcConfig], JSON_UNESCAPED_SLASHES)]
                );

                // Wire up all edges
                // Start -> Slider Question
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)",
                    [$tenantId, $funnelId, $startNodeId, $sliderNodeId]
                );
                // Slider Question -> Choice Question
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)",
                    [$tenantId, $funnelId, $sliderNodeId, $choiceNodeId]
                );
                // Choice Question (Option 1) -> Form
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $funnelId, $choiceNodeId, $optStandardId, $formNodeId]
                );
                // Choice Question (Option 2) -> Form
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $funnelId, $choiceNodeId, $optPremiumId, $formNodeId]
                );
                // Form -> Quote Outcome
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)",
                    [$tenantId, $funnelId, $formNodeId, $destNodeId]
                );
            } elseif ($seedDemo) {
                // Seed standard Quiz Demo Template only if explicitly requested
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y) VALUES (?, ?, 'start_1', 'start', 'Start', 1, 80, 200)",
                    [$tenantId, $funnelId]
                );
                $startNodeId = (int) Database::lastInsertId();

                $qConfig = json_encode(['variable_key' => 'Q1', 'input_type' => 'single_choice'], JSON_UNESCAPED_SLASHES);
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'q_1', 'question', 'Primary Goal', 0, 360, 200, ?)",
                    [$tenantId, $funnelId, $qConfig]
                );
                $qNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'What is your primary business goal?', 'Select the option that best describes your objective', 'single_choice', 1, 1)",
                    [$tenantId, $funnelId, $qNodeId]
                );
                $qId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES 
                    (?, ?, 'opt_1', 'Increase Lead Conversions', '10', 1),
                    (?, ?, 'opt_2', 'Automate Quote Estimation', '20', 2)",
                    [$tenantId, $qId, $tenantId, $qId]
                );
                $opt1Id = (int) Database::lastInsertId();
                $opt2Id = $opt1Id + 1;

                $formConfig = json_encode([
                    'fields' => [
                        ['id' => 1, 'field_key' => 'full_name', 'label' => 'Full Name', 'field_type' => 'text', 'placeholder' => 'Enter your name', 'is_required' => 1],
                        ['id' => 2, 'field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'placeholder' => 'Enter your email', 'is_required' => 1]
                    ],
                    'submit_action' => 'redirect',
                    'button_text' => 'See My Recommendation',
                    'success_heading' => 'Thank You!',
                    'success_message' => 'Your personalized plan is ready.'
                ], JSON_UNESCAPED_SLASHES);

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, 'form_1', 'form', 'Lead Capture', 0, 680, 200, ?)",
                    [$tenantId, $funnelId, $formConfig]
                );
                $formNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y) VALUES (?, ?, 'dest_1', 'destination', 'Outcome Page', 0, 1000, 200)",
                    [$tenantId, $funnelId]
                );
                $destNodeId = (int) Database::lastInsertId();

                Database::execute(
                    "INSERT INTO destinations (tenant_id, funnel_id, node_id, name, url) VALUES (?, ?, ?, 'Recommended Solution', 'https://example.com/recommendation')",
                    [$tenantId, $funnelId, $destNodeId]
                );

                // Wire up Start -> Q1 -> (Opt 1, Opt 2) -> Form -> Dest
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)",
                    [$tenantId, $funnelId, $startNodeId, $qNodeId]
                );
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $funnelId, $qNodeId, $opt1Id, $formNodeId]
                );
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $funnelId, $qNodeId, $opt2Id, $formNodeId]
                );
                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)",
                    [$tenantId, $funnelId, $formNodeId, $destNodeId]
                );
            } else {
                // Clean Canvas: Only initial Start Step (Zero demo workflow clutter)
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y) VALUES (?, ?, 'start_1', 'start', 'Start', 1, 120, 220)",
                    [$tenantId, $funnelId]
                );
            }

            AuditLogService::log($tenantId, $userId, 'create_funnel', 'funnel', $funnelId, ['name' => $name, 'slug' => $slug, 'funnel_mode' => $funnelMode]);

            return self::getFunnel($tenantId, $funnelId);
        });
    }

    public static function updateFunnel(int $tenantId, int $funnelId, array $data, int $userId): array
    {
        $funnel = self::getFunnel($tenantId, $funnelId);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        $name = trim($data['name'] ?? $funnel['name']);
        $slug = !empty($data['slug']) ? trim($data['slug']) : $funnel['slug'];
        $description = $data['description'] ?? $funnel['description'];
        $funnelMode = in_array($data['funnel_mode'] ?? $funnel['funnel_mode'] ?? 'quiz', ['quiz', 'calculator'], true) 
            ? ($data['funnel_mode'] ?? $funnel['funnel_mode'] ?? 'quiz') 
            : 'quiz';

        $existingSettings = json_decode($funnel['settings_json'] ?? '{}', true) ?: [];
        $settingsToMerge = [];
        if (isset($data['settings']) && is_array($data['settings'])) {
            $settingsToMerge = $data['settings'];
        } elseif (isset($data['settings_json'])) {
            $settingsToMerge = is_array($data['settings_json']) 
                ? $data['settings_json'] 
                : (json_decode($data['settings_json'], true) ?: []);
        }
        $newSettings = array_merge($existingSettings, $settingsToMerge);
        $encodedSettings = json_encode($newSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Database::execute(
            "UPDATE funnels SET name = ?, slug = ?, description = ?, funnel_mode = ?, settings_json = ? WHERE tenant_id = ? AND id = ?",
            [$name, $slug, $description, $funnelMode, $encodedSettings, $tenantId, $funnelId]
        );

        if (!empty($funnel['current_published_version_id'])) {
            Database::execute(
                "UPDATE funnel_versions SET settings_snapshot_json = ? WHERE id = ? AND tenant_id = ?",
                [$encodedSettings, $funnel['current_published_version_id'], $tenantId]
            );
        }


        AuditLogService::log($tenantId, $userId, 'update_funnel_settings', 'funnel', $funnelId);

        return self::getFunnel($tenantId, $funnelId);
    }

    public static function duplicateFunnel(int $tenantId, int $funnelId, int $userId): array
    {
        $limitCheck = UsageLimitService::checkCanCreateFunnel($tenantId);
        if (!$limitCheck['allowed']) {
            throw new RuntimeException($limitCheck['message']);
        }

        $original = self::getFunnel($tenantId, $funnelId);
        if (!$original) {
            throw new RuntimeException("Funnel not found.");
        }

        $newName = $original['name'] . ' (Copy)';
        $newSlug = $original['slug'] . '-copy-' . bin2hex(random_bytes(2));

        return Database::transaction(function () use ($tenantId, $funnelId, $newName, $newSlug, $original, $userId) {
            Database::execute(
                "INSERT INTO funnels (tenant_id, name, slug, description, status, settings_json, created_by) VALUES (?, ?, ?, ?, 'draft', ?, ?)",
                [$tenantId, $newName, $newSlug, $original['description'], $original['settings_json'], $userId]
            );
            $newFunnelId = (int) Database::lastInsertId();

            // Duplicate nodes and map old ID to new ID
            $nodes = Database::query("SELECT * FROM funnel_nodes WHERE funnel_id = ?", [$funnelId]);
            $nodeMap = [];
            foreach ($nodes as $node) {
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y, config_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $newFunnelId, $node['node_key'], $node['node_type'], $node['title'], $node['position_x'], $node['position_y'], $node['config_json']]
                );
                $newNodeId = (int) Database::lastInsertId();
                $nodeMap[$node['id']] = $newNodeId;
            }

            // Duplicate questions and options
            $questions = Database::query("SELECT * FROM questions WHERE funnel_id = ?", [$funnelId]);
            $optionMap = [];
            foreach ($questions as $q) {
                if (!isset($nodeMap[$q['node_id']])) continue;
                $newNodeId = $nodeMap[$q['node_id']];

                Database::execute(
                    "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, validation_json, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $newFunnelId, $newNodeId, $q['question_text'], $q['help_text'], $q['question_type'], $q['is_required'], $q['validation_json'], $q['display_order']]
                );
                $newQuestionId = (int) Database::lastInsertId();

                $options = Database::query("SELECT * FROM question_options WHERE question_id = ?", [$q['id']]);
                foreach ($options as $opt) {
                    Database::execute(
                        "INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, ?, ?, ?, ?)",
                        [$tenantId, $newQuestionId, $opt['option_key'], $opt['label'], $opt['value'], $opt['display_order']]
                    );
                    $newOptionId = (int) Database::lastInsertId();
                    $optionMap[$opt['id']] = $newOptionId;
                }
            }

            // Duplicate destinations
            $destinations = Database::query("SELECT * FROM destinations WHERE funnel_id = ?", [$funnelId]);
            foreach ($destinations as $d) {
                if (!isset($nodeMap[$d['node_id']])) continue;
                $newNodeId = $nodeMap[$d['node_id']];
                Database::execute(
                    "INSERT INTO destinations (tenant_id, funnel_id, node_id, name, url, allowed_host, tracking_config_json) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $newFunnelId, $newNodeId, $d['name'], $d['url'], $d['allowed_host'], $d['tracking_config_json']]
                );
            }

            // Duplicate edges
            $edges = Database::query("SELECT * FROM funnel_edges WHERE funnel_id = ?", [$funnelId]);
            foreach ($edges as $edge) {
                if (!isset($nodeMap[$edge['source_node_id']]) || !isset($nodeMap[$edge['target_node_id']])) continue;
                $newSourceNodeId = $nodeMap[$edge['source_node_id']];
                $newTargetNodeId = $nodeMap[$edge['target_node_id']];
                $newSourceOptionId = $edge['source_option_id'] ? ($optionMap[$edge['source_option_id']] ?? null) : null;

                Database::execute(
                    "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $newFunnelId, $newSourceNodeId, $newSourceOptionId, $newTargetNodeId]
                );
            }

            // Duplicate form fields
            $formFields = Database::query("SELECT * FROM form_fields WHERE funnel_id = ?", [$funnelId]);
            foreach ($formFields as $ff) {
                Database::execute(
                    "INSERT INTO form_fields (tenant_id, funnel_id, field_key, label, field_type, placeholder, help_text, is_required, validation_json, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $newFunnelId, $ff['field_key'], $ff['label'], $ff['field_type'], $ff['placeholder'], $ff['help_text'], $ff['is_required'], $ff['validation_json'], $ff['display_order'], $ff['is_active']]
                );
                $newFieldId = (int) Database::lastInsertId();

                $ffOptions = Database::query("SELECT * FROM form_field_options WHERE field_id = ?", [$ff['id']]);
                foreach ($ffOptions as $ffo) {
                    Database::execute(
                        "INSERT INTO form_field_options (tenant_id, field_id, label, value, display_order) VALUES (?, ?, ?, ?, ?)",
                        [$tenantId, $newFieldId, $ffo['label'], $ffo['value'], $ffo['display_order']]
                    );
                }
            }

            AuditLogService::log($tenantId, $userId, 'duplicate_funnel', 'funnel', $newFunnelId, ['source_funnel_id' => $funnelId]);

            return self::getFunnel($tenantId, $newFunnelId);
        });
    }

    public static function unpublishFunnel(int $tenantId, int $funnelId, int $userId): array
    {
        $funnel = self::getFunnel($tenantId, $funnelId);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        Database::execute(
            "UPDATE funnels SET status = 'draft' WHERE tenant_id = ? AND id = ?",
            [$tenantId, $funnelId]
        );

        AuditLogService::log($tenantId, $userId, 'unpublish_funnel', 'funnel', $funnelId);

        return self::getFunnel($tenantId, $funnelId);
    }

    public static function deleteFunnel(int $tenantId, int $funnelId, int $userId): bool
    {
        $funnel = self::getFunnel($tenantId, $funnelId);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        Database::execute("DELETE FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
        AuditLogService::log($tenantId, $userId, 'delete_funnel', 'funnel', $funnelId, ['name' => $funnel['name']]);
        return true;
    }
}

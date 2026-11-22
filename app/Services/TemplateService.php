<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class TemplateService
{
    /**
     * Get distinct categories dynamically with counts and clean labels.
     */
    public static function getCategories(): array
    {
        // Auto-seed any category existing in workflow_templates into template_categories
        $orphanCats = Database::query(
            "SELECT DISTINCT wt.category 
             FROM workflow_templates wt 
             LEFT JOIN template_categories tc ON wt.category = tc.key_name 
             WHERE tc.id IS NULL AND wt.category IS NOT NULL AND wt.category != ''"
        );
        foreach ($orphanCats as $oc) {
            $k = trim($oc['category']);
            $lbl = ucwords(str_replace(['_', '-'], ' ', $k));
            try {
                Database::execute(
                    "INSERT IGNORE INTO template_categories (key_name, label, display_order) VALUES (?, ?, 50)",
                    [$k, $lbl]
                );
            } catch (\Throwable $e) {}
        }

        $sql = "SELECT tc.id as db_id, tc.key_name, tc.label, tc.description, tc.display_order,
                       COUNT(wt.id) as count
                FROM template_categories tc
                LEFT JOIN workflow_templates wt ON tc.key_name = wt.category
                GROUP BY tc.id, tc.key_name, tc.label, tc.description, tc.display_order
                ORDER BY tc.display_order ASC, tc.label ASC";
        $rows = Database::query($sql);
        $categories = [];
        foreach ($rows as $r) {
            $categories[] = [
                'id' => $r['key_name'],
                'db_id' => (int)$r['db_id'],
                'label' => $r['label'],
                'description' => $r['description'] ?? '',
                'count' => (int)$r['count'],
            ];
        }

        return $categories;
    }

    /**
     * Create / Add a new template category.
     */
    public static function createCategory(string $label, ?string $keyName = null): array
    {
        $label = trim($label);
        if (empty($label)) {
            throw new RuntimeException("Category name is required.");
        }

        if (empty($keyName)) {
            $keyName = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $label));
            $keyName = trim($keyName, '_');
        }
        if (empty($keyName)) {
            $keyName = 'cat_' . bin2hex(random_bytes(3));
        }

        $existing = Database::fetchOne("SELECT * FROM template_categories WHERE key_name = ?", [$keyName]);
        if ($existing) {
            return [
                'id' => $existing['key_name'],
                'db_id' => (int)$existing['id'],
                'label' => $existing['label'],
                'count' => 0
            ];
        }

        $maxOrder = Database::fetchOne("SELECT IFNULL(MAX(display_order), 0) + 1 as next_order FROM template_categories")['next_order'];
        Database::execute(
            "INSERT INTO template_categories (key_name, label, display_order) VALUES (?, ?, ?)",
            [$keyName, $label, $maxOrder]
        );
        $newDbId = (int) Database::lastInsertId();

        return [
            'id' => $keyName,
            'db_id' => $newDbId,
            'label' => $label,
            'count' => 0
        ];
    }

    /**
     * Delete a template category (only if no templates are using it).
     */
    public static function deleteCategory(int|string $idOrKey): bool
    {
        $field = is_numeric($idOrKey) ? 'id' : 'key_name';
        $cat = Database::fetchOne("SELECT * FROM template_categories WHERE {$field} = ?", [$idOrKey]);
        if (!$cat) {
            return false;
        }

        $inUse = Database::fetchOne("SELECT COUNT(*) as cnt FROM workflow_templates WHERE category = ?", [$cat['key_name']])['cnt'];
        if ($inUse > 0) {
            throw new RuntimeException("Cannot delete category '{$cat['label']}' because {$inUse} template(s) are currently assigned to it. Reassign or delete those templates first.");
        }

        Database::execute("DELETE FROM template_categories WHERE id = ?", [$cat['id']]);
        return true;
    }

    /**
     * List active public workflow templates for workspace users.
     */
    public static function listPublicTemplates(?string $category = null, ?string $mode = null, ?string $search = null): array
    {
        $sql = "SELECT id, name, slug, description, category, funnel_mode, thumbnail_url, badge, node_count, is_featured, display_order, created_at, updated_at
                FROM workflow_templates
                WHERE is_active = 1";
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($mode) && in_array($mode, ['quiz', 'calculator'], true)) {
            $sql .= " AND funnel_mode = ?";
            $params[] = $mode;
        }

        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY is_featured DESC, display_order ASC, id ASC";

        $rows = Database::query($sql, $params);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['node_count'] = (int)$row['node_count'];
            $row['is_featured'] = !empty($row['is_featured']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Get single template detail by ID or Slug.
     */
    public static function getTemplate(int|string $idOrSlug, bool $requireActive = true): ?array
    {
        $field = is_numeric($idOrSlug) ? 'id' : 'slug';
        $sql = "SELECT * FROM workflow_templates WHERE {$field} = ?";
        $params = [$idOrSlug];

        if ($requireActive) {
            $sql .= " AND is_active = 1";
        }

        $row = Database::fetchOne($sql, $params);
        if (!$row) {
            return null;
        }

        $row['id'] = (int)$row['id'];
        $row['node_count'] = (int)$row['node_count'];
        $row['is_active'] = !empty($row['is_active']);
        $row['is_featured'] = !empty($row['is_featured']);
        $row['graph'] = !empty($row['graph_json']) ? (json_decode($row['graph_json'], true) ?: []) : [];
        $row['settings'] = !empty($row['settings_json']) ? (json_decode($row['settings_json'], true) ?: []) : [];
        $row['form_schema'] = !empty($row['form_schema_json']) ? (json_decode($row['form_schema_json'], true) ?: []) : [];

        return $row;
    }

    /**
     * Instantiate a workflow template into a brand-new funnel in tenant workspace.
     */
    public static function instantiateTemplate(int $tenantId, int $templateId, ?string $customName, int $userId): array
    {
        // 1. Quota & Limits check
        $limitCheck = UsageLimitService::checkCanCreateFunnel($tenantId);
        if (!$limitCheck['allowed']) {
            throw new RuntimeException($limitCheck['message']);
        }

        // 2. Fetch template
        $template = self::getTemplate($templateId, true);
        if (!$template) {
            throw new RuntimeException("Workflow template not found or is currently inactive.");
        }

        // 3. Feature check for calculator mode
        if ($template['funnel_mode'] === 'calculator' && !UsageLimitService::hasFeature($tenantId, 'calculator_mode') && !AuthorizationService::isPlatformAdmin()) {
            throw new RuntimeException("This template uses Interactive Calculator mode, which requires a Pro or Growth plan subscription.");
        }

        $funnelName = !empty($customName) ? trim($customName) : ($template['name'] . ' (My Funnel)');
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $funnelName));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'funnel-' . bin2hex(random_bytes(3));
        }

        // Unique slug resolution
        $slug = $baseSlug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM funnels WHERE slug = ?", [$slug])) {
            $slug = "{$baseSlug}-" . (++$counter);
        }

        $settingsJson = !empty($template['settings_json']) ? $template['settings_json'] : json_encode([
            'theme' => 'light',
            'accent_color' => $template['funnel_mode'] === 'calculator' ? '#10B981' : '#1d5fd6',
            'enable_back_button' => true,
            'show_progress_bar' => true,
        ]);

        return Database::transaction(function () use ($tenantId, $template, $funnelName, $slug, $settingsJson, $userId) {
            // A. Insert Funnel Record
            Database::execute(
                "INSERT INTO funnels (tenant_id, name, slug, description, funnel_mode, status, settings_json, created_by)
                 VALUES (?, ?, ?, ?, ?, 'draft', ?, ?)",
                [$tenantId, $funnelName, $slug, $template['description'], $template['funnel_mode'], $settingsJson, $userId]
            );
            $newFunnelId = (int) Database::lastInsertId();

            // B. Populate Graph Nodes, Edges, Questions, and Destinations
            $graphData = $template['graph'] ?? [];
            if (!empty($graphData['nodes'])) {
                FunnelGraphService::saveGraph($tenantId, $newFunnelId, $graphData, $userId);
            }

            // C. Populate Pre-Quiz Form Schema Fields (if present)
            if (!empty($template['form_schema']) && is_array($template['form_schema'])) {
                try {
                    FormSchemaService::saveFormFields($tenantId, $newFunnelId, $template['form_schema'], $userId);
                } catch (\Throwable $e) {
                    Logger::warning("Could not pre-populate form schema from template: " . $e->getMessage());
                }
            }

            // D. Log Audit Entry
            AuditLogService::log(
                $tenantId,
                $userId,
                'create_funnel_from_template',
                'funnels',
                $newFunnelId,
                [
                    'template_id' => $template['id'],
                    'template_name' => $template['name'],
                    'funnel_mode' => $template['funnel_mode']
                ]
            );

            Logger::info("User #{$userId} instantiated template '{$template['name']}' as Funnel #{$newFunnelId} ({$slug})");

            return [
                'id' => $newFunnelId,
                'name' => $funnelName,
                'slug' => $slug,
                'funnel_mode' => $template['funnel_mode'],
                'status' => 'draft',
                'template_id' => $template['id'],
            ];
        });
    }

    /**
     * List all templates (including inactive) for Super Admin management.
     */
    public static function listAllForAdmin(?string $search = null): array
    {
        $sql = "SELECT wt.*, u.name as creator_name
                FROM workflow_templates wt
                LEFT JOIN users u ON wt.created_by = u.id";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE wt.name LIKE ? OR wt.description LIKE ? OR wt.category LIKE ?";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY wt.display_order ASC, wt.id DESC";

        $rows = Database::query($sql, $params);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['node_count'] = (int)$row['node_count'];
            $row['is_active'] = !empty($row['is_active']);
            $row['is_featured'] = !empty($row['is_featured']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Create / Upload a new template by Super Admin.
     */
    public static function createTemplate(array $data, int $adminId): array
    {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new RuntimeException("Template name is required.");
        }

        $baseSlug = !empty($data['slug']) ? trim($data['slug']) : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'template-' . bin2hex(random_bytes(3));
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM workflow_templates WHERE slug = ?", [$slug])) {
            $slug = "{$baseSlug}-" . (++$counter);
        }

        $description = trim((string)($data['description'] ?? ''));
        $rawCategory = trim((string)($data['category'] ?? 'lead_generation'));
        $category = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $rawCategory));
        $category = trim($category, '_');
        if (empty($category)) {
            $category = 'lead_generation';
        }
        try {
            self::createCategory($rawCategory, $category);
        } catch (\Throwable $e) {}
        $funnelMode = in_array($data['funnel_mode'] ?? '', ['quiz', 'calculator'], true) ? $data['funnel_mode'] : 'quiz';
        $badge = !empty($data['badge']) ? trim((string)$data['badge']) : null;
        $thumbnailUrl = !empty($data['thumbnail_url']) ? trim((string)$data['thumbnail_url']) : null;
        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
        $isFeatured = isset($data['is_featured']) ? (int)(bool)$data['is_featured'] : 0;
        $displayOrder = (int)($data['display_order'] ?? 0);

        // Process Graph JSON
        $graph = $data['graph'] ?? ($data['graph_json'] ?? []);
        if (is_string($graph)) {
            $graph = json_decode($graph, true) ?: [];
        }

        $nodes = $graph['nodes'] ?? [];
        $nodeCount = count($nodes);
        $graphJson = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Settings JSON
        $settings = $data['settings'] ?? ($data['settings_json'] ?? []);
        $settingsJson = is_array($settings) ? json_encode($settings) : (string)$settings;

        // Form Schema JSON
        $formSchema = $data['form_schema'] ?? ($data['form_schema_json'] ?? []);
        $formSchemaJson = is_array($formSchema) ? json_encode($formSchema) : (string)$formSchema;

        Database::execute(
            "INSERT INTO workflow_templates 
             (name, slug, description, category, funnel_mode, thumbnail_url, badge, node_count, graph_json, settings_json, form_schema_json, is_active, is_featured, display_order, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $name,
                $slug,
                $description,
                $category,
                $funnelMode,
                $thumbnailUrl,
                $badge,
                $nodeCount,
                $graphJson,
                $settingsJson,
                $formSchemaJson,
                $isActive,
                $isFeatured,
                $displayOrder,
                $adminId
            ]
        );

        $newId = (int) Database::lastInsertId();

        AuditLogService::log(
            null,
            $adminId,
            'create_workflow_template',
            'workflow_templates',
            $newId,
            ['name' => $name, 'category' => $category, 'slug' => $slug]
        );

        return [
            'id' => $newId,
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'node_count' => $nodeCount,
        ];
    }

    /**
     * 1-Click Convert an existing Funnel into a Global Workflow Template.
     */
    public static function createFromExistingFunnel(int $funnelId, array $meta, int $adminId): array
    {
        $funnel = Database::fetchOne("SELECT * FROM funnels WHERE id = ?", [$funnelId]);
        if (!$funnel) {
            throw new RuntimeException("Source funnel not found.");
        }

        $tenantId = (int)$funnel['tenant_id'];

        // Extract complete graph structure
        $graph = FunnelGraphService::getGraph($tenantId, $funnelId);

        // Clean tenant specific references from nodes and edges
        $cleanNodes = [];
        foreach ($graph['nodes'] as $node) {
            unset($node['id'], $node['tenant_id'], $node['funnel_id'], $node['created_at'], $node['updated_at']);
            $cleanNodes[] = $node;
        }

        $cleanEdges = [];
        foreach ($graph['edges'] as $edge) {
            unset($edge['id'], $edge['tenant_id'], $edge['funnel_id']);
            $cleanEdges[] = $edge;
        }

        $cleanGraph = [
            'nodes' => $cleanNodes,
            'edges' => $cleanEdges,
        ];

        // Extract pre-quiz form schema fields
        $formFields = FormSchemaService::getFormFields($tenantId, $funnelId);
        $cleanFormSchema = [];
        foreach ($formFields as $ff) {
            unset($ff['id'], $ff['tenant_id'], $ff['funnel_id']);
            if (!empty($ff['options'])) {
                foreach ($ff['options'] as &$opt) {
                    unset($opt['id'], $opt['field_id']);
                }
                unset($opt);
            }
            $cleanFormSchema[] = $ff;
        }

        $templateData = [
            'name' => !empty($meta['name']) ? trim($meta['name']) : ($funnel['name'] . ' Template'),
            'slug' => !empty($meta['slug']) ? trim($meta['slug']) : '',
            'description' => !empty($meta['description']) ? trim($meta['description']) : ($funnel['description'] ?? 'Exported from workspace funnel.'),
            'category' => !empty($meta['category']) ? trim($meta['category']) : 'lead_generation',
            'funnel_mode' => $funnel['funnel_mode'] ?? 'quiz',
            'badge' => !empty($meta['badge']) ? trim($meta['badge']) : 'Community Favorite',
            'thumbnail_url' => $meta['thumbnail_url'] ?? null,
            'is_active' => isset($meta['is_active']) ? (int)(bool)$meta['is_active'] : 1,
            'is_featured' => isset($meta['is_featured']) ? (int)(bool)$meta['is_featured'] : 0,
            'display_order' => (int)($meta['display_order'] ?? 0),
            'graph' => $cleanGraph,
            'settings' => json_decode($funnel['settings_json'] ?? '{}', true) ?: [],
            'form_schema' => $cleanFormSchema,
        ];

        return self::createTemplate($templateData, $adminId);
    }

    /**
     * Update an existing workflow template.
     */
    public static function updateTemplate(int $id, array $data, int $adminId): bool
    {
        $existing = Database::fetchOne("SELECT * FROM workflow_templates WHERE id = ?", [$id]);
        if (!$existing) {
            throw new RuntimeException("Template not found.");
        }

        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = trim($data['name']);
        }
        if (isset($data['slug']) && !empty(trim($data['slug']))) {
            $newSlug = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', trim($data['slug'])));
            $newSlug = trim($newSlug, '-');
            $existingSlug = Database::fetchOne("SELECT id FROM workflow_templates WHERE slug = ? AND id != ?", [$newSlug, $id]);
            if (!$existingSlug) {
                $fields[] = "slug = ?";
                $params[] = $newSlug;
            }
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = trim($data['description']);
        }
        if (isset($data['category'])) {
            $rawCat = trim((string)$data['category']);
            $cat = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $rawCat));
            $cat = trim($cat, '_');
            $fields[] = "category = ?";
            $params[] = !empty($cat) ? $cat : 'general';
        }
        if (isset($data['funnel_mode']) && in_array($data['funnel_mode'], ['quiz', 'calculator'], true)) {
            $fields[] = "funnel_mode = ?";
            $params[] = $data['funnel_mode'];
        }
        if (isset($data['badge'])) {
            $fields[] = "badge = ?";
            $params[] = trim((string)$data['badge']) ?: null;
        }
        if (isset($data['thumbnail_url'])) {
            $fields[] = "thumbnail_url = ?";
            $params[] = trim((string)$data['thumbnail_url']) ?: null;
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = (int)(bool)$data['is_active'];
        }
        if (isset($data['is_featured'])) {
            $fields[] = "is_featured = ?";
            $params[] = (int)(bool)$data['is_featured'];
        }
        if (isset($data['display_order'])) {
            $fields[] = "display_order = ?";
            $params[] = (int)$data['display_order'];
        }

        // Allow updating workflow from an existing modified funnel
        if (!empty($data['source_funnel_id'])) {
            $sourceFunnel = Database::fetchOne("SELECT * FROM funnels WHERE id = ?", [(int)$data['source_funnel_id']]);
            if ($sourceFunnel) {
                $tenantId = (int)$sourceFunnel['tenant_id'];
                $graph = FunnelGraphService::getGraph($tenantId, (int)$sourceFunnel['id']);
                
                $cleanNodes = [];
                foreach ($graph['nodes'] as $node) {
                    unset($node['id'], $node['tenant_id'], $node['funnel_id'], $node['created_at'], $node['updated_at']);
                    $cleanNodes[] = $node;
                }
                $cleanEdges = [];
                foreach ($graph['edges'] as $edge) {
                    unset($edge['id'], $edge['tenant_id'], $edge['funnel_id']);
                    $cleanEdges[] = $edge;
                }
                $cleanGraph = ['nodes' => $cleanNodes, 'edges' => $cleanEdges];
                
                $fields[] = "graph_json = ?";
                $params[] = json_encode($cleanGraph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $fields[] = "node_count = ?";
                $params[] = count($cleanNodes);
                
                if (!empty($sourceFunnel['settings_json'])) {
                    $fields[] = "settings_json = ?";
                    $params[] = $sourceFunnel['settings_json'];
                }
                
                $formFields = FormSchemaService::getFormFields($tenantId, (int)$sourceFunnel['id']);
                if (!empty($formFields)) {
                    $cleanFormSchema = [];
                    foreach ($formFields as $ff) {
                        unset($ff['id'], $ff['tenant_id'], $ff['funnel_id']);
                        if (!empty($ff['options'])) {
                            foreach ($ff['options'] as &$opt) {
                                unset($opt['id'], $opt['field_id']);
                            }
                            unset($opt);
                        }
                        $cleanFormSchema[] = $ff;
                    }
                    $fields[] = "form_schema_json = ?";
                    $params[] = json_encode($cleanFormSchema);
                }
            }
        }

        if (isset($data['graph']) || isset($data['graph_json'])) {
            $graph = $data['graph'] ?? $data['graph_json'];
            if (is_string($graph)) {
                $graph = json_decode($graph, true) ?: [];
            }
            $fields[] = "graph_json = ?";
            $params[] = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $fields[] = "node_count = ?";
            $params[] = count($graph['nodes'] ?? []);
        }

        if (isset($data['settings']) || isset($data['settings_json'])) {
            $settings = $data['settings'] ?? $data['settings_json'];
            $fields[] = "settings_json = ?";
            $params[] = is_array($settings) ? json_encode($settings) : (string)$settings;
        }

        if (isset($data['form_schema']) || isset($data['form_schema_json'])) {
            $formSchema = $data['form_schema'] ?? $data['form_schema_json'];
            $fields[] = "form_schema_json = ?";
            $params[] = is_array($formSchema) ? json_encode($formSchema) : (string)$formSchema;
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE workflow_templates SET " . implode(", ", $fields) . " WHERE id = ?";
        Database::execute($sql, $params);

        AuditLogService::log(null, $adminId, 'update_workflow_template', 'workflow_templates', $id, $data);
        return true;
    }

    /**
     * Delete a workflow template.
     */
    public static function deleteTemplate(int $id, int $adminId): bool
    {
        $existing = Database::fetchOne("SELECT id, name FROM workflow_templates WHERE id = ?", [$id]);
        if (!$existing) {
            return false;
        }

        Database::execute("DELETE FROM workflow_templates WHERE id = ?", [$id]);
        AuditLogService::log(null, $adminId, 'delete_workflow_template', 'workflow_templates', $id, ['name' => $existing['name']]);
        return true;
    }
}

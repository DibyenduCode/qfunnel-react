<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class FunnelPublishService
{
    public static function publish(int $tenantId, int $funnelId, int $userId): array
    {
        // 1. Authorize role
        if (!AuthorizationService::canPublishFunnel()) {
            throw new RuntimeException("You do not have permission to publish funnels.");
        }

        // 2. Validate Graph
        $validation = FunnelGraphService::validateGraph($tenantId, $funnelId);
        if (!$validation['is_valid']) {
            $errorMessages = array_map(fn($issue) => $issue['message'], array_filter($validation['issues'], fn($i) => $i['severity'] === 'error'));
            throw new RuntimeException("Cannot publish funnel due to flow validation errors:\n- " . implode("\n- ", $errorMessages));
        }

        // 3. Freeze Snapshots
        return Database::transaction(function () use ($tenantId, $funnelId, $userId) {
            $funnel = Database::fetchOne("SELECT * FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
            if (!$funnel) {
                throw new RuntimeException("Funnel not found.");
            }

            // Next version number
            $latestVersion = Database::fetchColumn("SELECT MAX(version_number) FROM funnel_versions WHERE funnel_id = ?", [$funnelId]);
            $nextVersionNumber = ($latestVersion ? (int)$latestVersion : 0) + 1;

            // Graph Snapshot
            $nodes = Database::query("SELECT * FROM funnel_nodes WHERE funnel_id = ?", [$funnelId]);
            $questions = Database::query("SELECT * FROM questions WHERE funnel_id = ?", [$funnelId]);
            $options = Database::query("SELECT qo.* FROM question_options qo JOIN questions q ON qo.question_id = q.id WHERE q.funnel_id = ? ORDER BY qo.display_order ASC, qo.id ASC", [$funnelId]);
            $edges = Database::query("SELECT * FROM funnel_edges WHERE funnel_id = ?", [$funnelId]);
            $destinations = Database::query("SELECT * FROM destinations WHERE funnel_id = ?", [$funnelId]);

            $startNode = Database::fetchOne("SELECT id FROM funnel_nodes WHERE funnel_id = ? AND is_start = 1 LIMIT 1", [$funnelId]);
            if (!$startNode) {
                $startNode = Database::fetchOne("SELECT id FROM funnel_nodes WHERE funnel_id = ? AND node_type = 'start' ORDER BY id ASC LIMIT 1", [$funnelId]);
            }
            if (!$startNode) {
                $startNode = Database::fetchOne("SELECT id FROM funnel_nodes WHERE funnel_id = ? AND node_type IN ('question','form','video_gate') ORDER BY id ASC LIMIT 1", [$funnelId]);
            }

            $graphSnapshot = [
                'nodes' => $nodes,
                'questions' => $questions,
                'options' => $options,
                'edges' => $edges,
                'destinations' => $destinations,
                'start_node_id' => $startNode ? (int)$startNode['id'] : null,
            ];

            // Form Schema Snapshot
            $formFields = Database::query("SELECT * FROM form_fields WHERE funnel_id = ? AND is_active = 1 ORDER BY display_order ASC", [$funnelId]);
            foreach ($formFields as &$ff) {
                $ff['options'] = Database::query("SELECT * FROM form_field_options WHERE field_id = ? ORDER BY display_order ASC", [$ff['id']]);
            }
            unset($ff);

            $formSchemaSnapshot = [
                'fields' => $formFields,
            ];

            $settingsSnapshot = json_decode($funnel['settings_json'] ?? '{}', true) ?: [];

            // Insert Funnel Version
            Database::execute(
                "INSERT INTO funnel_versions (tenant_id, funnel_id, version_number, graph_snapshot_json, form_schema_snapshot_json, settings_snapshot_json, published_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $tenantId,
                    $funnelId,
                    $nextVersionNumber,
                    json_encode($graphSnapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($formSchemaSnapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($settingsSnapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $userId
                ]
            );
            $versionId = (int) Database::lastInsertId();

            // Update Funnel Status
            Database::execute(
                "UPDATE funnels SET status = 'published', current_published_version_id = ?, has_unpublished_changes = 0, updated_at = NOW() WHERE id = ?",
                [$versionId, $funnelId]
            );

            AuditLogService::log($tenantId, $userId, 'publish_funnel_version', 'funnel', $funnelId, [
                'version_number' => $nextVersionNumber,
                'version_id' => $versionId,
            ]);

            Logger::info("Published version {$nextVersionNumber} for funnel {$funnelId} (Tenant: {$tenantId})");

            return [
                'version_id' => $versionId,
                'version_number' => $nextVersionNumber,
                'status' => 'published',
            ];
        });
    }
}

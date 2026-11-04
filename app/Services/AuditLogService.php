<?php

namespace App\Services;

use App\Core\Database;

class AuditLogService
{
    public static function log(?int $tenantId, ?int $userId, string $action, string $resourceType, ?int $resourceId = null, ?array $metadata = null): void
    {
        try {
            Database::execute(
                "INSERT INTO audit_logs (tenant_id, user_id, action, resource_type, resource_id, metadata_json, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $tenantId,
                    $userId,
                    $action,
                    $resourceType,
                    $resourceId,
                    !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null
                ]
            );
        } catch (\Throwable $e) {
            // Audit logging failure should not crash main request
        }
    }

    public static function listLogs(?int $tenantId = null, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name, u.email as user_email, t.name as tenant_name 
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id";
        $params = [];

        if ($tenantId !== null) {
            $sql .= " WHERE al.tenant_id = ?";
            $params[] = $tenantId;
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT " . (int)$limit;

        return Database::query($sql, $params);
    }

    public static function listLogsPaginated(?int $tenantId = null, int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT al.*, u.name as user_name, u.email as user_email, t.name as tenant_name 
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id";
        $params = [];

        if ($tenantId !== null) {
            $sql .= " WHERE al.tenant_id = ?";
            $params[] = $tenantId;
        }

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_logs";
        $totalRows = (int) Database::fetchColumn($countSql, $params);

        $sql .= " ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = Database::query($sql, $params);

        return [
            'items' => $items,
            'total' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalRows / $perPage),
        ];
    }
}

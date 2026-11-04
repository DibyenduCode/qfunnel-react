<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class SubmissionService
{
    public static function listSubmissions(int $tenantId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $nameExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('full_name', 'name', 'first_name', 'your_name', 'contact_name') OR fsv.field_type_snapshot = 'name' OR LOWER(fsv.field_label_snapshot) LIKE '%name%') ORDER BY fsv.id ASC LIMIT 1)";
        $emailExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('email', 'email_address') OR fsv.field_type_snapshot = 'email' OR LOWER(fsv.field_label_snapshot) LIKE '%email%') ORDER BY fsv.id ASC LIMIT 1)";
        $phoneExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('phone', 'mobile', 'whatsapp', 'tel', 'phone_number', 'p') OR fsv.field_type_snapshot = 'phone' OR LOWER(fsv.field_label_snapshot) LIKE '%phone%' OR LOWER(fsv.field_label_snapshot) LIKE '%mobile%') ORDER BY fsv.id ASC LIMIT 1)";

        // Total Counts for Tabs
        $totalAll = (int)Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ?", [$tenantId]);
        $totalQualified = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT qs.id) 
             FROM quiz_sessions qs 
             JOIN form_submissions fs ON fs.session_id = qs.id
             JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id
             WHERE qs.tenant_id = ? AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')",
            [$tenantId]
        );
        $totalAbandoned = max(0, $totalAll - $totalQualified);

        $sql = "SELECT qs.id, qs.status, qs.lead_status, qs.started_at, qs.completed_at, qs.created_at, qs.calculated_value, qs.formatted_quote,
                       f.name as funnel_name, f.slug as funnel_slug,
                       d.name as destination_name,
                       {$nameExpr} as lead_name,
                       {$emailExpr} as lead_email,
                       {$phoneExpr} as lead_phone
                FROM quiz_sessions qs
                JOIN funnels f ON qs.funnel_id = f.id
                LEFT JOIN destinations d ON qs.final_destination_id = d.id
                WHERE qs.tenant_id = ?";
        $params = [$tenantId];

        // Tab Filter: qualified (default) vs all vs abandoned
        $tab = $filters['tab'] ?? 'qualified';
        if ($tab === 'qualified') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM form_submissions fs 
                JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                WHERE fs.session_id = qs.id AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')
            )";
        } elseif ($tab === 'abandoned') {
            $sql .= " AND NOT EXISTS (
                SELECT 1 FROM form_submissions fs 
                JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                WHERE fs.session_id = qs.id AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')
            )";
        }

        if (!empty($filters['funnel_id'])) {
            $sql .= " AND qs.funnel_id = ?";
            $params[] = (int)$filters['funnel_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND qs.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['lead_status'])) {
            $sql .= " AND qs.lead_status = ?";
            $params[] = $filters['lead_status'];
        }

        // Date Range filter
        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $sql .= " AND DATE(qs.created_at) = CURDATE()";
                    break;
                case 'yesterday':
                    $sql .= " AND DATE(qs.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case '7days':
                    $sql .= " AND qs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30days':
                    $sql .= " AND qs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (
                EXISTS (
                    SELECT 1 FROM form_submissions fs 
                    JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                    WHERE fs.session_id = qs.id AND fsv.value_text LIKE ?
                ) OR f.name LIKE ? OR qs.id = ?
            )";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = (int)$filters['search'];
        }

        // Count total
        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_table";
        $totalRows = (int) Database::fetchColumn($countSql, $params);

        $sql .= " ORDER BY qs.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = Database::query($sql, $params);

        // Batch load top 2 answers for each session to show qualification pills in the table!
        if (!empty($items)) {
            $sessionIds = array_column($items, 'id');
            $inList = implode(',', array_map('intval', $sessionIds));
            $answers = Database::query(
                "SELECT session_id, question_text_snapshot, answer_value_text, answer_type 
                 FROM quiz_answers 
                 WHERE session_id IN ({$inList}) AND superseded_at IS NULL AND (answer_type != 'form' OR answer_type IS NULL)
                 ORDER BY id ASC"
            );
            $answersBySession = [];
            foreach ($answers as $ans) {
                if (!empty($ans['answer_value_text'])) {
                    $answersBySession[$ans['session_id']][] = [
                        'question' => $ans['question_text_snapshot'],
                        'answer' => $ans['answer_value_text'],
                        'type' => $ans['answer_type']
                    ];
                }
            }
            foreach ($items as &$item) {
                $item['answers'] = $answersBySession[$item['id']] ?? [];
            }
        }

        return [
            'items' => $items,
            'total' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalRows / $perPage),
            'counts' => [
                'all' => $totalAll,
                'qualified' => $totalQualified,
                'abandoned' => $totalAbandoned
            ]
        ];
    }

    public static function getSubmissionDetail(int $tenantId, int $sessionId): ?array
    {
        $session = Database::fetchOne(
            "SELECT qs.*, f.name as funnel_name, f.slug as funnel_slug, fv.version_number, d.name as destination_name, d.url as destination_url
             FROM quiz_sessions qs
             JOIN funnels f ON qs.funnel_id = f.id
             JOIN funnel_versions fv ON qs.funnel_version_id = fv.id
             LEFT JOIN destinations d ON qs.final_destination_id = d.id
             WHERE qs.tenant_id = ? AND qs.id = ?",
            [$tenantId, $sessionId]
        );

        if (!$session) {
            return null;
        }

        // Pre-Quiz Form Values
        $formSubmission = Database::fetchOne(
            "SELECT * FROM form_submissions WHERE session_id = ?",
            [$sessionId]
        );

        $formValues = [];
        if ($formSubmission) {
            $formValues = Database::query(
                "SELECT * FROM form_submission_values WHERE form_submission_id = ? ORDER BY id ASC",
                [$formSubmission['id']]
            );
        }

        // Answer History (only active, non-superseded answers)
        $answers = Database::query(
            "SELECT qa.*, q.question_type 
             FROM quiz_answers qa
             LEFT JOIN questions q ON qa.question_id = q.id
             WHERE qa.session_id = ? AND qa.superseded_at IS NULL AND (qa.answer_type != 'form' OR qa.answer_type IS NULL)
             ORDER BY qa.id ASC",
            [$sessionId]
        );

        // Complete Journey Step Events Timeline
        $events = Database::query(
            "SELECT qse.*, fn.title as node_title, fn.node_type, qo.label as option_label
             FROM quiz_step_events qse
             LEFT JOIN funnel_nodes fn ON qse.node_id = fn.id
             LEFT JOIN question_options qo ON qse.option_id = qo.id
             WHERE qse.session_id = ?
             ORDER BY qse.occurred_at ASC, qse.id ASC",
            [$sessionId]
        );

        // Result record
        $result = Database::fetchOne(
            "SELECT * FROM quiz_results WHERE session_id = ?",
            [$sessionId]
        );

        return [
            'session' => $session,
            'form_submission' => $formSubmission,
            'form_values' => $formValues,
            'answers' => $answers,
            'events' => $events,
            'result' => $result ? json_decode($result['summary_json'], true) : null,
        ];
    }

    public static function updateLeadStatus(int $tenantId, int $sessionId, string $leadStatus): bool
    {
        $stages = LeadStageService::getStagesForTenant($tenantId);
        $allowed = array_column($stages, 'slug');
        if (!in_array($leadStatus, $allowed, true)) {
            return false;
        }

        Database::execute(
            "UPDATE quiz_sessions SET lead_status = ? WHERE tenant_id = ? AND id = ?",
            [$leadStatus, $tenantId, $sessionId]
        );
        return true;
    }

    public static function bulkUpdateLeadStatus(int $tenantId, array $sessionIds, string $leadStatus): int
    {
        $stages = LeadStageService::getStagesForTenant($tenantId);
        $allowed = array_column($stages, 'slug');
        if (!in_array($leadStatus, $allowed, true) || empty($sessionIds)) {
            return 0;
        }

        $cleanIds = array_filter(array_map('intval', $sessionIds));
        if (empty($cleanIds)) return 0;

        $inList = implode(',', $cleanIds);
        Database::execute(
            "UPDATE quiz_sessions SET lead_status = ? WHERE tenant_id = ? AND id IN ({$inList})",
            [$leadStatus, $tenantId]
        );
        return count($cleanIds);
    }

    public static function bulkDelete(int $tenantId, array $sessionIds): int
    {
        $cleanIds = array_filter(array_map('intval', $sessionIds));
        if (empty($cleanIds)) return 0;

        $inList = implode(',', $cleanIds);
        Database::execute("DELETE FROM quiz_answers WHERE tenant_id = ? AND session_id IN ({$inList})", [$tenantId]);
        Database::execute("DELETE FROM quiz_step_events WHERE tenant_id = ? AND session_id IN ({$inList})", [$tenantId]);
        Database::execute("DELETE FROM quiz_results WHERE session_id IN ({$inList})");
        
        $formSubs = Database::query("SELECT id FROM form_submissions WHERE session_id IN ({$inList})");
        if (!empty($formSubs)) {
            $fsIds = implode(',', array_column($formSubs, 'id'));
            Database::execute("DELETE FROM form_submission_values WHERE form_submission_id IN ({$fsIds})");
            Database::execute("DELETE FROM form_submissions WHERE id IN ({$fsIds})");
        }

        Database::execute("DELETE FROM quiz_sessions WHERE tenant_id = ? AND id IN ({$inList})", [$tenantId]);
        return count($cleanIds);
    }

    public static function exportCsv(int $tenantId, ?int $funnelId = null, ?string $tab = null, array $filters = []): string
    {
        $nameExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('full_name', 'name', 'first_name', 'your_name', 'contact_name') OR fsv.field_type_snapshot = 'name' OR LOWER(fsv.field_label_snapshot) LIKE '%name%') ORDER BY fsv.id ASC LIMIT 1)";
        $emailExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('email', 'email_address') OR fsv.field_type_snapshot = 'email' OR LOWER(fsv.field_label_snapshot) LIKE '%email%') ORDER BY fsv.id ASC LIMIT 1)";
        $phoneExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('phone', 'mobile', 'whatsapp', 'tel', 'phone_number', 'p') OR fsv.field_type_snapshot = 'phone' OR LOWER(fsv.field_label_snapshot) LIKE '%phone%' OR LOWER(fsv.field_label_snapshot) LIKE '%mobile%') ORDER BY fsv.id ASC LIMIT 1)";

        $sql = "SELECT qs.id as session_id, f.name as funnel_name, qs.status, qs.lead_status, qs.started_at, qs.completed_at, qs.created_at, d.name as destination_name, qs.calculated_value, qs.formatted_quote,
                       {$nameExpr} as full_name,
                       {$emailExpr} as email,
                       {$phoneExpr} as phone
                FROM quiz_sessions qs
                JOIN funnels f ON qs.funnel_id = f.id
                LEFT JOIN destinations d ON qs.final_destination_id = d.id
                WHERE qs.tenant_id = ?";
        $params = [$tenantId];

        if ($funnelId) {
            $sql .= " AND qs.funnel_id = ?";
            $params[] = $funnelId;
        }

        if ($tab === 'qualified') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM form_submissions fs 
                JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                WHERE fs.session_id = qs.id AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')
            )";
        } elseif ($tab === 'abandoned') {
            $sql .= " AND NOT EXISTS (
                SELECT 1 FROM form_submissions fs 
                JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                WHERE fs.session_id = qs.id AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')
            )";
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $sql .= " AND DATE(qs.created_at) = CURDATE()";
                    break;
                case 'yesterday':
                    $sql .= " AND DATE(qs.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case '7days':
                    $sql .= " AND qs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30days':
                    $sql .= " AND qs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        $sql .= " ORDER BY qs.created_at DESC";
        $rows = Database::query($sql, $params);

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['Session ID', 'Funnel', 'Lead Name', 'Email', 'Phone', 'Lead CRM Status', 'Instant Quote', 'Quiz Status', 'Submission Date', 'Destination Reached']);

        foreach ($rows as $r) {
            fputcsv($fp, [
                $r['session_id'],
                $r['funnel_name'],
                $r['full_name'] ?: 'N/A',
                $r['email'] ?: 'N/A',
                $r['phone'] ?: 'N/A',
                strtoupper($r['lead_status'] ?? 'NEW'),
                !empty($r['formatted_quote']) ? $r['formatted_quote'] : ($r['calculated_value'] !== null ? (string)$r['calculated_value'] : 'N/A'),
                ucfirst(str_replace('_', ' ', $r['status'])),
                $r['created_at'] ?? '',
                $r['destination_name'] ?: 'N/A',
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}

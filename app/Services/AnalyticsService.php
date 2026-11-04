<?php

namespace App\Services;

use App\Core\Database;

class AnalyticsService
{
    public static function getDashboardMetrics(int $tenantId): array
    {
        $totalFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status != 'archived'", [$tenantId]);
        $publishedFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status = 'published'", [$tenantId]);
        $draftFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status = 'draft'", [$tenantId]);

        // Total views: count of quiz_viewed events OR total visitor sessions created
        $eventViews = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_step_events WHERE tenant_id = ? AND event_type = 'quiz_viewed'", [$tenantId]);
        $sessionCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ?", [$tenantId]);
        $totalViews = max($eventViews, $sessionCount);

        // Starts and Completions
        $totalStarts = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND started_at IS NOT NULL", [$tenantId]);
        if ($totalStarts === 0 && $sessionCount > 0) {
            $totalStarts = $sessionCount;
        }
        $totalCompletions = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND status = 'completed'", [$tenantId]);

        // Qualified leads: visitors who submitted real contact data (name, email, phone)
        $qualifiedLeads = (int) Database::fetchColumn(
            "SELECT COUNT(DISTINCT qs.id) 
             FROM quiz_sessions qs 
             WHERE qs.tenant_id = ? AND (
                 EXISTS (
                     SELECT 1 FROM form_submissions fs 
                     JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id 
                     WHERE fs.session_id = qs.id AND (fsv.value_text IS NOT NULL AND fsv.value_text != '')
                 )
                 OR EXISTS (
                     SELECT 1 FROM quiz_answers qa 
                     WHERE qa.session_id = qs.id AND qa.answer_type = 'form' AND (qa.answer_value_text IS NOT NULL AND qa.answer_value_text != '')
                 )
             )",
            [$tenantId]
        );

        $rawFormSubs = (int) Database::fetchColumn("SELECT COUNT(*) FROM form_submissions WHERE tenant_id = ?", [$tenantId]);
        $totalLeads = max($qualifiedLeads, $rawFormSubs);
        $totalSubmissions = $sessionCount;

        $completionRate = $totalStarts > 0 
            ? round(($totalCompletions / $totalStarts) * 100, 1) 
            : ($totalViews > 0 ? round(($totalCompletions / $totalViews) * 100, 1) : 0.0);

        // Recent Submissions
        $nameExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('full_name', 'name', 'first_name', 'your_name', 'contact_name') OR fsv.field_type_snapshot = 'name' OR LOWER(fsv.field_label_snapshot) LIKE '%name%') ORDER BY fsv.id ASC LIMIT 1)";
        $emailExpr = "(SELECT fsv.value_text FROM form_submissions fs JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id WHERE fs.session_id = qs.id AND (fsv.field_key_snapshot IN ('email', 'email_address') OR fsv.field_type_snapshot = 'email' OR LOWER(fsv.field_label_snapshot) LIKE '%email%') ORDER BY fsv.id ASC LIMIT 1)";

        $recentSubmissions = Database::query(
            "SELECT qs.id, qs.status, qs.lead_status, qs.created_at, f.name as funnel_name,
                    {$nameExpr} as lead_name,
                    {$emailExpr} as lead_email
             FROM quiz_sessions qs
             JOIN funnels f ON qs.funnel_id = f.id
             WHERE qs.tenant_id = ?
             ORDER BY qs.created_at DESC LIMIT 5",
            [$tenantId]
        );

        // Fallback for in-quiz form nodes where lead data was captured in quiz_answers
        foreach ($recentSubmissions as &$sub) {
            if (empty($sub['lead_name']) || empty($sub['lead_email'])) {
                $formAnswer = Database::fetchOne(
                    "SELECT answer_value_text FROM quiz_answers WHERE session_id = ? AND answer_type = 'form' ORDER BY id DESC LIMIT 1",
                    [$sub['id']]
                );
                if ($formAnswer && !empty($formAnswer['answer_value_text'])) {
                    $json = json_decode($formAnswer['answer_value_text'], true);
                    if (is_array($json)) {
                        if (empty($sub['lead_name'])) {
                            $sub['lead_name'] = $json['name'] ?? $json['full_name'] ?? $json['first_name'] ?? null;
                        }
                        if (empty($sub['lead_email'])) {
                            $sub['lead_email'] = $json['email'] ?? $json['email_address'] ?? null;
                        }
                    }
                }
            }
        }
        unset($sub);

        // Recent Funnels
        $recentFunnels = Database::query(
            "SELECT f.*, 
                    (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.funnel_id = f.id) as starts_count,
                    (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.funnel_id = f.id AND qs.status = 'completed') as completions_count
             FROM funnels f 
             WHERE f.tenant_id = ? 
             ORDER BY f.updated_at DESC LIMIT 5",
            [$tenantId]
        );

        // 7-Day Performance Trend (Daily Starts vs Completions)
        $trendRows = Database::query(
            "SELECT DATE(created_at) as session_date,
                    COUNT(*) as starts,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completions
             FROM quiz_sessions
             WHERE tenant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY session_date ASC",
            [$tenantId]
        );

        $trendMap = [];
        foreach ($trendRows as $row) {
            $trendMap[$row['session_date']] = [
                'starts' => (int)$row['starts'],
                'completions' => (int)$row['completions'],
            ];
        }

        $dates = [];
        $startsData = [];
        $completionsData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $displayDate = date('M d', strtotime($date));
            $dates[] = $displayDate;
            $startsData[] = $trendMap[$date]['starts'] ?? 0;
            $completionsData[] = $trendMap[$date]['completions'] ?? 0;
        }

        $activityTrend = [
            'labels' => $dates,
            'starts' => $startsData,
            'completions' => $completionsData,
            'total_week_starts' => array_sum($startsData),
            'total_week_completions' => array_sum($completionsData),
        ];

        // Pipeline Stage Distribution
        $stages = LeadStageService::getStagesForTenant($tenantId);
        $stageCounts = Database::query(
            "SELECT lead_status, COUNT(*) as count 
             FROM quiz_sessions 
             WHERE tenant_id = ? 
             GROUP BY lead_status",
            [$tenantId]
        );
        $countMap = [];
        foreach ($stageCounts as $sc) {
            $countMap[$sc['lead_status']] = (int)$sc['count'];
        }

        $stageLabels = [];
        $stageData = [];
        $stageColors = [];
        $stageDetails = [];
        $totalPipelineLeads = 0;

        foreach ($stages as $st) {
            $cnt = $countMap[$st['slug']] ?? 0;
            $stageLabels[] = $st['name'];
            $stageData[] = $cnt;
            $stageColors[] = $st['color'] ?? '#6366F1';
            $totalPipelineLeads += $cnt;
            $stageDetails[] = [
                'name' => $st['name'],
                'slug' => $st['slug'],
                'color' => $st['color'] ?? '#6366F1',
                'count' => $cnt,
                'emoji' => LeadStageService::getStageEmoji($st['slug'] ?? '', $st['color'] ?? ''),
            ];
        }

        $stageDistribution = [
            'labels' => $stageLabels,
            'data' => $stageData,
            'colors' => $stageColors,
            'details' => $stageDetails,
            'total' => $totalPipelineLeads,
        ];

        return [
            'total_funnels' => $totalFunnels,
            'published_funnels' => $publishedFunnels,
            'draft_funnels' => $draftFunnels,
            'total_views' => $totalViews,
            'total_starts' => $totalStarts,
            'total_completions' => $totalCompletions,
            'total_leads' => $totalLeads,
            'total_submissions' => $totalSubmissions,
            'qualified_submissions' => $qualifiedLeads,
            'completion_rate' => $completionRate,
            'recent_submissions' => $recentSubmissions,
            'recent_funnels' => $recentFunnels,
            'activity_trend' => $activityTrend,
            'stage_distribution' => $stageDistribution,
        ];
    }

    public static function getFunnelMetrics(int $tenantId, int $funnelId): array
    {
        $funnel = Database::fetchOne("SELECT * FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
        if (!$funnel) {
            return [];
        }

        $views = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_step_events WHERE tenant_id = ? AND funnel_id = ? AND event_type = 'quiz_viewed'", [$tenantId, $funnelId]);
        $formStarts = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_step_events WHERE tenant_id = ? AND funnel_id = ? AND event_type = 'pre_form_submitted'", [$tenantId, $funnelId]);
        $quizStarts = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND funnel_id = ? AND started_at IS NOT NULL", [$tenantId, $funnelId]);
        $completions = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND funnel_id = ? AND status = 'completed'", [$tenantId, $funnelId]);

        $completionRate = $quizStarts > 0 ? round(($completions / $quizStarts) * 100, 1) : 0.0;
        $leadCaptureRate = $views > 0 ? round(($formStarts / $views) * 100, 1) : 0.0;

        // Question Progress & Drop-Off
        $questions = Database::query(
            "SELECT q.id, q.node_id, q.question_text, fn.title as node_title,
                    (SELECT COUNT(*) FROM quiz_answers qa WHERE qa.question_id = q.id AND qa.superseded_at IS NULL) as answers_count
             FROM questions q
             JOIN funnel_nodes fn ON q.node_id = fn.id
             WHERE q.tenant_id = ? AND q.funnel_id = ?
             ORDER BY q.display_order ASC",
            [$tenantId, $funnelId]
        );

        // Option Distribution
        $optionStats = Database::query(
            "SELECT qo.id, qo.label, qo.question_id, q.question_text,
                    (SELECT COUNT(*) FROM quiz_answers qa WHERE qa.question_id = qo.question_id AND JSON_CONTAINS(qa.selected_option_ids_json, CAST(qo.id AS CHAR), '$') AND qa.superseded_at IS NULL) as select_count
             FROM question_options qo
             JOIN questions q ON qo.question_id = q.id
             WHERE qo.tenant_id = ? AND q.funnel_id = ?
             ORDER BY select_count DESC",
            [$tenantId, $funnelId]
        );

        // Destination Distribution
        $destinations = Database::query(
            "SELECT d.id, d.name, d.url,
                    (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.final_destination_id = d.id AND qs.status = 'completed') as reach_count
             FROM destinations d
             WHERE d.tenant_id = ? AND d.funnel_id = ?
             ORDER BY reach_count DESC",
            [$tenantId, $funnelId]
        );

        // 7-Day Performance Trend for this specific funnel
        $trendRows = Database::query(
            "SELECT DATE(created_at) as session_date,
                    COUNT(*) as starts,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completions
             FROM quiz_sessions
             WHERE tenant_id = ? AND funnel_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY session_date ASC",
            [$tenantId, $funnelId]
        );

        $trendMap = [];
        foreach ($trendRows as $row) {
            $trendMap[$row['session_date']] = [
                'starts' => (int)$row['starts'],
                'completions' => (int)$row['completions'],
            ];
        }

        $dates = [];
        $startsData = [];
        $completionsData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = date('M d', strtotime($date));
            $startsData[] = $trendMap[$date]['starts'] ?? 0;
            $completionsData[] = $trendMap[$date]['completions'] ?? 0;
        }

        $activityTrend = [
            'labels' => $dates,
            'starts' => $startsData,
            'completions' => $completionsData,
        ];

        return [
            'funnel' => $funnel,
            'views' => $views,
            'form_starts' => $formStarts,
            'quiz_starts' => $quizStarts,
            'completions' => $completions,
            'completion_rate' => $completionRate,
            'lead_capture_rate' => $leadCaptureRate,
            'questions' => $questions,
            'options' => $optionStats,
            'destinations' => $destinations,
            'activity_trend' => $activityTrend,
        ];
    }
}

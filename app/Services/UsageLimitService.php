<?php

namespace App\Services;

use App\Core\Database;

class UsageLimitService
{
    /**
     * Streamlined premium feature catalog with high-impact upgrade drivers.
     */
    public static function getFeatureCatalog(): array
    {
        return [
            'white_label' => [
                'name' => 'Complete White-Labeling',
                'category' => 'Branding & Customization',
                'is_premium' => true,
                'description' => 'Remove "Powered by LeadCollector" badge and all platform watermarks from live quizzes.',
            ],
            'calculator_mode' => [
                'name' => 'Calculator & Quote Engine',
                'category' => 'Dynamic Calculation',
                'is_premium' => true,
                'description' => 'Dynamic math formula engine (@Q1 * 100 + @Q2), pricing estimators, and instant quote cards.',
            ],
            'webhooks' => [
                'name' => 'Outbound HTTP Webhooks',
                'category' => 'Integrations & Automation',
                'is_premium' => true,
                'description' => 'Send real-time JSON payloads to Zapier, Make, custom CRMs, or HTTP endpoints on completion.',
            ],
            'csv_export' => [
                'name' => '1-Click CSV Lead Export',
                'category' => 'Analytics & Data Export',
                'is_premium' => true,
                'description' => 'Download all lead submissions, form answers, and visitor timeline records as CSV files.',
            ],
            'api_access' => [
                'name' => 'REST API & Developer Access',
                'category' => 'Integrations & Automation',
                'is_premium' => true,
                'description' => 'Programmatic workspace API keys to query funnels and submissions externally.',
            ],
            'funnel_cloning' => [
                'name' => '1-Click Funnel Duplication',
                'category' => 'Funnel Management',
                'is_premium' => true,
                'description' => 'Instantly duplicate existing funnels and complete node canvas graphs with 1 click.',
            ],
            'step_dropoff_analytics' => [
                'name' => 'Step Drop-Off & Bottleneck Analytics',
                'category' => 'Analytics & Optimization',
                'is_premium' => true,
                'description' => 'Visual funnel drop-off metrics pinpointing exact question steps where visitors abandon.',
            ],
            'custom_tracking_pixels' => [
                'name' => 'Ad Tracking Pixels (Meta, Google, TikTok)',
                'category' => 'Marketing & Attribution',
                'is_premium' => true,
                'description' => 'Inject Meta Pixel, Google Tag Manager & TikTok Pixel tracking scripts for conversion attribution.',
            ],
        ];
    }

    /**
     * Baseline table-stakes features included across all subscription tiers.
     */
    public static function getStandardFeatures(): array
    {
        return [
            'visual_builder' => true,
            'lead_capture_forms' => true,
            'conditional_logic' => true,
            'dynamic_scoring' => true,
            'back_navigation' => true,
            'auto_redirect' => true,
            'analytics' => true,
            'journey_inspector' => true,
            'custom_branding' => true,
            'custom_trust_badges' => true,
            'custom_footer' => true,
            'embed_widgets' => true,
            'quick_connect' => true,
            'webhook_retry' => true,
            'team_collaboration' => true,
            'audit_logs' => true,
            'priority_support' => true,
        ];
    }

    /**
     * Get feature flags for a tenant.
     */
    public static function getTenantFeatures(int $tenantId): array
    {
        $sub = Database::fetchOne(
            "SELECT ts.*, p.name as plan_name, p.slug as plan_slug, p.features_json 
             FROM tenant_subscriptions ts
             JOIN plans p ON ts.plan_id = p.id
             WHERE ts.tenant_id = ? AND ts.status IN ('active', 'trial')
             ORDER BY ts.id DESC LIMIT 1",
            [$tenantId]
        );

        $catalog = self::getFeatureCatalog();
        $features = self::getStandardFeatures(); // Standard table-stakes always available

        $planFeatures = [];
        $planSlug = $sub['plan_slug'] ?? 'starter';
        if ($sub && !empty($sub['features_json'])) {
            $planFeatures = json_decode($sub['features_json'], true) ?: [];
        }

        $isAllInclusive = in_array($planSlug, ['pro', 'enterprise'], true);

        foreach ($catalog as $key => $meta) {
            if (isset($planFeatures[$key])) {
                $features[$key] = (bool)$planFeatures[$key];
            } elseif ($isAllInclusive) {
                $features[$key] = true;
            } else {
                // Premium features default to false on free/starter tiers unless explicitly granted
                $features[$key] = !$meta['is_premium'];
            }
        }

        return $features;
    }

    /**
     * Check if a tenant has access to a specific feature.
     */
    public static function hasFeature(int $tenantId, string $featureKey): bool
    {
        $features = self::getTenantFeatures($tenantId);
        return !empty($features[$featureKey]);
    }

    public static function getTenantLimits(int $tenantId): array
    {
        $sub = Database::fetchOne(
            "SELECT ts.*, p.name as plan_name, p.slug as plan_slug, p.limits_json, p.features_json 
             FROM tenant_subscriptions ts
             JOIN plans p ON ts.plan_id = p.id
             WHERE ts.tenant_id = ? AND ts.status IN ('active', 'trial')
             ORDER BY ts.id DESC LIMIT 1",
            [$tenantId]
        );

        $limits = [
            'max_funnels' => 3,
            'max_submissions_per_month' => 200,
            'max_team_members' => 2,
            'plan_name' => 'Starter Trial',
            'plan_slug' => 'starter',
            'status' => 'trial',
        ];

        if ($sub) {
            $planLimits = json_decode($sub['limits_json'] ?? '{}', true) ?: [];
            $limits['max_funnels'] = (int)($planLimits['max_funnels'] ?? 3);
            $limits['max_submissions_per_month'] = (int)($planLimits['max_submissions_per_month'] ?? 200);
            $limits['max_team_members'] = (int)($planLimits['max_team_members'] ?? 2);
            $limits['plan_name'] = $sub['plan_name'];
            $limits['plan_slug'] = $sub['plan_slug'];
            $limits['status'] = $sub['status'];
        }

        $limits['current_funnels'] = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status != 'archived'", 
            [$tenantId]
        );
        $limits['current_team_members'] = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ?", 
            [$tenantId]
        );

        return $limits;
    }

    public static function checkCanCreateFunnel(int $tenantId): array
    {
        $limits = self::getTenantLimits($tenantId);
        $currentCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status != 'archived'", [$tenantId]);

        if ($limits['max_funnels'] > 0 && $currentCount >= $limits['max_funnels']) {
            return [
                'allowed' => false,
                'message' => "You have reached your plan limit of {$limits['max_funnels']} funnels. Upgrade your subscription to create more.",
                'current' => $currentCount,
                'limit' => $limits['max_funnels'],
            ];
        }

        return ['allowed' => true, 'current' => $currentCount, 'limit' => $limits['max_funnels']];
    }

    public static function checkCanAddTeamMember(int $tenantId): array
    {
        $limits = self::getTenantLimits($tenantId);
        $currentCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ?", [$tenantId]);

        if ($limits['max_team_members'] > 0 && $currentCount >= $limits['max_team_members']) {
            return [
                'allowed' => false,
                'message' => "You have reached your plan limit of {$limits['max_team_members']} team members. Upgrade your plan to add more seats.",
                'current' => $currentCount,
                'limit' => $limits['max_team_members'],
            ];
        }

        return ['allowed' => true, 'current' => $currentCount, 'limit' => $limits['max_team_members']];
    }

    public static function checkCanAcceptSubmission(int $tenantId): array
    {
        $limits = self::getTenantLimits($tenantId);
        if ($limits['max_submissions_per_month'] <= 0) {
            return ['allowed' => true, 'current' => 0, 'limit' => 0];
        }

        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $monthlySubmissions = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND started_at >= ?",
            [$tenantId, $firstDayOfMonth]
        );

        if ($monthlySubmissions >= $limits['max_submissions_per_month']) {
            return [
                'allowed' => false,
                'message' => "Monthly lead quota exhausted ({$monthlySubmissions}/{$limits['max_submissions_per_month']}). Please upgrade your subscription to capture additional leads.",
                'current' => $monthlySubmissions,
                'limit' => $limits['max_submissions_per_month'],
            ];
        }

        return ['allowed' => true, 'current' => $monthlySubmissions, 'limit' => $limits['max_submissions_per_month']];
    }

    public static function getUsageOverview(int $tenantId): array
    {
        $limits = self::getTenantLimits($tenantId);
        $funnelCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ? AND status != 'archived'", [$tenantId]);
        $memberCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ?", [$tenantId]);

        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $monthlySubmissions = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM quiz_sessions WHERE tenant_id = ? AND started_at >= ?",
            [$tenantId, $firstDayOfMonth]
        );

        $isLeadExhausted = $limits['max_submissions_per_month'] > 0 && $monthlySubmissions >= $limits['max_submissions_per_month'];

        return [
            'plan_name' => $limits['plan_name'],
            'plan_slug' => $limits['plan_slug'],
            'status' => $limits['status'],
            'is_lead_quota_exhausted' => $isLeadExhausted,
            'funnels' => [
                'used' => $funnelCount,
                'limit' => $limits['max_funnels'],
                'is_unlimited' => $limits['max_funnels'] <= 0,
                'percentage' => $limits['max_funnels'] > 0 ? min(100, round(($funnelCount / $limits['max_funnels']) * 100)) : 0,
            ],
            'submissions' => [
                'used' => $monthlySubmissions,
                'limit' => $limits['max_submissions_per_month'],
                'is_unlimited' => $limits['max_submissions_per_month'] <= 0,
                'percentage' => $limits['max_submissions_per_month'] > 0 ? min(100, round(($monthlySubmissions / $limits['max_submissions_per_month']) * 100)) : 0,
                'is_exhausted' => $isLeadExhausted,
            ],
            'team_members' => [
                'used' => $memberCount,
                'limit' => $limits['max_team_members'],
                'is_unlimited' => $limits['max_team_members'] <= 0,
                'percentage' => $limits['max_team_members'] > 0 ? min(100, round(($memberCount / $limits['max_team_members']) * 100)) : 0,
            ],
        ];
    }
}

<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class LeadStageService
{
    /**
     * Standard default system stages.
     */
    public static function getDefaultStages(): array
    {
        return [
            ['name' => 'New Leads', 'slug' => 'new', 'color' => '#3B82F6', 'order_index' => 1, 'is_system' => 1],
            ['name' => 'Contacted', 'slug' => 'contacted', 'color' => '#F59E0B', 'order_index' => 2, 'is_system' => 1],
            ['name' => 'Qualified', 'slug' => 'qualified', 'color' => '#10B981', 'order_index' => 3, 'is_system' => 1],
            ['name' => 'Converted / Won', 'slug' => 'converted', 'color' => '#8B5CF6', 'order_index' => 4, 'is_system' => 1],
            ['name' => 'Junk / Spam', 'slug' => 'junk', 'color' => '#94A3B8', 'order_index' => 5, 'is_system' => 1],
        ];
    }

    /**
     * Map a stage slug or custom hex color to a vivid 3D sphere emoji for OS dropdowns.
     */
    public static function getStageEmoji(string $slug, string $hexColor = ''): string
    {
        // 1. Explicit mapping for core system stages
        switch (strtolower($slug)) {
            case 'new':
                return '🔵';
            case 'contacted':
                return '🟡';
            case 'qualified':
                return '🟢';
            case 'converted':
                return '🟣';
            case 'junk':
                return '⚪';
        }

        // 2. Intelligent HSL classification for custom user colors
        $hex = ltrim($hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '🔵';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $l = ($max + $min) / 2.0;

        if ($l < 0.18) {
            return '⚫'; // Black / Dark
        }
        if ($delta == 0) {
            return '⚪'; // Monochromatic / Gray
        }

        $s = $delta / (1.0 - abs(2.0 * $l - 1.0));
        if ($s < 0.16 && $l > 0.50) {
            return '⚪'; // Low saturation / slate
        }

        if ($max === $r) {
            $h = 60.0 * fmod((($g - $b) / $delta), 6);
            if ($h < 0) $h += 360.0;
        } elseif ($max === $g) {
            $h = 60.0 * ((($b - $r) / $delta) + 2.0);
        } else {
            $h = 60.0 * ((($r - $g) / $delta) + 4.0);
        }

        // Map hue degrees to colorful sphere emojis
        if ($h >= 345 || $h < 15) {
            return '🔴'; // Red
        } elseif ($h >= 15 && $h < 45) {
            return '🟠'; // Orange
        } elseif ($h >= 45 && $h < 75) {
            return '🟡'; // Yellow
        } elseif ($h >= 75 && $h < 170) {
            return '🟢'; // Green / Emerald / Teal
        } elseif ($h >= 170 && $h < 260) {
            return '🔵'; // Cyan / Blue / Indigo
        } elseif ($h >= 260 && $h < 345) {
            return '🟣'; // Purple / Violet / Pink
        }

        return '🔵';
    }

    /**
     * Get all active stages for a tenant (auto-seeds defaults if none exist).
     */
    public static function getStagesForTenant(int $tenantId): array
    {
        $stages = Database::query(
            "SELECT * FROM tenant_lead_stages WHERE tenant_id = ? ORDER BY order_index ASC, id ASC",
            [$tenantId]
        );

        if (empty($stages)) {
            // Auto-seed default stages for this tenant
            self::seedDefaultStages($tenantId);
            $stages = Database::query(
                "SELECT * FROM tenant_lead_stages WHERE tenant_id = ? ORDER BY order_index ASC, id ASC",
                [$tenantId]
            );
        }

        return $stages;
    }

    /**
     * Seed default stages for a tenant.
     */
    public static function seedDefaultStages(int $tenantId): void
    {
        $defaults = self::getDefaultStages();
        foreach ($defaults as $s) {
            Database::execute(
                "INSERT IGNORE INTO tenant_lead_stages (tenant_id, name, slug, color, order_index, is_system)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$tenantId, $s['name'], $s['slug'], $s['color'], $s['order_index'], $s['is_system']]
            );
        }
    }

    /**
     * Create a new custom lead stage for a tenant.
     */
    public static function createStage(int $tenantId, string $name, string $color): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException("Stage name cannot be empty.");
        }

        // Generate URL-safe and DB-safe slug
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'stage-' . time();
        }

        // Validate hex color or fallback to indigo
        $color = trim($color);
        if (!preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $color)) {
            $color = '#6366F1';
        }

        // Check for duplicate name or slug for this tenant
        $exists = Database::fetchOne(
            "SELECT id FROM tenant_lead_stages WHERE tenant_id = ? AND (LOWER(name) = ? OR slug = ?)",
            [$tenantId, strtolower($name), $baseSlug]
        );
        if ($exists) {
            throw new RuntimeException("A stage named '{$name}' already exists.");
        }

        $slug = $baseSlug;

        $maxOrder = (int)Database::fetchColumn(
            "SELECT MAX(order_index) FROM tenant_lead_stages WHERE tenant_id = ?",
            [$tenantId]
        );

        Database::execute(
            "INSERT INTO tenant_lead_stages (tenant_id, name, slug, color, order_index, is_system)
             VALUES (?, ?, ?, ?, ?, 0)",
            [$tenantId, $name, $slug, $color, $maxOrder + 1]
        );

        $id = (int)Database::lastInsertId();

        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
            'order_index' => $maxOrder + 1,
            'is_system' => 0
        ];
    }

    /**
     * Delete a custom lead stage (System stages cannot be deleted).
     */
    public static function deleteStage(int $tenantId, int $stageId): bool
    {
        $stage = Database::fetchOne(
            "SELECT * FROM tenant_lead_stages WHERE tenant_id = ? AND id = ?",
            [$tenantId, $stageId]
        );

        if (!$stage) {
            throw new RuntimeException("Stage not found.");
        }

        if (!empty($stage['is_system'])) {
            throw new RuntimeException("Default system stages cannot be deleted.");
        }

        // Reassign any leads that were on this deleted stage back to 'new'
        Database::execute(
            "UPDATE quiz_sessions SET lead_status = 'new' WHERE tenant_id = ? AND lead_status = ?",
            [$tenantId, $stage['slug']]
        );

        Database::execute(
            "DELETE FROM tenant_lead_stages WHERE tenant_id = ? AND id = ?",
            [$tenantId, $stageId]
        );

        return true;
    }
}

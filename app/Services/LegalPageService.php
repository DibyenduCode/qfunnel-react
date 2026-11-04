<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;

class LegalPageService
{
    /**
     * Get all legal pages for Super Admin management
     */
    public static function getAllPages(): array
    {
        return Database::query("SELECT * FROM legal_pages ORDER BY id ASC");
    }

    /**
     * Get list of published legal pages for public navigation
     */
    public static function getPublishedPages(): array
    {
        return Database::query("SELECT id, slug, title, meta_description, updated_at FROM legal_pages WHERE is_published = 1 ORDER BY id ASC");
    }

    /**
     * Find a legal page by its slug
     */
    public static function getPageBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        $sql = "SELECT * FROM legal_pages WHERE slug = ?";
        if ($publishedOnly) {
            $sql .= " AND is_published = 1";
        }
        $sql .= " LIMIT 1";

        return Database::fetchOne($sql, [$slug]);
    }

    /**
     * Find a legal page by its ID
     */
    public static function getPageById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM legal_pages WHERE id = ? LIMIT 1", [$id]);
    }

    /**
     * Create a new legal page
     */
    public static function createPage(array $data): int
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException("Page title is required.");
        }

        $rawSlug = trim($data['slug'] ?? '');
        $slug = self::sanitizeSlug(!empty($rawSlug) ? $rawSlug : $title);

        // Check uniqueness
        $existing = Database::fetchOne("SELECT id FROM legal_pages WHERE slug = ?", [$slug]);
        if ($existing) {
            throw new InvalidArgumentException("A legal page with URL slug '{$slug}' already exists.");
        }

        $metaDesc = trim($data['meta_description'] ?? '');
        $content = trim($data['content'] ?? '');
        $isPublished = !empty($data['is_published']) ? 1 : 0;

        Database::execute(
            "INSERT INTO legal_pages (slug, title, meta_description, content, is_published, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [$slug, $title, $metaDesc, $content, $isPublished]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update an existing legal page
     */
    public static function updatePage(int $id, array $data): bool
    {
        $page = self::getPageById($id);
        if (!$page) {
            throw new RuntimeException("Legal page not found.");
        }

        $title = trim($data['title'] ?? $page['title']);
        if (empty($title)) {
            throw new InvalidArgumentException("Page title cannot be empty.");
        }

        $rawSlug = trim($data['slug'] ?? $page['slug']);
        $slug = self::sanitizeSlug($rawSlug);

        // Check uniqueness if slug changed
        if ($slug !== $page['slug']) {
            $existing = Database::fetchOne("SELECT id FROM legal_pages WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existing) {
                throw new InvalidArgumentException("Another legal page with URL slug '{$slug}' already exists.");
            }
        }

        $metaDesc = trim($data['meta_description'] ?? $page['meta_description'] ?? '');
        $content = isset($data['content']) ? trim($data['content']) : $page['content'];
        $isPublished = isset($data['is_published']) ? ($data['is_published'] ? 1 : 0) : $page['is_published'];

        Database::execute(
            "UPDATE legal_pages 
             SET slug = ?, title = ?, meta_description = ?, content = ?, is_published = ?, updated_at = NOW() 
             WHERE id = ?",
            [$slug, $title, $metaDesc, $content, $isPublished, $id]
        );

        return true;
    }

    /**
     * Delete a legal page
     */
    public static function deletePage(int $id): bool
    {
        Database::execute("DELETE FROM legal_pages WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Helper to slugify string
     */
    public static function sanitizeSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-_]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Env;
use Throwable;

class SeoService
{
    /**
     * Helper to build fully qualified canonical URL.
     */
    public static function makeFullUrl(string $path = ''): string
    {
        $baseUrl = rtrim(Env::get('APP_URL', 'https://leadcollector.in'), '/');
        $clean = '/' . ltrim($path, '/');
        return $clean === '/' ? $baseUrl . '/' : $baseUrl . $clean;
    }

    /**
     * Resolves metadata for any given route path.
     */
    public static function resolveMetadata(string $path): array
    {
        $appName = Env::get('APP_NAME', 'LeadCollector');
        $cleanPath = '/' . ltrim($path, '/');
        if ($cleanPath !== '/' && str_ends_with($cleanPath, '/')) {
            $cleanPath = rtrim($cleanPath, '/');
        }

        $defaultImage = self::makeFullUrl('/dist/logo.png');
        $defaultSiteName = 'LeadCollector.in';
        $defaultTitle = "{$appName} — Visual Interactive Quiz Funnels & Lead Generation Platform";
        $defaultDesc = "Build high-converting interactive quiz funnels, calculator flows, and multi-step lead capture forms with LeadCollector.in.";

        // Match Funnel / Quiz route: /quiz/{slug} or /funnel/{slug} or /q/{slug}
        if (preg_match('#^/(quiz|funnel|q)/([a-zA-Z0-9_\-]+)#i', $cleanPath, $matches)) {
            $slug = $matches[2];
            try {
                $funnel = Database::fetchOne(
                    "SELECT f.id, f.name, f.slug, f.description, f.funnel_mode, f.settings_json, f.status,
                            t.name AS tenant_name, t.logo_url AS tenant_logo
                     FROM funnels f
                     JOIN tenants t ON t.id = f.tenant_id
                     WHERE f.slug = ? AND f.status = 'published' AND t.status = 'active'
                     LIMIT 1",
                    [$slug]
                );

                if ($funnel) {
                    $settings = !empty($funnel['settings_json']) ? json_decode($funnel['settings_json'], true) : [];
                    $seoSettings = $settings['seo'] ?? [];

                    $funnelTitle = !empty($seoSettings['meta_title'])
                        ? $seoSettings['meta_title']
                        : (!empty($settings['meta_title']) ? $settings['meta_title'] : "{$funnel['name']} | {$funnel['tenant_name']}");

                    $funnelDesc = !empty($seoSettings['meta_description'])
                        ? $seoSettings['meta_description']
                        : (!empty($settings['meta_description'])
                            ? $settings['meta_description']
                            : (!empty($funnel['description'])
                                ? mb_substr(strip_tags($funnel['description']), 0, 160)
                                : "Take this interactive {$funnel['funnel_mode']} by {$funnel['tenant_name']} to receive personalized recommendations and instant quotes."));

                    $funnelImage = !empty($seoSettings['og_image_url'])
                        ? $seoSettings['og_image_url']
                        : (!empty($settings['logo_url'])
                            ? $settings['logo_url']
                            : (!empty($funnel['tenant_logo']) ? $funnel['tenant_logo'] : $defaultImage));

                    if (!empty($funnelImage) && !str_starts_with($funnelImage, 'http://') && !str_starts_with($funnelImage, 'https://')) {
                        $funnelImage = self::makeFullUrl('/' . ltrim($funnelImage, '/'));
                    }

                    return [
                        'title' => $funnelTitle,
                        'description' => $funnelDesc,
                        'image' => $funnelImage,
                        'url' => self::makeFullUrl("/quiz/{$funnel['slug']}"),
                        'type' => 'article',
                        'site_name' => $funnel['tenant_name'] ?: $defaultSiteName,
                        'noindex' => false,
                    ];
                }
            } catch (Throwable $e) {
                // Graceful fallback on database error
            }

            // Fallback for draft or preview funnel
            return [
                'title' => "Interactive Quiz — {$appName}",
                'description' => "Complete this interactive quiz to get tailored recommendations.",
                'image' => $defaultImage,
                'url' => self::makeFullUrl($cleanPath),
                'type' => 'website',
                'site_name' => $defaultSiteName,
                'noindex' => true,
            ];
        }

        // Match Legal pages: /terms, /privacy, /refund-policy, /cookie-policy, /legal/{slug}
        if (preg_match('#^/(terms|privacy|refund-policy|cookie-policy|legal(?:/([a-zA-Z0-9_\-]+))?)$#i', $cleanPath, $matches)) {
            $legalSlug = $matches[2] ?? $matches[1];
            try {
                $legalPage = Database::fetchOne(
                    "SELECT title, meta_description, content FROM legal_pages WHERE slug = ? AND is_published = 1 LIMIT 1",
                    [$legalSlug]
                );

                if ($legalPage) {
                    $desc = !empty($legalPage['meta_description'])
                        ? $legalPage['meta_description']
                        : mb_substr(strip_tags($legalPage['content']), 0, 160);

                    return [
                        'title' => "{$legalPage['title']} — {$appName}",
                        'description' => $desc,
                        'image' => $defaultImage,
                        'url' => self::makeFullUrl($cleanPath),
                        'type' => 'website',
                        'site_name' => $defaultSiteName,
                        'noindex' => false,
                    ];
                }
            } catch (Throwable $e) {
                // Fallback
            }

            $legalTitles = [
                'terms' => 'Terms & Conditions',
                'privacy' => 'Privacy Policy',
                'refund-policy' => 'Refund & Cancellation Policy',
                'cookie-policy' => 'Cookie Policy',
            ];
            $title = ($legalTitles[$legalSlug] ?? ucwords(str_replace('-', ' ', $legalSlug))) . " — {$appName}";

            return [
                'title' => $title,
                'description' => "Read the official {$title} for {$defaultSiteName}.",
                'image' => $defaultImage,
                'url' => self::makeFullUrl($cleanPath),
                'type' => 'website',
                'site_name' => $defaultSiteName,
                'noindex' => false,
            ];
        }

        // Match Pricing page: /pricing
        if ($cleanPath === '/pricing') {
            return [
                'title' => "Transparent Plans & Pricing — {$appName}",
                'description' => "Choose the right plan for your business. Free, Starter, Standard, and Pro tiers with unlimited lead captures, interactive calculators, and email automation.",
                'image' => $defaultImage,
                'url' => self::makeFullUrl('/pricing'),
                'type' => 'website',
                'site_name' => $defaultSiteName,
                'noindex' => false,
            ];
        }

        // Match Auth routes: /login, /register, /forgot-password, /reset-password
        if (in_array($cleanPath, ['/login', '/register', '/forgot-password', '/reset-password', '/verify-otp'], true)) {
            $authTitles = [
                '/login' => "Sign In — {$appName}",
                '/register' => "Create Account — {$appName}",
                '/forgot-password' => "Forgot Password — {$appName}",
                '/reset-password' => "Reset Password — {$appName}",
                '/verify-otp' => "Verify Your Email — {$appName}",
            ];
            return [
                'title' => $authTitles[$cleanPath] ?? "Account — {$appName}",
                'description' => "Sign in to manage your high-converting quiz funnels and leads.",
                'image' => $defaultImage,
                'url' => self::makeFullUrl($cleanPath),
                'type' => 'website',
                'site_name' => $defaultSiteName,
                'noindex' => true,
            ];
        }

        // Match Admin/Dashboard routes: /admin, /app, /dashboard
        if (str_starts_with($cleanPath, '/admin') || str_starts_with($cleanPath, '/app') || str_starts_with($cleanPath, '/dashboard')) {
            return [
                'title' => "Workspace Dashboard — {$appName}",
                'description' => "Manage your visual quiz funnels, view analytics, and export verified leads.",
                'image' => $defaultImage,
                'url' => self::makeFullUrl($cleanPath),
                'type' => 'website',
                'site_name' => $defaultSiteName,
                'noindex' => true,
            ];
        }

        // Root Home / Landing page
        return [
            'title' => $defaultTitle,
            'description' => $defaultDesc,
            'image' => $defaultImage,
            'url' => self::makeFullUrl('/'),
            'type' => 'website',
            'site_name' => $defaultSiteName,
            'noindex' => false,
        ];
    }

    /**
     * Injects dynamic meta tags and replaces title in HTML string.
     */
    public static function injectMetadataIntoHtml(string $html, string $path): string
    {
        $meta = self::resolveMetadata($path);

        $title = htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($meta['url'], ENT_QUOTES, 'UTF-8');
        $image = htmlspecialchars($meta['image'], ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($meta['type'], ENT_QUOTES, 'UTF-8');
        $siteName = htmlspecialchars($meta['site_name'], ENT_QUOTES, 'UTF-8');

        $robots = !empty($meta['noindex'])
            ? '<meta name="robots" content="noindex, nofollow" />'
            : '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />';

        // Construct complete SEO + Open Graph + Twitter Card tags
        $seoTags = implode("\n    ", [
            '<!-- Dynamic SEO & Open Graph Meta Tags (Server Injected for Crawlers & Social Previews) -->',
            "<meta name=\"description\" content=\"{$desc}\" />",
            "<link rel=\"canonical\" href=\"{$url}\" />",
            $robots,
            "<meta property=\"og:type\" content=\"{$type}\" />",
            "<meta property=\"og:site_name\" content=\"{$siteName}\" />",
            "<meta property=\"og:title\" content=\"{$title}\" />",
            "<meta property=\"og:description\" content=\"{$desc}\" />",
            "<meta property=\"og:url\" content=\"{$url}\" />",
            "<meta property=\"og:image\" content=\"{$image}\" />",
            "<meta property=\"og:image:alt\" content=\"{$title}\" />",
            '<meta name="twitter:card" content="summary_large_image" />',
            "<meta name=\"twitter:title\" content=\"{$title}\" />",
            "<meta name=\"twitter:description\" content=\"{$desc}\" />",
            "<meta name=\"twitter:image\" content=\"{$image}\" />",
        ]);

        // 1. Replace existing <title>...</title>
        if (preg_match('#<title>.*?</title>#i', $html)) {
            $html = preg_replace('#<title>.*?</title>#i', "<title>{$title}</title>", $html, 1);
        } else {
            $seoTags = "<title>{$title}</title>\n    " . $seoTags;
        }

        // 2. Inject right after <head> or before </head>
        if (stripos($html, '<head>') !== false) {
            $html = preg_replace('#(<head[^>]*>)#i', "$1\n    " . $seoTags, $html, 1);
        } elseif (stripos($html, '</head>') !== false) {
            $html = str_ireplace('</head>', "    {$seoTags}\n  </head>", $html);
        } else {
            $html = $seoTags . "\n" . $html;
        }

        return $html;
    }

    /**
     * Generates dynamic XML sitemap listing all published funnels and public static pages.
     */
    public static function generateSitemapXml(): string
    {
        $baseUrl = rtrim(Env::get('APP_URL', 'https://leadcollector.in'), '/');
        $nowIso = date('c');

        $staticUrls = [
            ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $nowIso],
            ['loc' => $baseUrl . '/pricing', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $nowIso],
            ['loc' => $baseUrl . '/terms', 'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $nowIso],
            ['loc' => $baseUrl . '/privacy', 'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $nowIso],
            ['loc' => $baseUrl . '/refund-policy', 'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $nowIso],
            ['loc' => $baseUrl . '/cookie-policy', 'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $nowIso],
        ];

        // Fetch published funnels
        $funnelUrls = [];
        try {
            $funnels = Database::query(
                "SELECT f.slug, f.updated_at
                 FROM funnels f
                 JOIN tenants t ON t.id = f.tenant_id
                 WHERE f.status = 'published' AND t.status = 'active'
                 ORDER BY f.updated_at DESC"
            );

            foreach ($funnels as $f) {
                $lastmod = !empty($f['updated_at']) ? date('c', strtotime($f['updated_at'])) : $nowIso;
                $funnelUrls[] = [
                    'loc' => $baseUrl . '/quiz/' . rawurlencode($f['slug']),
                    'priority' => '0.9',
                    'changefreq' => 'daily',
                    'lastmod' => $lastmod,
                ];
            }
        } catch (Throwable $e) {
            // Graceful fallback
        }

        // Fetch published legal pages from DB if any
        try {
            $legalPages = Database::query("SELECT slug, updated_at FROM legal_pages WHERE is_published = 1");
            foreach ($legalPages as $lp) {
                $loc = $baseUrl . '/' . ltrim($lp['slug'], '/');
                $exists = false;
                foreach ($staticUrls as &$s) {
                    if ($s['loc'] === $loc) {
                        $s['lastmod'] = date('c', strtotime($lp['updated_at']));
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $staticUrls[] = [
                        'loc' => $loc,
                        'priority' => '0.4',
                        'changefreq' => 'monthly',
                        'lastmod' => date('c', strtotime($lp['updated_at'])),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Graceful fallback
        }

        $allUrls = array_merge($staticUrls, $funnelUrls);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($allUrls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Generates dynamic robots.txt.
     */
    public static function generateRobotsTxt(): string
    {
        $baseUrl = rtrim(Env::get('APP_URL', 'https://leadcollector.in'), '/');

        return "User-agent: *\n" .
               "Allow: /\n" .
               "Allow: /quiz/\n" .
               "Allow: /funnel/\n" .
               "Allow: /pricing\n" .
               "Allow: /terms\n" .
               "Allow: /privacy\n" .
               "Allow: /refund-policy\n" .
               "Allow: /cookie-policy\n" .
               "Disallow: /admin/\n" .
               "Disallow: /api/\n" .
               "Disallow: /storage/\n" .
               "Disallow: /login\n" .
               "Disallow: /register\n" .
               "Disallow: /forgot-password\n" .
               "Disallow: /reset-password\n\n" .
               "Sitemap: {$baseUrl}/sitemap.xml\n";
    }
}

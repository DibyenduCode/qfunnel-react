<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;

class LegalPageService
{
    /**
     * Ensure default legal and company policy documents are seeded if table is empty
     */
    public static function ensureDefaults(): void
    {
        try {
            $countRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM legal_pages");
            $count = (int)($countRow['cnt'] ?? 0);
            if ($count > 0) {
                return;
            }

            self::seedDefaultPages();
        } catch (\Throwable $e) {
            // Silently ignore if table does not exist or connection fails
        }
    }

    /**
     * Seed initial production legal and company policies
     */
    public static function seedDefaultPages(): void
    {
        $defaults = [
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'meta_description' => 'Learn how LeadCollector collects, stores, and safeguards your data and user assessment submissions.',
                'content' => <<<HTML
<h2>1. Overview & Commitment</h2>
<p>At LeadCollector ("we", "our", or "us", accessible via leadcollector.in), we prioritize the privacy and confidentiality of our customers, website visitors, and end-users who interact with interactive quizzes and calculators created through our software platform. This Privacy Policy details how we collect, store, isolate, and safeguard your data.</p>

<h2>2. Information We Collect</h2>
<h3>A. Workspace Customer Account Data</h3>
<p>When you register for a LeadCollector account, we collect your name, business email address, company name, workspace subdomains, and encrypted password credentials.</p>
<h3>B. End-User Lead Submission Data</h3>
<p>When visitors complete an interactive branching quiz, estimate calculator, or lead form hosted via our runner, we collect the responses, answers, calculated quotes, timestamps, and optional contact details provided by the visitor.</p>

<h2>3. Multi-Tenant Data Isolation & Security</h2>
<p>LeadCollector is architected with strict multi-tenancy boundaries. Customer workspace data and lead submissions are strictly isolated by unique tenant identifiers. One workspace tenant cannot access, query, or view data belonging to another workspace.</p>
<p><strong>Encryption Standards:</strong> All data transmitted between your visitors and LeadCollector is protected via TLS 1.3 / 256-Bit SSL encryption. Sensitive authentication secrets and webhook tokens are hashed using industry-standard algorithms.</p>

<h2>4. How We Use Information</h2>
<ul>
  <li>To operate, maintain, and render visual branching funnels and calculations.</li>
  <li>To deliver lead notifications to your connected integrations (Zapier, Webhooks, WhatsApp).</li>
  <li>To calculate conversion metrics, drop-off rates, and funnel analytics.</li>
  <li>We do not sell, rent, or monetize your customer lead data to third parties or advertising networks.</li>
</ul>

<h2>5. GDPR & Data Subject Rights</h2>
<p>Under GDPR, CCPA, and applicable global privacy laws, you and your visitors have the right to access, rectify, export, or permanently delete personal data. You can delete lead submissions or workspaces directly from the LeadCollector dashboard or by emailing privacy@leadcollector.in.</p>

<h2>6. Contact Our Privacy Officer</h2>
<p>If you have questions regarding this Privacy Policy or our security practices, contact us at:</p>
<p>LeadCollector Data Protection Officer<br>Email: privacy@leadcollector.in<br>Domain: leadcollector.in</p>
HTML,
                'is_published' => 1,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'meta_description' => 'Review the terms and conditions governing the use of the LeadCollector quiz funnels platform.',
                'content' => <<<HTML
<h2>1. Acceptance of Terms</h2>
<p>By registering for an account, accessing, or using LeadCollector ("the Service", provided by LeadCollector Technologies Pvt Ltd / LeadCollector.in), you agree to be bound by these Terms of Service. If you are entering into this agreement on behalf of a company, organization, or other legal entity, you represent that you possess the authority to bind such entity to these terms.</p>

<h2>2. Account Registration & Tenant Security</h2>
<p>To use LeadCollector, you must provide valid credentials during account creation. You are solely responsible for safeguarding the credentials associated with your workspace and all actions taken under your account. You must notify LeadCollector immediately if you discover any unauthorized use or security compromise.</p>

<h2>3. Acceptable Use Policy</h2>
<p>You agree not to misuse the platform. Specifically, you agree that you will not use LeadCollector to:</p>
<ul>
  <li>Collect personal credit card numbers, national identification numbers, or HIPAA-restricted patient health data directly into unsecured form input fields without approved compliance add-ons.</li>
  <li>Conduct phishing campaigns, fraudulent deceptive sweepstakes, or distribute malicious code.</li>
  <li>Exceed rate limits or attempt to reverse-engineer the underlying API infrastructure and tenant isolation models.</li>
  <li>Send unsolicited commercial bulk emails (SPAM) via webhook triggers or calendar integrations.</li>
</ul>

<h2>4. Subscription Billing, Upgrades & Cancellations</h2>
<p>Paid subscription plans (Starter, Growth, Agency Scale) are billed in advance on a recurring monthly or annual basis via Stripe, Cashfree, PayPal, or supported regional payment processors. Upgrades take effect immediately with prorated billing. Downgrades or cancellations take effect at the end of the current paid billing cycle.</p>

<h2>5. Data Ownership & Customer Intellectual Property</h2>
<p>You retain all ownership rights, title, and interest in and to all question structures, funnel configurations, branding assets, formulas, and submission data you create or collect using LeadCollector. LeadCollector retains all rights, title, and interest in the software platform, runtime engine, visual canvas components, and documentation.</p>

<h2>6. Service Level Agreement & Availability</h2>
<p>We strive to maintain a 99.9% uptime record for the public quiz runner runtime. Scheduled maintenance windows are communicated in advance via our operational status channels.</p>

<h2>7. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law, in no event shall LeadCollector, its affiliates, directors, or employees be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or related to your use of the Service.</p>

<h2>8. Governing Law & Contact</h2>
<p>For questions regarding these Terms of Service, contact our legal team at legal@leadcollector.in.</p>
HTML,
                'is_published' => 1,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'meta_description' => 'Understand how LeadCollector uses cookies, session identifiers, and tracking technologies.',
                'content' => <<<HTML
<h2>1. What Are Cookies?</h2>
<p>Cookies are small data files stored on your device (computer, tablet, or smartphone) when you visit websites or interact with web-based applications. They are widely used to ensure websites function properly, retain user state across multiple steps, and deliver customized user experiences.</p>

<h2>2. The Cookies We Use & Why</h2>
<ul>
  <li><strong>Essential & Security Cookies:</strong> Strictly required to maintain secure administrator sessions, protect against Cross-Site Request Forgery (CSRF), and verify authenticated tenant boundaries. These cannot be disabled.</li>
  <li><strong>Quiz Runner State Cookies:</strong> Stores session identifiers for active quiz respondents, allowing users to navigate between branching questions and retain partial quote calculation without losing their progress.</li>
  <li><strong>Performance & Analytics Cookies:</strong> Helps us understand how visitors interact with the marketing homepage and funnels to optimize page speed and conversion paths.</li>
</ul>

<h2>3. Managing Cookie Preferences</h2>
<p>You can configure your browser settings to reject or delete cookies. However, please note that disabling essential cookies may impact the proper functionality of the LeadCollector quiz builder and authenticated dashboards.</p>
HTML,
                'is_published' => 1,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund & Cancellation Policy',
                'meta_description' => 'Review our clear cancellation terms, subscription downgrade rules, and refund policy.',
                'content' => <<<HTML
<h2>1. Subscription Billing & Auto-Renewal</h2>
<p>LeadCollector subscription plans are billed on a recurring monthly or annual cycle. You may cancel your subscription at any time directly from your Workspace Billing dashboard.</p>

<h2>2. 14-Day Money Back Guarantee for Annual Plans</h2>
<p>We provide a 14-day money-back guarantee for all newly purchased annual subscription plans. If you are not completely satisfied with LeadCollector within 14 days of your initial annual upgrade, contact support for a full refund.</p>

<h2>3. Monthly Subscriptions & Cancellations</h2>
<p>Monthly subscriptions can be canceled at any time prior to the next renewal date. When canceled, your plan will remain active until the end of the paid billing period, and no further charges will occur. Partial month refunds are not issued.</p>

<h2>4. How to Request a Refund</h2>
<p>To submit a cancellation or refund inquiry, email billing@leadcollector.in with your workspace name and account email. Our finance team processes eligible refund requests within 3 to 5 business days.</p>
HTML,
                'is_published' => 1,
            ],
            [
                'slug' => 'about',
                'title' => 'About LeadCollector',
                'meta_description' => 'LeadCollector transforms static web forms into intelligent, interactive conversations with dynamic quotes and visual quiz funnels.',
                'content' => <<<HTML
<h2>1. Our Mission</h2>
<p>We founded LeadCollector with a simple conviction: traditional 10-field web forms are broken. Buyers demand immediate value, transparency, and personal recommendations before handing over their contact information. We transform static web forms into intelligent, interactive conversations that convert cold visitors into qualified buyers.</p>

<h2>2. The Problem: Static Forms Bounce 97% of Prospective Buyers</h2>
<p>When high-intent prospects arrive on your landing pages, presenting them with a wall of empty input boxes feels demanding and transactional. Visitors leave without knowing if your service fits their budget, timeline, or exact requirements. High cost-per-click ad traffic is wasted because forms ask for everything while delivering zero upfront value.</p>

<h2>3. The LeadCollector Solution: Dynamic Diagnostic Journeys</h2>
<p>LeadCollector replaces rigid forms with guided visual question paths. Each answer recalculates dynamic project quotes, qualifies visitor readiness, and routes hot buyers straight to WhatsApp or scheduled consultations. Visitors willingly share details because they receive a transparent instant quote, tailored roadmap, and direct expert access.</p>

<h2>4. Architectural Pillars</h2>
<ul>
  <li><strong>Visual Node Canvas:</strong> Built on interactive React Flow graphs with zero-code branching, arithmetic formulas, score tallies, and live step previewing.</li>
  <li><strong>Strict Multi-Tenancy:</strong> Complete data isolation across tenants with encrypted API credentials, granular role-based team management, and GDPR compliance.</li>
  <li><strong>Immediate Sales Handoff:</strong> Convert prospects within 60 seconds with 1-click WhatsApp deep links, Calendly appointments, and automated webhooks.</li>
</ul>

<h2>5. Contact & Support</h2>
<p>Have questions or need enterprise assistance? Contact our team at <a href="mailto:contact@leadcollector.in">contact@leadcollector.in</a> or visit our <a href="/support">Help Center</a>.</p>
HTML,
                'is_published' => 1,
            ]
        ];

        foreach ($defaults as $page) {
            Database::execute(
                "INSERT IGNORE INTO legal_pages (slug, title, meta_description, content, is_published, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [$page['slug'], $page['title'], $page['meta_description'], $page['content'], $page['is_published']]
            );
        }
    }

    /**
     * Get all legal pages for Super Admin management
     */
    public static function getAllPages(): array
    {
        self::ensureDefaults();
        return Database::query("SELECT * FROM legal_pages ORDER BY id ASC");
    }

    /**
     * Get list of published legal pages for public navigation
     */
    public static function getPublishedPages(): array
    {
        self::ensureDefaults();
        return Database::query("SELECT id, slug, title, meta_description, updated_at FROM legal_pages WHERE is_published = 1 ORDER BY id ASC");
    }

    /**
     * Find a legal page by its slug
     */
    public static function getPageBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        self::ensureDefaults();
        $cleanSlug = self::sanitizeSlug($slug);

        // Support cookie aliases (cookies <-> cookie-policy)
        $slugVariants = [$cleanSlug];
        if ($cleanSlug === 'cookies') {
            $slugVariants[] = 'cookie-policy';
        } elseif ($cleanSlug === 'cookie-policy') {
            $slugVariants[] = 'cookies';
        }

        $placeholders = implode(',', array_fill(0, count($slugVariants), '?'));
        $sql = "SELECT * FROM legal_pages WHERE slug IN ({$placeholders})";
        if ($publishedOnly) {
            $sql .= " AND is_published = 1";
        }
        $sql .= " LIMIT 1";

        return Database::fetchOne($sql, $slugVariants);
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

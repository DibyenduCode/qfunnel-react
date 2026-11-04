<?php

namespace Database\Seeders;

use App\Core\Database;
use App\Core\Logger;

class DatabaseSeeder
{
    public static function run(): void
    {
        echo "=== Seeding Quiz Funnel SaaS Database ===\n";

        // 1. Seed Plans
        self::seedPlans();

        // 2. Seed Users & Tenants
        $data = self::seedUsersAndTenants();

        // 3. Seed Reference Funnel
        self::seedReferenceFunnel($data['tenant_id'], $data['owner_id']);

        echo "\n[✓] Seeding completed successfully!\n";
    }

    private static function seedPlans(): void
    {
        echo "[>] Seeding Subscription Plans... ";
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0.00,
                'billing_interval' => 'monthly',
                'description' => 'Essential single-funnel plan for testing the platform',
                'limits_json' => json_encode(['max_funnels' => 1, 'max_submissions_per_month' => 100, 'max_team_members' => 1]),
                'features_json' => json_encode([
                    'visual_builder' => true,
                    'conditional_logic' => true,
                    'lead_capture_forms' => true,
                    'dynamic_scoring' => true,
                    'funnel_cloning' => false,
                    'custom_branding' => false,
                    'white_label' => false,
                    'custom_trust_badges' => false,
                    'custom_footer' => false,
                    'embed_widgets' => false,
                    'auto_redirect' => true,
                    'back_navigation' => true,
                    'analytics' => true,
                    'step_dropoff_analytics' => false,
                    'csv_export' => false,
                    'journey_inspector' => true,
                    'webhooks' => false,
                    'webhook_retry' => false,
                    'api_access' => false,
                    'team_collaboration' => false,
                    'audit_logs' => false,
                    'priority_support' => false,
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 749.00,
                'billing_interval' => 'monthly',
                'description' => 'Ideal for emerging creators and growing lead funnels',
                'limits_json' => json_encode(['max_funnels' => 5, 'max_submissions_per_month' => 1000, 'max_team_members' => 3]),
                'features_json' => json_encode([
                    'visual_builder' => true,
                    'conditional_logic' => true,
                    'lead_capture_forms' => true,
                    'dynamic_scoring' => true,
                    'funnel_cloning' => true,
                    'custom_branding' => false,
                    'white_label' => false,
                    'custom_trust_badges' => true,
                    'custom_footer' => true,
                    'embed_widgets' => true,
                    'auto_redirect' => true,
                    'back_navigation' => true,
                    'analytics' => true,
                    'step_dropoff_analytics' => false,
                    'csv_export' => true,
                    'journey_inspector' => true,
                    'webhooks' => false,
                    'webhook_retry' => false,
                    'api_access' => false,
                    'team_collaboration' => true,
                    'audit_logs' => false,
                    'priority_support' => false,
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'price' => 999.00,
                'billing_interval' => 'monthly',
                'description' => 'Robust multi-funnel plan for scaling marketing operations',
                'limits_json' => json_encode(['max_funnels' => 10, 'max_submissions_per_month' => 5000, 'max_team_members' => 10]),
                'features_json' => json_encode([
                    'visual_builder' => true,
                    'conditional_logic' => true,
                    'lead_capture_forms' => true,
                    'dynamic_scoring' => true,
                    'funnel_cloning' => true,
                    'custom_branding' => true,
                    'white_label' => false,
                    'custom_trust_badges' => true,
                    'custom_footer' => true,
                    'embed_widgets' => true,
                    'auto_redirect' => true,
                    'back_navigation' => true,
                    'analytics' => true,
                    'step_dropoff_analytics' => true,
                    'csv_export' => true,
                    'journey_inspector' => true,
                    'webhooks' => true,
                    'webhook_retry' => true,
                    'api_access' => false,
                    'team_collaboration' => true,
                    'audit_logs' => true,
                    'priority_support' => false,
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 1499.00,
                'billing_interval' => 'monthly',
                'description' => 'Unlimited funnels and submissions for high-velocity teams',
                'limits_json' => json_encode(['max_funnels' => -1, 'max_submissions_per_month' => -1, 'max_team_members' => -1]),
                'features_json' => json_encode([
                    'visual_builder' => true,
                    'conditional_logic' => true,
                    'lead_capture_forms' => true,
                    'dynamic_scoring' => true,
                    'funnel_cloning' => true,
                    'custom_branding' => true,
                    'white_label' => true,
                    'custom_trust_badges' => true,
                    'custom_footer' => true,
                    'embed_widgets' => true,
                    'auto_redirect' => true,
                    'back_navigation' => true,
                    'analytics' => true,
                    'step_dropoff_analytics' => true,
                    'csv_export' => true,
                    'journey_inspector' => true,
                    'webhooks' => true,
                    'webhook_retry' => true,
                    'api_access' => true,
                    'team_collaboration' => true,
                    'audit_logs' => true,
                    'priority_support' => true,
                ]),
                'is_active' => 1
            ]
        ];

        foreach ($plans as $p) {
            $existing = Database::fetchOne("SELECT id FROM plans WHERE slug = ?", [$p['slug']]);
            if (!$existing) {
                Database::execute(
                    "INSERT INTO plans (name, slug, price, billing_interval, description, limits_json, features_json, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$p['name'], $p['slug'], $p['price'], $p['billing_interval'], $p['description'], $p['limits_json'], $p['features_json'], $p['is_active']]
                );
            } else {
                Database::execute(
                    "UPDATE plans SET name = ?, price = ?, billing_interval = ?, description = ?, limits_json = ?, features_json = ?, is_active = ? WHERE id = ?",
                    [$p['name'], $p['price'], $p['billing_interval'], $p['description'], $p['limits_json'], $p['features_json'], $p['is_active'], $existing['id']]
                );
            }
        }
        echo "DONE.\n";
    }

    private static function seedUsersAndTenants(): array
    {
        echo "[>] Seeding Admin, Tenant, and Users... ";
        $passwordHash = password_hash('Password123!', PASSWORD_DEFAULT);

        // Platform Admin
        $admin = Database::fetchOne("SELECT id FROM users WHERE email = ?", ['superadmin@qfunnel.local']);
        if (!$admin) {
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 1, 'active')",
                ['System Administrator', 'superadmin@qfunnel.local', $passwordHash]
            );
            $adminId = Database::lastInsertId();
        } else {
            $adminId = $admin['id'];
        }

        // Tenant
        $tenant = Database::fetchOne("SELECT id FROM tenants WHERE slug = ?", ['apex-health']);
        if (!$tenant) {
            Database::execute(
                "INSERT INTO tenants (name, slug, status) VALUES (?, ?, 'active')",
                ['Apex Health & Wellness', 'apex-health']
            );
            $tenantId = Database::lastInsertId();
        } else {
            $tenantId = $tenant['id'];
        }

        // Tenant Owner
        $owner = Database::fetchOne("SELECT id FROM users WHERE email = ?", ['owner@qfunnel.local']);
        if (!$owner) {
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 0, 'active')",
                ['Dr. Alex Morgan', 'owner@qfunnel.local', $passwordHash]
            );
            $ownerId = Database::lastInsertId();
        } else {
            $ownerId = $owner['id'];
        }

        // Team Editor
        $editor = Database::fetchOne("SELECT id FROM users WHERE email = ?", ['editor@qfunnel.local']);
        if (!$editor) {
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 0, 'active')",
                ['Jamie Lee', 'editor@qfunnel.local', $passwordHash]
            );
            $editorId = Database::lastInsertId();
        } else {
            $editorId = $editor['id'];
        }

        // Memberships: Workspace belongs to Customer Owner and Team Members
        // (Platform Super Admin operates globally and does not take a workspace seat)
        Database::execute(
            "INSERT IGNORE INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, 'owner')",
            [$tenantId, $ownerId]
        );
        Database::execute(
            "INSERT IGNORE INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, 'editor')",
            [$tenantId, $editorId]
        );

        // Tenant Subscription
        $proPlan = Database::fetchOne("SELECT id FROM plans WHERE slug = 'pro'");
        $existingSub = Database::fetchOne("SELECT id FROM tenant_subscriptions WHERE tenant_id = ?", [$tenantId]);
        if (!$existingSub && $proPlan) {
            Database::execute(
                "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, starts_at, ends_at) VALUES (?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))",
                [$tenantId, $proPlan['id']]
            );
        }

        echo "DONE.\n";
        return ['tenant_id' => $tenantId, 'owner_id' => $ownerId];
    }

    private static function seedReferenceFunnel(int $tenantId, int $ownerId): void
    {
        echo "[>] Seeding Doctor Practice Referral Funnel with visual branching... ";

        $existing = Database::fetchOne("SELECT id FROM funnels WHERE tenant_id = ? AND slug = ?", [$tenantId, 'doctor-referral']);
        if ($existing) {
            echo "ALREADY EXISTS.\n";
            return;
        }

        Database::transaction(function () use ($tenantId, $ownerId) {
            // 1. Create Funnel
            $settingsJson = json_encode([
                'theme' => 'light',
                'accent_color' => '#5B5BD6',
                'enable_back_button' => true,
                'show_progress_bar' => true,
                'auto_redirect' => false,
                'privacy_policy_url' => 'https://apexhealth.example.com/privacy',
            ]);

            Database::execute(
                "INSERT INTO funnels (tenant_id, name, slug, description, status, settings_json, created_by) VALUES (?, ?, ?, ?, 'draft', ?, ?)",
                [
                    $tenantId,
                    'Doctor Practice Referral Funnel',
                    'doctor-referral',
                    'Directs healthcare professionals and patients to specialized clinical centers based on profession, specialty, and location.',
                    $settingsJson,
                    $ownerId
                ]
            );
            $funnelId = (int) Database::lastInsertId();

            // 2. Pre-Quiz Form Fields
            $fields = [
                [
                    'field_key' => 'full_name',
                    'label' => 'Full Name',
                    'field_type' => 'text',
                    'placeholder' => 'e.g. Dr. Jane Doe',
                    'is_required' => 1,
                    'display_order' => 1,
                ],
                [
                    'field_key' => 'email',
                    'label' => 'Email Address',
                    'field_type' => 'email',
                    'placeholder' => 'you@domain.com',
                    'is_required' => 1,
                    'display_order' => 2,
                ],
                [
                    'field_key' => 'phone',
                    'label' => 'Phone / WhatsApp Number',
                    'field_type' => 'phone',
                    'placeholder' => '+91 98765 43210',
                    'is_required' => 1,
                    'display_order' => 3,
                ],
                [
                    'field_key' => 'contact_preference',
                    'label' => 'Preferred Contact Channel',
                    'field_type' => 'dropdown',
                    'placeholder' => 'Select preferred method',
                    'is_required' => 0,
                    'display_order' => 4,
                    'options' => ['Email', 'WhatsApp', 'Phone Call']
                ],
                [
                    'field_key' => 'consent',
                    'label' => 'I agree to receive personalized clinical referral updates.',
                    'field_type' => 'consent',
                    'placeholder' => null,
                    'is_required' => 1,
                    'display_order' => 5,
                ]
            ];

            foreach ($fields as $f) {
                Database::execute(
                    "INSERT INTO form_fields (tenant_id, funnel_id, field_key, label, field_type, placeholder, is_required, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [$tenantId, $funnelId, $f['field_key'], $f['label'], $f['field_type'], $f['placeholder'], $f['is_required'], $f['display_order']]
                );
                $fieldId = Database::lastInsertId();
                if (!empty($f['options'])) {
                    $optOrder = 1;
                    foreach ($f['options'] as $optVal) {
                        Database::execute(
                            "INSERT INTO form_field_options (tenant_id, field_id, label, value, display_order) VALUES (?, ?, ?, ?, ?)",
                            [$tenantId, $fieldId, $optVal, $optVal, $optOrder++]
                        );
                    }
                }
            }

            // 3. Funnel Nodes
            // Start Node
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'start_node', 'start', 'Funnel Entry', 80.00, 200.00)",
                [$tenantId, $funnelId]
            );
            $startNodeId = (int) Database::lastInsertId();

            // Question 1: Profession
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'q1_node', 'question', 'Primary Profession', 320.00, 200.00)",
                [$tenantId, $funnelId]
            );
            $q1NodeId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'What is your current profession?', 'Select the category that best describes your primary work.', 'single_choice', 1, 1)",
                [$tenantId, $funnelId, $q1NodeId]
            );
            $q1Id = (int) Database::lastInsertId();

            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_doctor', 'Doctor / Medical Specialist', 'doctor', 1)", [$tenantId, $q1Id]);
            $optDoctorId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_biz', 'Business Owner / Healthcare Admin', 'business_owner', 2)", [$tenantId, $q1Id]);
            $optBizId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_student', 'Medical Student / Researcher', 'student', 3)", [$tenantId, $q1Id]);
            $optStudentId = (int) Database::lastInsertId();

            // Question 2: Doctor Specialty
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'q2_node', 'question', 'Specialization', 620.00, 100.00)",
                [$tenantId, $funnelId]
            );
            $q2NodeId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'What type of doctor are you?', 'Choose your medical specialty domain.', 'single_choice', 1, 2)",
                [$tenantId, $funnelId, $q2NodeId]
            );
            $q2Id = (int) Database::lastInsertId();

            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_psych', 'Psychiatrist / Neuropsychiatrist', 'psychiatrist', 1)", [$tenantId, $q2Id]);
            $optPsychId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_dentist', 'Dentist / Orthodontist', 'dentist', 2)", [$tenantId, $q2Id]);
            $optDentistId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_physician', 'General Physician / Internal Medicine', 'physician', 3)", [$tenantId, $q2Id]);
            $optPhysicianId = (int) Database::lastInsertId();

            // Question 3: City
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'q3_node', 'question', 'Practice City', 920.00, 100.00)",
                [$tenantId, $funnelId]
            );
            $q3NodeId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'Which metropolitan city do you practice in?', 'Select your clinic region.', 'single_choice', 1, 3)",
                [$tenantId, $funnelId, $q3NodeId]
            );
            $q3Id = (int) Database::lastInsertId();

            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_delhi', 'Delhi NCR', 'delhi', 1)", [$tenantId, $q3Id]);
            $optDelhiId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_mumbai', 'Mumbai', 'mumbai', 2)", [$tenantId, $q3Id]);
            $optMumbaiId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_bangalore', 'Bengaluru', 'bangalore', 3)", [$tenantId, $q3Id]);
            $optBangaloreId = (int) Database::lastInsertId();

            // Question 4: Delhi Area
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'q4_node', 'question', 'Delhi Locality', 1220.00, 100.00)",
                [$tenantId, $funnelId]
            );
            $q4NodeId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'Which area of Delhi NCR is most convenient for you?', 'We will connect you to the nearest partner facility.', 'single_choice', 1, 4)",
                [$tenantId, $funnelId, $q4NodeId]
            );
            $q4Id = (int) Database::lastInsertId();

            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_saket', 'Saket & South Delhi', 'saket', 1)", [$tenantId, $q4Id]);
            $optSaketId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_central', 'Connaught Place & Central', 'central', 2)", [$tenantId, $q4Id]);
            $optCentralId = (int) Database::lastInsertId();

            // Question 5: Enterprise Size (from Business Owner)
            Database::execute(
                "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, 'q5_node', 'question', 'Enterprise Scale', 620.00, 360.00)",
                [$tenantId, $funnelId]
            );
            $q5NodeId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required, display_order) VALUES (?, ?, ?, 'What scale is your healthcare organization?', 'Select your team size.', 'single_choice', 1, 5)",
                [$tenantId, $funnelId, $q5NodeId]
            );
            $q5Id = (int) Database::lastInsertId();

            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_small', 'Private Practice (1-10 staff)', 'small', 1)", [$tenantId, $q5Id]);
            $optSmallId = (int) Database::lastInsertId();
            Database::execute("INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, 'opt_corp', 'Hospital / Network (10+ staff)', 'corp', 2)", [$tenantId, $q5Id]);
            $optCorpId = (int) Database::lastInsertId();

            // Destinations
            $destinations = [
                ['key' => 'dest_saket', 'title' => 'Saket Wellness Center', 'name' => 'Saket Clinical Campus', 'url' => 'https://apexhealth.example.com/clinics/saket', 'x' => 1520.00, 'y' => 50.00],
                ['key' => 'dest_central', 'title' => 'Central Hub', 'name' => 'Central Delhi Health Hub', 'url' => 'https://apexhealth.example.com/clinics/central', 'x' => 1520.00, 'y' => 170.00],
                ['key' => 'dest_mumbai', 'title' => 'Mumbai Clinic', 'name' => 'Mumbai Medical Suite', 'url' => 'https://apexhealth.example.com/clinics/mumbai', 'x' => 1220.00, 'y' => 240.00],
                ['key' => 'dest_bangalore', 'title' => 'Bengaluru Campus', 'name' => 'Bengaluru Health Campus', 'url' => 'https://apexhealth.example.com/clinics/bangalore', 'x' => 1220.00, 'y' => 340.00],
                ['key' => 'dest_dental', 'title' => 'Dental Network', 'name' => 'Dental Care Center', 'url' => 'https://apexhealth.example.com/clinics/dental', 'x' => 920.00, 'y' => 240.00],
                ['key' => 'dest_medical', 'title' => 'Physician Network', 'name' => 'Physician Care Portal', 'url' => 'https://apexhealth.example.com/clinics/physicians', 'x' => 920.00, 'y' => 340.00],
                ['key' => 'dest_small', 'title' => 'Small Practice Plan', 'name' => 'Small Practice Program', 'url' => 'https://apexhealth.example.com/plans/small', 'x' => 920.00, 'y' => 460.00],
                ['key' => 'dest_corp', 'title' => 'Enterprise Network', 'name' => 'Enterprise Health Network', 'url' => 'https://apexhealth.example.com/plans/corporate', 'x' => 920.00, 'y' => 560.00],
                ['key' => 'dest_general', 'title' => 'Research & Community', 'name' => 'Medical Research Community', 'url' => 'https://apexhealth.example.com/community', 'x' => 620.00, 'y' => 520.00],
            ];

            $destNodeIds = [];
            foreach ($destinations as $d) {
                Database::execute(
                    "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, position_x, position_y) VALUES (?, ?, ?, 'destination', ?, ?, ?)",
                    [$tenantId, $funnelId, $d['key'], $d['title'], $d['x'], $d['y']]
                );
                $dNodeId = (int) Database::lastInsertId();
                $destNodeIds[$d['key']] = $dNodeId;

                Database::execute(
                    "INSERT INTO destinations (tenant_id, funnel_id, node_id, name, url) VALUES (?, ?, ?, ?, ?)",
                    [$tenantId, $funnelId, $dNodeId, $d['name'], $d['url']]
                );
            }

            // 4. Funnel Edges (Visual Branching Connections)
            // Start -> Q1
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, NULL, ?)", [$tenantId, $funnelId, $startNodeId, $q1NodeId]);

            // Q1 -> options
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q1NodeId, $optDoctorId, $q2NodeId]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q1NodeId, $optBizId, $q5NodeId]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q1NodeId, $optStudentId, $destNodeIds['dest_general']]);

            // Q2 -> options
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q2NodeId, $optPsychId, $q3NodeId]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q2NodeId, $optDentistId, $destNodeIds['dest_dental']]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q2NodeId, $optPhysicianId, $destNodeIds['dest_medical']]);

            // Q3 -> options
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q3NodeId, $optDelhiId, $q4NodeId]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q3NodeId, $optMumbaiId, $destNodeIds['dest_mumbai']]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q3NodeId, $optBangaloreId, $destNodeIds['dest_bangalore']]);

            // Q4 -> options
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q4NodeId, $optSaketId, $destNodeIds['dest_saket']]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q4NodeId, $optCentralId, $destNodeIds['dest_central']]);

            // Q5 -> options
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q5NodeId, $optSmallId, $destNodeIds['dest_small']]);
            Database::execute("INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)", [$tenantId, $funnelId, $q5NodeId, $optCorpId, $destNodeIds['dest_corp']]);

            // 5. Freeze and Publish Version 1
            // Fetch complete graph snapshot
            $nodes = Database::query("SELECT * FROM funnel_nodes WHERE funnel_id = ?", [$funnelId]);
            $questions = Database::query("SELECT * FROM questions WHERE funnel_id = ?", [$funnelId]);
            $options = Database::query("SELECT qo.* FROM question_options qo JOIN questions q ON qo.question_id = q.id WHERE q.funnel_id = ?", [$funnelId]);
            $edges = Database::query("SELECT * FROM funnel_edges WHERE funnel_id = ?", [$funnelId]);
            $destList = Database::query("SELECT * FROM destinations WHERE funnel_id = ?", [$funnelId]);
            $formFields = Database::query("SELECT * FROM form_fields WHERE funnel_id = ? ORDER BY display_order", [$funnelId]);

            $graphSnapshot = [
                'nodes' => $nodes,
                'questions' => $questions,
                'options' => $options,
                'edges' => $edges,
                'destinations' => $destList,
                'start_node_id' => $startNodeId,
            ];

            $formSchemaSnapshot = [
                'fields' => $formFields,
            ];

            Database::execute(
                "INSERT INTO funnel_versions (tenant_id, funnel_id, version_number, graph_snapshot_json, form_schema_snapshot_json, settings_snapshot_json, published_by, published_at) VALUES (?, ?, 1, ?, ?, ?, ?, NOW())",
                [
                    $tenantId,
                    $funnelId,
                    json_encode($graphSnapshot),
                    json_encode($formSchemaSnapshot),
                    $settingsJson,
                    $ownerId
                ]
            );
            $versionId = (int) Database::lastInsertId();

            Database::execute(
                "UPDATE funnels SET status = 'published', current_published_version_id = ? WHERE id = ?",
                [$versionId, $funnelId]
            );

            // 6. Seed Sample Submissions with Real Journey Timelines
            self::seedSampleSubmissions($tenantId, $funnelId, $versionId, [
                'startNodeId' => $startNodeId,
                'q1NodeId' => $q1NodeId,
                'q1Id' => $q1Id,
                'optDoctorId' => $optDoctorId,
                'q2NodeId' => $q2NodeId,
                'q2Id' => $q2Id,
                'optPsychId' => $optPsychId,
                'q3NodeId' => $q3NodeId,
                'q3Id' => $q3Id,
                'optDelhiId' => $optDelhiId,
                'q4NodeId' => $q4NodeId,
                'q4Id' => $q4Id,
                'optSaketId' => $optSaketId,
                'destSaketNodeId' => $destNodeIds['dest_saket'],
            ]);
        });

        echo "DONE.\n";
    }

    private static function seedSampleSubmissions(int $tenantId, int $funnelId, int $versionId, array $ref): void
    {
        // Submission 1: Completed Doctor path to Saket Clinic
        $token1 = bin2hex(random_bytes(32));
        $hash1 = hash('sha256', $token1);

        $destRow = Database::fetchOne("SELECT id FROM destinations WHERE node_id = ?", [$ref['destSaketNodeId']]);
        $destId = $destRow ? $destRow['id'] : null;

        Database::execute(
            "INSERT INTO quiz_sessions (tenant_id, funnel_id, funnel_version_id, public_token_hash, status, current_node_id, started_at, completed_at, last_activity_at, expires_at, final_destination_id) VALUES (?, ?, ?, ?, 'completed', ?, DATE_SUB(NOW(), INTERVAL 35 MINUTE), DATE_SUB(NOW(), INTERVAL 31 MINUTE), DATE_SUB(NOW(), INTERVAL 31 MINUTE), DATE_ADD(NOW(), INTERVAL 7 DAY), ?)",
            [$tenantId, $funnelId, $versionId, $hash1, $ref['destSaketNodeId'], $destId]
        );
        $sessionId1 = (int) Database::lastInsertId();

        // Form submission
        Database::execute(
            "INSERT INTO form_submissions (tenant_id, funnel_id, funnel_version_id, session_id, submitted_at) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 34 MINUTE))",
            [$tenantId, $funnelId, $versionId, $sessionId1]
        );
        $formSubId = (int) Database::lastInsertId();

        Database::execute("INSERT INTO form_submission_values (tenant_id, form_submission_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, 'full_name', 'Full Name', 'text', 'Dr. Rajesh Sharma')", [$tenantId, $formSubId]);
        Database::execute("INSERT INTO form_submission_values (tenant_id, form_submission_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, 'email', 'Email Address', 'email', 'rajesh.sharma@delhiclinic.example.com')", [$tenantId, $formSubId]);
        Database::execute("INSERT INTO form_submission_values (tenant_id, form_submission_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, 'phone', 'Phone / WhatsApp Number', 'phone', '+91 98111 22334')", [$tenantId, $formSubId]);
        Database::execute("INSERT INTO form_submission_values (tenant_id, form_submission_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, 'contact_preference', 'Preferred Contact Channel', 'dropdown', 'WhatsApp')", [$tenantId, $formSubId]);
        Database::execute("INSERT INTO form_submission_values (tenant_id, form_submission_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, 'consent', 'Consent', 'consent', '1')", [$tenantId, $formSubId]);

        // Events and answers
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'quiz_viewed', DATE_SUB(NOW(), INTERVAL 35 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1]);
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'pre_form_submitted', DATE_SUB(NOW(), INTERVAL 34 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1]);
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'quiz_started', DATE_SUB(NOW(), INTERVAL 34 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1]);

        // Step 1: Q1 -> Doctor
        Database::execute(
            "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, visit_number, answered_at) VALUES (?, ?, ?, ?, ?, 'What is your current profession?', 'single_choice', 'Doctor / Medical Specialist', ?, 1, DATE_SUB(NOW(), INTERVAL 33 MINUTE))",
            [$tenantId, $sessionId1, $versionId, $ref['q1NodeId'], $ref['q1Id'], json_encode([$ref['optDoctorId']])]
        );
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, occurred_at) VALUES (?, ?, ?, ?, 'branch_selected', ?, ?, DATE_SUB(NOW(), INTERVAL 33 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1, $ref['q1NodeId'], $ref['optDoctorId']]);

        // Step 2: Q2 -> Psychiatrist
        Database::execute(
            "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, visit_number, answered_at) VALUES (?, ?, ?, ?, ?, 'What type of doctor are you?', 'single_choice', 'Psychiatrist / Neuropsychiatrist', ?, 1, DATE_SUB(NOW(), INTERVAL 32 MINUTE))",
            [$tenantId, $sessionId1, $versionId, $ref['q2NodeId'], $ref['q2Id'], json_encode([$ref['optPsychId']])]
        );
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, occurred_at) VALUES (?, ?, ?, ?, 'branch_selected', ?, ?, DATE_SUB(NOW(), INTERVAL 32 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1, $ref['q2NodeId'], $ref['optPsychId']]);

        // Step 3: Q3 -> Delhi NCR
        Database::execute(
            "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, visit_number, answered_at) VALUES (?, ?, ?, ?, ?, 'Which metropolitan city do you practice in?', 'single_choice', 'Delhi NCR', ?, 1, DATE_SUB(NOW(), INTERVAL 1900 SECOND))",
            [$tenantId, $sessionId1, $versionId, $ref['q3NodeId'], $ref['q3Id'], json_encode([$ref['optDelhiId']])]
        );
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, occurred_at) VALUES (?, ?, ?, ?, 'branch_selected', ?, ?, DATE_SUB(NOW(), INTERVAL 1900 SECOND))", [$tenantId, $funnelId, $versionId, $sessionId1, $ref['q3NodeId'], $ref['optDelhiId']]);

        // Step 4: Q4 -> Saket
        Database::execute(
            "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, visit_number, answered_at) VALUES (?, ?, ?, ?, ?, 'Which area of Delhi NCR is most convenient for you?', 'single_choice', 'Saket & South Delhi', ?, 1, DATE_SUB(NOW(), INTERVAL 31 MINUTE))",
            [$tenantId, $sessionId1, $versionId, $ref['q4NodeId'], $ref['q4Id'], json_encode([$ref['optSaketId']])]
        );
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, occurred_at) VALUES (?, ?, ?, ?, 'branch_selected', ?, ?, DATE_SUB(NOW(), INTERVAL 31 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1, $ref['q4NodeId'], $ref['optSaketId']]);

        // Result record
        $summary = [
            'lead_name' => 'Dr. Rajesh Sharma',
            'lead_email' => 'rajesh.sharma@delhiclinic.example.com',
            'destination_name' => 'Saket Clinical Campus',
            'destination_url' => 'https://apexhealth.example.com/clinics/saket',
            'answers' => [
                ['question' => 'What is your current profession?', 'answer' => 'Doctor / Medical Specialist'],
                ['question' => 'What type of doctor are you?', 'answer' => 'Psychiatrist / Neuropsychiatrist'],
                ['question' => 'Which metropolitan city do you practice in?', 'answer' => 'Delhi NCR'],
                ['question' => 'Which area of Delhi NCR is most convenient for you?', 'answer' => 'Saket & South Delhi'],
            ]
        ];

        Database::execute(
            "INSERT INTO quiz_results (tenant_id, funnel_id, funnel_version_id, session_id, result_token_hash, summary_json, destination_id, redirect_status, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'attempted', DATE_SUB(NOW(), INTERVAL 31 MINUTE))",
            [$tenantId, $funnelId, $versionId, $sessionId1, hash('sha256', bin2hex(random_bytes(32))), json_encode($summary), $destId]
        );
        Database::execute("INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'quiz_completed', DATE_SUB(NOW(), INTERVAL 31 MINUTE))", [$tenantId, $funnelId, $versionId, $sessionId1]);
    }
}

-- Migration 017: Workflow Templates Library System
-- Allows Super Admins to upload and manage pre-built global funnel templates
-- and Workspace Tenants to browse, preview, and instantiate them in 1 click.

CREATE TABLE IF NOT EXISTS workflow_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    description TEXT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'lead_generation',
    funnel_mode ENUM('quiz', 'calculator') NOT NULL DEFAULT 'quiz',
    thumbnail_url VARCHAR(500) NULL,
    badge VARCHAR(50) NULL COMMENT 'e.g. Popular, High-Converting, ROI Booster',
    node_count INT NOT NULL DEFAULT 0,
    graph_json LONGTEXT NOT NULL COMMENT 'Full JSON structure of nodes, edges, questions, options, destinations',
    settings_json LONGTEXT NULL COMMENT 'Default theme, colors, progress bar, white label settings',
    form_schema_json LONGTEXT NULL COMMENT 'Default lead capture fields snapshot',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_mode (funnel_mode),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed Production Starter Templates
-- ============================================================================

-- 1. High-Converting Lead Qualification Funnel
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'High-Converting Lead Qualification Funnel',
    'lead-qualification-starter',
    'A proven 4-stage qualification sequence with branching questions, high-contrast trust badges, and phone/email lead capture.',
    'lead_generation',
    'quiz',
    'Most Popular',
    5,
    1,
    1,
    1,
    '{"theme":"light","accent_color":"#1d5fd6","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"Acme Growth","trust_badge_text":"🔒 100% Confidential & Free Consultation"}',
    '[{"field_key":"full_name","label":"Full Name","field_type":"text","placeholder":"e.g. Rahul Sharma","is_required":true},{"field_key":"email","label":"Business Email","field_type":"email","placeholder":"e.g. rahul@company.com","is_required":true},{"field_key":"phone","label":"WhatsApp Number","field_type":"phone","placeholder":"e.g. +91 98765 43210","is_required":true}]',
    '{"nodes":[{"node_key":"start_1","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"q_goal","node_type":"question","title":"Primary Business Goal","is_start":0,"position_x":360,"position_y":220,"variable_key":"Q1","question_text":"What is your primary growth goal for the next quarter?","question_type":"single_choice","options":[{"option_key":"opt_leads","label":"Generate Higher Quality Inbound Leads","value":"leads"},{"option_key":"opt_close","label":"Automate Lead Qualification & Scoring","value":"automation"},{"option_key":"opt_cac","label":"Lower Customer Acquisition Cost (CAC)","value":"lower_cac"}]},{"node_key":"q_budget","node_type":"question","title":"Monthly Ad Spend","is_start":0,"position_x":640,"position_y":220,"variable_key":"Q2","question_text":"What is your estimated monthly marketing or advertising budget?","question_type":"single_choice","options":[{"option_key":"opt_b1","label":"Under ₹25,000 / month","value":"under_25k"},{"option_key":"opt_b2","label":"₹25,000 – ₹1,00,000 / month","value":"25k_100k"},{"option_key":"opt_b3","label":"₹1,00,000+ / month (High Scale)","value":"100k_plus"}]},{"node_key":"form_lead","node_type":"form","title":"Your Custom Growth Plan","is_start":0,"position_x":920,"position_y":220,"form":{"heading":"Where should we send your growth roadmap?","subheading":"Enter your contact details below to receive your instant tailored recommendations.","button_text":"Get My Growth Roadmap ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Rahul Sharma","is_required":1},{"field_key":"email","label":"Business Email","field_type":"email","placeholder":"e.g. rahul@company.com","is_required":1},{"field_key":"phone","label":"WhatsApp Number","field_type":"phone","placeholder":"e.g. +91 98765 43210","is_required":1}]}},{"node_key":"dest_thankyou","node_type":"destination","title":"Report Reserved","is_start":0,"position_x":1200,"position_y":220,"destination":{"name":"Thank You & Confirmation","type":"thank_you","headline":"🎉 Growth Roadmap Reserved!","message":"Thank you! Our growth specialist will review your answers and WhatsApp your tailored audit within 15 minutes."}}],"edges":[{"edge_key":"e_1","source_node_key":"start_1","target_node_key":"q_goal","source_handle":"out","target_handle":"in"},{"edge_key":"e_2","source_node_key":"q_goal","target_node_key":"q_budget","source_handle":"out","target_handle":"in"},{"edge_key":"e_3","source_node_key":"q_budget","target_node_key":"form_lead","source_handle":"out","target_handle":"in"},{"edge_key":"e_4","source_node_key":"form_lead","target_node_key":"dest_thankyou","source_handle":"out","target_handle":"in"}]}'
);

-- 2. Website & App Cost Estimator (Interactive Calculator Mode)
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'Website & App Development Cost Estimator',
    'website-cost-estimator',
    'Interactive dynamic slider calculation flow that quotes estimated development costs in real-time before capturing qualified leads.',
    'calculator',
    'calculator',
    'High Converting',
    5,
    1,
    1,
    2,
    '{"theme":"light","accent_color":"#10B981","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"Digital Dev Studio","trust_badge_text":"⚡ Instant Dynamic Estimate & Proposal"}',
    '[{"field_key":"full_name","label":"Your Name","field_type":"text","placeholder":"e.g. Priya Patel","is_required":true},{"field_key":"email","label":"Work Email","field_type":"email","placeholder":"e.g. priya@agency.com","is_required":true},{"field_key":"phone","label":"Phone / WhatsApp","field_type":"phone","placeholder":"e.g. +91 98200 12345","is_required":true}]',
    '{"nodes":[{"node_key":"start_calc","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"q_pages","node_type":"question","title":"Project Scale (Pages / Screens)","is_start":0,"position_x":360,"position_y":220,"variable_key":"Q1","input_type":"slider","slider_config":{"min_value":1,"max_value":40,"step_value":1,"default_value":8,"unit_label":"Pages / Screens","unit_prefix":""},"question_text":"How many custom pages or app screens do you anticipate needing?","question_type":"number"},{"node_key":"q_platform","node_type":"question","title":"Target Technology Platform","is_start":0,"position_x":640,"position_y":220,"variable_key":"Q2","question_text":"What type of application platform are you planning to build?","question_type":"single_choice","options":[{"option_key":"opt_web","label":"Modern Web Portal (React / Next.js)","value":"react_web"},{"option_key":"opt_ecom","label":"E-Commerce Storefront (Full Checkout)","value":"ecommerce"},{"option_key":"opt_app","label":"iOS & Android Mobile App (React Native / Flutter)","value":"mobile_app"}]},{"node_key":"form_quote","node_type":"form","title":"Calculate Instant Estimate","is_start":0,"position_x":920,"position_y":220,"form":{"heading":"Your instant cost estimate is being calculated","subheading":"Where should we send your itemized proposal and development breakdown?","button_text":"Reveal My Estimate ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Priya Patel","is_required":1},{"field_key":"email","label":"Work Email","field_type":"email","placeholder":"e.g. priya@agency.com","is_required":1},{"field_key":"phone","label":"Phone / WhatsApp","field_type":"phone","placeholder":"e.g. +91 98200 12345","is_required":1}]}},{"node_key":"dest_estimate","node_type":"destination","title":"Instant Quote Revealed","is_start":0,"position_x":1200,"position_y":220,"destination":{"name":"Detailed Estimate Card","type":"thank_you","headline":"💡 Estimated Project Scope & Budget Ready!","message":"Estimated Investment: ₹45,000 – ₹1,20,000 depending on final architecture. Our technical lead will contact you to confirm specifications."}}],"edges":[{"edge_key":"e_c1","source_node_key":"start_calc","target_node_key":"q_pages","source_handle":"out","target_handle":"in"},{"edge_key":"e_c2","source_node_key":"q_pages","target_node_key":"q_platform","source_handle":"out","target_handle":"in"},{"edge_key":"e_c3","source_node_key":"q_platform","target_node_key":"form_quote","source_handle":"out","target_handle":"in"},{"edge_key":"e_c4","source_node_key":"form_quote","target_node_key":"dest_estimate","source_handle":"out","target_handle":"in"}]}'
);

-- 3. High-Ticket Real Estate Buyer Pre-Qualifier
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'High-Ticket Real Estate Buyer Pre-Qualifier',
    'real-estate-prequalifier',
    'Filters serious property buyers by property typology, timeline, and investment budget for luxury realty developers and brokerage agencies.',
    'real_estate',
    'quiz',
    'High Ticket',
    6,
    1,
    1,
    3,
    '{"theme":"light","accent_color":"#b45309","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"Signature Living","trust_badge_text":"🏛️ RERA Certified Properties & Confidential Portfolio"}',
    '[{"field_key":"full_name","label":"Your Name","field_type":"text","placeholder":"e.g. Amit Sen","is_required":true},{"field_key":"email","label":"Email Address","field_type":"email","placeholder":"e.g. amit.sen@capital.com","is_required":true},{"field_key":"phone","label":"Mobile Number","field_type":"phone","placeholder":"e.g. +91 99887 76655","is_required":true}]',
    '{"nodes":[{"node_key":"re_start","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"re_type","node_type":"question","title":"Property Typology","is_start":0,"position_x":350,"position_y":220,"variable_key":"Q1","question_text":"What type of luxury property are you seeking?","question_type":"single_choice","options":[{"option_key":"apt","label":"Luxury 3 / 4 BHK High-Rise Apartment","value":"apartment"},{"option_key":"villa","label":"Private Gated Villa / Bungalow","value":"villa"},{"option_key":"plot","label":"Prime Commercial Space / Plot","value":"commercial"}]},{"node_key":"re_timeline","node_type":"question","title":"Purchase Horizon","is_start":0,"position_x":620,"position_y":220,"variable_key":"Q2","question_text":"What is your target timeline for finalizing the acquisition?","question_type":"single_choice","options":[{"option_key":"t1","label":"Immediate (Ready to Move in <30 Days)","value":"immediate"},{"option_key":"t2","label":"Within 3 to 6 Months","value":"3_6_months"},{"option_key":"t3","label":"Long-Term Wealth Investment (Under Construction)","value":"investment"}]},{"node_key":"re_budget","node_type":"question","title":"Target Budget","is_start":0,"position_x":890,"position_y":220,"variable_key":"Q3","question_text":"What is your planned investment bracket?","question_type":"single_choice","options":[{"option_key":"b1","label":"₹75 Lakhs – ₹1.5 Crore","value":"75l_1.5cr"},{"option_key":"b2","label":"₹1.5 Crore – ₹3.5 Crores","value":"1.5cr_3.5cr"},{"option_key":"b3","label":"₹3.5 Crores+ (Ultra Luxury Suite)","value":"3.5cr_plus"}]},{"node_key":"re_form","node_type":"form","title":"VIP Brochure Access","is_start":0,"position_x":1160,"position_y":220,"form":{"heading":"Request Private Floor Plans & Pricing","subheading":"Leave your contact details to receive unpublished pre-launch inventory directly.","button_text":"Access Exclusive Portfolio ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Amit Sen","is_required":1},{"field_key":"email","label":"Email Address","field_type":"email","placeholder":"e.g. amit.sen@capital.com","is_required":1},{"field_key":"phone","label":"Mobile Number","field_type":"phone","placeholder":"e.g. +91 99887 76655","is_required":1}]}},{"node_key":"re_dest","node_type":"destination","title":"VIP Portfolio Unlocked","is_start":0,"position_x":1430,"position_y":220,"destination":{"name":"Private Advisory Scheduled","type":"thank_you","headline":"🏰 Exclusive Portfolio Dispatched!","message":"Thank you. Your personal relationship manager has sent the digital brochure and will call to arrange a private site tour."}}],"edges":[{"edge_key":"e_r1","source_node_key":"re_start","target_node_key":"re_type","source_handle":"out","target_handle":"in"},{"edge_key":"e_r2","source_node_key":"re_type","target_node_key":"re_timeline","source_handle":"out","target_handle":"in"},{"edge_key":"e_r3","source_node_key":"re_timeline","target_node_key":"re_budget","source_handle":"out","target_handle":"in"},{"edge_key":"e_r4","source_node_key":"re_budget","target_node_key":"re_form","source_handle":"out","target_handle":"in"},{"edge_key":"e_r5","source_node_key":"re_form","target_node_key":"re_dest","source_handle":"out","target_handle":"in"}]}'
);

-- 4. B2B SaaS Free Trial & Demo Booking Flow
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'B2B SaaS Free Trial & Demo Booking Flow',
    'b2b-saas-demo-booking',
    'Qualifies software prospects by team size, stack integrations, and use case, then routes high-fit leads directly to an onboarding calendar.',
    'b2b_saas',
    'quiz',
    'B2B Essential',
    5,
    1,
    1,
    4,
    '{"theme":"light","accent_color":"#3730a3","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"CloudScale SaaS","trust_badge_text":"⚡ 14-Day Free Sandbox • No Credit Card Required"}',
    '[{"field_key":"full_name","label":"Full Name","field_type":"text","placeholder":"e.g. Vikram Singhania","is_required":true},{"field_key":"email","label":"Company Email","field_type":"email","placeholder":"e.g. vikram@cloudscale.io","is_required":true},{"field_key":"phone","label":"Direct Phone","field_type":"phone","placeholder":"e.g. +91 98111 22334","is_required":true}]',
    '{"nodes":[{"node_key":"saas_start","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"saas_size","node_type":"question","title":"Company / Team Size","is_start":0,"position_x":360,"position_y":220,"variable_key":"Q1","question_text":"How many active team members will be using the platform?","question_type":"single_choice","options":[{"option_key":"s1","label":"1 – 10 Members (Startup)","value":"1_10"},{"option_key":"s2","label":"11 – 50 Members (Growing Team)","value":"11_50"},{"option_key":"s3","label":"50+ Members (Enterprise Scale)","value":"50_plus"}]},{"node_key":"saas_stack","node_type":"question","title":"Primary Integration Required","is_start":0,"position_x":640,"position_y":220,"variable_key":"Q2","question_text":"Which tool is most critical to connect with your workflow?","question_type":"single_choice","options":[{"option_key":"int_crm","label":"Salesforce / HubSpot CRM","value":"crm"},{"option_key":"int_wa","label":"WhatsApp Business API","value":"whatsapp"},{"option_key":"int_webhook","label":"Custom Webhooks / REST APIs","value":"webhooks"}]},{"node_key":"saas_form","node_type":"form","title":"Setup Sandbox Workspace","is_start":0,"position_x":920,"position_y":220,"form":{"heading":"Create your personalized test instance","subheading":"Get immediate sandbox credentials and full feature trial access.","button_text":"Deploy My Free Workspace ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Vikram Singhania","is_required":1},{"field_key":"email","label":"Company Email","field_type":"email","placeholder":"e.g. vikram@cloudscale.io","is_required":1},{"field_key":"phone","label":"Direct Phone","field_type":"phone","placeholder":"e.g. +91 98111 22334","is_required":1}]}},{"node_key":"saas_dest","node_type":"destination","title":"Workspace Initialized","is_start":0,"position_x":1200,"position_y":220,"destination":{"name":"Sandbox Ready","type":"thank_you","headline":"🚀 Your Sandbox Instance is Ready!","message":"We have sent your sandbox activation link to your work email. A solutions engineer will reach out to ensure a seamless kickoff."}}],"edges":[{"edge_key":"e_s1","source_node_key":"saas_start","target_node_key":"saas_size","source_handle":"out","target_handle":"in"},{"edge_key":"e_s2","source_node_key":"saas_size","target_node_key":"saas_stack","source_handle":"out","target_handle":"in"},{"edge_key":"e_s3","source_node_key":"saas_stack","target_node_key":"saas_form","source_handle":"out","target_handle":"in"},{"edge_key":"e_s4","source_node_key":"saas_form","target_node_key":"saas_dest","source_handle":"out","target_handle":"in"}]}'
);

-- 5. Personalized Health & Fitness Assessment Quiz
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'Personalized Health & Fitness Diagnostic Quiz',
    'fitness-diagnostic-quiz',
    'Engaging lifestyle and body transformation quiz that calculates custom calorie targets and converts quiz takers into paid coaching clients.',
    'health_wellness',
    'quiz',
    'High Engagement',
    5,
    1,
    0,
    5,
    '{"theme":"light","accent_color":"#0f766e","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"FitMorph Transformation","trust_badge_text":"🥗 Certified Nutritionists • 10,000+ Success Stories"}',
    '[{"field_key":"full_name","label":"Your Name","field_type":"text","placeholder":"e.g. Ananya Das","is_required":true},{"field_key":"email","label":"Email Address","field_type":"email","placeholder":"e.g. ananya@lifestyle.in","is_required":true},{"field_key":"phone","label":"WhatsApp Number","field_type":"phone","placeholder":"e.g. +91 97766 55443","is_required":true}]',
    '{"nodes":[{"node_key":"fit_start","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"fit_goal","node_type":"question","title":"Transformation Goal","is_start":0,"position_x":360,"position_y":220,"variable_key":"Q1","question_text":"What is your #1 fitness priority over the next 60 days?","question_type":"single_choice","options":[{"option_key":"g_fat","label":"Burn Stubborn Belly Fat & Tone Up","value":"fat_loss"},{"option_key":"g_muscle","label":"Build Lean Muscle & Strength","value":"muscle_gain"},{"option_key":"g_energy","label":"Boost Stamina, Energy & Posture","value":"energy"}]},{"node_key":"fit_diet","node_type":"question","title":"Dietary Preference","is_start":0,"position_x":640,"position_y":220,"variable_key":"Q2","question_text":"What best describes your everyday eating lifestyle?","question_type":"single_choice","options":[{"option_key":"d_veg","label":"Pure Vegetarian / Jain Friendly","value":"vegetarian"},{"option_key":"d_nonveg","label":"Non-Vegetarian / High Protein","value":"non_veg"},{"option_key":"d_egg","label":"Eggetarian / Balanced","value":"eggetarian"}]},{"node_key":"fit_form","node_type":"form","title":"Calculate Custom Macro Blueprint","is_start":0,"position_x":920,"position_y":220,"form":{"heading":"Your personalized nutrition profile is ready","subheading":"Where should we send your recommended daily calorie and macro target?","button_text":"See My Transformation Plan ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Ananya Das","is_required":1},{"field_key":"email","label":"Email Address","field_type":"email","placeholder":"e.g. ananya@lifestyle.in","is_required":1},{"field_key":"phone","label":"WhatsApp Number","field_type":"phone","placeholder":"e.g. +91 97766 55443","is_required":1}]}},{"node_key":"fit_dest","node_type":"destination","title":"Blueprint Generated","is_start":0,"position_x":1200,"position_y":220,"destination":{"name":"Custom Plan Ready","type":"thank_you","headline":"🎉 Your 60-Day Transformation Blueprint is Ready!","message":"Check your WhatsApp! We have sent your personalized calorie breakdown and a 7-day starter grocery guide."}}],"edges":[{"edge_key":"e_f1","source_node_key":"fit_start","target_node_key":"fit_goal","source_handle":"out","target_handle":"in"},{"edge_key":"e_f2","source_node_key":"fit_goal","target_node_key":"fit_diet","source_handle":"out","target_handle":"in"},{"edge_key":"e_f3","source_node_key":"fit_diet","target_node_key":"fit_form","source_handle":"out","target_handle":"in"},{"edge_key":"e_f4","source_node_key":"fit_form","target_node_key":"fit_dest","source_handle":"out","target_handle":"in"}]}'
);

-- 6. Video Sales Letter (VSL) Gated Lead Funnel
INSERT INTO workflow_templates 
(name, slug, description, category, funnel_mode, badge, node_count, is_active, is_featured, display_order, settings_json, form_schema_json, graph_json)
VALUES (
    'Video Sales Letter (VSL) Lead Gate Funnel',
    'vsl-lead-gate-funnel',
    'Hooks viewers with high-value educational video, pauses playback at a critical milestone to capture buyer credentials, then unlocks the full breakdown.',
    'marketing',
    'quiz',
    'High Conversion',
    4,
    1,
    0,
    6,
    '{"theme":"dark","accent_color":"#e11d48","enable_back_button":true,"show_progress_bar":true,"auto_redirect":false,"card_radius":"rounded-2xl","company_name":"Acquisition Masterclass","trust_badge_text":"🎥 Exclusive Masterclass Video"}',
    '[{"field_key":"full_name","label":"Your Name","field_type":"text","placeholder":"e.g. Siddharth Roy","is_required":true},{"field_key":"email","label":"Best Email Address","field_type":"email","placeholder":"e.g. siddharth@direct.com","is_required":true}]',
    '{"nodes":[{"node_key":"vsl_start","node_type":"start","title":"Start","is_start":1,"position_x":80,"position_y":220},{"node_key":"vsl_video","node_type":"video_gate","title":"Watch Masterclass","is_start":0,"position_x":380,"position_y":220,"video_gate":{"video_url":"https://www.youtube.com/watch?v=dQw4w9WgXcQ","video_headline":"The 3 Secret Levers That 10x Customer Acquisition","playback_mode":"gated","gate_timestamp_seconds":35,"gate_headline":"🔒 Enter your details to continue watching","gate_button_text":"Continue Masterclass ➔","gate_type":"lead_form"}},{"node_key":"vsl_form","node_type":"form","title":"Claim Private Bonuses","is_start":0,"position_x":700,"position_y":220,"form":{"heading":"Claim Your Implementation Workbook","subheading":"Enter where we should send the complementary step-by-step checklist.","button_text":"Send My Workbook ➔","fields":[{"field_key":"name","label":"Full Name","field_type":"text","placeholder":"e.g. Siddharth Roy","is_required":1},{"field_key":"email","label":"Best Email Address","field_type":"email","placeholder":"e.g. siddharth@direct.com","is_required":1}]}},{"node_key":"vsl_dest","node_type":"destination","title":"Masterclass Complete","is_start":0,"position_x":1000,"position_y":220,"destination":{"name":"Access Granted","type":"thank_you","headline":"🌟 Welcome to the Inner Circle!","message":"Your workbook has been emailed. Be sure to check your inbox within the next 2 minutes."}}],"edges":[{"edge_key":"e_v1","source_node_key":"vsl_start","target_node_key":"vsl_video","source_handle":"out","target_handle":"in"},{"edge_key":"e_v2","source_node_key":"vsl_video","target_node_key":"vsl_form","source_handle":"out","target_handle":"in"},{"edge_key":"e_v3","source_node_key":"vsl_form","target_node_key":"vsl_dest","source_handle":"out","target_handle":"in"}]}'
);

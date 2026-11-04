export interface User {
  id: number;
  name: string;
  email: string;
  is_platform_admin: number | boolean;
  status: 'active' | 'disabled';
  last_login_at?: string;
  created_at?: string;
}

export interface Tenant {
  id: number;
  name: string;
  slug: string;
  status: 'active' | 'suspended' | 'deleted' | 'terminated';
  created_at?: string;
}

export interface TenantMembership {
  id: number;
  tenant_id: number;
  user_id: number;
  role: 'owner' | 'admin' | 'editor' | 'analyst';
  tenant_name?: string;
  tenant_slug?: string;
  tenant_status?: string;
}

export interface PlanLimits {
  max_funnels: number;
  max_submissions_per_month: number;
  max_team_members: number;
  plan_name?: string;
  plan_slug?: string;
  status?: string;
  current_funnels?: number;
  current_team_members?: number;
}

export interface PlanFeatures {
  visual_builder?: boolean;
  conditional_logic?: boolean;
  lead_capture_forms?: boolean;
  dynamic_scoring?: boolean;
  funnel_cloning?: boolean;
  calculator_mode?: boolean;
  custom_branding?: boolean;
  white_label?: boolean;
  custom_trust_badges?: boolean;
  custom_footer?: boolean;
  embed_widgets?: boolean;
  auto_redirect?: boolean;
  back_navigation?: boolean;
  analytics?: boolean;
  step_dropoff_analytics?: boolean;
  custom_tracking_pixels?: boolean;
  csv_export?: boolean;
  journey_inspector?: boolean;
  webhooks?: boolean;
  api_access?: boolean;
  team_collaboration?: boolean;
  audit_logs?: boolean;
}

export interface Funnel {
  id: number;
  tenant_id: number;
  name: string;
  slug: string;
  description?: string;
  status: 'draft' | 'published' | 'archived';
  funnel_mode: 'quiz' | 'calculator';
  current_published_version_id?: number | null;
  settings_json?: string;
  created_at: string;
  updated_at: string;
  has_unpublished_changes?: boolean;
  submissions_count?: number;
}

export interface FunnelSettings {
  theme?: 'light' | 'dark';
  accent_color?: string;
  company_name?: string;
  logo_url?: string;
  logo_position?: 'left' | 'center' | 'right';
  logo_height?: number;
  trust_badge_text?: string;
  footer_text?: string;
  privacy_policy_url?: string;
  terms_url?: string;
  white_label?: boolean;
  card_radius?: string;
  show_progress_bar?: boolean;
  button_radius?: string;
  button_style?: 'solid' | 'glow' | 'outline';
  cta_button_text?: string;
  font_family?: 'sans' | 'serif' | 'geometric' | 'system';
  background_style?: 'default' | 'dots' | 'warm' | 'dark';
  card_elevation?: 'flat' | 'soft' | 'floating';
  social_proof_badge?: string;
  fb_pixel_id?: string;
  google_tag_id?: string;
  tiktok_pixel_id?: string;
  meta_title?: string;
  meta_description?: string;
}

export interface LeadStage {
  id: number;
  tenant_id: number;
  name: string;
  slug: string;
  color: string;
  display_order: number;
  is_system: number;
}

export interface Submission {
  id: number;
  tenant_id: number;
  funnel_id: number;
  funnel_name: string;
  session_token: string;
  status: 'started' | 'in_progress' | 'completed' | 'abandoned' | 'form_pending_video_gate' | string;
  lead_status: string;
  lead_name?: string;
  lead_email?: string;
  lead_phone?: string;
  lead_company?: string;
  destination_name?: string;
  destination_url?: string;
  started_at?: string;
  created_at?: string;
  completed_at?: string;
  calculated_value?: number | null;
  formatted_quote?: string;
  form_values?: Record<string, string>;
  answers?: Array<{ question: string; answer: string; type?: string }>;
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  price: number | string;
  billing_interval: 'monthly' | 'quarterly' | 'semi_annual' | 'yearly';
  description?: string;
  limits_json: string | PlanLimits;
  features_json: string | PlanFeatures;
  is_active: number | boolean;
  is_visible?: number | boolean;
}


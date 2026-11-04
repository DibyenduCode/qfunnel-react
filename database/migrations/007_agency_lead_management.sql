-- Migration 007: Agency Lead Management & Follow-up Tracking
ALTER TABLE quiz_sessions ADD COLUMN IF NOT EXISTS lead_status ENUM('new', 'contacted', 'qualified', 'converted', 'junk') NOT NULL DEFAULT 'new' AFTER formatted_quote;
ALTER TABLE quiz_sessions ADD INDEX IF NOT EXISTS idx_tenant_lead_status (tenant_id, lead_status);

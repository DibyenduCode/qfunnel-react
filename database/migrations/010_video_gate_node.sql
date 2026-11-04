-- 010_video_gate_node.sql
-- Add support for 'video_gate' node type and flexible session statuses (e.g. form_pending_video_gate)

ALTER TABLE `funnel_nodes` MODIFY COLUMN `node_type` ENUM('start','question','destination','form','video_gate') NOT NULL;
ALTER TABLE `quiz_sessions` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'form_pending';


-- 011_conditional_router_node.sql
-- Add support for 'condition' node type and source_handle for multi-handle branching

ALTER TABLE `funnel_nodes` MODIFY COLUMN `node_type` ENUM('start','question','destination','form','video_gate','loader','booking','condition') NOT NULL;
ALTER TABLE `funnel_edges` ADD COLUMN IF NOT EXISTS `source_handle` VARCHAR(100) NULL AFTER `source_option_id`;


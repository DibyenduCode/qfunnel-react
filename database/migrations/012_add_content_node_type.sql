-- Migration 012: Add content node type to funnel_nodes
ALTER TABLE `funnel_nodes` 
MODIFY COLUMN `node_type` ENUM('start','question','destination','form','video_gate','loader','booking','condition','content') NOT NULL;


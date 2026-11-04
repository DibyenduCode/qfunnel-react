<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class FunnelGraphService
{
    public static function parseVideoUrl(string $url): array
    {
        $url = trim($url);
        if (preg_match('/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $url, $m)) {
            return [
                'provider' => 'youtube',
                'id' => $m[1],
                'hash' => '',
                'clean_url' => "https://www.youtube.com/watch?v={$m[1]}"
            ];
        }
        if (preg_match('/(?:vimeo\.com\/(?:video\/)?|player\.vimeo\.com\/video\/)([0-9]+)(?:\/([a-zA-Z0-9]+))?/i', $url, $m)) {
            $id = $m[1];
            $hash = $m[2] ?? '';
            return [
                'provider' => 'vimeo',
                'id' => $id,
                'hash' => $hash,
                'clean_url' => $hash ? "https://vimeo.com/{$id}/{$hash}" : "https://vimeo.com/{$id}"
            ];
        }
        return [
            'provider' => null,
            'id' => null,
            'hash' => '',
            'clean_url' => $url
        ];
    }

    public static function getGraph(int $tenantId, int $funnelId): array
    {
        $funnel = Database::fetchOne("SELECT * FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        // Fetch nodes
        $nodes = Database::query(
            "SELECT id, node_key, node_type, title, is_start, position_x, position_y, config_json 
             FROM funnel_nodes 
             WHERE tenant_id = ? AND funnel_id = ? 
             ORDER BY id ASC",
            [$tenantId, $funnelId]
        );

        // Fetch questions and their options
        $questions = Database::query(
            "SELECT * FROM questions WHERE tenant_id = ? AND funnel_id = ? ORDER BY display_order ASC",
            [$tenantId, $funnelId]
        );

        $questionsByNode = [];
        foreach ($questions as $q) {
            $options = Database::query(
                "SELECT * FROM question_options WHERE tenant_id = ? AND question_id = ? ORDER BY display_order ASC",
                [$tenantId, $q['id']]
            );
            $q['options'] = $options;
            $questionsByNode[$q['node_id']] = $q;
        }

        // Fetch destinations
        $destinations = Database::query(
            "SELECT * FROM destinations WHERE tenant_id = ? AND funnel_id = ?",
            [$tenantId, $funnelId]
        );
        $destinationsByNode = [];
        foreach ($destinations as $d) {
            $destinationsByNode[$d['node_id']] = $d;
        }

        // Enrich nodes
        $enrichedNodes = [];
        $startNodeId = null;
        $qCounter = 1;
        foreach ($nodes as $node) {
            $node['id'] = (int)$node['id'];
            $node['position_x'] = (float)$node['position_x'];
            $node['position_y'] = (float)$node['position_y'];
            $node['is_start'] = !empty($node['is_start']);
            if ($node['is_start'] && !$startNodeId) {
                $startNodeId = (int)$node['id'];
            }

            $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];

            if ($node['node_type'] === 'question') {
                $node['variable_key'] = $nodeConfig['variable_key'] ?? ('Q' . ($qCounter++));
                $node['input_type'] = $nodeConfig['input_type'] ?? 'single_choice';
                $node['slider_config'] = $nodeConfig['slider_config'] ?? [
                    'min_value' => 1,
                    'max_value' => 100,
                    'step_value' => 1,
                    'default_value' => 10,
                    'unit_label' => 'Units',
                    'unit_prefix' => ''
                ];
                $node['question'] = $questionsByNode[$node['id']] ?? null;
            } elseif ($node['node_type'] === 'destination') {
                $destObj = $destinationsByNode[$node['id']] ?? null;
                $calcObj = $nodeConfig['calculator'] ?? null;
                $quickConnectObj = $nodeConfig['quick_connect'] ?? null;
                if (!empty($destObj['tracking_config_json'])) {
                    $tConf = json_decode($destObj['tracking_config_json'], true) ?: [];
                    if (!$calcObj) $calcObj = $tConf['calculator'] ?? null;
                    if (!$quickConnectObj) $quickConnectObj = $tConf['quick_connect'] ?? null;
                }
                $node['calculator'] = $calcObj ?? [
                    'enabled' => ($funnel['funnel_mode'] ?? 'quiz') === 'calculator',
                    'formula_expression' => '@Q1 * 1000',
                    'result_label' => 'Your Estimated Quote',
                    'result_prefix' => '₹',
                    'result_suffix' => '',
                    'decimal_places' => 0
                ];
                $node['quick_connect'] = $quickConnectObj ?? [
                    'enabled' => false,
                    'action_type' => 'both',
                    'phone_number' => '',
                    'call_button_text' => 'Call Specialist Now',
                    'whatsapp_number' => '',
                    'whatsapp_button_text' => 'Chat on WhatsApp',
                    'whatsapp_message' => 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
                ];
                $node['destination'] = $destObj;
            } elseif ($node['node_type'] === 'form') {
                $formData = !empty($nodeConfig['fields']) ? $nodeConfig : (!empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : []);
                if (empty($formData['heading']) && !empty($node['title'])) {
                    $formData['heading'] = $node['title'];
                }
                if (empty($formData['subheading']) && !empty($node['description'])) {
                    $formData['subheading'] = $node['description'];
                }
                if (empty($formData['button_text'])) {
                    $formData['button_text'] = 'Submit Details';
                }
                if (!empty($formData['fields']) && is_array($formData['fields'])) {
                    foreach ($formData['fields'] as $fIdx => &$fld) {
                        if (empty($fld['id'])) {
                            $fld['id'] = (int)($node['id'] * 1000 + $fIdx + 1);
                        }
                    }
                    unset($fld);
                }
                $node['form'] = $formData;
            } elseif ($node['node_type'] === 'video_gate') {
                $vg = !empty($nodeConfig['video_gate']) ? $nodeConfig['video_gate'] : $nodeConfig;
                $vgUrl = $vg['video_url'] ?? '';
                $parsed = self::parseVideoUrl($vgUrl);
                $vg['video_provider'] = $parsed['provider'] ?: ($vg['video_provider'] ?? 'youtube');
                $vg['video_id'] = $parsed['id'] ?: ($vg['video_id'] ?? '');
                $vg['video_hash'] = $parsed['hash'] ?: ($vg['video_hash'] ?? '');
                $vg['video_url'] = $vgUrl;
                $vg['video_headline'] = $vg['video_headline'] ?? '';
                $vg['autoplay_behavior'] = isset($vg['autoplay_behavior']) ? (bool)$vg['autoplay_behavior'] : true;
                $vg['gate_enabled'] = isset($vg['gate_enabled']) ? (bool)$vg['gate_enabled'] : true;
                $vg['gate_timestamp_seconds'] = isset($vg['gate_timestamp_seconds']) ? (float)$vg['gate_timestamp_seconds'] : 45.0;
                $vg['gate_type'] = $vg['gate_type'] ?? 'lead_form';
                $vg['gate_headline'] = $vg['gate_headline'] ?? '🔒 Complete this quick step to continue watching';
                $vg['gate_subheadline'] = $vg['gate_subheadline'] ?? 'Enter your details below to unlock the rest of this video.';
                $vg['gate_button_text'] = $vg['gate_button_text'] ?? '▶️ Unlock & Continue Video';
                $vg['allow_skip_gate'] = !empty($vg['allow_skip_gate']);
                $vg['end_action'] = $vg['end_action'] ?? ($vg['on_video_end'] === 'message' || $vg['on_video_end'] === 'thank_you' ? 'thank_you' : 'next_step');
                $vg['on_video_end'] = $vg['on_video_end'] ?? 'auto_advance';
                $vg['success_heading'] = $vg['success_heading'] ?? '🎉 Thank You for Watching!';
                $vg['success_message'] = $vg['success_message'] ?? 'Your submission has been received. We will be in touch shortly.';
                $vg['cta_button_text'] = $vg['cta_button_text'] ?? 'Continue to Next Step ➔';
                $vg['form_fields'] = $vg['form_fields'] ?? [
                    ['id' => (int)($node['id'] * 1000 + 1), 'field_key' => 'name', 'label' => 'Full Name', 'field_type' => 'text', 'placeholder' => 'Enter your name', 'is_required' => 1],
                    ['id' => (int)($node['id'] * 1000 + 2), 'field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'placeholder' => 'Enter your email', 'is_required' => 1]
                ];
                $vg['question'] = $questionsByNode[$node['id']] ?? ($vg['question'] ?? [
                    'question_text' => 'What best describes your current situation?',
                    'options' => []
                ]);
                $node['video_gate'] = $vg;
                if (!empty($questionsByNode[$node['id']])) {
                    $node['question'] = $questionsByNode[$node['id']];
                }
            }
            $enrichedNodes[] = $node;
        }

        // If no start node explicitly set, fallback to first non-destination node
        if (!$startNodeId && !empty($enrichedNodes)) {
            foreach ($enrichedNodes as $en) {
                if ($en['node_type'] !== 'destination') {
                    $startNodeId = (int)$en['id'];
                    break;
                }
            }
        }

        // Fetch edges
        $edges = Database::query(
            "SELECT fe.*, qo.option_key as option_key, qo.label as option_label 
             FROM funnel_edges fe 
             LEFT JOIN question_options qo ON fe.source_option_id = qo.id 
             WHERE fe.tenant_id = ? AND fe.funnel_id = ?",
            [$tenantId, $funnelId]
        );

        return [
            'funnel' => $funnel,
            'nodes' => $enrichedNodes,
            'edges' => $edges,
            'start_node_id' => $startNodeId,
        ];
    }

    public static function saveGraph(int $tenantId, int $funnelId, array $data, int $userId): array
    {
        $funnel = Database::fetchOne("SELECT * FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        $nodes = $data['nodes'] ?? [];
        $edges = $data['edges'] ?? [];

        return Database::transaction(function () use ($tenantId, $funnelId, $nodes, $edges, $userId) {
            // Map client keys / old IDs to newly created or updated database IDs
            $nodeIdMap = [];
            $existingNodes = Database::query("SELECT id, node_key FROM funnel_nodes WHERE funnel_id = ?", [$funnelId]);
            $existingNodeMap = [];
            foreach ($existingNodes as $en) {
                $existingNodeMap[$en['node_key']] = (int)$en['id'];
            }

            $touchedNodeIds = [];

            // 1. Process Nodes
            foreach ($nodes as $node) {
                $nodeKey = $node['node_key'] ?? ('node_' . bin2hex(random_bytes(4)));
                $nodeType = $node['node_type'] ?? 'question';
                $title = $node['title'] ?? ($nodeType === 'start' ? 'Start' : 'Node');
                $posX = (float)($node['position_x'] ?? 100);
                $posY = (float)($node['position_y'] ?? 100);

                $isStart = (!empty($node['is_start']) && $nodeType !== 'destination') ? 1 : 0;

                $configJson = null;
                $configPayload = !empty($node['config_json']) ? (is_array($node['config_json']) ? $node['config_json'] : (json_decode($node['config_json'], true) ?: [])) : [];

                if ($nodeType === 'form') {
                    $formPayload = $node['form'] ?? [];
                    if (is_string($formPayload)) {
                        $formPayload = json_decode($formPayload, true) ?: [];
                    }
                    if (!isset($formPayload['heading']) && !empty($title)) {
                        $formPayload['heading'] = $title;
                    }
                    if (!isset($formPayload['subheading']) && !empty($node['description'])) {
                        $formPayload['subheading'] = $node['description'];
                    }
                    $configPayload = array_merge($configPayload, $formPayload);
                } elseif ($nodeType === 'question') {
                    if (isset($node['variable_key'])) $configPayload['variable_key'] = $node['variable_key'];
                    if (isset($node['input_type'])) $configPayload['input_type'] = $node['input_type'];
                    if (isset($node['slider_config'])) $configPayload['slider_config'] = $node['slider_config'];
                } elseif ($nodeType === 'destination') {
                    if (isset($node['destination_mode'])) $configPayload['destination_mode'] = $node['destination_mode'];
                    if (isset($node['calculator'])) $configPayload['calculator'] = $node['calculator'];
                    if (isset($node['quick_connect'])) $configPayload['quick_connect'] = $node['quick_connect'];
                } elseif ($nodeType === 'video_gate') {
                    $vgPayload = $node['video_gate'] ?? [];
                    if (is_string($vgPayload)) {
                        $vgPayload = json_decode($vgPayload, true) ?: [];
                    }
                    if (!empty($vgPayload['video_url'])) {
                        $parsed = self::parseVideoUrl($vgPayload['video_url']);
                        if ($parsed['provider']) {
                            $vgPayload['video_provider'] = $parsed['provider'];
                            $vgPayload['video_id'] = $parsed['id'];
                            $vgPayload['video_hash'] = $parsed['hash'];
                        }
                    }
                    $configPayload['video_gate'] = $vgPayload;
                } elseif ($nodeType === 'loader') {
                    $loaderPayload = $node['loader'] ?? ($configPayload['loader'] ?? $configPayload);
                    if (is_string($loaderPayload)) {
                        $loaderPayload = json_decode($loaderPayload, true) ?: [];
                    }
                    $mergedLoader = array_merge($configPayload['loader'] ?? [], $loaderPayload);
                    $configPayload['loader'] = $mergedLoader;
                    // Also expose top-level keys for direct access
                    foreach ($mergedLoader as $k => $v) {
                        $configPayload[$k] = $v;
                    }
                } elseif ($nodeType === 'booking') {
                    $bookingPayload = $node['booking'] ?? ($configPayload['booking'] ?? $configPayload);
                    if (is_string($bookingPayload)) {
                        $bookingPayload = json_decode($bookingPayload, true) ?: [];
                    }
                    $mergedBooking = array_merge($configPayload['booking'] ?? [], $bookingPayload);
                    $configPayload['booking'] = $mergedBooking;
                    // Also expose top-level keys for direct access
                    foreach ($mergedBooking as $k => $v) {
                        $configPayload[$k] = $v;
                    }
                }

                $configJson = !empty($configPayload) ? json_encode($configPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

                if (isset($existingNodeMap[$nodeKey])) {
                    $dbNodeId = $existingNodeMap[$nodeKey];
                    Database::execute(
                        "UPDATE funnel_nodes SET title = ?, is_start = ?, position_x = ?, position_y = ?, node_type = ?, config_json = ? WHERE id = ? AND tenant_id = ?",
                        [$title, $isStart, $posX, $posY, $nodeType, $configJson, $dbNodeId, $tenantId]
                    );
                } else {
                    Database::execute(
                        "INSERT INTO funnel_nodes (tenant_id, funnel_id, node_key, node_type, title, is_start, position_x, position_y, config_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$tenantId, $funnelId, $nodeKey, $nodeType, $title, $isStart, $posX, $posY, $configJson]
                    );
                    $dbNodeId = (int) Database::lastInsertId();
                }

                $nodeIdMap[$nodeKey] = $dbNodeId;
                if (!empty($node['id'])) {
                    $nodeIdMap[$node['id']] = $dbNodeId;
                }
                $touchedNodeIds[] = $dbNodeId;

                // Handle Question Node configuration (or Video Gate with Question Turnstile)
                $isQuestionNode = ($nodeType === 'question' && !empty($node['question']));
                $isVideoGateQuestion = ($nodeType === 'video_gate' && ($configPayload['video_gate']['gate_type'] ?? '') === 'question' && (!empty($node['question']) || !empty($configPayload['video_gate']['question'])));
                if ($isQuestionNode || $isVideoGateQuestion) {
                    $qData = !empty($node['question']) ? $node['question'] : ($configPayload['video_gate']['question'] ?? []);
                    $qText = trim($qData['question_text'] ?? 'Untitled Question');
                    $helpText = $qData['help_text'] ?? null;
                    $inputType = $node['input_type'] ?? ($configPayload['input_type'] ?? 'single_choice');
                    $qType = ($inputType === 'slider') ? 'number' : ($qData['question_type'] ?? 'single_choice');
                    $isRequired = !empty($qData['is_required']) ? 1 : 0;

                    $existingQ = Database::fetchOne("SELECT id FROM questions WHERE node_id = ?", [$dbNodeId]);
                    if ($existingQ) {
                        $qId = (int)$existingQ['id'];
                        Database::execute(
                            "UPDATE questions SET question_text = ?, help_text = ?, question_type = ?, is_required = ? WHERE id = ?",
                            [$qText, $helpText, $qType, $isRequired, $qId]
                        );
                    } else {
                        Database::execute(
                            "INSERT INTO questions (tenant_id, funnel_id, node_id, question_text, help_text, question_type, is_required) VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$tenantId, $funnelId, $dbNodeId, $qText, $helpText, $qType, $isRequired]
                        );
                        $qId = (int) Database::lastInsertId();
                    }

                    // Handle Options
                    $options = ($inputType === 'slider') ? [] : ($qData['options'] ?? []);
                    $touchedOptionIds = [];
                    $optOrder = 1;

                    foreach ($options as $opt) {
                        $optKey = $opt['option_key'] ?? ('opt_' . bin2hex(random_bytes(4)));
                        $label = trim($opt['label'] ?? 'Option');
                        $value = trim($opt['value'] ?? $label);

                        $existingOpt = Database::fetchOne("SELECT id FROM question_options WHERE question_id = ? AND option_key = ?", [$qId, $optKey]);
                        if ($existingOpt) {
                            $optId = (int)$existingOpt['id'];
                            Database::execute(
                                "UPDATE question_options SET label = ?, value = ?, display_order = ? WHERE id = ?",
                                [$label, $value, $optOrder++, $optId]
                            );
                        } else {
                            Database::execute(
                                "INSERT INTO question_options (tenant_id, question_id, option_key, label, value, display_order) VALUES (?, ?, ?, ?, ?, ?)",
                                [$tenantId, $qId, $optKey, $label, $value, $optOrder++]
                            );
                            $optId = (int) Database::lastInsertId();
                        }

                        $touchedOptionIds[] = $optId;
                        $nodeIdMap["opt_{$optKey}"] = $optId;
                        if (!empty($opt['id'])) {
                            $nodeIdMap["opt_{$opt['id']}"] = $optId;
                        }
                    }

                    // Delete removed options
                    if (!empty($touchedOptionIds)) {
                        $placeholders = implode(',', array_fill(0, count($touchedOptionIds), '?'));
                        Database::execute("DELETE FROM question_options WHERE question_id = ? AND id NOT IN ({$placeholders})", array_merge([$qId], $touchedOptionIds));
                    } elseif ($inputType === 'slider') {
                        Database::execute("DELETE FROM question_options WHERE question_id = ?", [$qId]);
                    }
                }

                // Handle Destination Node configuration
                if ($nodeType === 'destination' && !empty($node['destination'])) {
                    $dData = $node['destination'];
                    $name = trim($dData['name'] ?? $title);
                    $url = trim($dData['url'] ?? '');
                    $calcPayload = $node['calculator'] ?? ($configPayload['calculator'] ?? null);
                    $qcPayload = $node['quick_connect'] ?? ($configPayload['quick_connect'] ?? null);
                    $trackingJson = json_encode([
                        'calculator' => $calcPayload,
                        'quick_connect' => $qcPayload
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    $existingDest = Database::fetchOne("SELECT id FROM destinations WHERE node_id = ?", [$dbNodeId]);
                    if ($existingDest) {
                        Database::execute(
                            "UPDATE destinations SET name = ?, url = ?, tracking_config_json = ? WHERE id = ?",
                            [$name, $url, $trackingJson, $existingDest['id']]
                        );
                    } else {
                        Database::execute(
                            "INSERT INTO destinations (tenant_id, funnel_id, node_id, name, url, tracking_config_json) VALUES (?, ?, ?, ?, ?, ?)",
                            [$tenantId, $funnelId, $dbNodeId, $name, $url, $trackingJson]
                        );
                    }
                }
            }

            // Delete nodes not in payload
            if (!empty($touchedNodeIds)) {
                $placeholders = implode(',', array_fill(0, count($touchedNodeIds), '?'));
                Database::execute("DELETE FROM funnel_nodes WHERE funnel_id = ? AND id NOT IN ({$placeholders})", array_merge([$funnelId], $touchedNodeIds));
            } else {
                Database::execute("DELETE FROM destinations WHERE funnel_id = ?", [$funnelId]);
                Database::execute("DELETE FROM questions WHERE funnel_id = ?", [$funnelId]);
                Database::execute("DELETE FROM funnel_nodes WHERE funnel_id = ?", [$funnelId]);
            }

            // Ensure exactly one node is designated as start if any valid nodes exist
            $startCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnel_nodes WHERE funnel_id = ? AND is_start = 1", [$funnelId]);
            if ($startCount === 0) {
                $hasLegacyStart = Database::fetchOne("SELECT id FROM funnel_nodes WHERE funnel_id = ? AND node_type = 'start' ORDER BY id ASC LIMIT 1", [$funnelId]);
                if ($hasLegacyStart) {
                    Database::execute("UPDATE funnel_nodes SET is_start = 1 WHERE id = ?", [$hasLegacyStart['id']]);
                } else {
                    Database::execute("UPDATE funnel_nodes SET is_start = 1 WHERE funnel_id = ? AND node_type IN ('question', 'form', 'video_gate') ORDER BY id ASC LIMIT 1", [$funnelId]);
                }
            }

            // 2. Process Edges
            Database::execute("DELETE FROM funnel_edges WHERE funnel_id = ?", [$funnelId]);

            foreach ($edges as $edge) {
                $srcKey = $edge['source_node_key'] ?? $edge['source_node_id'] ?? null;
                $tgtKey = $edge['target_node_key'] ?? $edge['target_node_id'] ?? null;
                $optKey = $edge['source_option_key'] ?? $edge['source_option_id'] ?? null;

                $srcNodeId = $nodeIdMap[$srcKey] ?? (is_numeric($srcKey) ? (int)$srcKey : null);
                $tgtNodeId = $nodeIdMap[$tgtKey] ?? (is_numeric($tgtKey) ? (int)$tgtKey : null);
                $optId = null;
                if (!empty($optKey)) {
                    $optId = $nodeIdMap["opt_{$optKey}"] ?? (is_numeric($optKey) ? (int)$optKey : null);
                }

                if ($srcNodeId && $tgtNodeId) {
                    Database::execute(
                        "INSERT INTO funnel_edges (tenant_id, funnel_id, source_node_id, source_option_id, target_node_id) VALUES (?, ?, ?, ?, ?)",
                        [$tenantId, $funnelId, $srcNodeId, $optId, $tgtNodeId]
                    );
                }
            }

            // Update funnel timestamp and mark draft as having unpublished changes
            Database::execute("UPDATE funnels SET has_unpublished_changes = 1, updated_at = NOW() WHERE id = ?", [$funnelId]);

            AuditLogService::log($tenantId, $userId, 'save_funnel_graph', 'funnel', $funnelId);

            return self::getGraph($tenantId, $funnelId);
        });
    }

    public static function validateGraph(int $tenantId, int $funnelId): array
    {
        $graph = self::getGraph($tenantId, $funnelId);
        $nodes = $graph['nodes'];
        $edges = $graph['edges'];

        $issues = [];

        // 1. Check Starting Step
        $startNodes = array_filter($nodes, fn($n) => !empty($n['is_start']));
        if (count($startNodes) === 0) {
            $issues[] = [
                'type' => 'missing_start_node',
                'severity' => 'error',
                'message' => 'Please designate a starting step for your funnel (select any Question or Form node and set it as Starting Step).',
                'node_id' => null,
            ];
        } elseif (count($startNodes) > 1) {
            $issues[] = [
                'type' => 'multiple_start_nodes',
                'severity' => 'error',
                'message' => 'Multiple starting steps detected. Only one node can be designated as the starting step.',
                'node_id' => null,
            ];
        } else {
            $startNode = reset($startNodes);
            if ($startNode['node_type'] === 'destination') {
                $issues[] = [
                    'type' => 'invalid_start_node',
                    'severity' => 'error',
                    'message' => 'A destination page cannot be designated as the starting step.',
                    'node_id' => $startNode['id'],
                ];
            }
        }

        // 2. Check Question Nodes and Option Routes
        $nodeMap = [];
        foreach ($nodes as $n) {
            $nodeMap[$n['id']] = $n;
        }

        $edgesBySourceOption = [];
        $edgesBySourceNode = [];
        foreach ($edges as $e) {
            $edgesBySourceNode[$e['source_node_id']][] = $e;
            if (!empty($e['source_option_id'])) {
                $edgesBySourceOption[$e['source_option_id']] = $e;
            }
        }

        foreach ($nodes as $node) {
            if ($node['node_type'] === 'question') {
                $inputType = $node['input_type'] ?? 'single_choice';
                if ($inputType === 'slider') {
                    // Number / Slider Question routes from the node handle directly
                    $sliderEdges = $edgesBySourceNode[$node['id']] ?? [];
                    if (empty($sliderEdges)) {
                        $issues[] = [
                            'type' => 'slider_missing_route',
                            'severity' => 'error',
                            'message' => "Slider Question '{$node['title']}' has no next step connected.",
                            'node_id' => $node['id'],
                        ];
                    }
                } else {
                    $q = $node['question'];
                    if (!$q || empty($q['options'])) {
                        $issues[] = [
                            'type' => 'question_no_options',
                            'severity' => 'error',
                            'message' => "Question '{$node['title']}' has no answer options configured.",
                            'node_id' => $node['id'],
                        ];
                    } else {
                        foreach ($q['options'] as $opt) {
                            if (!isset($edgesBySourceOption[$opt['id']])) {
                                $issues[] = [
                                    'type' => 'option_missing_route',
                                    'severity' => 'error',
                                    'message' => "Option '{$opt['label']}' in '{$node['title']}' has no next step connected.",
                                    'node_id' => $node['id'],
                                    'option_id' => $opt['id'],
                                ];
                            }
                        }
                    }
                }
            } elseif ($node['node_type'] === 'destination') {
                $qc = $node['quick_connect'] ?? null;
                $isQC = $qc && !empty($qc['enabled']);
                if (!$isQC) {
                    $d = $node['destination'];
                    if (!$d || empty($d['url']) || !filter_var($d['url'], FILTER_VALIDATE_URL)) {
                        $issues[] = [
                            'type' => 'invalid_destination_url',
                            'severity' => 'error',
                            'message' => "Destination '{$node['title']}' has an invalid or missing landing page URL.",
                            'node_id' => $node['id'],
                        ];
                    }
                }

                // Validate calculator formula if enabled
                $calc = $node['calculator'] ?? null;
                if ($calc && !empty($calc['enabled'])) {
                    $formula = trim($calc['formula_expression'] ?? '');
                    if ($formula === '') {
                        $issues[] = [
                            'type' => 'empty_calculator_formula',
                            'severity' => 'error',
                            'message' => "Quote Destination '{$node['title']}' has calculation enabled but the formula expression is empty.",
                            'node_id' => $node['id'],
                        ];
                    } else {
                        $openParen = substr_count($formula, '(');
                        $closeParen = substr_count($formula, ')');
                        if ($openParen !== $closeParen) {
                            $issues[] = [
                                'type' => 'unbalanced_formula_parentheses',
                                'severity' => 'error',
                                'message' => "Formula in '{$node['title']}' has unbalanced parentheses: {$openParen} open '(' vs {$closeParen} close ')'.",
                                'node_id' => $node['id'],
                            ];
                        }
                    }
                }
            } elseif ($node['node_type'] === 'form') {
                $form = $node['form'] ?? [];
                $fields = $form['fields'] ?? [];
                if (empty($fields)) {
                    $issues[] = [
                        'type' => 'form_no_fields',
                        'severity' => 'warning',
                        'message' => "Form '{$node['title']}' has no fields configured.",
                        'node_id' => $node['id'],
                    ];
                }
                $submitAction = $form['submit_action'] ?? 'redirect';
                $formEdges = $edgesBySourceNode[$node['id']] ?? [];
                if ($submitAction === 'redirect' && empty($formEdges)) {
                    $issues[] = [
                        'type' => 'form_missing_route',
                        'severity' => 'error',
                        'message' => "Form '{$node['title']}' is set to route to a next step, but no next node is connected.",
                        'node_id' => $node['id'],
                    ];
                }
            } elseif ($node['node_type'] === 'video_gate') {
                $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
                $vg = $node['video_gate'] ?? ($nodeConfig['video_gate'] ?? []);
                $videoUrl = trim($vg['video_url'] ?? '');
                $parsed = self::parseVideoUrl($videoUrl);

                if (empty($videoUrl)) {
                    $issues[] = [
                        'type' => 'video_gate_missing_url',
                        'severity' => 'error',
                        'message' => "Video Gate '{$node['title']}' requires a YouTube or Vimeo URL.",
                        'node_id' => $node['id'],
                    ];
                } elseif (!$parsed['provider']) {
                    $issues[] = [
                        'type' => 'video_gate_invalid_url',
                        'severity' => 'error',
                        'message' => "Video Gate '{$node['title']}' must have a valid YouTube or Vimeo URL (e.g. https://www.youtube.com/watch?v=... or https://vimeo.com/...).",
                        'node_id' => $node['id'],
                    ];
                }

                $gateSeconds = isset($vg['gate_timestamp_seconds']) ? (float)$vg['gate_timestamp_seconds'] : 45.0;
                if ($gateSeconds < 0) {
                    $issues[] = [
                        'type' => 'video_gate_invalid_timestamp',
                        'severity' => 'error',
                        'message' => "Video Gate '{$node['title']}' gate timestamp must be 0 seconds or greater.",
                        'node_id' => $node['id'],
                    ];
                }

                $endAction = $vg['end_action'] ?? ($vg['on_video_end'] === 'message' || $vg['on_video_end'] === 'thank_you' ? 'thank_you' : 'next_step');
                $vgEdges = $edgesBySourceNode[$node['id']] ?? [];
                if ($endAction !== 'thank_you' && $endAction !== 'message' && empty($vgEdges)) {
                    $issues[] = [
                        'type' => 'video_gate_missing_route',
                        'severity' => 'error',
                        'message' => "Video Gate '{$node['title']}' is configured to advance to a next step, but no next node is connected.",
                        'node_id' => $node['id'],
                    ];
                }
            } elseif ($node['node_type'] === 'loader') {
                $loaderEdges = $edgesBySourceNode[$node['id']] ?? [];
                if (empty($loaderEdges)) {
                    $issues[] = [
                        'type' => 'loader_missing_route',
                        'severity' => 'error',
                        'message' => "Analyzing Loader Step '{$node['title']}' must connect to a next step.",
                        'node_id' => $node['id'],
                    ];
                }
            } elseif ($node['node_type'] === 'booking') {
                $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
                $bk = $node['booking'] ?? ($nodeConfig['booking'] ?? []);
                $bUrl = trim($bk['booking_url'] ?? ($nodeConfig['booking_url'] ?? ($node['booking_url'] ?? '')));
                if (empty($bUrl)) {
                    $issues[] = [
                        'type' => 'booking_missing_url',
                        'severity' => 'error',
                        'message' => "Booking Step '{$node['title']}' requires a calendar booking link (e.g. Calendly or Cal.com URL).",
                        'node_id' => $node['id'],
                    ];
                }
                $bookingEdges = $edgesBySourceNode[$node['id']] ?? [];
                if (empty($bookingEdges)) {
                    $issues[] = [
                        'type' => 'booking_missing_route',
                        'severity' => 'error',
                        'message' => "Booking Step '{$node['title']}' must connect to an outcome destination.",
                        'node_id' => $node['id'],
                    ];
                }
            }
        }

        // 3. Unreachable Node Detection (BFS from Start)
        if (!empty($startNodes)) {
            $startNode = reset($startNodes);
            $visited = [(int)$startNode['id'] => true];
            $queue = [(int)$startNode['id']];

            while (!empty($queue)) {
                $currId = array_shift($queue);
                $outgoing = $edgesBySourceNode[$currId] ?? [];
                foreach ($outgoing as $e) {
                    $tgtId = (int)$e['target_node_id'];
                    if (!isset($visited[$tgtId])) {
                        $visited[$tgtId] = true;
                        $queue[] = $tgtId;
                    }
                }
            }

            foreach ($nodes as $node) {
                if (!isset($visited[(int)$node['id']])) {
                    $issues[] = [
                        'type' => 'unreachable_node',
                        'severity' => 'warning',
                        'message' => "Node '{$node['title']}' is not reachable from the Start node.",
                        'node_id' => $node['id'],
                    ];
                }
            }
        }

        $isValid = count(array_filter($issues, fn($i) => $i['severity'] === 'error')) === 0;

        return [
            'is_valid' => $isValid,
            'errors_count' => count(array_filter($issues, fn($i) => $i['severity'] === 'error')),
            'warnings_count' => count(array_filter($issues, fn($i) => $i['severity'] === 'warning')),
            'issues' => $issues,
        ];
    }
}

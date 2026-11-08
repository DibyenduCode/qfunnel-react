<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class QuizEngineService
{
    public static function initializeSession(string $funnelSlug): array
    {
        $funnel = FunnelService::getPublishedFunnelBySlug($funnelSlug);
        if (!$funnel) {
            throw new RuntimeException("This quiz is not currently available or published.");
        }

        $tenantId = (int)$funnel['tenant_id'];
        $funnelId = (int)$funnel['id'];
        $versionId = (int)$funnel['current_published_version_id'];

        $graphSnapshot = json_decode($funnel['graph_snapshot_json'], true) ?: [];
        $formSnapshot = json_decode($funnel['form_schema_snapshot_json'], true) ?: [];
        $settingsSnapshot = json_decode($funnel['settings_snapshot_json'], true) ?: [];

        $startNodeId = $graphSnapshot['start_node_id'] ?? null;
        if (!$startNodeId) {
            foreach ($graphSnapshot['nodes'] ?? [] as $n) {
                if (!empty($n['is_start'])) {
                    $startNodeId = (int)$n['id'];
                    break;
                }
            }
        }
        if (!$startNodeId) {
            foreach ($graphSnapshot['nodes'] ?? [] as $n) {
                if ($n['node_type'] === 'start') {
                    $startNodeId = (int)$n['id'];
                    break;
                }
            }
        }
        if (!$startNodeId) {
            foreach ($graphSnapshot['nodes'] ?? [] as $n) {
                if ($n['node_type'] !== 'destination') {
                    $startNodeId = (int)$n['id'];
                    break;
                }
            }
        }

        if (!$startNodeId) {
            throw new RuntimeException("Published funnel does not have an entry point.");
        }

        // Generate cryptographic token
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        // Resolve start node object from graph snapshot
        $startNodeObj = null;
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            if ((int)$n['id'] === $startNodeId) {
                $startNodeObj = $n;
                break;
            }
        }

        // Only legacy funnels with an explicit 'start' node type use pre-quiz forms
        $isLegacyStartNode = ($startNodeObj && $startNodeObj['node_type'] === 'start');
        $hasForm = $isLegacyStartNode && !empty($formSnapshot['fields']);
        $initialStatus = $hasForm ? 'form_pending' : 'in_progress';
        $initialNodeId = $startNodeId;

        // Backward compatibility: If the start node is a legacy 'start' node with no form, follow the wire to target
        if ($isLegacyStartNode && !$hasForm) {
            $edges = $graphSnapshot['edges'] ?? [];
            foreach ($edges as $e) {
                if ((int)$e['source_node_id'] === (int)$startNodeId) {
                    $initialNodeId = (int)$e['target_node_id'];
                    break;
                }
            }
        }

        $sessionId = (int) Database::transaction(function () use ($tenantId, $funnelId, $versionId, $tokenHash, $initialStatus, $initialNodeId, $hasForm) {
            Database::execute(
                "INSERT INTO quiz_sessions (tenant_id, funnel_id, funnel_version_id, public_token_hash, status, current_node_id, started_at, last_activity_at, expires_at) 
                 VALUES (?, ?, ?, ?, ?, ?, " . ($hasForm ? "NULL" : "NOW()") . ", NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))",
                [$tenantId, $funnelId, $versionId, $tokenHash, $initialStatus, $initialNodeId]
            );
            $sId = (int) Database::lastInsertId();

            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) 
                 VALUES (?, ?, ?, ?, 'quiz_viewed', NOW())",
                [$tenantId, $funnelId, $versionId, $sId]
            );

            if (!$hasForm) {
                Database::execute(
                    "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) 
                     VALUES (?, ?, ?, ?, 'quiz_started', NOW())",
                    [$tenantId, $funnelId, $versionId, $sId]
                );
            }

            return $sId;
        });

        return [
            'session_token' => $rawToken,
            'status' => $initialStatus,
            'funnel' => [
                'name' => $funnel['name'],
                'description' => $funnel['description'],
                'settings' => $settingsSnapshot,
            ],
            'has_form' => $hasForm,
            'form_fields' => $hasForm ? $formSnapshot['fields'] : [],
        ];
    }

    public static function resolveSession(string $rawToken): ?array
    {
        $hash = hash('sha256', $rawToken);
        return Database::fetchOne(
            "SELECT qs.*, f.name as funnel_name, f.slug as funnel_slug, fv.graph_snapshot_json, fv.form_schema_snapshot_json, fv.settings_snapshot_json
             FROM quiz_sessions qs
             JOIN funnels f ON qs.funnel_id = f.id
             JOIN funnel_versions fv ON qs.funnel_version_id = fv.id
             WHERE qs.public_token_hash = ?",
            [$hash]
        );
    }

    public static function submitForm(string $rawToken, array $formData): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid or expired quiz session.");
        }

        if ($session['status'] !== 'form_pending') {
            $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];
            $currentNodeId = (int)$session['current_node_id'];
            $node = null;
            foreach ($graphSnapshot['nodes'] ?? [] as $n) {
                if ((int)$n['id'] === $currentNodeId) {
                    $node = $n;
                    break;
                }
            }
            if ($node && $node['node_type'] === 'form') {
                return self::submitAnswer($rawToken, $currentNodeId, ['form_data' => $formData]);
            }
            // Already submitted form, proceed to current step
            return self::getCurrentStep($rawToken);
        }

        $formSnapshot = json_decode($session['form_schema_snapshot_json'], true) ?: [];
        $fields = $formSnapshot['fields'] ?? [];

        // Validate form submission
        $validation = FormSchemaService::validateSubmission($fields, $formData);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error_code' => 'FORM_VALIDATION_FAILED',
                'errors' => $validation['errors'],
            ];
        }

        $cleanData = $validation['clean_data'];
        $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];
        $startNodeId = (int)$session['current_node_id'];

        // Determine next node from Start node edge
        $edges = $graphSnapshot['edges'] ?? [];
        $startEdge = null;
        foreach ($edges as $e) {
            if ((int)$e['source_node_id'] === $startNodeId) {
                $startEdge = $e;
                break;
            }
        }

        if (!$startEdge) {
            throw new RuntimeException("Funnel configuration error: Start node is not connected to a next step.");
        }

        $firstQuestionNodeId = (int)$startEdge['target_node_id'];

        Database::transaction(function () use ($session, $fields, $cleanData, $firstQuestionNodeId) {
            // 1. Save Form Submission
            Database::execute(
                "INSERT INTO form_submissions (tenant_id, funnel_id, funnel_version_id, session_id, submitted_at) VALUES (?, ?, ?, ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id']]
            );
            $formSubId = (int) Database::lastInsertId();

            // 2. Save Snapshotted Form Values
            foreach ($fields as $f) {
                $key = $f['field_key'];
                $val = $cleanData[$key] ?? '';
                Database::execute(
                    "INSERT INTO form_submission_values (tenant_id, form_submission_id, field_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$session['tenant_id'], $formSubId, $f['id'] ?? null, $key, $f['label'], $f['field_type'], $val]
                );
            }

            // 3. Record Events
            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'pre_form_submitted', NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id']]
            );
            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, occurred_at) VALUES (?, ?, ?, ?, 'quiz_started', NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id']]
            );

            // 4. Advance Session
            Database::execute(
                "UPDATE quiz_sessions SET status = 'in_progress', started_at = NOW(), current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
                [$firstQuestionNodeId, $session['id']]
            );
        });

        // 5. Trigger Webhook Event (lead.captured)
        try {
            WebhookService::trigger((int)$session['tenant_id'], 'lead.captured', [
                'session_id' => (int)$session['id'],
                'funnel_id' => (int)$session['funnel_id'],
                'funnel_name' => $session['funnel_name'],
                'lead_data' => $cleanData,
                'submitted_at' => date('Y-m-d H:i:s'),
            ], (int)$session['funnel_id']);
        } catch (\Throwable $e) {
            Logger::error("Webhook dispatch failed: " . $e->getMessage());
        }

        return self::getCurrentStep($rawToken);
    }

    public static function getCurrentStep(string $rawToken): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid or expired session.");
        }

        $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];
        $settingsSnapshot = json_decode($session['settings_snapshot_json'], true) ?: [];

        // Check if session is completed
        if ($session['status'] === 'completed') {
            return self::getCompletedStep($session, $graphSnapshot, $settingsSnapshot);
        }

        // Check if still pending form
        if ($session['status'] === 'form_pending') {
            $formSnapshot = json_decode($session['form_schema_snapshot_json'], true) ?: [];
            return [
                'success' => true,
                'status' => 'form_pending',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'form_fields' => $formSnapshot['fields'] ?? [],
            ];
        }

        $currentNodeId = (int)$session['current_node_id'];
        $node = null;
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            if ((int)$n['id'] === $currentNodeId) {
                $node = $n;
                break;
            }
        }

        if (!$node) {
            throw new RuntimeException("Current question node could not be resolved from graph snapshot.");
        }

        // If current node is a destination node, complete it
        if ($node['node_type'] === 'destination') {
            return self::completeSession($session, $node, $graphSnapshot, $settingsSnapshot);
        }

        // If current node is a conditional router node, evaluate rule & advance automatically
        if ($node['node_type'] === 'condition') {
            return self::evaluateAndAdvanceCondition($rawToken, $session, $node, $graphSnapshot);
        }

        // If current node is a video gate node, return video gate step
        if ($node['node_type'] === 'video_gate') {
            $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
            $vgConfig = $nodeConfig['video_gate'] ?? ($node['video_gate'] ?? []);
            $historyCount = (int) Database::fetchColumn(
                "SELECT COUNT(DISTINCT node_id) FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL",
                [$session['id']]
            );

            // Check if gate was already unlocked in this session
            $alreadyUnlocked = (bool) Database::fetchOne(
                "SELECT id FROM quiz_step_events WHERE session_id = ? AND node_id = ? AND event_type IN ('video_gate_completed', 'video_gate_skipped')",
                [$session['id'], $currentNodeId]
            );

            $playbackMode = $vgConfig['playback_mode'] ?? 'gated';
            $isStandard = ($playbackMode === 'standard');
            $isGateUnlocked = $isStandard || $alreadyUnlocked;

            // Question options if gate_type is question
            $question = null;
            $options = [];
            if (($vgConfig['gate_type'] ?? 'lead_form') === 'question') {
                foreach ($graphSnapshot['questions'] ?? [] as $q) {
                    if ((int)$q['node_id'] === $currentNodeId) {
                        $question = $q;
                        break;
                    }
                }
                if ($question) {
                    foreach ($graphSnapshot['options'] ?? [] as $opt) {
                        if ((int)$opt['question_id'] === (int)$question['id']) {
                            $options[] = [
                                'id' => (int)$opt['id'],
                                'option_key' => $opt['option_key'],
                                'label' => $opt['label'],
                                'value' => $opt['value'],
                            ];
                        }
                    }
                }
                if (empty($options) && !empty($vgConfig['question']['options'])) {
                    $options = $vgConfig['question']['options'];
                }
            }

            return [
                'success' => true,
                'status' => 'video_gate_step',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'step' => [
                    'node_id' => $currentNodeId,
                    'node_type' => 'video_gate',
                    'title' => $node['title'] ?? 'Video Presentation',
                    'is_gate_unlocked' => $isGateUnlocked,
                    'video_gate' => [
                        'video_provider' => $vgConfig['video_provider'] ?? 'youtube',
                        'video_url' => $vgConfig['video_url'] ?? '',
                        'video_id' => $vgConfig['video_id'] ?? '',
                        'video_hash' => $vgConfig['video_hash'] ?? '',
                        'video_headline' => $vgConfig['video_headline'] ?? '',
                        'playback_mode' => $playbackMode,
                        'cta_reveal' => $vgConfig['cta_reveal'] ?? 'always',
                        'cta_delay_seconds' => (int)($vgConfig['cta_delay_seconds'] ?? 15),
                        'cta_button_text' => $vgConfig['cta_button_text'] ?? 'Continue to Next Step ➔',
                        'autoplay_behavior' => isset($vgConfig['autoplay_behavior']) ? (bool)$vgConfig['autoplay_behavior'] : true,
                        'gate_enabled' => !$isStandard && (isset($vgConfig['gate_enabled']) ? (bool)$vgConfig['gate_enabled'] : true),
                        'gate_timestamp_seconds' => (float)($vgConfig['gate_timestamp_seconds'] ?? 45.0),
                        'gate_type' => $vgConfig['gate_type'] ?? 'lead_form',
                        'gate_headline' => $vgConfig['gate_headline'] ?? '🔒 Complete this quick step to continue watching',
                        'gate_subheadline' => $vgConfig['gate_subheadline'] ?? 'Enter your details below to unlock the rest of this video.',
                        'gate_button_text' => $vgConfig['gate_button_text'] ?? '▶️ Unlock & Continue Video',
                        'allow_skip_gate' => !empty($vgConfig['allow_skip_gate']),
                        'end_action' => $vgConfig['end_action'] ?? ($vgConfig['on_video_end'] === 'message' || $vgConfig['on_video_end'] === 'thank_you' ? 'thank_you' : 'next_step'),
                        'on_video_end' => $vgConfig['on_video_end'] ?? 'auto_advance',
                        'success_heading' => $vgConfig['success_heading'] ?? '🎉 Thank You for Watching!',
                        'success_message' => $vgConfig['success_message'] ?? 'Your submission has been received. We will be in touch shortly.',
                        'cta_button_text' => $vgConfig['cta_button_text'] ?? 'Continue to Next Step ➔',
                        'form_fields' => $vgConfig['form_fields'] ?? [
                            ['id' => 1, 'field_key' => 'name', 'label' => 'Full Name', 'field_type' => 'text', 'placeholder' => 'Enter your name', 'is_required' => 1],
                            ['id' => 2, 'field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'placeholder' => 'Enter your email', 'is_required' => 1]
                        ],
                        'options' => $options,
                        'question' => [
                            'question_text' => $question['question_text'] ?? ($vgConfig['question']['question_text'] ?? 'Please choose an option to continue:'),
                            'options' => $options
                        ]
                    ],
                    'step_number' => $historyCount + 1,
                    'can_go_back' => $historyCount > 0 && !empty($settingsSnapshot['enable_back_button']),
                ]
            ];
        }

        // If current node is a form node, return form step
        if ($node['node_type'] === 'form') {
            $formConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
            $historyCount = (int) Database::fetchColumn(
                "SELECT COUNT(DISTINCT node_id) FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL",
                [$session['id']]
            );

            return [
                'success' => true,
                'status' => 'form_step',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'step' => [
                    'node_id' => $currentNodeId,
                    'node_type' => 'form',
                    'title' => $formConfig['heading'] ?? ($node['title'] ?? 'Lead Capture Form'),
                    'heading' => $formConfig['heading'] ?? ($node['title'] ?? 'Lead Capture Form'),
                    'subheading' => $formConfig['subheading'] ?? ($formConfig['description'] ?? ($node['description'] ?? 'Please fill out the form to proceed.')),
                    'description' => $formConfig['subheading'] ?? ($formConfig['description'] ?? ($node['description'] ?? 'Please fill out the form to proceed.')),
                    'form_fields' => $formConfig['fields'] ?? [],
                    'submit_action' => $formConfig['submit_action'] ?? 'redirect',
                    'button_text' => $formConfig['button_text'] ?? ($node['button_text'] ?? 'Submit Details'),
                    'success_heading' => $formConfig['success_heading'] ?? 'Thank You!',
                    'success_message' => $formConfig['success_message'] ?? 'Your submission has been received.',
                    'step_number' => $historyCount + 1,
                    'can_go_back' => $historyCount > 0 && !empty($settingsSnapshot['enable_back_button']),
                ]
            ];
        }

        // If current node is a content slide step
        if ($node['node_type'] === 'content') {
            $contentConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
            $content = $contentConfig['content'] ?? ($node['content'] ?? $contentConfig);
            $historyCount = (int) Database::fetchColumn(
                "SELECT COUNT(DISTINCT node_id) FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL",
                [$session['id']]
            );

            return [
                'success' => true,
                'status' => 'in_progress',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'step' => [
                    'step_type' => 'content',
                    'node_id' => $currentNodeId,
                    'node_type' => 'content',
                    'title' => $node['title'] ?? 'Key Insight & Overview',
                    'content' => [
                        'headline' => $content['headline'] ?? ($node['title'] ?? 'Key Insight & Overview'),
                        'body_text' => $content['body_text'] ?? ($content['subtext'] ?? ''),
                        'image_url' => $content['image_url'] ?? '',
                        'button_text' => $content['button_text'] ?? 'Continue to Next Step',
                    ],
                    'step_number' => $historyCount + 1,
                    'can_go_back' => $historyCount > 0 && !empty($settingsSnapshot['enable_back_button']),
                ]
            ];
        }

        // If current node is a loader interstitial step
        if ($node['node_type'] === 'loader') {
            $loaderConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
            $historyCount = (int) Database::fetchColumn(
                "SELECT COUNT(DISTINCT node_id) FROM quiz_step_events WHERE session_id = ?",
                [$session['id']]
            );

            return [
                'success' => true,
                'status' => 'loader_step',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'step' => [
                    'node_id' => $currentNodeId,
                    'node_type' => 'loader',
                    'title' => $node['title'] ?? 'Analyzing Responses...',
                    'headline' => $loaderConfig['headline'] ?? ($node['title'] ?? 'Analyzing your responses...'),
                    'subtext' => $loaderConfig['subtext'] ?? 'Matching optimal solutions based on your answers...',
                    'duration_seconds' => (float)($loaderConfig['duration_seconds'] ?? 2.5),
                    'step_number' => $historyCount + 1,
                    'can_go_back' => false,
                ]
            ];
        }

        // If current node is a booking calendar step
        if ($node['node_type'] === 'booking') {
            $bookingConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
            $historyCount = (int) Database::fetchColumn(
                "SELECT COUNT(DISTINCT node_id) FROM quiz_step_events WHERE session_id = ?",
                [$session['id']]
            );

            return [
                'success' => true,
                'status' => 'booking_step',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'step' => [
                    'node_id' => $currentNodeId,
                    'node_type' => 'booking',
                    'title' => $node['title'] ?? 'Schedule Consultation',
                    'headline' => $bookingConfig['headline'] ?? ($node['title'] ?? 'Schedule Consultation'),
                    'provider' => $bookingConfig['provider'] ?? 'calendly',
                    'booking_url' => $bookingConfig['booking_url'] ?? '',
                    'embed_mode' => $bookingConfig['embed_mode'] ?? 'inline',
                    'step_number' => $historyCount + 1,
                    'can_go_back' => $historyCount > 0 && !empty($settingsSnapshot['enable_back_button']),
                ]
            ];
        }

        // Resolve question & public options
        $question = null;
        foreach ($graphSnapshot['questions'] ?? [] as $q) {
            if ((int)$q['node_id'] === $currentNodeId) {
                $question = $q;
                break;
            }
        }

        if (!$question) {
            throw new RuntimeException("Question details missing for node {$currentNodeId}.");
        }

        $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
        $inputType = $node['input_type'] ?? ($nodeConfig['input_type'] ?? ($question['question_type'] ?? 'single_choice'));
        $sliderConfig = $node['slider_config'] ?? ($nodeConfig['slider_config'] ?? [
            'min_value' => 1,
            'max_value' => 100,
            'step_value' => 1,
            'default_value' => 10,
            'unit_label' => '',
            'unit_prefix' => ''
        ]);
        $varKey = $node['variable_key'] ?? ($nodeConfig['variable_key'] ?? 'Q1');

        $options = [];
        if ($inputType !== 'slider') {
            foreach ($graphSnapshot['options'] ?? [] as $opt) {
                if ((int)$opt['question_id'] === (int)$question['id']) {
                    $options[] = [
                        'id' => $opt['id'],
                        'option_key' => $opt['option_key'],
                        'label' => $opt['label'],
                        'value' => $opt['value'] ?? '',
                        'display_order' => $opt['display_order'],
                    ];
                }
            }
        }

        // Fetch previous answer if revisiting via back-button
        $existingAnswer = Database::fetchOne(
            "SELECT * FROM quiz_answers WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL ORDER BY id DESC LIMIT 1",
            [$session['id'], $currentNodeId]
        );

        $selectedOptionIds = [];
        $currentSliderValue = null;
        if ($existingAnswer) {
            if ($existingAnswer['answer_type'] === 'slider' || $inputType === 'slider') {
                $currentSliderValue = is_numeric($existingAnswer['answer_value_text']) ? (float)$existingAnswer['answer_value_text'] : null;
            } elseif (!empty($existingAnswer['selected_option_ids_json'])) {
                $selectedOptionIds = json_decode($existingAnswer['selected_option_ids_json'], true) ?: [];
            }
        }
        if ($currentSliderValue === null && $inputType === 'slider') {
            $currentSliderValue = (float)($sliderConfig['default_value'] ?? 10);
        }

        // History count (for progress indication)
        $historyCount = (int) Database::fetchColumn(
            "SELECT COUNT(DISTINCT node_id) FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL",
            [$session['id']]
        );

        $hasPreviousStep = $historyCount > 0;

        return [
            'success' => true,
            'status' => 'in_progress',
            'funnel_name' => $session['funnel_name'],
            'settings' => $settingsSnapshot,
            'step' => [
                'node_id' => $currentNodeId,
                'question_id' => $question['id'],
                'question_text' => $question['question_text'],
                'help_text' => $question['help_text'],
                'question_type' => $question['question_type'],
                'input_type' => $inputType,
                'slider_config' => $sliderConfig,
                'variable_key' => $varKey,
                'current_slider_value' => $currentSliderValue,
                'is_required' => (bool)$question['is_required'],
                'options' => $options,
                'selected_option_ids' => $selectedOptionIds,
                'step_number' => $historyCount + 1,
                'can_go_back' => $hasPreviousStep && !empty($settingsSnapshot['enable_back_button']),
            ]
        ];
    }

    public static function submitAnswer(string $rawToken, int $nodeId, array $answerPayload): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid or expired session.");
        }

        if ($session['status'] !== 'in_progress') {
            return self::getCurrentStep($rawToken);
        }

        if ((int)$session['current_node_id'] !== $nodeId) {
            throw new RuntimeException("Answer submission out of sequence.");
        }

        $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];
        $settingsSnapshot = json_decode($session['settings_snapshot_json'], true) ?: [];

        $node = null;
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            if ((int)$n['id'] === $nodeId) {
                $node = $n;
                break;
            }
        }

        if ($node && $node['node_type'] === 'form') {
            return self::submitFormNode($rawToken, $session, $node, $answerPayload, $graphSnapshot, $settingsSnapshot);
        }

        if ($node && $node['node_type'] === 'video_gate') {
            return self::advanceVideoStep($rawToken, $nodeId);
        }

        if ($node && in_array($node['node_type'], ['content', 'loader', 'booking'], true)) {
            $targetNodeId = null;
            foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                if ((int)$edge['source_node_id'] === $nodeId) {
                    $targetNodeId = (int)$edge['target_node_id'];
                    break;
                }
            }

            if (!$targetNodeId) {
                throw new RuntimeException("Routing error: No destination connected for this step.");
            }

            Database::transaction(function () use ($session, $nodeId, $targetNodeId) {
                Database::execute(
                    "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
                     VALUES (?, ?, ?, ?, 'step_completed', ?, NOW())",
                    [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $nodeId]
                );

                Database::execute(
                    "UPDATE quiz_sessions SET current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
                    [$targetNodeId, $session['id']]
                );
            });

            return self::getCurrentStep($rawToken);
        }

        // Find question
        $question = null;
        foreach ($graphSnapshot['questions'] ?? [] as $q) {
            if ((int)$q['node_id'] === $nodeId) {
                $question = $q;
                break;
            }
        }

        if (!$question) {
            throw new RuntimeException("Question not found.");
        }

        $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
        $isSlider = ($node['input_type'] ?? '') === 'slider' 
            || ($nodeConfig['input_type'] ?? '') === 'slider' 
            || ($question['question_type'] ?? '') === 'slider' 
            || isset($answerPayload['slider_value']);

        if ($isSlider) {
            $sliderValue = (float)($answerPayload['slider_value'] ?? $answerPayload['answer_value'] ?? $answerPayload['value'] ?? 0);

            // For slider questions, find edge connected to this question node (source_option_id is NULL)
            $targetNodeId = null;
            foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                if ((int)$edge['source_node_id'] === $nodeId && empty($edge['source_option_id'])) {
                    $targetNodeId = (int)$edge['target_node_id'];
                    break;
                }
            }

            if (!$targetNodeId) {
                // If edge had an option attached or fallback to any edge from this node
                foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                    if ((int)$edge['source_node_id'] === $nodeId) {
                        $targetNodeId = (int)$edge['target_node_id'];
                        break;
                    }
                }
            }

            if (!$targetNodeId) {
                throw new RuntimeException("Routing error: No destination connected for this slider question.");
            }

            Database::transaction(function () use ($session, $nodeId, $question, $sliderValue, $targetNodeId) {
                Database::execute(
                    "UPDATE quiz_answers SET superseded_at = NOW() WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL",
                    [$session['id'], $nodeId]
                );

                Database::execute(
                    "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, answered_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'slider', ?, ?, NOW())",
                    [
                        $session['tenant_id'],
                        $session['id'],
                        $session['funnel_version_id'],
                        $nodeId,
                        $question['id'],
                        $question['question_text'],
                        (string)$sliderValue,
                        json_encode(['value' => $sliderValue])
                    ]
                );

                Database::execute(
                    "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
                     VALUES (?, ?, ?, ?, 'slider_submitted', ?, NOW())",
                    [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $nodeId]
                );

                Database::execute(
                    "UPDATE quiz_sessions SET current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
                    [$targetNodeId, $session['id']]
                );
            });

            return self::getCurrentStep($rawToken);
        }

        $selectedOptionId = isset($answerPayload['option_id']) ? (int)$answerPayload['option_id'] : null;
        if (!$selectedOptionId) {
            throw new RuntimeException("Please select an answer to continue.");
        }

        // Verify option belongs to question
        $selectedOption = null;
        foreach ($graphSnapshot['options'] ?? [] as $opt) {
            if ((int)$opt['id'] === $selectedOptionId && (int)$opt['question_id'] === (int)$question['id']) {
                $selectedOption = $opt;
                break;
            }
        }

        if (!$selectedOption) {
            throw new RuntimeException("Invalid answer choice.");
        }

        // Find next edge from graph snapshot
        $targetNodeId = null;
        foreach ($graphSnapshot['edges'] ?? [] as $edge) {
            if ((int)$edge['source_node_id'] === $nodeId && (int)$edge['source_option_id'] === $selectedOptionId) {
                $targetNodeId = (int)$edge['target_node_id'];
                break;
            }
        }

        if (!$targetNodeId) {
            throw new RuntimeException("Routing error: No destination connected for this option.");
        }

        // Persist Answer and Advance
        Database::transaction(function () use ($session, $nodeId, $question, $selectedOption, $selectedOptionId, $targetNodeId) {
            // Invalidate any subsequent downstream answers if user had navigated back and changed an earlier answer
            // Find visit sequence after current node
            Database::execute(
                "UPDATE quiz_answers SET superseded_at = NOW() WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL",
                [$session['id'], $nodeId]
            );

            // Record Answer
            Database::execute(
                "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, answered_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'single_choice', ?, ?, NOW())",
                [
                    $session['tenant_id'],
                    $session['id'],
                    $session['funnel_version_id'],
                    $nodeId,
                    $question['id'],
                    $question['question_text'],
                    $selectedOption['label'],
                    json_encode([$selectedOptionId])
                ]
            );

            // Record Event
            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, occurred_at) 
                 VALUES (?, ?, ?, ?, 'branch_selected', ?, ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $nodeId, $selectedOptionId]
            );

            // Advance Session current node
            Database::execute(
                "UPDATE quiz_sessions SET current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
                [$targetNodeId, $session['id']]
            );
        });

        return self::getCurrentStep($rawToken);
    }

    public static function navigateBack(string $rawToken): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session || $session['status'] === 'form_pending') {
            return self::getCurrentStep($rawToken);
        }

        $currentNodeId = (int)$session['current_node_id'];
        // Find the most recent active answer that is NOT the current node
        $previousAnswer = Database::fetchOne(
            "SELECT * FROM quiz_answers WHERE session_id = ? AND superseded_at IS NULL AND node_id != ? ORDER BY id DESC LIMIT 1",
            [$session['id'], $currentNodeId]
        );

        if ($previousAnswer) {
            // Set current node to this previous question node and ensure status is in_progress
            $prevNodeId = (int)$previousAnswer['node_id'];
            Database::execute(
                "UPDATE quiz_sessions SET status = 'in_progress', current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
                [$prevNodeId, $session['id']]
            );
        }

        return self::getCurrentStep($rawToken);
    }

    private static function completeSession(array $session, array $destNode, array $graphSnapshot, array $settingsSnapshot): array
    {
        $destId = null;
        $destName = $destNode['title'] ?? 'Completion';
        $destUrl = 'https://example.com';

        foreach ($graphSnapshot['destinations'] ?? [] as $d) {
            if ((int)$d['node_id'] === (int)$destNode['id']) {
                $destId = (int)$d['id'];
                $destName = $d['name'];
                $destUrl = $d['url'];
                break;
            }
        }

        // Generate unguessable result token
        $rawResultToken = bin2hex(random_bytes(32));
        $resultTokenHash = hash('sha256', $rawResultToken);

        // Fetch lead form details
        $formVals = Database::query(
            "SELECT fsv.field_label_snapshot, fsv.value_text 
             FROM form_submissions fs
             JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id
             WHERE fs.session_id = ? 
             ORDER BY fsv.id ASC",
            [$session['id']]
        );

        $answers = Database::query(
            "SELECT question_text_snapshot, answer_value_text, answered_at 
             FROM quiz_answers 
             WHERE session_id = ? AND superseded_at IS NULL AND (answer_type != 'form' OR answer_type IS NULL)
             ORDER BY id ASC",
            [$session['id']]
        );

        // Check for Calculator & Quote Configuration
        $destConfig = !empty($destNode['config_json']) ? (json_decode($destNode['config_json'], true) ?: []) : [];
        $calcConfig = $destNode['calculator'] ?? ($destConfig['calculator'] ?? null);

        $calculatedValue = null;
        $formattedQuote = null;
        $quoteCard = null;

        if ($calcConfig && !empty($calcConfig['enabled']) && !empty($calcConfig['formula_expression'])) {
            $variables = self::extractSessionVariables((int)$session['id'], $graphSnapshot);
            $calculatedValue = self::evaluateFormula($calcConfig['formula_expression'], $variables);
            $decimals = (int)($calcConfig['decimal_places'] ?? 0);
            $prefix = $calcConfig['result_prefix'] ?? '';
            $suffix = $calcConfig['result_suffix'] ?? '';
            $formattedQuote = trim($prefix . ' ' . number_format($calculatedValue, $decimals) . ' ' . $suffix);

            $quoteCard = [
                'enabled' => true,
                'result_label' => $calcConfig['result_label'] ?? 'Your Estimated Quote',
                'calculated_value' => $calculatedValue,
                'formatted_quote' => $formattedQuote,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'formula' => $calcConfig['formula_expression'],
                'variables' => $variables
            ];

            // Dynamic Tag Replacement in Destination URL and Name
            $destUrl = str_replace(
                ['{{calculated_value}}', '{{formatted_quote}}', '%7B%7Bcalculated_value%7D%7D', '%7B%7Bformatted_quote%7D%7D'],
                [(string)$calculatedValue, urlencode($formattedQuote), (string)$calculatedValue, urlencode($formattedQuote)],
                $destUrl
            );
            $destName = str_replace(
                ['{{calculated_value}}', '{{formatted_quote}}'],
                [(string)$calculatedValue, $formattedQuote],
                $destName
            );
        }

        // Check for Quick Connect (1-Click Call / WhatsApp / Both)
        $destTracking = !empty($destNode['tracking_config_json']) ? (json_decode($destNode['tracking_config_json'], true) ?: []) : [];
        $qcConfig = $destNode['quick_connect'] ?? ($destConfig['quick_connect'] ?? ($destTracking['quick_connect'] ?? null));
        $quickConnect = null;

        if ($qcConfig && !empty($qcConfig['enabled'])) {
            $rawPhone = trim($qcConfig['phone_number'] ?? '');
            $rawWa = trim($qcConfig['whatsapp_number'] ?? '');
            $cleanPhone = preg_replace('/[^0-9+]/', '', $rawPhone);
            $cleanWa = preg_replace('/[^0-9]/', '', $rawWa);
            $actionType = $qcConfig['action_type'] ?? 'both';

            $rawMsg = trim($qcConfig['whatsapp_message'] ?? ($qcConfig['message'] ?? 'Hi! I just completed the {{funnel_name}}.'));
            $replacements = [
                '{{funnel_name}}' => $session['funnel_name'],
                '{{destination_name}}' => $destName,
                '{{calculated_value}}' => (string)($calculatedValue ?? ''),
                '{{formatted_quote}}' => (string)($formattedQuote ?? '')
            ];
            $customMsg = str_replace(array_keys($replacements), array_values($replacements), $rawMsg);

            $callUrl = $cleanPhone ? ('tel:' . $cleanPhone) : '';
            $waUrl = $cleanWa ? ('https://wa.me/' . $cleanWa . ($customMsg ? ('?text=' . rawurlencode($customMsg)) : '')) : '';

            $quickConnect = [
                'enabled' => true,
                'action_type' => $actionType,
                'phone_number' => $rawPhone,
                'clean_phone' => $cleanPhone,
                'call_button_text' => !empty($qcConfig['call_button_text']) ? $qcConfig['call_button_text'] : 'Call Now',
                'call_url' => $callUrl,
                'whatsapp_number' => $rawWa,
                'clean_whatsapp' => $cleanWa,
                'whatsapp_button_text' => !empty($qcConfig['whatsapp_button_text']) ? $qcConfig['whatsapp_button_text'] : 'Chat on WhatsApp',
                'whatsapp_message' => $customMsg,
                'whatsapp_url' => $waUrl
            ];
            // When Direct Connect (Call/WhatsApp) is chosen, do not treat as an external redirect
            $destUrl = '';
        }

        // Media (Image or Video)
        $mediaConfig = $destNode['media'] ?? ($destConfig['media'] ?? ($destTracking['media'] ?? null));
        $media = null;
        if ($mediaConfig && ($mediaConfig['media_type'] ?? 'none') !== 'none') {
            $mType = $mediaConfig['media_type'];
            if ($mType === 'video' && !empty($mediaConfig['video_url'])) {
                $parsed = FunnelGraphService::parseVideoUrl($mediaConfig['video_url']);
                $media = [
                    'media_type' => 'video',
                    'video_url' => $mediaConfig['video_url'],
                    'provider' => $parsed['provider'],
                    'video_id' => $parsed['id'],
                    'video_hash' => $parsed['hash'] ?? ''
                ];
            } elseif ($mType === 'image' && !empty($mediaConfig['image_url'])) {
                $media = [
                    'media_type' => 'image',
                    'image_url' => $mediaConfig['image_url'],
                    'alt_text' => $mediaConfig['alt_text'] ?? ''
                ];
            }
        }

        $summary = [
            'funnel_name' => $session['funnel_name'],
            'lead_fields' => $formVals,
            'answers' => $answers,
            'destination_name' => $destName,
            'destination_url' => $destUrl,
            'calculated_value' => $calculatedValue,
            'formatted_quote' => $formattedQuote,
            'calculator' => $quoteCard,
            'quote_card' => $quoteCard,
            'quick_connect' => $quickConnect,
            'media' => $media,
            'completed_at' => date('Y-m-d H:i:s'),
        ];

        Database::transaction(function () use ($session, $destNode, $destId, $resultTokenHash, $summary, $calculatedValue, $formattedQuote) {
            Database::execute(
                "UPDATE quiz_sessions SET status = 'completed', completed_at = NOW(), final_destination_id = ?, calculated_value = ?, formatted_quote = ?, last_activity_at = NOW() WHERE id = ?",
                [$destId, $calculatedValue, $formattedQuote, $session['id']]
            );

            Database::execute(
                "UPDATE form_submissions SET calculated_value = ?, formatted_quote = ? WHERE session_id = ?",
                [$calculatedValue, $formattedQuote, $session['id']]
            );

            Database::execute(
                "INSERT INTO quiz_results (tenant_id, funnel_id, funnel_version_id, session_id, result_token_hash, summary_json, destination_id, calculated_value, formatted_quote, redirect_status, completed_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()) 
                 ON DUPLICATE KEY UPDATE summary_json = VALUES(summary_json), destination_id = VALUES(destination_id), calculated_value = VALUES(calculated_value), formatted_quote = VALUES(formatted_quote)",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $resultTokenHash, json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $destId, $calculatedValue, $formattedQuote]
            );

            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
                 VALUES (?, ?, ?, ?, 'quiz_completed', ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $destNode['id']]
            );
        });

        // Trigger Webhook Event (quiz.completed)
        try {
            WebhookService::trigger((int)$session['tenant_id'], 'quiz.completed', [
                'session_id' => (int)$session['id'],
                'funnel_id' => (int)$session['funnel_id'],
                'funnel_name' => $session['funnel_name'],
                'destination_name' => $destName,
                'destination_url' => $destUrl,
                'calculated_value' => $calculatedValue,
                'formatted_quote' => $formattedQuote,
                'quote_card' => $quoteCard,
                'quick_connect' => $quickConnect,
                'lead_fields' => $formVals,
                'answers' => $answers,
                'completed_at' => date('Y-m-d H:i:s'),
            ], (int)$session['funnel_id']);
        } catch (\Throwable $e) {
            Logger::error("Webhook dispatch failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'status' => 'completed',
            'funnel_name' => $session['funnel_name'],
            'settings' => $settingsSnapshot,
            'result' => [
                'result_token' => $rawResultToken,
                'destination_name' => $destName,
                'destination_url' => $destUrl,
                'calculated_value' => $calculatedValue,
                'formatted_quote' => $formattedQuote,
                'quote_card' => $quoteCard,
                'quick_connect' => $quickConnect,
                'media' => $media,
                'summary' => $summary,
                'auto_redirect' => !empty($settingsSnapshot['auto_redirect']),
                'redirect_delay_seconds' => (int)($settingsSnapshot['redirect_delay_seconds'] ?? 3),
            ]
        ];
    }

    public static function submitFormNode(string $rawToken, array $session, array $node, array $answerPayload, array $graphSnapshot, array $settingsSnapshot): array
    {
        $nodeId = (int)$node['id'];
        $formConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
        $fields = $formConfig['fields'] ?? [];
        $formData = $answerPayload['form_data'] ?? $answerPayload;

        // Validate required fields
        $errors = [];
        foreach ($fields as $f) {
            $key = $f['field_key'];
            $val = trim($formData[$key] ?? '');
            if (!empty($f['is_required']) && $val === '') {
                $errors[$key] = ($f['label'] ?? $key) . ' is required.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'error_code' => 'FORM_VALIDATION_FAILED',
                'error' => reset($errors),
                'errors' => $errors,
            ];
        }

        $submitAction = $formConfig['submit_action'] ?? 'redirect';

        // 1. Save Form Submission into DB
        Database::transaction(function () use ($session, $nodeId, $node, $fields, $formData) {
            Database::execute(
                "INSERT INTO form_submissions (tenant_id, funnel_id, funnel_version_id, session_id, submitted_at) VALUES (?, ?, ?, ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id']]
            );
            $formSubId = (int) Database::lastInsertId();

            foreach ($fields as $f) {
                $key = $f['field_key'];
                $val = isset($formData[$key]) ? (string)$formData[$key] : '';
                Database::execute(
                    "INSERT INTO form_submission_values (tenant_id, form_submission_id, field_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$session['tenant_id'], $formSubId, $f['id'] ?? null, $key, $f['label'] ?? $key, $f['field_type'] ?? 'text', $val]
                );
            }

            // Invalidate any previous answer for this node if user navigated back
            Database::execute(
                "UPDATE quiz_answers SET superseded_at = NOW() WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL",
                [$session['id'], $nodeId]
            );

            // Record in quiz_answers for sequence tracking
            Database::execute(
                "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, answered_at) 
                 VALUES (?, ?, ?, ?, NULL, ?, 'form', ?, ?, NOW())",
                [
                    $session['tenant_id'],
                    $session['id'],
                    $session['funnel_version_id'],
                    $nodeId,
                    $node['title'] ?? 'Form Step',
                    json_encode($formData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode(array_keys($formData))
                ]
            );

            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
                 VALUES (?, ?, ?, ?, 'form_step_submitted', ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $nodeId]
            );
        });

        // 2. Post-submit decision: Message (No redirection) vs Redirect
        if ($submitAction === 'message') {
            return self::completeSessionWithMessage($session, $node, $formConfig, $graphSnapshot, $settingsSnapshot);
        }

        // Redirect: advance to target node from graph snapshot
        $edges = $graphSnapshot['edges'] ?? [];
        $targetNodeId = null;
        foreach ($edges as $e) {
            if ((int)$e['source_node_id'] === $nodeId) {
                $targetNodeId = (int)$e['target_node_id'];
                break;
            }
        }

        if (!$targetNodeId) {
            throw new RuntimeException("Routing error: No next step connected after form submission.");
        }

        Database::execute(
            "UPDATE quiz_sessions SET current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
            [$targetNodeId, $session['id']]
        );

        return self::getCurrentStep($rawToken);
    }

    public static function recordVideoEvent(string $rawToken, int $nodeId, string $eventType, array $eventData = []): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid or expired quiz session.");
        }

        $allowedEvents = ['video_started', 'video_gate_reached', 'video_gate_completed', 'video_gate_skipped', 'video_completed'];
        if (!in_array($eventType, $allowedEvents, true)) {
            throw new RuntimeException("Unsupported video event type: {$eventType}");
        }

        // When reaching the gate without completion, mark session status as 'form_pending_video_gate'
        if ($eventType === 'video_gate_reached') {
            Database::execute(
                "UPDATE quiz_sessions SET status = 'form_pending_video_gate', last_activity_at = NOW() WHERE id = ? AND status = 'in_progress'",
                [$session['id']]
            );
        } elseif ($eventType === 'video_completed' || $eventType === 'video_gate_completed' || $eventType === 'video_gate_skipped') {
            Database::execute(
                "UPDATE quiz_sessions SET status = 'in_progress', last_activity_at = NOW() WHERE id = ? AND status = 'form_pending_video_gate'",
                [$session['id']]
            );
        }

        Database::execute(
            "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, event_data_json, occurred_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $session['tenant_id'],
                $session['funnel_id'],
                $session['funnel_version_id'],
                $session['id'],
                $eventType,
                $nodeId,
                !empty($eventData) ? json_encode($eventData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null
            ]
        );

        return ['success' => true, 'event' => $eventType];
    }

    public static function submitVideoGate(string $rawToken, int $nodeId, array $payload): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid or expired quiz session.");
        }

        $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];
        $node = null;
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            if ((int)$n['id'] === $nodeId) {
                $node = $n;
                break;
            }
        }
        if (!$node || $node['node_type'] !== 'video_gate') {
            throw new RuntimeException("Target step is not an interactive video gate.");
        }

        $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
        $vgConfig = $nodeConfig['video_gate'] ?? ($node['video_gate'] ?? []);
        $gateType = $vgConfig['gate_type'] ?? 'lead_form';

        if ($gateType === 'lead_form') {
            $fields = $vgConfig['form_fields'] ?? [
                ['field_key' => 'name', 'label' => 'Full Name', 'field_type' => 'text', 'is_required' => 1],
                ['field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'is_required' => 1],
            ];
            $rawFormData = $payload['form_data'] ?? $payload;
            $formData = [];
            foreach ($rawFormData as $k => $v) {
                if (!in_array($k, ['session_token', 'node_id', 'form_data'], true)) {
                    $formData[$k] = is_string($v) ? trim($v) : $v;
                }
            }

            $errors = [];
            foreach ($fields as $f) {
                $key = $f['field_key'] ?? ($f['key'] ?? '');
                if (!$key) continue;
                $val = trim($formData[$key] ?? '');
                $isRequired = !empty($f['is_required']) || !empty($f['required']);
                $fType = $f['field_type'] ?? ($f['type'] ?? 'text');
                $fLabel = $f['label'] ?? ($f['field_label'] ?? ucfirst($key));

                if ($isRequired && $val === '') {
                    $errors[$key] = $fLabel . ' is required.';
                } elseif ($val !== '' && in_array($fType, ['email'], true) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $errors[$key] = 'Please provide a valid email address.';
                }
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'error_code' => 'VALIDATION_FAILED',
                    'error' => reset($errors),
                    'errors' => $errors
                ];
            }

            Database::transaction(function () use ($session, $nodeId, $node, $fields, $formData, $vgConfig) {
                Database::execute(
                    "INSERT INTO form_submissions (tenant_id, funnel_id, funnel_version_id, session_id, submitted_at) VALUES (?, ?, ?, ?, NOW())",
                    [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id']]
                );
                $formSubId = (int) Database::lastInsertId();

                foreach ($fields as $f) {
                    $key = $f['field_key'] ?? ($f['key'] ?? '');
                    if (!$key) continue;
                    $val = isset($formData[$key]) ? (string)$formData[$key] : '';
                    $fType = $f['field_type'] ?? ($f['type'] ?? 'text');
                    $fLabel = $f['label'] ?? ($f['field_label'] ?? ucfirst($key));

                    Database::execute(
                        "INSERT INTO form_submission_values (tenant_id, form_submission_id, field_id, field_key_snapshot, field_label_snapshot, field_type_snapshot, value_text) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$session['tenant_id'], $formSubId, $f['id'] ?? null, $key, $fLabel, $fType, $val]
                    );
                }

                // Invalidate prior answer for this node if any
                Database::execute(
                    "UPDATE quiz_answers SET superseded_at = NOW() WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL",
                    [$session['id'], $nodeId]
                );

                Database::execute(
                    "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, answered_at) 
                     VALUES (?, ?, ?, ?, NULL, ?, 'video_gate_lead', ?, ?, NOW())",
                    [
                        $session['tenant_id'],
                        $session['id'],
                        $session['funnel_version_id'],
                        $nodeId,
                        $node['title'] ?? 'Video Gate Lead Capture',
                        json_encode($formData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        json_encode(array_keys($formData))
                    ]
                );

                Database::execute(
                    "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, event_data_json, occurred_at) 
                     VALUES (?, ?, ?, ?, 'video_gate_completed', ?, ?, NOW())",
                    [
                        $session['tenant_id'],
                        $session['funnel_id'],
                        $session['funnel_version_id'],
                        $session['id'],
                        $nodeId,
                        json_encode(['gate_type' => 'lead_form', 'lead' => $formData, 'provider' => $vgConfig['video_provider'] ?? 'youtube', 'gate_seconds' => $vgConfig['gate_timestamp_seconds'] ?? 45])
                    ]
                );

                // Restore session status to in_progress
                Database::execute(
                    "UPDATE quiz_sessions SET status = 'in_progress', last_activity_at = NOW() WHERE id = ?",
                    [$session['id']]
                );
            });

            // Trigger Webhook Event (lead.captured)
            try {
                WebhookService::trigger((int)$session['tenant_id'], 'lead.captured', [
                    'session_id' => (int)$session['id'],
                    'funnel_id' => (int)$session['funnel_id'],
                    'funnel_name' => $session['funnel_name'],
                    'node_id' => $nodeId,
                    'source' => 'video_gate',
                    'lead_data' => $formData,
                    'submitted_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                Logger::error("Webhook dispatch failed: " . $e->getMessage());
            }

            return ['success' => true, 'is_unlocked' => true, 'gate_type' => 'lead_form'];
        } else {
            // Gate type: question
            $selectedOptionId = isset($payload['option_id']) ? (int)$payload['option_id'] : null;
            $selectedOptionLabel = $payload['option_label'] ?? '';

            if (!$selectedOptionId && empty($selectedOptionLabel)) {
                return ['success' => false, 'error' => 'Please select an answer to continue.'];
            }

            Database::transaction(function () use ($session, $nodeId, $node, $selectedOptionId, $selectedOptionLabel, $vgConfig) {
                Database::execute(
                    "UPDATE quiz_answers SET superseded_at = NOW() WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL",
                    [$session['id'], $nodeId]
                );

                Database::execute(
                    "INSERT INTO quiz_answers (tenant_id, session_id, funnel_version_id, node_id, question_id, question_text_snapshot, answer_type, answer_value_text, selected_option_ids_json, answered_at) 
                     VALUES (?, ?, ?, ?, NULL, ?, 'video_gate_question', ?, ?, NOW())",
                    [
                        $session['tenant_id'],
                        $session['id'],
                        $session['funnel_version_id'],
                        $nodeId,
                        $vgConfig['question']['question_text'] ?? ($node['title'] ?? 'Video Question'),
                        $selectedOptionLabel ?: (string)$selectedOptionId,
                        json_encode($selectedOptionId ? [$selectedOptionId] : [])
                    ]
                );

                Database::execute(
                    "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, option_id, event_data_json, occurred_at) 
                     VALUES (?, ?, ?, ?, 'video_gate_completed', ?, ?, ?, NOW())",
                    [
                        $session['tenant_id'],
                        $session['funnel_id'],
                        $session['funnel_version_id'],
                        $session['id'],
                        $nodeId,
                        $selectedOptionId,
                        json_encode(['gate_type' => 'question', 'option_id' => $selectedOptionId, 'option_label' => $selectedOptionLabel, 'provider' => $vgConfig['video_provider'] ?? 'youtube', 'gate_seconds' => $vgConfig['gate_timestamp_seconds'] ?? 45])
                    ]
                );

                Database::execute(
                    "UPDATE quiz_sessions SET status = 'in_progress', last_activity_at = NOW() WHERE id = ?",
                    [$session['id']]
                );
            });

            return ['success' => true, 'is_unlocked' => true, 'gate_type' => 'question', 'option_id' => $selectedOptionId];
        }
    }

    public static function advanceVideoStep(string $rawToken, int $nodeId): array
    {
        $session = self::resolveSession($rawToken);
        if (!$session) {
            throw new RuntimeException("Invalid session.");
        }

        $graphSnapshot = json_decode($session['graph_snapshot_json'], true) ?: [];

        $node = null;
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            if ((int)$n['id'] === $nodeId) {
                $node = $n;
                break;
            }
        }
        $nodeConfig = !empty($node['config_json']) ? (json_decode($node['config_json'], true) ?: []) : [];
        $vgConfig = $nodeConfig['video_gate'] ?? ($node['video_gate'] ?? []);
        $endAction = $vgConfig['end_action'] ?? ($vgConfig['on_video_end'] === 'message' || $vgConfig['on_video_end'] === 'thank_you' ? 'thank_you' : 'next_step');

        if ($endAction === 'thank_you' || $endAction === 'message') {
            $settingsSnapshot = json_decode($session['settings_snapshot_json'], true) ?: [];
            return self::completeSessionWithMessage($session, $node ?: ['id' => $nodeId, 'title' => 'Video Step'], [
                'success_heading' => $vgConfig['success_heading'] ?? '🎉 Thank You for Watching!',
                'success_message' => $vgConfig['success_message'] ?? 'Your submission has been received. We will be in touch shortly.'
            ], $graphSnapshot, $settingsSnapshot);
        }

        // Find the most recent active answer for this node to see if an option was selected
        $lastAns = Database::fetchOne(
            "SELECT * FROM quiz_answers WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL ORDER BY id DESC LIMIT 1",
            [$session['id'], $nodeId]
        );

        $selectedOptionId = null;
        if ($lastAns && !empty($lastAns['selected_option_ids_json'])) {
            $opts = json_decode($lastAns['selected_option_ids_json'], true) ?: [];
            if (!empty($opts)) {
                $selectedOptionId = (int)$opts[0];
            }
        }

        $targetNodeId = null;
        // 1. Try option-specific branch
        if ($selectedOptionId) {
            foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                if ((int)$edge['source_node_id'] === $nodeId && (int)$edge['source_option_id'] === $selectedOptionId) {
                    $targetNodeId = (int)$edge['target_node_id'];
                    break;
                }
            }
        }

        // 2. Try default outgoing edge from this node
        if (!$targetNodeId) {
            foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                if ((int)$edge['source_node_id'] === $nodeId && empty($edge['source_option_id'])) {
                    $targetNodeId = (int)$edge['target_node_id'];
                    break;
                }
            }
        }

        // 3. Fallback to any edge from this node
        if (!$targetNodeId) {
            foreach ($graphSnapshot['edges'] ?? [] as $edge) {
                if ((int)$edge['source_node_id'] === $nodeId) {
                    $targetNodeId = (int)$edge['target_node_id'];
                    break;
                }
            }
        }

        if (!$targetNodeId) {
            throw new RuntimeException("Routing error: No destination connected for this video step.");
        }

        Database::execute(
            "UPDATE quiz_sessions SET current_node_id = ?, status = 'in_progress', last_activity_at = NOW() WHERE id = ?",
            [$targetNodeId, $session['id']]
        );

        return self::getCurrentStep($rawToken);
    }

    private static function completeSessionWithMessage(array $session, array $formNode, array $formConfig, array $graphSnapshot, array $settingsSnapshot): array
    {
        $rawResultToken = bin2hex(random_bytes(32));
        $resultTokenHash = hash('sha256', $rawResultToken);

        $heading = !empty($formConfig['success_heading']) ? $formConfig['success_heading'] : 'Thank You!';
        $message = !empty($formConfig['success_message']) ? $formConfig['success_message'] : 'Your submission has been received.';

        // Fetch lead form details
        $formVals = Database::query(
            "SELECT fsv.field_label_snapshot, fsv.value_text 
             FROM form_submissions fs
             JOIN form_submission_values fsv ON fsv.form_submission_id = fs.id
             WHERE fs.session_id = ? 
             ORDER BY fsv.id ASC",
            [$session['id']]
        );

        $answers = Database::query(
            "SELECT question_text_snapshot, answer_value_text, answered_at 
             FROM quiz_answers 
             WHERE session_id = ? AND superseded_at IS NULL AND (answer_type != 'form' OR answer_type IS NULL)
             ORDER BY id ASC",
            [$session['id']]
        );

        $summary = [
            'funnel_name' => $session['funnel_name'],
            'completion_type' => 'message',
            'success_heading' => $heading,
            'success_message' => $message,
            'lead_fields' => $formVals,
            'answers' => $answers,
            'completed_at' => date('Y-m-d H:i:s'),
        ];

        Database::transaction(function () use ($session, $formNode, $resultTokenHash, $summary) {
            Database::execute(
                "UPDATE quiz_sessions SET status = 'completed', completed_at = NOW(), last_activity_at = NOW() WHERE id = ?",
                [$session['id']]
            );

            Database::execute(
                "INSERT INTO quiz_results (tenant_id, funnel_id, funnel_version_id, session_id, result_token_hash, summary_json, destination_id, redirect_status, completed_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NULL, 'none', NOW()) 
                 ON DUPLICATE KEY UPDATE summary_json = VALUES(summary_json)",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $resultTokenHash, json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]
            );

            Database::execute(
                "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
                 VALUES (?, ?, ?, ?, 'quiz_completed', ?, NOW())",
                [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $formNode['id']]
            );
        });

        // Trigger Webhook Event (quiz.completed)
        try {
            WebhookService::trigger((int)$session['tenant_id'], 'quiz.completed', [
                'session_id' => (int)$session['id'],
                'funnel_id' => (int)$session['funnel_id'],
                'funnel_name' => $session['funnel_name'],
                'completion_type' => 'message',
                'success_heading' => $heading,
                'lead_fields' => $formVals,
                'answers' => $answers,
                'completed_at' => date('Y-m-d H:i:s'),
            ], (int)$session['funnel_id']);
        } catch (\Throwable $e) {
            Logger::error("Webhook dispatch failed: " . $e->getMessage());
        }

        return [
            'success' => true,
            'status' => 'completed',
            'funnel_name' => $session['funnel_name'],
            'settings' => $settingsSnapshot,
            'result' => [
                'result_token' => $rawResultToken,
                'completion_type' => 'message',
                'success_heading' => $heading,
                'success_message' => $message,
                'summary' => $summary,
                'auto_redirect' => false,
            ]
        ];
    }

    private static function getCompletedStep(array $session, array $graphSnapshot, array $settingsSnapshot): array
    {
        $result = Database::fetchOne("SELECT * FROM quiz_results WHERE session_id = ?", [$session['id']]);
        $summary = $result ? json_decode($result['summary_json'], true) : [];
        $calculatedValue = $result['calculated_value'] ?? ($summary['calculated_value'] ?? null);
        $formattedQuote = $result['formatted_quote'] ?? ($summary['formatted_quote'] ?? null);
        $quoteCard = $summary['quote_card'] ?? ($summary['calculator'] ?? null);
        $quickConnect = $summary['quick_connect'] ?? null;
        $media = $summary['media'] ?? null;

        if (($summary['completion_type'] ?? '') === 'message') {
            return [
                'success' => true,
                'status' => 'completed',
                'funnel_name' => $session['funnel_name'],
                'settings' => $settingsSnapshot,
                'result' => [
                    'completion_type' => 'message',
                    'success_heading' => $summary['success_heading'] ?? 'Thank You!',
                    'success_message' => $summary['success_message'] ?? 'Your submission has been received.',
                    'calculated_value' => $calculatedValue,
                    'formatted_quote' => $formattedQuote,
                    'quote_card' => $quoteCard,
                    'quick_connect' => $quickConnect,
                    'summary' => $summary,
                    'auto_redirect' => false,
                ]
            ];
        }

        $destName = $summary['destination_name'] ?? 'Your Results';
        $destUrl = $summary['destination_url'] ?? '#';

        return [
            'success' => true,
            'status' => 'completed',
            'funnel_name' => $session['funnel_name'],
            'settings' => $settingsSnapshot,
            'result' => [
                'destination_name' => $destName,
                'destination_url' => $destUrl,
                'calculated_value' => $calculatedValue,
                'formatted_quote' => $formattedQuote,
                'quote_card' => $quoteCard,
                'quick_connect' => $quickConnect,
                'media' => $media,
                'summary' => $summary,
                'auto_redirect' => !empty($settingsSnapshot['auto_redirect']),
            ]
        ];
    }

    public static function extractSessionVariables(int $sessionId, array $graphSnapshot): array
    {
        $answers = Database::query(
            "SELECT qa.*, q.id as q_id 
             FROM quiz_answers qa
             LEFT JOIN questions q ON qa.question_id = q.id
             WHERE qa.session_id = ? AND qa.superseded_at IS NULL AND (qa.answer_type != 'form' OR qa.answer_type IS NULL)
             ORDER BY qa.id ASC",
            [$sessionId]
        );

        $variables = [];
        $qIdx = 1;

        $nodesById = [];
        foreach ($graphSnapshot['nodes'] ?? [] as $n) {
            $nodesById[(int)$n['id']] = $n;
        }

        $optionsById = [];
        foreach ($graphSnapshot['options'] ?? [] as $opt) {
            $optionsById[(int)$opt['id']] = $opt;
        }

        foreach ($answers as $ans) {
            $nodeId = (int)$ans['node_id'];
            $node = $nodesById[$nodeId] ?? null;
            $varKey = !empty($node['variable_key']) ? strtoupper(ltrim($node['variable_key'], '@')) : ('Q' . $qIdx);

            $val = 0.0;
            if ($ans['answer_type'] === 'slider') {
                $val = (float)$ans['answer_value_text'];
            } else {
                $optIds = json_decode($ans['selected_option_ids_json'] ?? '[]', true) ?: [];
                $firstOptId = reset($optIds);
                if ($firstOptId && isset($optionsById[(int)$firstOptId])) {
                    $optVal = $optionsById[(int)$firstOptId]['value'] ?? '';
                    if (is_numeric($optVal)) {
                        $val = (float)$optVal;
                    } elseif (is_numeric($ans['answer_value_text'])) {
                        $val = (float)$ans['answer_value_text'];
                    }
                } elseif (is_numeric($ans['answer_value_text'])) {
                    $val = (float)$ans['answer_value_text'];
                }
            }

            $variables[$varKey] = $val;
            $qIdx++;
        }

        return $variables;
    }

    public static function evaluateFormula(string $expr, array $variables = []): float
    {
        if (trim($expr) === '') {
            return 0.0;
        }

        // 1. Substitute variables @Q1, @VAR_KEY
        $parsed = preg_replace_callback('/@([A-Za-z0-9_]+)/', function ($matches) use ($variables) {
            $key = strtoupper($matches[1]);
            $val = 0.0;
            foreach ($variables as $vKey => $vVal) {
                if (strtoupper($vKey) === $key) {
                    $val = (float)$vVal;
                    break;
                }
            }
            return (string)$val;
        }, $expr);

        // Remove whitespace
        $parsed = preg_replace('/\s+/', '', $parsed);
        if ($parsed === '') {
            return 0.0;
        }

        // Validate characters
        if (!preg_match('/^[0-9.+\\-*\/%^()]+$/', $parsed)) {
            Logger::error("Invalid characters in formula: {$expr}");
            return 0.0;
        }

        // Tokenize
        $tokens = [];
        $len = strlen($parsed);
        $i = 0;
        while ($i < $len) {
            $ch = $parsed[$i];
            if (strpos('+-*/%^()', $ch) !== false) {
                // Check unary minus
                if ($ch === '-' && (empty($tokens) || (is_string(end($tokens)) && strpos('+-*/%^(', end($tokens)) !== false))) {
                    $numStr = '-';
                    $i++;
                    while ($i < $len && (ctype_digit($parsed[$i]) || $parsed[$i] === '.')) {
                        $numStr .= $parsed[$i];
                        $i++;
                    }
                    if ($numStr === '-') {
                        return 0.0;
                    }
                    $tokens[] = (float)$numStr;
                    continue;
                } else {
                    $tokens[] = $ch;
                    $i++;
                }
            } elseif (ctype_digit($ch) || $ch === '.') {
                $numStr = '';
                while ($i < $len && (ctype_digit($parsed[$i]) || $parsed[$i] === '.')) {
                    $numStr .= $parsed[$i];
                    $i++;
                }
                $tokens[] = (float)$numStr;
            } else {
                return 0.0;
            }
        }

        // Shunting-Yard algorithm
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 2, '^' => 3];
        $associativity = ['+' => 'L', '-' => 'L', '*' => 'L', '/' => 'L', '%' => 'L', '^' => 'R'];
        $outputQueue = [];
        $opStack = [];

        foreach ($tokens as $token) {
            if (is_float($token) || is_int($token)) {
                $outputQueue[] = (float)$token;
            } elseif (isset($precedence[$token])) {
                while (!empty($opStack)) {
                    $top = end($opStack);
                    if (isset($precedence[$top]) && (
                        ($associativity[$token] === 'L' && $precedence[$token] <= $precedence[$top]) ||
                        ($associativity[$token] === 'R' && $precedence[$token] < $precedence[$top])
                    )) {
                        $outputQueue[] = array_pop($opStack);
                    } else {
                        break;
                    }
                }
                $opStack[] = $token;
            } elseif ($token === '(') {
                $opStack[] = $token;
            } elseif ($token === ')') {
                while (!empty($opStack) && end($opStack) !== '(') {
                    $outputQueue[] = array_pop($opStack);
                }
                if (empty($opStack)) {
                    return 0.0; // Unbalanced
                }
                array_pop($opStack); // Discard '('
            }
        }

        while (!empty($opStack)) {
            $top = array_pop($opStack);
            if ($top === '(' || $top === ')') {
                return 0.0;
            }
            $outputQueue[] = $top;
        }

        // Evaluate RPN
        $evalStack = [];
        foreach ($outputQueue as $token) {
            if (is_float($token) || is_int($token)) {
                $evalStack[] = (float)$token;
            } else {
                if (count($evalStack) < 2) {
                    return 0.0;
                }
                $b = array_pop($evalStack);
                $a = array_pop($evalStack);
                $res = 0.0;
                switch ($token) {
                    case '+': $res = $a + $b; break;
                    case '-': $res = $a - $b; break;
                    case '*': $res = $a * $b; break;
                    case '/':
                        if ($b == 0.0) return 0.0;
                        $res = $a / $b;
                        break;
                    case '%':
                        if ($b == 0.0) return 0.0;
                        $res = fmod($a, $b);
                        break;
                    case '^': $res = pow($a, $b); break;
                }
                $evalStack[] = (float)$res;
            }
        }

        if (count($evalStack) !== 1) {
            return 0.0;
        }

        $final = reset($evalStack);
        return is_finite($final) ? (float)$final : 0.0;
    }

    public static function getPublicResult(string $rawResultToken): ?array
    {
        $hash = hash('sha256', $rawResultToken);
        $result = Database::fetchOne(
            "SELECT qr.*, f.name as funnel_name, f.settings_json 
             FROM quiz_results qr
             JOIN funnels f ON qr.funnel_id = f.id
             WHERE qr.result_token_hash = ?",
            [$hash]
        );

        if (!$result) {
            return null;
        }

        return [
            'funnel_name' => $result['funnel_name'],
            'summary' => json_decode($result['summary_json'], true) ?: [],
            'completed_at' => $result['completed_at'],
        ];
    }

    public static function evaluateCondition(array $condConfig, int $sessionId, array $graphSnapshot): bool
    {
        $condType = $condConfig['condition_type'] ?? 'score';
        $operator = $condConfig['operator'] ?? '>=';
        $targetVal = trim((string)($condConfig['value'] ?? '0'));
        $variables = self::extractSessionVariables($sessionId, $graphSnapshot);

        $actualVal = null;
        if ($condType === 'score') {
            // Cumulative score of all answers so far
            $actualVal = (float)array_sum($variables);
        } elseif ($condType === 'variable') {
            $varKey = strtoupper(ltrim($condConfig['variable_key'] ?? 'Q1', '@'));
            $actualVal = isset($variables[$varKey]) ? (float)$variables[$varKey] : 0.0;
        } elseif ($condType === 'slider' || $condType === 'answer') {
            $targetNodeId = (int)($condConfig['target_node_id'] ?? 0);
            $ans = null;
            if ($targetNodeId > 0) {
                $ans = Database::fetchOne(
                    "SELECT * FROM quiz_answers WHERE session_id = ? AND node_id = ? AND superseded_at IS NULL ORDER BY id DESC LIMIT 1",
                    [$sessionId, $targetNodeId]
                );
            }
            if (!$ans) {
                // Fallback to most recent slider answer
                $ans = Database::fetchOne(
                    "SELECT * FROM quiz_answers WHERE session_id = ? AND answer_type = 'slider' AND superseded_at IS NULL ORDER BY id DESC LIMIT 1",
                    [$sessionId]
                );
            }

            if ($ans) {
                if ($ans['answer_type'] === 'slider' || is_numeric($ans['answer_value_text'])) {
                    $actualVal = (float)$ans['answer_value_text'];
                } else {
                    $actualVal = $ans['answer_value_text'];
                }
            } else {
                $actualVal = 0.0;
            }
        } else {
            $actualVal = (float)array_sum($variables);
        }

        // Compare values
        if (is_numeric($actualVal) && is_numeric($targetVal)) {
            $numActual = (float)$actualVal;
            $numTarget = (float)$targetVal;
            switch ($operator) {
                case '>': return $numActual > $numTarget;
                case '>=': return $numActual >= $numTarget;
                case '<': return $numActual < $numTarget;
                case '<=': return $numActual <= $numTarget;
                case '==': return abs($numActual - $numTarget) < 0.0001;
                case '!=': return abs($numActual - $numTarget) >= 0.0001;
                default: return $numActual >= $numTarget;
            }
        } else {
            $strActual = strtolower(trim((string)$actualVal));
            $strTarget = strtolower($targetVal);
            switch ($operator) {
                case '==': return $strActual === $strTarget;
                case '!=': return $strActual !== $strTarget;
                case 'contains': return strpos($strActual, $strTarget) !== false;
                default: return $strActual === $strTarget;
            }
        }
    }

    public static function evaluateAndAdvanceCondition(string $rawToken, array $session, array $condNode, array $graphSnapshot): array
    {
        $nodeConfig = !empty($condNode['config_json']) ? (json_decode($condNode['config_json'], true) ?: []) : [];
        $condConfig = $condNode['condition'] ?? ($nodeConfig['condition'] ?? $nodeConfig);

        $isMatch = self::evaluateCondition($condConfig, (int)$session['id'], $graphSnapshot);

        $edges = $graphSnapshot['edges'] ?? [];
        $matchTarget = null;
        $defaultTarget = null;
        $fallbackTarget = null;

        foreach ($edges as $e) {
            if ((int)$e['source_node_id'] === (int)$condNode['id']) {
                $handle = $e['source_handle'] ?? $e['source_option_id'] ?? null;
                if ($handle === 'match') {
                    $matchTarget = (int)$e['target_node_id'];
                } elseif ($handle === 'default' || $handle === 'else') {
                    $defaultTarget = (int)$e['target_node_id'];
                } else {
                    if (!$fallbackTarget) $fallbackTarget = (int)$e['target_node_id'];
                }
            }
        }

        $nextTarget = null;
        if ($isMatch && $matchTarget) {
            $nextTarget = $matchTarget;
        } elseif (!$isMatch && $defaultTarget) {
            $nextTarget = $defaultTarget;
        } elseif ($matchTarget) {
            $nextTarget = $matchTarget;
        } elseif ($defaultTarget) {
            $nextTarget = $defaultTarget;
        } elseif ($fallbackTarget) {
            $nextTarget = $fallbackTarget;
        }

        if (!$nextTarget) {
            throw new RuntimeException("Conditional routing error: No destination connected for condition in '{$condNode['title']}'.");
        }

        // Record journey event
        Database::execute(
            "INSERT INTO quiz_step_events (tenant_id, funnel_id, funnel_version_id, session_id, event_type, node_id, occurred_at) 
             VALUES (?, ?, ?, ?, 'condition_routed', ?, NOW())",
            [$session['tenant_id'], $session['funnel_id'], $session['funnel_version_id'], $session['id'], $condNode['id']]
        );

        // Advance session
        Database::execute(
            "UPDATE quiz_sessions SET current_node_id = ?, last_activity_at = NOW() WHERE id = ?",
            [$nextTarget, $session['id']]
        );

        // Recursively return next step
        return self::getCurrentStep($rawToken);
    }
}

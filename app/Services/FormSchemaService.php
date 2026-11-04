<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class FormSchemaService
{
    public static function getFormFields(int $tenantId, int $funnelId): array
    {
        $fields = Database::query(
            "SELECT * FROM form_fields WHERE tenant_id = ? AND funnel_id = ? ORDER BY display_order ASC",
            [$tenantId, $funnelId]
        );

        foreach ($fields as &$f) {
            $f['options'] = Database::query(
                "SELECT * FROM form_field_options WHERE field_id = ? ORDER BY display_order ASC",
                [$f['id']]
            );
        }
        unset($f);

        return $fields;
    }

    public static function saveFormFields(int $tenantId, int $funnelId, array $fieldsData, int $userId): array
    {
        $funnel = Database::fetchOne("SELECT id FROM funnels WHERE tenant_id = ? AND id = ?", [$tenantId, $funnelId]);
        if (!$funnel) {
            throw new RuntimeException("Funnel not found.");
        }

        return Database::transaction(function () use ($tenantId, $funnelId, $fieldsData, $userId) {
            $existing = Database::query("SELECT id, field_key FROM form_fields WHERE funnel_id = ?", [$funnelId]);
            $existingMap = [];
            foreach ($existing as $ex) {
                $existingMap[$ex['field_key']] = (int)$ex['id'];
            }

            $touchedIds = [];
            $displayOrder = 1;

            foreach ($fieldsData as $field) {
                $label = trim($field['label'] ?? 'Field');
                $key = trim($field['field_key'] ?? '');
                if (empty($key)) {
                    $key = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $label));
                    $key = trim($key, '_');
                    if (empty($key)) {
                        $key = 'field_' . bin2hex(random_bytes(3));
                    }
                }

                $type = $field['field_type'] ?? 'text';
                $placeholder = $field['placeholder'] ?? null;
                $helpText = $field['help_text'] ?? null;
                $isRequired = !empty($field['is_required']) ? 1 : 0;
                $isActive = isset($field['is_active']) ? (int)$field['is_active'] : 1;

                if (isset($existingMap[$key])) {
                    $fieldId = $existingMap[$key];
                    Database::execute(
                        "UPDATE form_fields SET label = ?, field_type = ?, placeholder = ?, help_text = ?, is_required = ?, display_order = ?, is_active = ? WHERE id = ?",
                        [$label, $type, $placeholder, $helpText, $isRequired, $displayOrder++, $isActive, $fieldId]
                    );
                } else {
                    Database::execute(
                        "INSERT INTO form_fields (tenant_id, funnel_id, field_key, label, field_type, placeholder, help_text, is_required, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$tenantId, $funnelId, $key, $label, $type, $placeholder, $helpText, $isRequired, $displayOrder++, $isActive]
                    );
                    $fieldId = (int) Database::lastInsertId();
                }

                $touchedIds[] = $fieldId;

                // Handle Options for select/dropdown fields
                Database::execute("DELETE FROM form_field_options WHERE field_id = ?", [$fieldId]);
                if (!empty($field['options']) && is_array($field['options'])) {
                    $optOrder = 1;
                    foreach ($field['options'] as $opt) {
                        $optLabel = is_array($opt) ? ($opt['label'] ?? '') : (string)$opt;
                        $optVal = is_array($opt) ? ($opt['value'] ?? $optLabel) : (string)$opt;
                        if (trim($optLabel) !== '') {
                            Database::execute(
                                "INSERT INTO form_field_options (tenant_id, field_id, label, value, display_order) VALUES (?, ?, ?, ?, ?)",
                                [$tenantId, $fieldId, trim($optLabel), trim($optVal), $optOrder++]
                            );
                        }
                    }
                }
            }

            // Delete removed fields
            if (!empty($touchedIds)) {
                $placeholders = implode(',', array_fill(0, count($touchedIds), '?'));
                Database::execute("DELETE FROM form_fields WHERE funnel_id = ? AND id NOT IN ({$placeholders})", array_merge([$funnelId], $touchedIds));
            } else {
                Database::execute("DELETE FROM form_fields WHERE funnel_id = ?", [$funnelId]);
            }

            AuditLogService::log($tenantId, $userId, 'save_form_fields', 'funnel', $funnelId);

            return self::getFormFields($tenantId, $funnelId);
        });
    }

    public static function validateSubmission(array $fields, array $inputData): array
    {
        $errors = [];
        $cleanData = [];

        foreach ($fields as $field) {
            $key = $field['field_key'];
            $label = $field['label'];
            $type = $field['field_type'];
            $isRequired = !empty($field['is_required']);

            $val = $inputData[$key] ?? null;

            if ($type === 'consent') {
                if ($isRequired && empty($val)) {
                    $errors[$key] = "Please acknowledge and agree to continue.";
                }
                $cleanData[$key] = !empty($val) ? '1' : '0';
                continue;
            }

            $strVal = is_string($val) ? trim($val) : '';

            if ($isRequired && $strVal === '') {
                $errors[$key] = "{$label} is required.";
                continue;
            }

            if ($strVal !== '') {
                if ($type === 'email' && !filter_var($strVal, FILTER_VALIDATE_EMAIL)) {
                    $errors[$key] = "Please enter a valid email address.";
                    continue;
                }
                if ($type === 'number' && !is_numeric($strVal)) {
                    $errors[$key] = "Please enter a valid number.";
                    continue;
                }
            }

            $cleanData[$key] = $strVal;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'clean_data' => $cleanData,
        ];
    }
}

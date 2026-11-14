<?php

namespace App\Services;

use App\Core\Database;

class PlatformSettingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = Database::fetchOne("SELECT setting_value FROM platform_settings WHERE setting_key = ?", [$key]);
        if (!$row || $row['setting_value'] === null) {
            return $default;
        }

        $decoded = json_decode($row['setting_value'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $row['setting_value'];
    }

    public static function set(string $key, mixed $value): void
    {
        $valToStore = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        Database::execute(
            "INSERT INTO platform_settings (setting_key, setting_value, updated_at) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
            [$key, $valToStore]
        );
    }

    public static function getPaymentGateways(): array
    {
        $defaults = [
            'stripe' => [
                'enabled' => false,
                'mode' => 'test',
                'publishable_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
            ],
            'paypal' => [
                'enabled' => false,
                'mode' => 'sandbox',
                'client_id' => '',
                'client_secret' => '',
            ],
            'razorpay' => [
                'enabled' => false,
                'key_id' => '',
                'key_secret' => '',
            ],
            'bank_transfer' => [
                'enabled' => false,
                'bank_name' => 'HDFC Bank / State Bank of India',
                'account_holder' => 'LeadCollector Technologies Pvt Ltd',
                'account_number' => '',
                'routing_code' => '',
                'instructions' => "Please transfer subscription dues via UPI / NEFT / IMPS / RTGS to the above bank account and mention your Workspace ID in the transaction reference.\nOnce transferred, notify billing@leadcollector.in.",
            ],
            'manual_custom' => [
                'enabled' => false,
                'title' => 'Offline / Invoice Payment',
                'instructions' => "For offline purchase orders or custom INR tax invoices, contact your account manager or email billing@leadcollector.in.",
                'qr_code_url' => '',
            ],
        ];

        $saved = self::get('payment_gateways', []);
        if (empty($saved) || !is_array($saved)) {
            return $defaults;
        }

        // Merge defaults to assure all keys exist
        return array_replace_recursive($defaults, $saved);
    }

    public static function getDefaultCurrency(): string
    {
        return (string) self::get('default_currency', 'INR');
    }

    public static function getCurrencySymbol(): string
    {
        $curr = self::getDefaultCurrency();
        return match ($curr) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => '₹',
        };
    }
}

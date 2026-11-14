<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use RuntimeException;

class EmailVerificationService
{
    public const OTP_EXPIRY_MINUTES = 15;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate and dispatch a 6-digit verification OTP to user.
     */
    public static function generateOtp(int $userId, string $email, string $userName): array
    {
        $email = trim(strtolower($email));

        // Check cooldown to prevent email bombing
        $recent = Database::fetchOne(
            "SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_ago 
             FROM email_verifications 
             WHERE user_id = ? 
             ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        if ($recent && $recent['seconds_ago'] !== null && (int)$recent['seconds_ago'] < self::RESEND_COOLDOWN_SECONDS) {
            $waitSeconds = self::RESEND_COOLDOWN_SECONDS - (int)$recent['seconds_ago'];
            return [
                'success' => false,
                'error' => "Please wait {$waitSeconds} seconds before requesting another code.",
                'retry_after' => $waitSeconds,
            ];
        }

        // Invalidate old OTPs for this user
        Database::execute("DELETE FROM email_verifications WHERE user_id = ?", [$userId]);

        // Generate 6-digit cryptographic numeric OTP
        $otpCode = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::OTP_EXPIRY_MINUTES . ' minutes'));

        // Persist OTP
        Database::execute(
            "INSERT INTO email_verifications (user_id, email, otp_code, expires_at, attempts, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())",
            [$userId, $email, $otpCode, $expiresAt]
        );

        // Send OTP email template
        $mailResult = MailService::sendTemplate('email_verification_otp', $email, $userName, [
            'otp_code' => $otpCode,
            'otp_expiry_minutes' => self::OTP_EXPIRY_MINUTES,
            'user_name' => $userName,
            'user_email' => $email,
        ]);

        Logger::info("Email verification OTP generated for user #{$userId} ({$email})");

        return [
            'success' => true,
            'message' => "A 6-digit verification code has been sent to {$email}.",
            'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
            'otp' => $otpCode, // Available for development/testing
            'simulated' => !empty($mailResult['simulated']),
        ];
    }

    /**
     * Verify submitted OTP for a user.
     */
    public static function verifyOtp(int $userId, string $submittedOtp): array
    {
        $submittedOtp = trim($submittedOtp);

        $record = Database::fetchOne(
            "SELECT * FROM email_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        if (!$record) {
            return [
                'success' => false,
                'error' => 'No active verification code found. Please request a new code.',
            ];
        }

        // Check attempts limit
        if ((int)$record['attempts'] >= self::MAX_ATTEMPTS) {
            Database::execute("DELETE FROM email_verifications WHERE id = ?", [$record['id']]);
            return [
                'success' => false,
                'error' => 'Too many failed verification attempts. Please request a fresh code.',
            ];
        }

        // Check expiration
        if (strtotime($record['expires_at']) < time()) {
            Database::execute("DELETE FROM email_verifications WHERE id = ?", [$record['id']]);
            return [
                'success' => false,
                'error' => 'Verification code has expired. Please request a new code.',
            ];
        }

        // Validate code match
        if (!hash_equals((string)$record['otp_code'], $submittedOtp)) {
            Database::execute(
                "UPDATE email_verifications SET attempts = attempts + 1 WHERE id = ?",
                [$record['id']]
            );
            $remaining = self::MAX_ATTEMPTS - ((int)$record['attempts'] + 1);
            return [
                'success' => false,
                'error' => "Invalid code. {$remaining} attempts remaining.",
            ];
        }

        // Verification SUCCESS!
        Database::execute(
            "UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$userId]
        );

        // Delete used verification record
        Database::execute("DELETE FROM email_verifications WHERE user_id = ?", [$userId]);

        // Refresh user in session
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            unset($user['password_hash']);
            Session::set('user', $user);
        }

        // Send Welcome Email
        $tenant = TenantContextService::getActiveTenant();
        MailService::sendTemplate('welcome_email', $user['email'], $user['name'], [
            'tenant_name' => $tenant['name'] ?? 'My Workspace',
            'workspace_slug' => $tenant['slug'] ?? 'workspace',
            'user_name' => $user['name'],
            'user_email' => $user['email'],
        ]);

        Logger::info("User #{$userId} successfully verified email ({$user['email']})");

        return [
            'success' => true,
            'message' => 'Email verified successfully! Welcome to your dashboard.',
            'user' => $user,
        ];
    }

    /**
     * Resend verification OTP to currently authenticated user.
     */
    public static function resend(int $userId): array
    {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }

        if (!empty($user['email_verified_at'])) {
            return ['success' => false, 'error' => 'Email is already verified.'];
        }

        return self::generateOtp($userId, $user['email'], $user['name']);
    }

    /**
     * Check if user is verified.
     */
    public static function isUserVerified(int $userId): bool
    {
        $user = Database::fetchOne("SELECT email_verified_at, is_platform_admin FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return false;
        }

        // Platform admins are exempt
        if (!empty($user['is_platform_admin'])) {
            return true;
        }

        return !empty($user['email_verified_at']);
    }
}

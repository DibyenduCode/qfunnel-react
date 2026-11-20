<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Env;
use RuntimeException;
use Throwable;

class MailService
{
    /**
     * Retrieve current Global SMTP configuration.
     */
    public static function getSmtpSettings(): array
    {
        $envHost = Env::get('MAIL_HOST', '');
        $envPort = (int)Env::get('MAIL_PORT', 587);
        $envUser = Env::get('MAIL_USERNAME', '');
        $envPass = Env::get('MAIL_PASSWORD', '');
        $envFrom = Env::get('MAIL_FROM_ADDRESS', 'no-reply@filterfunnel.in');
        $envFromName = Env::get('MAIL_FROM_NAME', 'FilterFunnel Platform');
        $envReplyTo = Env::get('MAIL_REPLY_TO', 'support@filterfunnel.in');
        $envEnc = Env::get('MAIL_ENCRYPTION', 'tls');
        $envEnabled = !empty($envHost) && $envHost !== 'smtp.gmail.com';

        $defaults = [
            'enabled' => $envEnabled,
            'host' => !empty($envHost) ? $envHost : 'smtp.gmail.com',
            'port' => $envPort > 0 ? $envPort : 587,
            'encryption' => in_array($envEnc, ['tls', 'ssl', 'none'], true) ? $envEnc : 'tls',
            'username' => $envUser,
            'password' => $envPass,
            'from_email' => $envFrom,
            'from_name' => $envFromName,
            'reply_to' => $envReplyTo,
        ];

        $saved = PlatformSettingService::get('smtp_settings', []);
        if (!is_array($saved)) {
            return $defaults;
        }

        return array_merge($defaults, $saved);
    }

    /**
     * Save Global SMTP configuration.
     */
    public static function saveSmtpSettings(array $settings): void
    {
        $current = self::getSmtpSettings();
        $updated = [
            'enabled' => !empty($settings['enabled']),
            'host' => trim((string)($settings['host'] ?? $current['host'])),
            'port' => (int)($settings['port'] ?? $current['port']),
            'encryption' => in_array($settings['encryption'] ?? '', ['tls', 'ssl', 'none'], true) 
                ? $settings['encryption'] 
                : 'tls',
            'username' => trim((string)($settings['username'] ?? $current['username'])),
            'password' => isset($settings['password']) && $settings['password'] !== '' 
                ? (string)$settings['password'] 
                : $current['password'],
            'from_email' => trim((string)($settings['from_email'] ?? $current['from_email'])),
            'from_name' => trim((string)($settings['from_name'] ?? $current['from_name'])),
            'reply_to' => trim((string)($settings['reply_to'] ?? $current['reply_to'])),
        ];

        PlatformSettingService::set('smtp_settings', $updated);
        Logger::info("Global SMTP settings updated by administrator.");
    }

    /**
     * Test SMTP connection by sending a diagnostic test email.
     */
    public static function testSmtpConnection(string $testRecipient): array
    {
        $settings = self::getSmtpSettings();
        if (empty($settings['host']) || empty($settings['port'])) {
            return [
                'success' => false,
                'message' => 'SMTP Host and Port are required to test connection.',
            ];
        }

        $subject = "Test Email from FilterFunnel Platform (" . date('H:i:s') . ")";
        $bodyHtml = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b;'>
                <h2 style='color: #1d5fd6; margin-top: 0;'>✓ SMTP Connection Successful</h2>
                <p>Congratulations! Your Global SMTP server is successfully configured and connected to <strong>FilterFunnel Platform</strong>.</p>
                <div style='background: #f1f5f9; padding: 14px 18px; border-radius: 8px; font-size: 13px; line-height: 1.6;'>
                    <strong>Host:</strong> {$settings['host']}:{$settings['port']}<br>
                    <strong>Encryption:</strong> " . strtoupper($settings['encryption']) . "<br>
                    <strong>From:</strong> {$settings['from_name']} &lt;{$settings['from_email']}&gt;<br>
                    <strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "
                </div>
                <p style='color: #64748b; font-size: 12px; margin-top: 20px;'>This test message confirms outbound email dispatch capability.</p>
            </div>
        ";

        return self::sendRaw(
            $testRecipient,
            'Super Admin Test',
            $subject,
            $bodyHtml,
            "SMTP Connection Successful. Host: {$settings['host']}:{$settings['port']}",
            true // force live send even if enabled=false for testing
        );
    }

    /**
     * Low-level socket SMTP client supporting SSL, TLS (STARTTLS), and AUTH LOGIN.
     */
    public static function sendRaw(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        bool $forceLive = false
    ): array {
        $settings = self::getSmtpSettings();
        $isLive = $forceLive || !empty($settings['enabled']);

        // Fallback / Simulation mode if SMTP is disabled
        if (!$isLive) {
            $nativeRes = self::sendNativeMail($toEmail, $toName, $subject, $htmlBody, $settings);
            if ($nativeRes['success']) {
                return $nativeRes;
            }

            $logId = self::logEmail($toEmail, $toName, null, $subject, 'simulated', null);
            Logger::info("SMTP Disabled: Simulated email to {$toEmail} | Subject: {$subject}");
            return [
                'success' => true,
                'simulated' => true,
                'message' => "SMTP is currently disabled. Email was logged in Simulated Outbox.",
                'log_id' => $logId,
            ];
        }

        $host = $settings['host'];
        $port = (int)$settings['port'];
        $encryption = $settings['encryption'];
        $username = $settings['username'];
        $password = $settings['password'];
        $fromEmail = $settings['from_email'];
        $fromName = $settings['from_name'];
        $replyTo = !empty($settings['reply_to']) ? $settings['reply_to'] : $fromEmail;

        $targetHost = ($encryption === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";
        $timeout = 10;
        $errno = 0;
        $errstr = '';

        $socketContext = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client($targetHost . ":{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $socketContext);
        if (!$socket) {
            $errorMsg = "Socket connect failed to {$targetHost}:{$port} - {$errstr} ({$errno})";
            $nativeRes = self::sendNativeMail($toEmail, $toName, $subject, $htmlBody, $settings);
            if ($nativeRes['success']) {
                Logger::warning("SMTP socket failed ({$errorMsg}). Fallback to native mail succeeded.");
                return $nativeRes;
            }
            self::logEmail($toEmail, $toName, null, $subject, 'failed', $errorMsg);
            return ['success' => false, 'message' => $errorMsg];
        }

        stream_set_timeout($socket, $timeout);

        try {
            $resp = self::readSmtpResponse($socket);
            if (!str_starts_with($resp, '220')) {
                throw new RuntimeException("Initial greeting failed: {$resp}");
            }

            // EHLO
            $clientHost = gethostname() ?: 'localhost';
            self::writeSmtpCommand($socket, "EHLO {$clientHost}");
            $ehloResp = self::readSmtpResponse($socket);

            // STARTTLS if requested
            if ($encryption === 'tls') {
                self::writeSmtpCommand($socket, "STARTTLS");
                $tlsResp = self::readSmtpResponse($socket);
                if (!str_starts_with($tlsResp, '220')) {
                    throw new RuntimeException("STARTTLS failed: {$tlsResp}");
                }

                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }

                $cryptoOk = stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if (!$cryptoOk) {
                    throw new RuntimeException("Failed to negotiate TLS encryption.");
                }

                // Re-send EHLO over encrypted channel
                self::writeSmtpCommand($socket, "EHLO {$clientHost}");
                self::readSmtpResponse($socket);
            }

            // AUTH LOGIN if credentials provided
            if (!empty($username)) {
                self::writeSmtpCommand($socket, "AUTH LOGIN");
                $authResp = self::readSmtpResponse($socket);
                if (!str_starts_with($authResp, '334')) {
                    throw new RuntimeException("AUTH LOGIN rejected: {$authResp}");
                }

                self::writeSmtpCommand($socket, base64_encode($username));
                $userResp = self::readSmtpResponse($socket);
                if (!str_starts_with($userResp, '334')) {
                    throw new RuntimeException("SMTP Username rejected: {$userResp}");
                }

                self::writeSmtpCommand($socket, base64_encode($password));
                $passResp = self::readSmtpResponse($socket);
                if (!str_starts_with($passResp, '235')) {
                    throw new RuntimeException("SMTP Authentication failed: {$passResp}");
                }
            }

            // MAIL FROM
            self::writeSmtpCommand($socket, "MAIL FROM:<{$fromEmail}>");
            $fromResp = self::readSmtpResponse($socket);
            if (!str_starts_with($fromResp, '250')) {
                throw new RuntimeException("MAIL FROM rejected: {$fromResp}");
            }

            // RCPT TO
            self::writeSmtpCommand($socket, "RCPT TO:<{$toEmail}>");
            $rcptResp = self::readSmtpResponse($socket);
            if (!str_starts_with($rcptResp, '250') && !str_starts_with($rcptResp, '251')) {
                throw new RuntimeException("RCPT TO rejected: {$rcptResp}");
            }

            // DATA
            self::writeSmtpCommand($socket, "DATA");
            $dataResp = self::readSmtpResponse($socket);
            if (!str_starts_with($dataResp, '354')) {
                throw new RuntimeException("DATA command rejected: {$dataResp}");
            }

            // Construct MIME message
            $boundary = "bnd_" . md5(uniqid((string)time(), true));
            $domain = parse_url(Env::get('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'filterfunnel.in';
            $msgId = "<" . md5(uniqid((string)time(), true)) . "@" . $domain . ">";
            $headers = [
                "Date: " . date('r'),
                "Message-ID: {$msgId}",
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
                "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>",
                "Reply-To: <{$replyTo}>",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "X-Mailer: FilterFunnel Mailer",
                "Auto-Submitted: auto-generated",
            ];

            $plain = !empty($textBody) ? $textBody : strip_tags($htmlBody);

            $message = implode("\r\n", $headers) . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($plain)) . "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= ".\r\n";

            fwrite($socket, $message);
            $sendResp = self::readSmtpResponse($socket);
            if (!str_starts_with($sendResp, '250')) {
                throw new RuntimeException("Message transmission failed: {$sendResp}");
            }

            // QUIT
            self::writeSmtpCommand($socket, "QUIT");
            @fclose($socket);

            $logId = self::logEmail($toEmail, $toName, null, $subject, 'sent', null);
            Logger::info("SMTP sent email successfully to {$toEmail} | Subject: {$subject}");

            return [
                'success' => true,
                'simulated' => false,
                'message' => "Email dispatched successfully via {$host}.",
                'log_id' => $logId,
            ];
        } catch (Throwable $e) {
            @fclose($socket);
            $err = $e->getMessage();
            $nativeRes = self::sendNativeMail($toEmail, $toName, $subject, $htmlBody, $settings);
            if ($nativeRes['success']) {
                Logger::warning("SMTP Error ({$err}). Fallback to native mail succeeded for {$toEmail}.");
                return $nativeRes;
            }
            self::logEmail($toEmail, $toName, null, $subject, 'failed', $err);
            Logger::error("SMTP Error sending to {$toEmail}: {$err}");
            return ['success' => false, 'message' => $err];
        }
    }

    private static function writeSmtpCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private static function readSmtpResponse($socket): string
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Response line format: '250-...' means multi-line, '250 ...' means end of response
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * Dispatch email based on a managed system template.
     */
    public static function sendTemplate(
        string $templateKey,
        string $toEmail,
        string $toName,
        array $tags = []
    ): array {
        self::ensureDefaultTemplates();

        $tpl = self::getTemplate($templateKey);
        if (!$tpl) {
            $err = "Email template '{$templateKey}' not found.";
            self::logEmail($toEmail, $toName, $templateKey, 'Unknown Template', 'failed', $err);
            return ['success' => false, 'message' => $err];
        }

        if (empty($tpl['is_active'])) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => "Template '{$templateKey}' is currently disabled.",
            ];
        }

        // Standard default tags populated from Super Admin SMTP & platform settings
        $settings = self::getSmtpSettings();
        $appName = !empty($settings['from_name']) ? $settings['from_name'] : Env::get('APP_NAME', 'FilterFunnel');
        $supportEmail = !empty($settings['reply_to']) ? $settings['reply_to'] : (!empty($settings['from_email']) ? $settings['from_email'] : 'support@filterfunnel.in');
        $appUrl = rtrim(Env::get('APP_URL', 'https://filterfunnel.in'), '/');
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $basePath = (isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], '/qfunnel2')) ? '/qfunnel2' : '';
            $detectedUrl = "{$scheme}://{$_SERVER['HTTP_HOST']}{$basePath}";
            if (empty($appUrl) || (str_contains($appUrl, 'localhost') && !str_contains($_SERVER['HTTP_HOST'], 'localhost')) || ($appUrl === 'https://filterfunnel.in' && !str_contains($_SERVER['HTTP_HOST'], 'filterfunnel.in'))) {
                $appUrl = $detectedUrl;
            }
        }

        $mergedTags = array_merge([
            'app_name' => $appName,
            'app_url' => $appUrl,
            'login_url' => "{$appUrl}/login",
            'dashboard_url' => "{$appUrl}/dashboard",
            'support_email' => $supportEmail,
            'current_year' => date('Y'),
            'user_name' => $toName,
            'user_email' => $toEmail,
        ], $tags);

        // Replace tags in subject and body
        $subject = self::replaceTags($tpl['subject'], $mergedTags);
        $bodyHtml = self::replaceTags($tpl['body_html'], $mergedTags);
        $bodyText = !empty($tpl['body_text']) ? self::replaceTags($tpl['body_text'], $mergedTags) : strip_tags($bodyHtml);

        // Wrap HTML body in consistent professional email wrapper
        $wrappedHtml = self::renderEmailWrapper($subject, $tpl['preheader'] ?? '', $bodyHtml, $mergedTags);

        $result = self::sendRaw($toEmail, $toName, $subject, $wrappedHtml, $bodyText);

        // Update email_logs with template_key
        if (!empty($result['log_id'])) {
            Database::execute("UPDATE email_logs SET template_key = ? WHERE id = ?", [$templateKey, $result['log_id']]);
        }

        return $result;
    }

    private static function replaceTags(string $content, array $tags): string
    {
        foreach ($tags as $k => $v) {
            $val = is_scalar($v) ? (string)$v : json_encode($v);
            $content = str_replace(["{{{$k}}}", "{{ {$k} }}"], $val, $content);
        }
        return $content;
    }

    /**
     * Professional responsive email master layout.
     */
    public static function renderEmailWrapper(string $subject, string $preheader, string $contentHtml, array $tags): string
    {
        $appName = htmlspecialchars($tags['app_name'] ?? 'FilterFunnel');
        $supportEmail = htmlspecialchars($tags['support_email'] ?? 'support@filterfunnel.in');
        $year = date('Y');

        $preheaderDiv = !empty($preheader)
            ? "<div style=\"display:none; font-size:1px; color:#ffffff; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;\">" . htmlspecialchars($preheader) . "</div>"
            : "";

        return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <title>{$subject}</title>
</head>
<body style=\"margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;\">
    {$preheaderDiv}
    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #f8f9fa; padding: 36px 16px;\">
        <tr>
            <td align=\"center\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;\">
                    <!-- Brand Top Bar -->
                    <tr>
                        <td style=\"padding: 22px 32px; background-color: #ffffff; border-bottom: 1px solid #f1f5f9;\">
                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                <tr>
                                    <td>
                                        <span style=\"font-size: 17px; font-weight: 800; letter-spacing: -0.4px; color: #0f172a; text-decoration: none;\">
                                            {$appName}
                                        </span>
                                    </td>
                                    <td align=\"right\">
                                        <span style=\"font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 3px 9px; border-radius: 9999px;\">
                                            Platform Notification
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style=\"padding: 32px; font-size: 14px; line-height: 1.65; color: #334155;\">
                            {$contentHtml}
                        </td>
                    </tr>

                    <!-- Clean Brand Footer -->
                    <tr>
                        <td style=\"padding: 24px 32px; background-color: #fafbfc; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #64748b; line-height: 1.6;\">
                            <p style=\"margin: 0 0 6px 0; color: #475569;\">Sent securely by <strong>{$appName}</strong> • Interactive Funnels & Automation</p>
                            <p style=\"margin: 0; color: #94a3b8;\">Questions? Contact our team at <a href=\"mailto:{$supportEmail}\" style=\"color: #1d5fd6; text-decoration: none; font-weight: 500;\">{$supportEmail}</a></p>
                            <p style=\"margin: 10px 0 0 0; font-size: 11px; color: #94a3b8;\">If this email arrived in your Spam folder, please mark it as <em>\"Not Spam\"</em> to ensure you receive future platform alerts.</p>
                            <p style=\"margin: 8px 0 0 0; font-size: 11px; color: #cbd5e1;\">© {$year} {$appName}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";
    }

    /**
     * Retrieve all managed templates.
     */
    public static function getTemplates(): array
    {
        self::ensureDefaultTemplates();
        return Database::query("SELECT * FROM email_templates ORDER BY id ASC");
    }

    /**
     * Retrieve a specific template by key.
     */
    public static function getTemplate(string $key): ?array
    {
        self::ensureDefaultTemplates();
        return Database::fetchOne("SELECT * FROM email_templates WHERE template_key = ?", [$key]) ?: null;
    }

    /**
     * Save/update a template.
     */
    public static function saveTemplate(string $key, array $data): bool
    {
        $existing = self::getTemplate($key);
        if (!$existing) {
            return false;
        }

        Database::execute(
            "UPDATE email_templates 
             SET name = ?, description = ?, subject = ?, preheader = ?, body_html = ?, body_text = ?, is_active = ?, updated_at = NOW() 
             WHERE template_key = ?",
            [
                trim((string)($data['name'] ?? $existing['name'])),
                trim((string)($data['description'] ?? $existing['description'])),
                trim((string)($data['subject'] ?? $existing['subject'])),
                trim((string)($data['preheader'] ?? $existing['preheader'])),
                (string)($data['body_html'] ?? $existing['body_html']),
                (string)($data['body_text'] ?? $existing['body_text']),
                isset($data['is_active']) ? (int)$data['is_active'] : $existing['is_active'],
                $key
            ]
        );

        Logger::info("Email template '{$key}' updated.");
        return true;
    }

    /**
     * Reset a template to system default.
     */
    public static function resetTemplateToDefault(string $key): bool
    {
        $defaults = self::getDefaultTemplateDefinitions();
        if (!isset($defaults[$key])) {
            return false;
        }

        $d = $defaults[$key];
        Database::execute(
            "UPDATE email_templates 
             SET name = ?, description = ?, subject = ?, preheader = ?, body_html = ?, body_text = ?, available_tags = ?, is_active = 1, updated_at = NOW()
             WHERE template_key = ?",
            [
                $d['name'],
                $d['description'],
                $d['subject'],
                $d['preheader'],
                $d['body_html'],
                $d['body_text'],
                json_encode($d['available_tags']),
                $key
            ]
        );

        return true;
    }

    /**
     * Seed default email templates if missing.
     */
    public static function ensureDefaultTemplates(): void
    {
        $definitions = self::getDefaultTemplateDefinitions();
        foreach ($definitions as $key => $def) {
            $exists = Database::fetchOne("SELECT id FROM email_templates WHERE template_key = ?", [$key]);
            if (!$exists) {
                Database::execute(
                    "INSERT INTO email_templates (template_key, name, description, subject, preheader, body_html, body_text, available_tags, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [
                        $key,
                        $def['name'],
                        $def['description'],
                        $def['subject'],
                        $def['preheader'],
                        $def['body_html'],
                        $def['body_text'],
                        json_encode($def['available_tags']),
                    ]
                );
            }
        }
    }

    public static function getDefaultTemplateDefinitions(): array
    {
        return [
            'email_verification_otp' => [
                'name' => 'Email Verification Code (OTP)',
                'description' => 'Sent immediately after user registration to verify ownership with a 6-digit OTP code.',
                'subject' => 'Your {{app_name}} verification code: {{otp_code}}',
                'preheader' => 'Use code {{otp_code}} to verify your account and access your dashboard.',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'user_email',
                    'otp_code',
                    'otp_expiry_minutes',
                    'support_email'
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Verify Your Email Address</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">Thank you for registering on <strong>{{app_name}}</strong>. Please enter the 6-digit verification code below to verify your email and activate your workspace:</p>
                    
                    <div style="margin: 28px 0; text-align: center;">
                        <div style="display: inline-block; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 18px 40px;">
                            <span style="font-size: 38px; font-weight: 800; letter-spacing: 12px; color: #0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; margin-left: 12px;">
                                {{otp_code}}
                            </span>
                        </div>
                        <div style="margin-top: 14px;">
                            <span style="display: inline-block; background-color: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; padding: 5px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                <span style="vertical-align: middle; margin-right: 4px;">⏳</span> Valid for {{otp_expiry_minutes}} minutes
                            </span>
                        </div>
                    </div>

                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-top: 24px; font-size: 12px; color: #64748b; line-height: 1.5;">
                        <span style="font-size: 14px; vertical-align: middle; margin-right: 4px;">🛡️</span> <strong>Security Note:</strong> FilterFunnel team members will never ask you for your verification code. If you did not create an account, you can safely ignore this email.
                    </div>
                ',
                'body_text' => "Hello {{user_name}},\n\nYour 6-digit verification code is: {{otp_code}}\n\nThis code expires in {{otp_expiry_minutes}} minutes.\n\nThank you,\n{{app_name}} Team",
            ],

            'welcome_email' => [
                'name' => 'Welcome to Platform',
                'description' => 'Sent after email verification to welcome new workspace owners.',
                'subject' => 'Welcome to {{app_name}}, {{user_name}} 👋',
                'preheader' => 'Your workspace {{tenant_name}} is ready. Build your first quiz funnel.',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'user_email',
                    'tenant_name',
                    'workspace_slug',
                    'dashboard_url',
                    'support_email'
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Welcome to {{app_name}} 👋</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">Your email is verified and your customer workspace <strong>{{tenant_name}}</strong> is live and ready to build high-converting interactive funnels.</p>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 24px 0; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 14px 16px; font-size: 18px; width: 44px; vertical-align: middle; text-align: center;">🧩</td>
                            <td style="padding: 14px 16px 14px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                                <strong style="color: #0f172a;">Visual Flow Builder:</strong> Drag and drop branching question nodes and 2-field lead capture forms in the visual canvas.
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 14px 16px; font-size: 18px; vertical-align: middle; text-align: center;">🔀</td>
                            <td style="padding: 14px 16px 14px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                                <strong style="color: #0f172a;">Smart Decision Logic:</strong> Add decision nodes to route qualified high-budget prospects to custom instant quotes.
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 16px; font-size: 18px; vertical-align: middle; text-align: center;">🌐</td>
                            <td style="padding: 14px 16px 14px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                                <strong style="color: #0f172a;">Publish & Embed:</strong> Connect custom domains or embed directly on your website to start capturing leads immediately.
                            </td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 28px 0;">
                        <a href="{{dashboard_url}}" style="background-color: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 13px 32px; border-radius: 8px; display: inline-block;">Open Workspace Dashboard →</a>
                    </div>

                    <p style="font-size: 13px; color: #64748b;">Have any questions or need setup help? Reply directly to this email or reach our support team at {{support_email}}.</p>
                ',
                'body_text' => "Hello {{user_name}},\n\nWelcome to {{app_name}}! Your workspace {{tenant_name}} is ready.\n\nOpen your dashboard here: {{dashboard_url}}\n\nBest regards,\n{{app_name}} Team",
            ],

            'invoice_receipt' => [
                'name' => 'Billing & Payment Receipt',
                'description' => 'Sent when a subscription payment is recorded or invoice is generated.',
                'subject' => 'Payment Receipt for Invoice #{{invoice_number}} - {{app_name}}',
                'preheader' => 'Payment confirmed for {{plan_name}} ({{currency_symbol}}{{amount}}).',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'tenant_name',
                    'invoice_number',
                    'plan_name',
                    'amount',
                    'currency_symbol',
                    'payment_method',
                    'billing_interval',
                    'paid_at',
                    'dashboard_url',
                    'support_email'
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Payment Receipt & Invoice 🧾</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">We have received your payment for workspace <strong>{{tenant_name}}</strong>. Your transaction has been processed successfully.</p>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; margin: 24px 0; border-collapse: collapse; overflow: hidden;">
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 4px;">📄</span> Invoice Reference
                            </td>
                            <td style="padding: 10px 16px; text-align: right; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; font-weight: 700; color: #0f172a;">
                                #{{invoice_number}}
                                <span style="margin-left: 6px; background-color: #ecfdf5; color: #047857; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 9999px; border: 1px solid #a7f3d0;">PAID</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 13px; color: #64748b;">
                                <span style="margin-right: 4px;">💳</span> Subscription Plan
                            </td>
                            <td style="padding: 10px 16px; text-align: right; font-size: 13px; font-weight: 600; color: #0f172a;">{{plan_name}} ({{billing_interval}})</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 13px; color: #64748b;">
                                <span style="margin-right: 4px;">🏦</span> Payment Method
                            </td>
                            <td style="padding: 10px 16px; text-align: right; font-size: 13px; color: #0f172a; text-transform: capitalize;">{{payment_method}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 13px; color: #64748b;">
                                <span style="margin-right: 4px;">📅</span> Date Paid
                            </td>
                            <td style="padding: 10px 16px; text-align: right; font-size: 13px; color: #0f172a;">{{paid_at}}</td>
                        </tr>
                        <tr style="background-color: #fafbfc;">
                            <td style="padding: 12px 16px; font-size: 14px; font-weight: 700; color: #0f172a;">
                                <span style="margin-right: 4px;">💰</span> Total Paid
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-size: 16px; font-weight: 800; color: #1d5fd6;">{{currency_symbol}}{{amount}}</td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 26px 0;">
                        <a href="{{dashboard_url}}" style="background-color: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 28px; border-radius: 8px; display: inline-block;">View Billing in Dashboard →</a>
                    </div>

                    <p style="font-size: 12px; color: #94a3b8; line-height: 1.5; margin-bottom: 0;">This receipt serves as your official digital invoice. For tax receipts or GST invoice details, please contact {{support_email}}.</p>
                ',
                'body_text' => "Payment Receipt: Invoice #{{invoice_number}}\n\nPlan: {{plan_name}}\nAmount: {{currency_symbol}}{{amount}}\nDate: {{paid_at}}\n\nThank you for choosing {{app_name}}!",
            ],

            'renewal_reminder' => [
                'name' => 'Subscription Renewal Reminder',
                'description' => 'Sent before subscription renewal or expiration date.',
                'subject' => 'Reminder: Your {{app_name}} subscription renews on {{due_date}}',
                'preheader' => 'Upcoming renewal for {{tenant_name}} ({{currency_symbol}}{{amount}}).',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'tenant_name',
                    'plan_name',
                    'amount',
                    'currency_symbol',
                    'due_date',
                    'billing_interval',
                    'dashboard_url',
                    'support_email'
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Upcoming Subscription Renewal 🗓️</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">This is a friendly reminder that your subscription for workspace <strong>{{tenant_name}}</strong> is scheduled for renewal on <strong>{{due_date}}</strong>.</p>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 22px 0; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; width: 34%;">
                                <span style="margin-right: 4px;">📦</span> Current Plan
                            </td>
                            <td style="padding: 10px 16px; font-size: 13px; font-weight: 700; color: #0f172a;">{{plan_name}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 4px;">💳</span> Scheduled Amount
                            </td>
                            <td style="padding: 10px 16px; font-size: 13px; font-weight: 700; color: #1d5fd6;">{{currency_symbol}}{{amount}} / {{billing_interval}}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 4px;">📅</span> Renewal Date
                            </td>
                            <td style="padding: 10px 16px; font-size: 13px; font-weight: 600; color: #0f172a;">{{due_date}}</td>
                        </tr>
                    </table>

                    <p style="font-size: 13px; color: #475569; line-height: 1.6;">To ensure uninterrupted access to your live quizzes and leads routing, please verify your billing information.</p>

                    <div style="text-align: center; margin: 26px 0;">
                        <a href="{{dashboard_url}}" style="background-color: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 28px; border-radius: 8px; display: inline-block;">Manage Billing & Invoices →</a>
                    </div>
                ',
                'body_text' => "Subscription Renewal Reminder\n\nWorkspace: {{tenant_name}}\nPlan: {{plan_name}}\nAmount: {{currency_symbol}}{{amount}}\nDue: {{due_date}}\n\nManage your billing here: {{dashboard_url}}",
            ],

            'account_suspended' => [
                'name' => 'Account Suspension Notice',
                'description' => 'Sent if payment grace period expires or administrative suspension occurs.',
                'subject' => 'Important: Your {{app_name}} workspace has been suspended',
                'preheader' => 'Action required to restore active status for {{tenant_name}}.',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'tenant_name',
                    'suspension_reason',
                    'dashboard_url',
                    'support_email'
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #dc2626; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Workspace Suspended ⚠️</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">Your customer workspace <strong>{{tenant_name}}</strong> has been suspended due to:</p>

                    <div style="margin: 20px 0; background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #dc2626; border-radius: 6px; padding: 14px 18px; color: #991b1b; font-size: 13px; font-weight: 600;">
                        <span style="font-size: 15px; vertical-align: middle; margin-right: 6px;">🛑</span> {{suspension_reason}}
                    </div>

                    <p style="font-size: 13px; color: #475569; line-height: 1.6;">While suspended, visitor quiz submissions are paused and editing features are locked. Your quiz funnels and customer lead data remain fully safe.</p>

                    <div style="text-align: center; margin: 26px 0;">
                        <a href="{{dashboard_url}}" style="background-color: #dc2626; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 28px; border-radius: 8px; display: inline-block;">Reactivate Workspace →</a>
                    </div>

                    <p style="font-size: 12px; color: #64748b;">If you need assistance or believe this is an error, contact our support team at {{support_email}}.</p>
                ',
                'body_text' => "Workspace Suspended: {{tenant_name}}\nReason: {{suspension_reason}}\n\nPlease visit your dashboard to reactivate: {{dashboard_url}}\n\n{{app_name}} Support",
            ],

            'team_invitation' => [
                'name' => 'Team Member Invitation & Credentials',
                'description' => 'Sent when a new user is invited to a workspace, containing login URL, email, and temporary password.',
                'subject' => '{{invited_by_name}} invited you to {{workspace_name}} on {{app_name}}',
                'preheader' => '{{invited_by_name}} invited you to collaborate as {{role}}. Log in with your temporary credentials.',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'user_email',
                    'password',
                    'workspace_name',
                    'role',
                    'invited_by_name',
                    'login_url',
                    'support_email',
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Workspace Team Invitation 🤝</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;"><strong>{{invited_by_name}}</strong> has invited you to collaborate on the customer workspace <strong>{{workspace_name}}</strong> as <strong style="text-transform: capitalize;">{{role}}</strong>.</p>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 24px 0; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; width: 34%;">
                                <span style="margin-right: 6px; font-size: 14px;">🏢</span> Workspace
                            </td>
                            <td style="padding: 11px 16px; font-size: 13px; font-weight: 700; color: #0f172a;">{{workspace_name}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 6px; font-size: 14px;">🏷️</span> Assigned Role
                            </td>
                            <td style="padding: 11px 16px;">
                                <span style="background-color: #eff6ff; color: #1d5fd6; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #bfdbfe; text-transform: uppercase; letter-spacing: 0.5px;">{{role}}</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 6px; font-size: 14px;">✉️</span> Login Email
                            </td>
                            <td style="padding: 11px 16px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #0f172a; font-weight: 600;">{{user_email}}</td>
                        </tr>
                        <tr>
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 6px; font-size: 14px;">🔑</span> Direct Password
                            </td>
                            <td style="padding: 11px 16px;">
                                <code style="background-color: #ffffff; border: 1px solid #cbd5e1; padding: 3px 8px; border-radius: 5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; font-weight: 700; color: #1d5fd6;">{{password}}</code>
                            </td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{login_url}}" style="background-color: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 13px 32px; border-radius: 8px; display: inline-block; letter-spacing: -0.2px;">Log In to Your Workspace →</a>
                    </div>

                    <div style="background-color: #f8fafc; border-left: 3px solid #64748b; padding: 10px 14px; border-radius: 4px; font-size: 12px; color: #64748b; line-height: 1.5;">
                        <span style="font-size: 13px; vertical-align: middle; margin-right: 4px;">🛡️</span> <strong>Security Note:</strong> This is a temporary access credential generated by your workspace administrator. You can update your password at any time from your Profile Settings.
                    </div>
                ',
                'body_text' => "Hello {{user_name}},\n\n{{invited_by_name}} invited you to join {{workspace_name}} as {{role}}.\n\nLogin URL: {{login_url}}\nEmail: {{user_email}}\nPassword: {{password}}\n\nLog in here: {{login_url}}\n\nBest regards,\n{{app_name}} Team",
            ],

            'team_member_added' => [
                'name' => 'Team Workspace Access Granted (Existing User)',
                'description' => 'Sent when an existing platform user is granted access to a new workspace.',
                'subject' => 'You\'ve been added to {{workspace_name}} on {{app_name}}',
                'preheader' => 'Access granted by {{invited_by_name}} as {{role}}. Switch workspaces in your dashboard.',
                'available_tags' => [
                    'app_name',
                    'user_name',
                    'user_email',
                    'workspace_name',
                    'role',
                    'invited_by_name',
                    'login_url',
                    'dashboard_url',
                    'support_email',
                ],
                'body_html' => '
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px; letter-spacing: -0.3px;">Workspace Access Granted 🤝</h2>
                    <p style="margin-top: 0; color: #475569; font-size: 14px; line-height: 1.6;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;"><strong>{{invited_by_name}}</strong> has granted you collaboration access to the workspace <strong>{{workspace_name}}</strong> as <strong style="text-transform: capitalize;">{{role}}</strong>.</p>

                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 24px 0; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; width: 34%;">
                                <span style="margin-right: 6px; font-size: 14px;">🏢</span> Workspace
                            </td>
                            <td style="padding: 11px 16px; font-size: 13px; font-weight: 700; color: #0f172a;">{{workspace_name}}</td>
                        </tr>
                        <tr>
                            <td style="padding: 11px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <span style="margin-right: 6px; font-size: 14px;">🏷️</span> Assigned Role
                            </td>
                            <td style="padding: 11px 16px;">
                                <span style="background-color: #eff6ff; color: #1d5fd6; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #bfdbfe; text-transform: uppercase; letter-spacing: 0.5px;">{{role}}</span>
                            </td>
                        </tr>
                    </table>

                    <p style="font-size: 13px; color: #475569; line-height: 1.6;">Since you already have a registered account on {{app_name}}, simply log in with your existing password and select <strong>{{workspace_name}}</strong> from the workspace dropdown switcher.</p>

                    <div style="text-align: center; margin: 28px 0;">
                        <a href="{{login_url}}" style="background-color: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 13px 32px; border-radius: 8px; display: inline-block;">Open Workspace →</a>
                    </div>
                ',
                'body_text' => "Hello {{user_name}},\n\n{{invited_by_name}} has granted you access to {{workspace_name}} as {{role}}.\n\nLog in with your existing credentials here: {{login_url}}",
            ],
        ];
    }

    /**
     * Dispatch email using PHP's native mail() function (standard on cPanel/Linux Exim sendmail).
     */
    public static function sendNativeMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $settings
    ): array {
        if (!function_exists('mail')) {
            return ['success' => false, 'message' => 'PHP mail() function is disabled on this server.'];
        }

        try {
            $fromEmail = !empty($settings['from_email']) ? $settings['from_email'] : 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'filterfunnel.in');
            $fromName = !empty($settings['from_name']) ? $settings['from_name'] : 'FilterFunnel Platform';
            $replyTo = !empty($settings['reply_to']) ? $settings['reply_to'] : $fromEmail;

            $domain = parse_url(Env::get('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'filterfunnel.in';
            $msgId = "<" . md5(uniqid((string)time(), true)) . "@" . $domain . ">";

            $headers = [
                'Date: ' . date('r'),
                'Message-ID: ' . $msgId,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $fromEmail),
                'Reply-To: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $replyTo),
                'X-Mailer: FilterFunnel Mailer',
                'Auto-Submitted: auto-generated',
            ];

            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $recipientHeader = !empty($toName) ? sprintf('"%s" <%s>', addcslashes($toName, '"'), $toEmail) : $toEmail;

            $additionalParams = (filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) ? "-f{$fromEmail}" : '';
            $sent = $additionalParams 
                ? @mail($recipientHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers), $additionalParams)
                : @mail($recipientHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers));

            if ($sent) {
                $logId = self::logEmail($toEmail, $toName, null, $subject, 'sent', null);
                Logger::info("Dispatched email via native PHP mail() to {$toEmail} | Subject: {$subject}");
                return [
                    'success' => true,
                    'simulated' => false,
                    'message' => 'Email sent via server mail agent.',
                    'log_id' => $logId,
                ];
            }
        } catch (Throwable $e) {
            Logger::error("Native mail() failed: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Native mail delivery failed.'];
    }

    /**
     * Log sent or failed emails.
     */
    private static function logEmail(
        string $recipientEmail,
        string $recipientName,
        ?string $templateKey,
        string $subject,
        string $status,
        ?string $errorMessage
    ): int {
        Database::execute(
            "INSERT INTO email_logs (recipient_email, recipient_name, template_key, subject, status, error_message, sent_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$recipientEmail, $recipientName, $templateKey, $subject, $status, $errorMessage]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Get recent email outbox logs.
     */
    public static function getRecentLogs(int $limit = 100): array
    {
        return Database::query(
            "SELECT el.*, et.name as template_name 
             FROM email_logs el 
             LEFT JOIN email_templates et ON el.template_key = et.template_key 
             ORDER BY el.id DESC 
             LIMIT ?",
            [$limit]
        );
    }
}

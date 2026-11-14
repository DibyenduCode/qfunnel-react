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
        $defaults = [
            'enabled' => false,
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls', // 'tls', 'ssl', 'none'
            'username' => '',
            'password' => '',
            'from_email' => 'no-reply@leadcollector.in',
            'from_name' => 'LeadCollector Platform',
            'reply_to' => 'support@leadcollector.in',
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

        $subject = "Test Email from LeadCollector Platform (" . date('H:i:s') . ")";
        $bodyHtml = "
            <div style='font-family: sans-serif; padding: 24px; color: #1e293b;'>
                <h2 style='color: #1d5fd6; margin-top: 0;'>✓ SMTP Connection Successful</h2>
                <p>Congratulations! Your Global SMTP server is successfully configured and connected to <strong>LeadCollector Platform</strong>.</p>
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

        $socket = @stream_socket_client($targetHost . ":{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $errorMsg = "Socket connect failed to {$targetHost}:{$port} - {$errstr} ({$errno})";
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
            $headers = [
                "Date: " . date('r'),
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
                "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>",
                "Reply-To: <{$replyTo}>",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "X-Mailer: QuizFunnel Mailer 2.0",
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

        // Standard default tags
        $appName = Env::get('APP_NAME', 'LeadCollector');
        $appUrl = rtrim(Env::get('APP_URL', 'https://leadcollector.in'), '/');
        $mergedTags = array_merge([
            'app_name' => $appName,
            'app_url' => $appUrl,
            'login_url' => "{$appUrl}/login",
            'dashboard_url' => "{$appUrl}/dashboard",
            'support_email' => 'support@leadcollector.in',
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
        $appName = htmlspecialchars($tags['app_name'] ?? 'LeadCollector');
        $supportEmail = htmlspecialchars($tags['support_email'] ?? 'support@leadcollector.in');
        $year = date('Y');

        return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$subject}</title>
</head>
<body style=\"margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;\">
    " . (!empty($preheader) ? "<div style=\"display:none; font-size:1px; color:#ffffff; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;\">" . htmlspecialchars($preheader) . "</div>" : "") . "
    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #f8fafc; padding: 32px 16px;\">
        <tr>
            <td align=\"center\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 580px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);\">
                    <!-- Brand Header -->
                    <tr>
                        <td style=\"padding: 28px 32px 20px 32px; background-color: #ffffff; border-bottom: 1px solid #f1f5f9;\">
                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                <tr>
                                    <td>
                                        <div style=\"display: inline-block; background-color: #1d5fd6; color: #ffffff; font-weight: bold; font-size: 16px; padding: 6px 12px; border-radius: 8px; letter-spacing: -0.5px;\">
                                            {$appName}
                                        </div>
                                    </td>
                                    <td align=\"right\" style=\"font-size: 12px; color: #64748b; font-weight: 500;\">
                                        Platform Notification
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

                    <!-- Brand Footer -->
                    <tr>
                        <td style=\"padding: 24px 32px; background-color: #f8fafc; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #64748b;\">
                            <p style=\"margin: 0 0 8px 0;\">Sent securely by <strong>{$appName}</strong> • Quiz Funnels & Interactive Automation</p>
                            <p style=\"margin: 0; color: #94a3b8;\">Need assistance? Contact our team at <a href=\"mailto:{$supportEmail}\" style=\"color: #1d5fd6; text-decoration: none;\">{$supportEmail}</a></p>
                            <p style=\"margin: 12px 0 0 0; font-size: 11px; color: #cbd5e1;\">© {$year} {$appName}. All rights reserved.</p>
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
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;">Verify Your Email Address</h2>
                    <p style="margin-top: 0; color: #475569;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569;">Thank you for registering on <strong>{{app_name}}</strong>. Please use the 6-digit One-Time Password (OTP) below to verify your email address and activate your workspace dashboard:</p>
                    
                    <div style="margin: 28px 0; text-align: center;">
                        <div style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1d5fd6; background: #eff6ff; border: 2px dashed #bfdbfe; border-radius: 12px; padding: 14px 28px; font-family: monospace;">
                            {{otp_code}}
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-left: 4px solid #1d5fd6; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #475569; margin-bottom: 24px;">
                        ⏱️ This verification code is valid for <strong>{{otp_expiry_minutes}} minutes</strong>. For security, never share this code with anyone.
                    </div>

                    <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">If you did not create an account on {{app_name}}, please disregard this message or notify our support team.</p>
                ',
                'body_text' => "Hello {{user_name}},\n\nYour 6-digit verification code is: {{otp_code}}\n\nThis code expires in {{otp_expiry_minutes}} minutes.\n\nThank you,\n{{app_name}} Team",
            ],

            'welcome_email' => [
                'name' => 'Welcome to Platform',
                'description' => 'Sent after email verification to welcome new workspace owners.',
                'subject' => 'Welcome to {{app_name}}, {{user_name}}! 🚀',
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
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;">Welcome to {{app_name}}! 🚀</h2>
                    <p style="margin-top: 0; color: #475569;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569;">Your email is verified and your customer workspace <strong>{{tenant_name}}</strong> is live and ready for high-converting interactive funnels.</p>

                    <div style="margin: 24px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a;">Quick Start Checklist:</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.8;">
                            <li>Create your first visual quiz with our drag-and-drop React Flow canvas</li>
                            <li>Configure branching paths, lead forms, and instant calculator quotes</li>
                            <li>Embed the quiz on your website via iframe or popup widget</li>
                            <li>Track incoming leads in real-time with automatic WhatsApp routing</li>
                        </ul>
                    </div>

                    <div style="text-align: center; margin: 28px 0;">
                        <a href="{{dashboard_url}}" style="background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 8px; display: inline-block;">Go to Workspace Dashboard →</a>
                    </div>

                    <p style="font-size: 13px; color: #64748b;">Have any questions? Simply reply to this email or reach us at {{support_email}}.</p>
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
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;">Payment Receipt & Invoice</h2>
                    <p style="margin-top: 0; color: #475569;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569;">We have received your payment for workspace <strong>{{tenant_name}}</strong>. Your transaction has been processed successfully.</p>

                    <table style="width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 13px;">
                        <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                            <td style="padding: 10px 14px; font-weight: 600; color: #475569;">Invoice Number</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: #0f172a; font-family: monospace;">#{{invoice_number}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 14px; color: #64748b;">Subscription Plan</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 600; color: #0f172a;">{{plan_name}} ({{billing_interval}})</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 14px; color: #64748b;">Payment Method</td>
                            <td style="padding: 10px 14px; text-align: right; color: #0f172a; text-transform: capitalize;">{{payment_method}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 10px 14px; color: #64748b;">Date Paid</td>
                            <td style="padding: 10px 14px; text-align: right; color: #0f172a;">{{paid_at}}</td>
                        </tr>
                        <tr style="background: #eff6ff; font-size: 14px;">
                            <td style="padding: 12px 14px; font-weight: 700; color: #1d5fd6;">Total Paid</td>
                            <td style="padding: 12px 14px; text-align: right; font-weight: 800; color: #1d5fd6;">{{currency_symbol}}{{amount}}</td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 24px 0;">
                        <a href="{{dashboard_url}}" style="background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;">View Billing in Dashboard →</a>
                    </div>

                    <p style="font-size: 12px; color: #64748b;">This receipt serves as your official proof of payment. For tax invoices or corporate GST details, please reach out to {{support_email}}.</p>
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
                    <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 8px;">Upcoming Subscription Renewal</h2>
                    <p style="margin-top: 0; color: #475569;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569;">This is a friendly reminder that your subscription for workspace <strong>{{tenant_name}}</strong> is scheduled for renewal on <strong>{{due_date}}</strong>.</p>

                    <div style="margin: 20px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #64748b;">Current Plan:</p>
                        <p style="margin: 0 0 12px 0; font-size: 16px; font-weight: 700; color: #0f172a;">{{plan_name}} • {{currency_symbol}}{{amount}} / {{billing_interval}}</p>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Renewal / Due Date: <strong>{{due_date}}</strong></p>
                    </div>

                    <p style="font-size: 13px; color: #475569;">To ensure uninterrupted access to your live quizzes, automated lead routing, and analytics, please ensure your payment method is up to date.</p>

                    <div style="text-align: center; margin: 24px 0;">
                        <a href="{{dashboard_url}}" style="background: #1d5fd6; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;">Manage Billing & Invoices →</a>
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
                    <h2 style="font-size: 20px; font-weight: 700; color: #dc2626; margin-top: 0; margin-bottom: 8px;">Workspace Suspended</h2>
                    <p style="margin-top: 0; color: #475569;">Hello <strong>{{user_name}}</strong>,</p>
                    <p style="color: #475569;">Your customer workspace <strong>{{tenant_name}}</strong> has been suspended due to:</p>

                    <div style="margin: 18px 0; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px 18px; color: #991b1b; font-size: 13px; font-weight: 600;">
                        ⚠️ {{suspension_reason}}
                    </div>

                    <p style="font-size: 13px; color: #475569;">While suspended, visitor quiz submissions are paused and editing features are locked. Your data remains fully safe.</p>

                    <div style="text-align: center; margin: 24px 0;">
                        <a href="{{dashboard_url}}" style="background: #dc2626; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 10px 20px; border-radius: 8px; display: inline-block;">Reactivate Workspace →</a>
                    </div>

                    <p style="font-size: 12px; color: #64748b;">If you need assistance or believe this is an error, contact our support team at {{support_email}}.</p>
                ',
                'body_text' => "Workspace Suspended: {{tenant_name}}\nReason: {{suspension_reason}}\n\nPlease visit your dashboard to reactivate: {{dashboard_url}}\n\n{{app_name}} Support",
            ]
        ];
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

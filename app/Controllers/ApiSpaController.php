<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;
use App\Services\FunnelService;
use App\Services\FunnelPublishService;
use App\Services\FormSchemaService;
use App\Services\SubmissionService;
use App\Services\LeadStageService;
use App\Services\AnalyticsService;
use App\Services\UsageLimitService;
use App\Services\SubscriptionService;
use App\Services\PlatformSettingService;
use App\Services\AuditLogService;
use App\Services\WebhookService;
use App\Services\LegalPageService;
use App\Services\MailService;
use App\Services\EmailVerificationService;

class ApiSpaController
{
    // ==========================================
    // AUTHENTICATION & SESSION
    // ==========================================

    public function csrfToken(Request $request): Response
    {
        return Response::success([
            'csrf_token' => csrf_token(),
        ]);
    }

    public function login(Request $request): Response
    {
        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');

        $result = AuthService::attempt($email, $password);

        if (!$result['success']) {
            return Response::error($result['error'] ?? 'Invalid credentials.', 'AUTH_FAILED', null, 401);
        }

        $user = current_user();
        $tenant = TenantContextService::getActiveTenant();
        $role = TenantContextService::getCurrentRole();
        $userTenants = TenantContextService::getUserTenants((int)$user['id']);

        return Response::success([
            'user' => $user,
            'active_tenant' => $tenant,
            'role' => $role,
            'user_tenants' => $userTenants,
            'csrf_token' => csrf_token(),
        ], "Login successful.");
    }

    public function register(Request $request): Response
    {
        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');
        $workspace = trim((string)$request->input('workspace', ''));

        $result = AuthService::register($name, $email, $password, $workspace);

        if (!$result['success']) {
            return Response::error($result['error'] ?? 'Registration failed.', 'REGISTER_FAILED', null, 400);
        }

        // Auto login after registration
        $loginRes = AuthService::attempt($email, $password);
        $user = current_user();
        $tenant = TenantContextService::getActiveTenant();
        $role = TenantContextService::getCurrentRole();
        $userTenants = TenantContextService::getUserTenants((int)$user['id']);

        return Response::success([
            'user' => $user,
            'active_tenant' => $tenant,
            'role' => $role,
            'user_tenants' => $userTenants,
            'csrf_token' => csrf_token(),
            'requires_verification' => empty($user['email_verified_at']),
            'email_verified' => !empty($user['email_verified_at']),
            'dev_otp' => $result['otp_data']['otp'] ?? null,
            'expires_in_minutes' => $result['otp_data']['expires_in_minutes'] ?? 15,
        ], "Registration successful. Please verify your email with the 6-digit code.", 201);
    }

    public function verifyOtp(Request $request): Response
    {
        $user = current_user();
        if (!$user) {
            return Response::error("Unauthenticated.", "UNAUTHENTICATED", null, 401);
        }

        $otp = trim((string)$request->input('otp', ''));
        if (empty($otp)) {
            return Response::error("Please enter the 6-digit verification code.", "VALIDATION_FAILED", null, 422);
        }

        $result = EmailVerificationService::verifyOtp((int)$user['id'], $otp);
        if (!$result['success']) {
            return Response::error($result['error'], "VERIFICATION_FAILED", null, 400);
        }

        $freshUser = current_user();
        $tenant = TenantContextService::getActiveTenant();
        $role = TenantContextService::getCurrentRole();
        $userTenants = TenantContextService::getUserTenants((int)$freshUser['id']);

        return Response::success([
            'user' => $freshUser,
            'active_tenant' => $tenant,
            'role' => $role,
            'user_tenants' => $userTenants,
            'email_verified' => true,
        ], $result['message']);
    }

    public function resendOtp(Request $request): Response
    {
        $user = current_user();
        if (!$user) {
            return Response::error("Unauthenticated.", "UNAUTHENTICATED", null, 401);
        }

        $result = EmailVerificationService::resend((int)$user['id']);
        if (!$result['success']) {
            return Response::error($result['error'], "RESEND_FAILED", [
                'retry_after' => $result['retry_after'] ?? null
            ], 429);
        }

        return Response::success([
            'expires_in_minutes' => $result['expires_in_minutes'] ?? 15,
            'dev_otp' => $result['otp'] ?? null,
            'simulated' => $result['simulated'] ?? false,
        ], $result['message']);
    }

    public function me(Request $request): Response
    {
        $user = current_user();
        if (!$user) {
            return Response::error("Unauthenticated.", "UNAUTHENTICATED", null, 401);
        }

        $tenant = TenantContextService::getActiveTenant();
        $role = TenantContextService::getCurrentRole();
        $userTenants = TenantContextService::getUserTenants((int)$user['id']);
        
        $limits = null;
        $features = null;
        if ($tenant) {
            $limits = UsageLimitService::getTenantLimits((int)$tenant['id']);
            $features = UsageLimitService::getTenantFeatures((int)$tenant['id']);
        }

        return Response::success([
            'user' => $user,
            'active_tenant' => $tenant,
            'role' => $role,
            'user_tenants' => $userTenants,
            'limits' => $limits,
            'features' => $features,
            'is_platform_admin' => !empty($user['is_platform_admin']),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function switchWorkspace(Request $request): Response
    {
        $user = current_user();
        if (!$user) {
            return Response::error("Unauthenticated.", "UNAUTHENTICATED", null, 401);
        }

        $tenantId = (int)$request->input('tenant_id');
        $success = TenantContextService::setActiveTenant($tenantId, (int)$user['id']);

        if (!$success) {
            return Response::error("Could not switch to specified workspace.", "FORBIDDEN", null, 403);
        }

        $tenant = TenantContextService::getActiveTenant();
        $role = TenantContextService::getCurrentRole();

        return Response::success([
            'active_tenant' => $tenant,
            'role' => $role,
        ], "Switched workspace successfully.");
    }

    public function logout(Request $request): Response
    {
        Session::destroy();
        Session::start();
        return Response::success(null, "Logged out successfully.");
    }

    // ==========================================
    // DASHBOARD & ANALYTICS
    // ==========================================

    public function dashboard(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        if (!$tenantId) {
            return Response::error("No active workspace.", "NO_TENANT", null, 400);
        }

        $metrics = AnalyticsService::getDashboardMetrics($tenantId);
        $usage = UsageLimitService::getUsageOverview($tenantId);
        $recentFunnels = FunnelService::listFunnels($tenantId, null, null, 1, 5);

        return Response::success([
            'metrics' => $metrics,
            'usage' => $usage,
            'recent_funnels' => $recentFunnels['items'] ?? ($recentFunnels['data'] ?? (is_array($recentFunnels) ? $recentFunnels : [])),
        ]);
    }

    public function analytics(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $funnels = FunnelService::listFunnels($tenantId);
        $funnelList = isset($funnels['items']) ? $funnels['items'] : (isset($funnels['data']) ? $funnels['data'] : (is_array($funnels) ? $funnels : []));
        $selectedFunnelId = $request->query('funnel_id') ? (int)$request->query('funnel_id') : (!empty($funnelList) ? (int)$funnelList[0]['id'] : null);

        $analyticsData = null;
        if ($selectedFunnelId) {
            $analyticsData = AnalyticsService::getFunnelMetrics($tenantId, $selectedFunnelId);
        }

        return Response::success([
            'funnels' => $funnelList,
            'selected_funnel_id' => $selectedFunnelId,
            'analytics' => $analyticsData,
        ]);
    }

    // ==========================================
    // FUNNELS CRUD & MANAGEMENT
    // ==========================================

    public function funnels(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $search = $request->query('search');
        $status = $request->query('status');
        $page = (int)$request->query('page', 1);

        $funnels = FunnelService::listFunnels($tenantId, $search, $status, $page, 15);
        $usage = UsageLimitService::getUsageOverview($tenantId);
        $funnelList = isset($funnels['items']) ? $funnels['items'] : (isset($funnels['data']) ? $funnels['data'] : (is_array($funnels) ? $funnels : []));

        return Response::success([
            'funnels' => $funnelList,
            'pagination' => [
                'current_page' => $funnels['page'] ?? ($funnels['current_page'] ?? 1),
                'total_pages' => $funnels['total_pages'] ?? 1,
                'total_records' => $funnels['total'] ?? ($funnels['total_records'] ?? count($funnelList)),
                'per_page' => $funnels['per_page'] ?? 15,
            ],
            'usage' => $usage,
        ]);
    }

    public function getFunnel(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $funnel = FunnelService::getFunnel($tenantId, (int)$id);

        if (!$funnel) {
            return Response::error("Funnel not found.", "NOT_FOUND", null, 404);
        }

        $formFields = FormSchemaService::getFormFields($tenantId, (int)$id);
        $settings = json_decode($funnel['settings_json'] ?? '{}', true) ?: [];
        $planFeatures = UsageLimitService::getTenantFeatures($tenantId);

        return Response::success([
            'funnel' => $funnel,
            'settings' => $settings,
            'form_fields' => $formFields,
            'plan_features' => $planFeatures,
        ]);
    }

    public function createFunnel(Request $request): Response
    {
        if (!AuthorizationService::canCreateFunnel()) {
            return Response::error("Permission denied to create funnels.", "FORBIDDEN", null, 403);
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $funnel = FunnelService::createFunnel($tenantId, $request->all(), (int)$user['id']);
            return Response::success($funnel, "Funnel created successfully.", 201);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "CREATE_FUNNEL_FAILED", null, 400);
        }
    }

    public function updateFunnelSettings(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $existing = FunnelService::getFunnel($tenantId, (int)$id);
            if (!$existing) {
                return Response::error("Funnel not found.", "NOT_FOUND", null, 404);
            }

            $currentSettings = json_decode($existing['settings_json'] ?? '{}', true) ?: [];
            $input = $request->all();

            $canWhiteLabel = UsageLimitService::hasFeature($tenantId, 'white_label') || AuthorizationService::isPlatformAdmin();
            $requestedWhiteLabel = !empty($input['white_label']);
            $whiteLabel = $requestedWhiteLabel && $canWhiteLabel;

            $newSettings = array_merge($currentSettings, [
                'theme' => in_array($input['theme'] ?? '', ['light', 'dark'], true) ? $input['theme'] : ($currentSettings['theme'] ?? 'light'),
                'accent_color' => $input['accent_color'] ?? ($currentSettings['accent_color'] ?? '#5B5BD6'),
                'company_name' => trim((string)($input['company_name'] ?? ($currentSettings['company_name'] ?? ''))),
                'logo_url' => trim((string)($input['logo_url'] ?? ($currentSettings['logo_url'] ?? ''))),
                'logo_position' => in_array($input['logo_position'] ?? '', ['left', 'center', 'right'], true) ? $input['logo_position'] : ($currentSettings['logo_position'] ?? 'left'),
                'logo_height' => max(24, min(90, (int)($input['logo_height'] ?? ($currentSettings['logo_height'] ?? 36)))),
                'trust_badge_text' => trim((string)($input['trust_badge_text'] ?? ($currentSettings['trust_badge_text'] ?? ''))),
                'footer_text' => trim((string)($input['footer_text'] ?? ($currentSettings['footer_text'] ?? ''))),
                'privacy_policy_url' => trim((string)($input['privacy_policy_url'] ?? ($currentSettings['privacy_policy_url'] ?? ''))),
                'terms_url' => trim((string)($input['terms_url'] ?? ($currentSettings['terms_url'] ?? ''))),
                'white_label' => $whiteLabel,
                'card_radius' => in_array($input['card_radius'] ?? '', ['rounded-none', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-3xl'], true) ? $input['card_radius'] : ($currentSettings['card_radius'] ?? 'rounded-2xl'),
                'show_progress_bar' => isset($input['show_progress_bar']) ? (bool)$input['show_progress_bar'] : true,
                // Marketer Customization Options
                'button_radius' => in_array($input['button_radius'] ?? '', ['rounded-md', 'rounded-xl', 'rounded-2xl', 'rounded-full'], true) ? $input['button_radius'] : ($currentSettings['button_radius'] ?? 'rounded-xl'),
                'button_style' => in_array($input['button_style'] ?? '', ['solid', 'glow', 'outline'], true) ? $input['button_style'] : ($currentSettings['button_style'] ?? 'solid'),
                'cta_button_text' => trim((string)($input['cta_button_text'] ?? ($currentSettings['cta_button_text'] ?? ''))),
                'font_family' => in_array($input['font_family'] ?? '', ['sans', 'serif', 'geometric', 'system'], true) ? $input['font_family'] : ($currentSettings['font_family'] ?? 'sans'),
                'background_style' => in_array($input['background_style'] ?? '', ['default', 'dots', 'warm', 'dark'], true) ? $input['background_style'] : ($currentSettings['background_style'] ?? 'default'),
                'card_elevation' => in_array($input['card_elevation'] ?? '', ['flat', 'soft', 'floating'], true) ? $input['card_elevation'] : ($currentSettings['card_elevation'] ?? 'soft'),
                'social_proof_badge' => trim((string)($input['social_proof_badge'] ?? ($currentSettings['social_proof_badge'] ?? ''))),
                'fb_pixel_id' => trim((string)($input['fb_pixel_id'] ?? ($currentSettings['fb_pixel_id'] ?? ''))),
                'google_tag_id' => trim((string)($input['google_tag_id'] ?? ($currentSettings['google_tag_id'] ?? ''))),
                'tiktok_pixel_id' => trim((string)($input['tiktok_pixel_id'] ?? ($currentSettings['tiktok_pixel_id'] ?? ''))),
                'meta_title' => trim((string)($input['meta_title'] ?? ($currentSettings['meta_title'] ?? ''))),
                'meta_description' => trim((string)($input['meta_description'] ?? ($currentSettings['meta_description'] ?? ''))),
            ]);

            FunnelService::updateFunnel($tenantId, (int)$id, [
                'name' => $input['name'] ?? $existing['name'],
                'slug' => $input['slug'] ?? $existing['slug'],
                'description' => $input['description'] ?? $existing['description'],
                'settings' => $newSettings,
                'settings_json' => json_encode($newSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ], (int)$user['id']);

            return Response::success($newSettings, "Funnel settings updated.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UPDATE_SETTINGS_FAILED", null, 400);
        }
    }

    public function uploadFunnelLogo(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $existing = FunnelService::getFunnel($tenantId, (int)$id);
            if (!$existing) {
                return Response::error("Funnel not found.", "NOT_FOUND", null, 404);
            }

            $uploadedFile = $request->file('logo_file') ?? $request->file('file') ?? $request->file('logo');
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return Response::error("Please select a valid image file to upload.", "NO_FILE", null, 400);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
            $mimeType = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                    if (\PHP_VERSION_ID < 80100) {
                        finfo_close($finfo);
                    }
                }
            }
            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($uploadedFile['tmp_name']);
            }

            if (!in_array($mimeType, $allowedTypes, true)) {
                return Response::error("Invalid file format. Allowed types: PNG, JPG, WebP, SVG, GIF.", "INVALID_FORMAT", null, 400);
            }

            if ($uploadedFile['size'] > 5 * 1024 * 1024) {
                return Response::error("Logo file size must be less than 5MB.", "FILE_TOO_LARGE", null, 400);
            }

            $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) ?: 'png';
            $cleanExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
            $fileName = 'logo_' . (int)$id . '_' . bin2hex(random_bytes(6)) . '.' . $cleanExt;
            $targetDir = dirname(__DIR__, 2) . '/public/uploads/logos';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $fileName;
            $saved = is_uploaded_file($uploadedFile['tmp_name'])
                ? @move_uploaded_file($uploadedFile['tmp_name'], $targetPath)
                : @copy($uploadedFile['tmp_name'], $targetPath);

            if (!$saved) {
                return Response::error("Failed to save uploaded logo file.", "UPLOAD_SAVE_FAILED", null, 500);
            }

            $logoUrl = url('/uploads/logos/' . $fileName);

            $currentSettings = json_decode($existing['settings_json'] ?? '{}', true) ?: [];
            $currentSettings['logo_url'] = $logoUrl;

            FunnelService::updateFunnel($tenantId, (int)$id, [
                'settings' => $currentSettings,
                'settings_json' => json_encode($currentSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ], (int)$user['id']);

            return Response::success([
                'logo_url' => $logoUrl,
                'file_name' => $fileName,
            ], "Logo uploaded and saved successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UPLOAD_FAILED", null, 400);
        }
    }

    public function uploadFunnelMedia(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        try {
            $existing = FunnelService::getFunnel($tenantId, (int)$id);
            if (!$existing) {
                return Response::error("Funnel not found.", "NOT_FOUND", null, 404);
            }

            $uploadedFile = $request->file('media_file') ?? $request->file('file') ?? $request->file('image');
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return Response::error("Please select a valid image file to upload.", "NO_FILE", null, 400);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
            $mimeType = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                    if (\PHP_VERSION_ID < 80100) {
                        finfo_close($finfo);
                    }
                }
            }
            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($uploadedFile['tmp_name']);
            }

            if (!in_array($mimeType, $allowedTypes, true)) {
                return Response::error("Invalid file format. Allowed types: PNG, JPG, WebP, SVG, GIF.", "INVALID_FORMAT", null, 400);
            }

            if ($uploadedFile['size'] > 10 * 1024 * 1024) {
                return Response::error("Image file size must be less than 10MB.", "FILE_TOO_LARGE", null, 400);
            }

            $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) ?: 'png';
            $cleanExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
            $fileName = 'media_' . (int)$id . '_' . bin2hex(random_bytes(6)) . '.' . $cleanExt;
            $targetDir = dirname(__DIR__, 2) . '/public/uploads/media';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $fileName;
            $saved = is_uploaded_file($uploadedFile['tmp_name'])
                ? @move_uploaded_file($uploadedFile['tmp_name'], $targetPath)
                : @copy($uploadedFile['tmp_name'], $targetPath);

            if (!$saved) {
                return Response::error("Failed to save uploaded image file.", "UPLOAD_SAVE_FAILED", null, 500);
            }

            $mediaUrl = url('/uploads/media/' . $fileName);
            $fullMediaUrl = full_url('/uploads/media/' . $fileName);

            return Response::success([
                'url' => $mediaUrl,
                'full_url' => $fullMediaUrl,
                'file_name' => $fileName,
            ], "Image uploaded successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UPLOAD_FAILED", null, 400);
        }
    }

    public function uploadGeneralMedia(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        try {
            $uploadedFile = $request->file('media_file') ?? $request->file('file') ?? $request->file('image');
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return Response::error("Please select a valid image file to upload.", "NO_FILE", null, 400);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
            $mimeType = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                    if (\PHP_VERSION_ID < 80100) {
                        finfo_close($finfo);
                    }
                }
            }
            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($uploadedFile['tmp_name']);
            }

            if (!in_array($mimeType, $allowedTypes, true)) {
                return Response::error("Invalid file format. Allowed types: PNG, JPG, WebP, SVG, GIF.", "INVALID_FORMAT", null, 400);
            }

            if ($uploadedFile['size'] > 10 * 1024 * 1024) {
                return Response::error("Image file size must be less than 10MB.", "FILE_TOO_LARGE", null, 400);
            }

            $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) ?: 'png';
            $cleanExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
            $fileName = 'media_t' . (int)$tenantId . '_' . bin2hex(random_bytes(6)) . '.' . $cleanExt;
            $targetDir = dirname(__DIR__, 2) . '/public/uploads/media';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $fileName;
            $saved = is_uploaded_file($uploadedFile['tmp_name'])
                ? @move_uploaded_file($uploadedFile['tmp_name'], $targetPath)
                : @copy($uploadedFile['tmp_name'], $targetPath);

            if (!$saved) {
                return Response::error("Failed to save uploaded image file.", "UPLOAD_SAVE_FAILED", null, 500);
            }

            $mediaUrl = url('/uploads/media/' . $fileName);
            $fullMediaUrl = full_url('/uploads/media/' . $fileName);

            return Response::success([
                'url' => $mediaUrl,
                'full_url' => $fullMediaUrl,
                'file_name' => $fileName,
            ], "Image uploaded successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UPLOAD_FAILED", null, 400);
        }
    }

    public function duplicateFunnel(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $clone = FunnelService::duplicateFunnel($tenantId, (int)$id, (int)$user['id']);
            return Response::success($clone, "Funnel duplicated successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "DUPLICATE_FAILED", null, 400);
        }
    }

    public function publishFunnel(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $published = FunnelPublishService::publish($tenantId, (int)$id, (int)$user['id']);
            return Response::success($published, "Funnel published successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "PUBLISH_FAILED", null, 400);
        }
    }

    public function unpublishFunnel(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            FunnelPublishService::unpublish($tenantId, (int)$id, (int)$user['id']);
            return Response::success(null, "Funnel unpublished successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UNPUBLISH_FAILED", null, 400);
        }
    }

    public function deleteFunnel(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            FunnelService::deleteFunnel($tenantId, (int)$id, (int)$user['id']);
            return Response::success(null, "Funnel deleted successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "DELETE_FAILED", null, 400);
        }
    }

    // ==========================================
    // SUBMISSIONS & LEADS CRM
    // ==========================================

    public function submissions(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        $filters = [
            'tab' => $request->query('tab', 'qualified'),
            'funnel_id' => $request->query('funnel_id'),
            'status' => $request->query('status'),
            'lead_status' => $request->query('lead_status'),
            'date_range' => $request->query('date_range'),
            'search' => trim((string)$request->query('search', '')),
        ];
        $page = (int)$request->query('page', 1);

        $submissions = SubmissionService::listSubmissions($tenantId, $filters, $page, 15);
        $subList = isset($submissions['items']) ? $submissions['items'] : (isset($submissions['data']) ? $submissions['data'] : (is_array($submissions) ? $submissions : []));
        $funnels = FunnelService::listFunnels($tenantId);
        $funnelList = isset($funnels['items']) ? $funnels['items'] : (isset($funnels['data']) ? $funnels['data'] : (is_array($funnels) ? $funnels : []));
        $leadStages = LeadStageService::getStagesForTenant($tenantId);

        return Response::success([
            'submissions' => $subList,
            'counts' => $submissions['counts'] ?? ['all' => 0, 'qualified' => 0, 'abandoned' => 0],
            'pagination' => [
                'current_page' => $submissions['page'] ?? ($submissions['current_page'] ?? 1),
                'total_pages' => $submissions['total_pages'] ?? 1,
                'total_records' => $submissions['total'] ?? ($submissions['total_records'] ?? count($subList)),
                'per_page' => $submissions['per_page'] ?? 15,
            ],
            'funnels' => $funnelList,
            'lead_stages' => $leadStages,
            'filters' => $filters,
        ]);
    }

    public function submissionDetail(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $detail = SubmissionService::getSubmissionDetail($tenantId, (int)$id);

        if (!$detail) {
            return Response::error("Submission record not found.", "NOT_FOUND", null, 404);
        }

        $leadStages = LeadStageService::getStagesForTenant($tenantId);

        return Response::success([
            'submission' => $detail,
            'lead_stages' => $leadStages,
        ]);
    }

    public function updateSubmissionStatus(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $leadStatus = (string)$request->input('lead_status');

        $updated = SubmissionService::updateLeadStatus($tenantId, (int)$id, $leadStatus);
        if (!$updated) {
            return Response::error("Invalid lead status provided.", "INVALID_STATUS", null, 400);
        }

        return Response::success(['lead_status' => $leadStatus], "Status updated.");
    }

    public function bulkSubmissionsAction(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $action = (string)$request->input('action');
        $sessionIds = (array)$request->input('session_ids', []);

        if (empty($sessionIds)) {
            return Response::error("No leads were selected.", "NO_SELECTION", null, 400);
        }

        if (str_starts_with($action, 'mark_')) {
            $newStatus = substr($action, 5);
            $count = SubmissionService::bulkUpdateLeadStatus($tenantId, $sessionIds, $newStatus);
            return Response::success(['count' => $count], "{$count} leads updated.");
        } elseif ($action === 'delete') {
            $count = SubmissionService::bulkDelete($tenantId, $sessionIds);
            return Response::success(['count' => $count], "{$count} leads deleted.");
        }

        return Response::error("Unknown bulk action.", "INVALID_ACTION", null, 400);
    }

    public function listLeadStages(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $stages = LeadStageService::getStagesForTenant($tenantId);
        return Response::success($stages);
    }

    public function createLeadStage(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $name = trim((string)$request->input('name'));
        $color = (string)$request->input('color', '#6366F1');

        if (empty($name)) {
            return Response::error("Stage name is required.", "VALIDATION_FAILED", null, 400);
        }

        $stage = LeadStageService::createStage($tenantId, $name, $color);
        return Response::success($stage, "Stage created successfully.", 201);
    }

    public function deleteLeadStage(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        LeadStageService::deleteStage($tenantId, (int)$id);
        return Response::success(null, "Stage deleted.");
    }

    // ==========================================
    // TEAM & MEMBERSHIP
    // ==========================================

    public function team(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $members = Database::query(
            "SELECT tm.id as membership_id, tm.role, tm.created_at, u.id as user_id, u.name, u.email, u.status, u.last_login_at 
             FROM tenant_memberships tm
             JOIN users u ON tm.user_id = u.id
             WHERE tm.tenant_id = ?
             ORDER BY tm.id ASC",
            [$tenantId]
        );

        $currentUserRole = TenantContextService::getCurrentRole();
        $usage = UsageLimitService::getUsageOverview($tenantId);

        return Response::success([
            'members' => $members,
            'current_user_role' => $currentUserRole,
            'usage' => $usage,
        ]);
    }

    public function inviteTeamMember(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $email = trim(strtolower((string)$request->input('email')));
        $name = trim((string)$request->input('name', ''));
        $role = (string)$request->input('role', 'editor');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error("Invalid email address.", "VALIDATION_FAILED", null, 400);
        }

        // Check if user exists
        $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        if (!$user) {
            $defaultPassword = password_hash('Welcome123!', PASSWORD_DEFAULT);
            Database::execute(
                "INSERT INTO users (name, email, password_hash, status) VALUES (?, ?, ?, 'active')",
                [$name ?: explode('@', $email)[0], $email, $defaultPassword]
            );
            $userId = (int)Database::lastInsertId();
        } else {
            $userId = (int)$user['id'];
        }

        // Check existing membership
        $existing = Database::fetchOne("SELECT id FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?", [$tenantId, $userId]);
        if ($existing) {
            return Response::error("User is already a member of this workspace.", "ALREADY_MEMBER", null, 400);
        }

        Database::execute(
            "INSERT INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, ?)",
            [$tenantId, $userId, $role]
        );

        return Response::success(null, "Team member added successfully.", 201);
    }

    public function updateTeamMember(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $role = (string)$request->input('role');

        if (!in_array($role, ['owner', 'admin', 'editor', 'analyst'], true)) {
            return Response::error("Invalid role.", "INVALID_ROLE", null, 400);
        }

        Database::execute("UPDATE tenant_memberships SET role = ? WHERE id = ? AND tenant_id = ?", [$role, (int)$id, $tenantId]);
        return Response::success(null, "Role updated.");
    }

    public function removeTeamMember(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        Database::execute("DELETE FROM tenant_memberships WHERE id = ? AND tenant_id = ?", [(int)$id, $tenantId]);
        return Response::success(null, "Member removed.");
    }

    // ==========================================
    // WEBHOOKS
    // ==========================================

    public function webhooks(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $endpoints = Database::query(
            "SELECT * FROM webhook_endpoints WHERE tenant_id = ? ORDER BY id DESC",
            [$tenantId]
        );

        $recentDeliveries = Database::query(
            "SELECT wd.*, we.target_url 
             FROM webhook_deliveries wd
             JOIN webhook_endpoints we ON wd.webhook_endpoint_id = we.id
             WHERE we.tenant_id = ?
             ORDER BY wd.id DESC LIMIT 20",
            [$tenantId]
        );

        $funnels = FunnelService::listFunnels($tenantId);
        $funnelList = isset($funnels['items']) ? $funnels['items'] : (isset($funnels['data']) ? $funnels['data'] : (is_array($funnels) ? $funnels : []));

        return Response::success([
            'endpoints' => $endpoints,
            'recent_deliveries' => $recentDeliveries,
            'funnels' => $funnelList,
        ]);
    }

    public function createWebhook(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $targetUrl = trim((string)$request->input('target_url'));
        $eventType = (string)$request->input('event_type', 'all');
        $funnelId = $request->input('funnel_id') ? (int)$request->input('funnel_id') : null;
        $secret = trim((string)$request->input('secret', '')) ?: bin2hex(random_bytes(16));

        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return Response::error("Valid target URL is required.", "INVALID_URL", null, 400);
        }

        Database::execute(
            "INSERT INTO webhook_endpoints (tenant_id, target_url, event_type, funnel_id, secret, is_active) VALUES (?, ?, ?, ?, ?, 1)",
            [$tenantId, $targetUrl, $eventType, $funnelId, $secret]
        );

        return Response::success(['id' => Database::lastInsertId()], "Webhook created.", 201);
    }

    public function deleteWebhook(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        Database::execute("DELETE FROM webhook_endpoints WHERE id = ? AND tenant_id = ?", [(int)$id, $tenantId]);
        return Response::success(null, "Webhook deleted.");
    }

    // ==========================================
    // BILLING & SUBSCRIPTION
    // ==========================================

    public function billing(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $tenant = TenantContextService::getActiveTenant();

        $sub = SubscriptionService::getActiveSubscription($tenantId);
        $billing = SubscriptionService::getBillingSummary($tenantId);
        $usage = UsageLimitService::getUsageOverview($tenantId);

        $currentPlanId = $sub['plan_id'] ?? 0;
        $plans = Database::query(
            "SELECT * FROM plans WHERE is_active = 1 AND (is_visible = 1 OR id = ?) ORDER BY price ASC, id ASC",
            [$currentPlanId]
        );
        $featureCatalog = UsageLimitService::getFeatureCatalog();
        $gateways = PlatformSettingService::getPaymentGateways();
        $invoices = SubscriptionService::getInvoices($tenantId);

        return Response::success([
            'tenant' => $tenant,
            'subscription' => $sub,
            'billing' => $billing,
            'usage' => $usage,
            'plans' => $plans,
            'feature_catalog' => $featureCatalog,
            'gateways' => $gateways,
            'invoices' => $invoices,
            'can_manage_billing' => in_array(TenantContextService::getCurrentRole(), ['owner', 'admin'], true) || AuthorizationService::isPlatformAdmin(),
        ]);
    }

    public function upgradePlan(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $planId = (int)$request->input('plan_id');

        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ? AND is_active = 1", [$planId]);
        if (!$plan) {
            return Response::error("Selected plan not found.", "NOT_FOUND", null, 404);
        }

        // Direct upgrade assignment
        $assigned = SubscriptionService::assignPlan($tenantId, $planId, 30, (int)$user['id'], 'Online Self-Service');
        if (!$assigned) {
            return Response::error("Failed to upgrade subscription.", "UPGRADE_FAILED", null, 500);
        }

        return Response::success(['plan_name' => $plan['name']], "Successfully upgraded to {$plan['name']}!");
    }

    // ==========================================
    // SETTINGS & PROFILE
    // ==========================================

    public function updateWorkspace(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $name = trim((string)$request->input('name'));

        if (empty($name)) {
            return Response::error("Workspace name cannot be empty.", "VALIDATION_FAILED", null, 400);
        }

        Database::execute("UPDATE tenants SET name = ? WHERE id = ?", [$name, $tenantId]);
        return Response::success(['name' => $name], "Workspace updated.");
    }

    public function updateProfile(Request $request): Response
    {
        $user = current_user();
        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error("Valid name and email are required.", "VALIDATION_FAILED", null, 400);
        }

        // Check email uniqueness
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, (int)$user['id']]);
        if ($existing) {
            return Response::error("Email already in use.", "EMAIL_TAKEN", null, 400);
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                return Response::error("Password must be at least 6 characters.", "PASSWORD_TOO_SHORT", null, 400);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            Database::execute("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?", [$name, $email, $hash, (int)$user['id']]);
        } else {
            Database::execute("UPDATE users SET name = ?, email = ? WHERE id = ?", [$name, $email, (int)$user['id']]);
        }

        $updatedUser = Database::fetchOne("SELECT id, name, email, is_platform_admin, status FROM users WHERE id = ?", [(int)$user['id']]);
        Session::set('user', $updatedUser);

        return Response::success($updatedUser, "Profile updated successfully.");
    }

    // ==========================================
    // PUBLIC QUIZ MANIFEST (FOR VISITOR RUNNER)
    // ==========================================

    public function quizManifest(Request $request, string $slug): Response
    {
        $funnel = Database::fetchOne(
            "SELECT f.id, f.tenant_id, f.name, f.slug, f.description, f.status, f.funnel_mode, f.settings_json, t.name as tenant_name 
             FROM funnels f 
             JOIN tenants t ON f.tenant_id = t.id 
             WHERE f.slug = ? AND f.status = 'published'",
            [$slug]
        );

        if (!$funnel) {
            return Response::error("Quiz not found or not published.", "NOT_FOUND", null, 404);
        }

        $settings = json_decode($funnel['settings_json'] ?? '{}', true) ?: [];

        return Response::success([
            'funnel_id' => (int)$funnel['id'],
            'name' => $funnel['name'],
            'slug' => $funnel['slug'],
            'description' => $funnel['description'],
            'funnel_mode' => $funnel['funnel_mode'] ?? 'quiz',
            'tenant_name' => $funnel['tenant_name'],
            'settings' => [
                'theme' => $settings['theme'] ?? 'light',
                'accent_color' => $settings['accent_color'] ?? '#5B5BD6',
                'company_name' => $settings['company_name'] ?? $funnel['tenant_name'],
                'logo_url' => $settings['logo_url'] ?? '',
                'logo_position' => $settings['logo_position'] ?? 'left',
                'logo_height' => max(24, min(90, (int)($settings['logo_height'] ?? 36))),
                'white_label' => !empty($settings['white_label']),
                'trust_badge_text' => !empty($settings['trust_badge_text']) ? $settings['trust_badge_text'] : '256-Bit SSL Encrypted • 100% Privacy Guaranteed',
                'footer_text' => !empty($settings['footer_text']) ? $settings['footer_text'] : ('© ' . date('Y') . ' ' . ($settings['company_name'] ?? $funnel['tenant_name']) . '. All rights reserved.'),
                'privacy_policy_url' => $settings['privacy_policy_url'] ?? '',
                'terms_url' => $settings['terms_url'] ?? '',
                'show_progress_bar' => !isset($settings['show_progress_bar']) || !empty($settings['show_progress_bar']),
                'card_radius' => $settings['card_radius'] ?? 'rounded-2xl',
                'button_radius' => $settings['button_radius'] ?? 'rounded-xl',
                'button_style' => $settings['button_style'] ?? 'solid',
                'cta_button_text' => $settings['cta_button_text'] ?? '',
                'font_family' => $settings['font_family'] ?? 'sans',
                'background_style' => $settings['background_style'] ?? 'default',
                'card_elevation' => $settings['card_elevation'] ?? 'soft',
                'social_proof_badge' => $settings['social_proof_badge'] ?? '',
                'fb_pixel_id' => $settings['fb_pixel_id'] ?? '',
                'google_tag_id' => $settings['google_tag_id'] ?? '',
                'tiktok_pixel_id' => $settings['tiktok_pixel_id'] ?? '',
                'meta_title' => $settings['meta_title'] ?? '',
                'meta_description' => $settings['meta_description'] ?? '',
            ]
        ]);
    }

    // ==========================================
    // SUPER ADMIN PLATFORM
    // ==========================================

    public function superadminDashboard(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $totalTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants WHERE status != 'deleted'");
        $activeTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants WHERE status = 'active'");
        $suspendedTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants WHERE status = 'suspended'");
        $terminationDueCount = count(SubscriptionService::getTerminationDueWorkspaces());

        $totalUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users");
        $activeUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'");

        $totalFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE status != 'archived'");
        $publishedFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE status = 'published'");
        $totalSubmissions = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions");
        if ($totalSubmissions === 0) {
            $totalSubmissions = (int) Database::fetchColumn("SELECT COUNT(*) FROM form_submissions");
        }

        // Plan distribution
        $planStats = Database::query(
            "SELECT p.id, p.name, p.slug, p.price, COUNT(ts.id) as subscriber_count
             FROM plans p
             LEFT JOIN tenant_subscriptions ts ON ts.plan_id = p.id AND ts.status IN ('active', 'trial')
             GROUP BY p.id, p.name, p.slug, p.price
             ORDER BY p.price ASC, p.id ASC"
        );

        // Recent Workspaces
        $recentTenants = Database::query(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM funnels f WHERE f.tenant_id = t.id AND f.status != 'archived') as funnels_count,
                    (SELECT u.name FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_name,
                    p.name as plan_name
             FROM tenants t
             LEFT JOIN tenant_subscriptions ts ON ts.tenant_id = t.id AND ts.status IN ('active', 'trial')
             LEFT JOIN plans p ON ts.plan_id = p.id
             WHERE t.status != 'deleted'
             ORDER BY t.created_at DESC
             LIMIT 6"
        );

        $recentLogs = Database::query(
            "SELECT al.*, u.name as user_name, u.email as user_email, t.name as tenant_name 
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             LEFT JOIN tenants t ON al.tenant_id = t.id
             ORDER BY al.id DESC LIMIT 10"
        );

        // Gateways count
        $gateways = PlatformSettingService::getPaymentGateways();
        $activeGatewaysCount = 0;
        foreach ($gateways as $gw) {
            if (!empty($gw['enabled'])) {
                $activeGatewaysCount++;
            }
        }

        return Response::success([
            'stats' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'termination_due_count' => $terminationDueCount,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_funnels' => $totalFunnels,
                'published_funnels' => $publishedFunnels,
                'total_submissions' => $totalSubmissions,
                'active_gateways_count' => $activeGatewaysCount,
            ],
            'plan_stats' => $planStats,
            'recent_tenants' => $recentTenants,
            'recent_logs' => $recentLogs,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Web Server',
                'db_driver' => 'MySQL / MariaDB via PDO',
                'max_upload' => ini_get('upload_max_filesize'),
                'timezone' => date_default_timezone_get(),
            ]
        ]);
    }

    public function superadminCustomers(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $customers = Database::query(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM funnels f WHERE f.tenant_id = t.id AND f.status != 'archived') as funnels_count,
                    (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.tenant_id = t.id) as sessions_count,
                    (SELECT u.id FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_id,
                    (SELECT u.name FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_name,
                    (SELECT u.email FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_email,
                    p.id as current_plan_id,
                    p.name as plan_name,
                    p.slug as plan_slug,
                    p.price as plan_price,
                    ts.id as subscription_id,
                    ts.status as subscription_status,
                    ts.billing_interval,
                    ts.current_period_start,
                    ts.current_period_end,
                    ts.next_billing_date,
                    ts.ends_at as subscription_ends_at,
                    ts.auto_renew,
                    ts.amount as subscription_amount,
                    ts.currency as subscription_currency
             FROM tenants t
             LEFT JOIN tenant_subscriptions ts ON ts.tenant_id = t.id AND ts.id = (SELECT MAX(id) FROM tenant_subscriptions WHERE tenant_id = t.id)
             LEFT JOIN plans p ON ts.plan_id = p.id
             WHERE t.status != 'deleted'
             ORDER BY t.created_at DESC"
        );

        $terminationDueList = SubscriptionService::getTerminationDueWorkspaces();
        $invoices = SubscriptionService::getAllInvoices(50);

        $users = Database::query(
            "SELECT u.*, 
                    (SELECT COUNT(*) FROM tenant_memberships tm WHERE tm.user_id = u.id) as workspace_count,
                    (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM tenant_memberships tm JOIN tenants t ON tm.tenant_id = t.id WHERE tm.user_id = u.id) as workspace_names
             FROM users u
             ORDER BY u.created_at DESC
             LIMIT 100"
        );

        $plans = Database::query("SELECT id, name, slug, price, billing_interval FROM plans WHERE is_active = 1 ORDER BY price ASC, id ASC");

        return Response::success([
            'customers' => $customers,
            'termination_due_list' => $terminationDueList,
            'invoices' => $invoices,
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    public function superadminToggleTenant(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [(int)$id]);
        if (!$tenant) {
            return Response::error("Tenant workspace not found.", "NOT_FOUND", null, 404);
        }

        $newStatus = $tenant['status'] === 'active' ? 'suspended' : 'active';
        Database::execute("UPDATE tenants SET status = ? WHERE id = ?", [$newStatus, $tenant['id']]);

        $currentUser = current_user();
        AuditLogService::log($tenant['id'], (int)($currentUser['id'] ?? 0), 'toggle_tenant_status', 'tenant', $tenant['id'], [
            'previous_status' => $tenant['status'],
            'new_status' => $newStatus
        ]);

        return Response::success([
            'tenant_id' => $tenant['id'],
            'new_status' => $newStatus,
        ], "Workspace '{$tenant['name']}' has been " . ($newStatus === 'active' ? 'reactivated.' : 'suspended.'));
    }

    public function superadminAssignPlan(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $planId = (int)$request->input('plan_id');
        $status = (string)$request->input('status', 'active');

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);

        if (!$tenant || !$plan) {
            return Response::error("Invalid tenant workspace or plan selected.", "VALIDATION_FAILED", null, 400);
        }

        // Deactivate previous active subscriptions
        Database::execute("UPDATE tenant_subscriptions SET status = 'cancelled' WHERE tenant_id = ? AND status IN ('active', 'trial')", [$tenantId]);

        $currentUser = current_user();
        Database::execute(
            "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, starts_at, ends_at, provider, provider_subscription_id) 
             VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 'manual_superadmin', ?)",
            [$tenantId, $planId, $status, 'assigned_by_admin_' . ($currentUser['id'] ?? 1)]
        );

        AuditLogService::log($tenantId, (int)($currentUser['id'] ?? 0), 'assign_plan', 'tenant', $tenantId, [
            'plan_id' => $planId,
            'plan_name' => $plan['name'],
            'status' => $status
        ]);

        return Response::success([
            'tenant_id' => $tenantId,
            'plan_name' => $plan['name'],
            'status' => $status,
        ], "Assigned plan '{$plan['name']}' to workspace '{$tenant['name']}'.");
    }

    public function superadminBillingCycle(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);

        try {
            SubscriptionService::modifyBillingCycle($tenantId, [
                'plan_id' => (int)$request->input('plan_id'),
                'billing_interval' => (string)$request->input('billing_interval', 'monthly'),
                'current_period_start' => (string)$request->input('current_period_start'),
                'current_period_end' => (string)$request->input('current_period_end'),
                'status' => (string)$request->input('status', 'active'),
                'auto_renew' => !empty($request->input('auto_renew')),
                'amount' => (float)$request->input('amount', 0),
                'currency' => (string)$request->input('currency', 'INR'),
            ], $adminUserId);

            return Response::success(null, "Billing cycle and subscription terms successfully updated.");
        } catch (\Throwable $e) {
            return Response::error("Failed to update billing cycle: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminRecordPayment(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);

        try {
            $invoice = SubscriptionService::recordManualPayment($tenantId, [
                'plan_id' => (int)$request->input('plan_id'),
                'amount' => (float)$request->input('amount', 0),
                'currency' => (string)$request->input('currency', 'INR'),
                'billing_interval' => (string)$request->input('billing_interval', 'monthly'),
                'payment_method' => (string)$request->input('payment_method', 'bank_transfer'),
                'transaction_reference' => (string)$request->input('transaction_reference', ''),
                'notes' => (string)$request->input('notes', ''),
            ], $adminUserId);

            return Response::success($invoice, "Payment recorded! Invoice #{$invoice['invoice_number']} generated and billing cycle extended.");
        } catch (\Throwable $e) {
            return Response::error("Failed to record payment: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminSuspendTenant(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);
        $reason = trim((string)$request->input('reason', 'Overdue subscription payment'));
        $notes = trim((string)$request->input('notes', ''));

        try {
            SubscriptionService::suspendWorkspace($tenantId, $reason, $notes, $adminUserId);
            return Response::success(null, "Workspace suspended. All customer funnels and lead data remain 100% securely preserved.");
        } catch (\Throwable $e) {
            return Response::error("Failed to suspend workspace: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminUnsuspendTenant(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);
        $extendUntil = trim((string)$request->input('extend_until', ''));

        try {
            SubscriptionService::unsuspendWorkspace($tenantId, !empty($extendUntil) ? $extendUntil : null, $adminUserId);
            return Response::success(null, "Workspace successfully reactivated! Normal service resumed.");
        } catch (\Throwable $e) {
            return Response::error("Failed to unsuspend workspace: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminMarkTerminationDue(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);
        $reason = trim((string)$request->input('reason', 'Account marked for termination review'));

        try {
            SubscriptionService::markTerminationDue($tenantId, $reason, $adminUserId);
            return Response::success(null, "Workspace added to Termination Due review queue.");
        } catch (\Throwable $e) {
            return Response::error("Error: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminDismissTerminationDue(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);

        try {
            SubscriptionService::dismissTerminationDue($tenantId, $adminUserId);
            return Response::success(null, "Workspace dismissed from Termination Due queue.");
        } catch (\Throwable $e) {
            return Response::error("Error: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminTerminateTenant(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $tenantId = (int)$id;
        $adminUserId = (int)(current_user()['id'] ?? 1);
        $confirmSlug = trim((string)$request->input('confirm_slug', ''));
        $reason = trim((string)$request->input('reason', 'Administrative manual contract termination'));

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            return Response::error("Workspace not found.", "NOT_FOUND", null, 404);
        }

        if (strtolower($confirmSlug) !== strtolower($tenant['slug'])) {
            return Response::error("Confirmation failed: You must type '{$tenant['slug']}' to confirm manual termination.", "VALIDATION_FAILED", null, 400);
        }

        try {
            SubscriptionService::terminateWorkspace($tenantId, $reason, $adminUserId);
            return Response::success(null, "Workspace '{$tenant['name']}' has been manually terminated. Historical data is archived.");
        } catch (\Throwable $e) {
            return Response::error("Failed to terminate workspace: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminToggleUserStatus(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $userId = (int)$id;
        $currentUser = current_user();

        if ($userId === (int)($currentUser['id'] ?? 0)) {
            return Response::error("You cannot suspend your own administrative account.", "ACTION_NOT_ALLOWED", null, 400);
        }

        $targetUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$targetUser) {
            return Response::error("Target user not found.", "NOT_FOUND", null, 404);
        }

        $newStatus = ($targetUser['status'] === 'active') ? 'suspended' : 'active';
        Database::execute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);

        AuditLogService::log(null, (int)$currentUser['id'], 'toggle_user_status', 'user', $userId, [
            'user_email' => $targetUser['email'],
            'new_status' => $newStatus
        ]);

        return Response::success([
            'user_id' => $userId,
            'new_status' => $newStatus,
        ], "User '{$targetUser['name']}' ({$targetUser['email']}) is now " . ($newStatus === 'active' ? 'active.' : 'suspended.'));
    }

    public function superadminPlans(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $plans = Database::query(
            "SELECT p.*, 
                    (SELECT COUNT(*) FROM tenant_subscriptions ts WHERE ts.plan_id = p.id AND ts.status IN ('active', 'trial')) as active_subscribers_count
             FROM plans p
             ORDER BY p.price ASC, p.id ASC"
        );
        $featureCatalog = UsageLimitService::getFeatureCatalog();

        return Response::success([
            'plans' => $plans,
            'feature_catalog' => $featureCatalog,
        ]);
    }

    public function superadminCreatePlan(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $name = trim((string)$request->input('name', ''));
        $slug = trim((string)$request->input('slug', ''));
        $price = (float)$request->input('price', 0);
        $interval = (string)$request->input('billing_interval', 'monthly');
        $description = trim((string)$request->input('description', ''));

        $maxFunnels = (int)$request->input('max_funnels', 1);
        $maxSubmissions = (int)$request->input('max_submissions_per_month', 100);
        $maxTeamMembers = (int)$request->input('max_team_members', 1);

        if (empty($name)) {
            return Response::error("Plan name is required.", "VALIDATION_FAILED", null, 400);
        }

        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
        }

        $existing = Database::fetchOne("SELECT id FROM plans WHERE slug = ?", [$slug]);
        if ($existing) {
            return Response::error("A plan with slug '{$slug}' already exists.", "SLUG_TAKEN", null, 400);
        }

        $limits = [
            'max_funnels' => $maxFunnels,
            'max_submissions_per_month' => $maxSubmissions,
            'max_team_members' => $maxTeamMembers,
        ];

        // Features catalog flags
        $catalog = UsageLimitService::getFeatureCatalog();
        $features = [];
        $inputFeatures = $request->input('features');
        if (is_array($inputFeatures)) {
            foreach ($catalog as $key => $meta) {
                $features[$key] = !empty($inputFeatures[$key]);
            }
        } else {
            foreach ($catalog as $key => $meta) {
                $features[$key] = !empty($request->input("feature_{$key}"));
            }
        }

        Database::execute(
            "INSERT INTO plans (name, slug, price, billing_interval, description, limits_json, features_json, is_active, is_visible) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $name,
                $slug,
                $price,
                $interval,
                $description,
                json_encode($limits),
                json_encode($features),
                !empty($request->input('is_active', 1)) ? 1 : 0,
                !empty($request->input('is_visible', 1)) ? 1 : 0
            ]
        );

        $newPlanId = (int)Database::lastInsertId();
        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'create_plan', 'plan', $newPlanId, ['name' => $name, 'slug' => $slug]);

        return Response::success(['id' => $newPlanId, 'name' => $name, 'slug' => $slug], "Plan '{$name}' created successfully.");
    }

    public function superadminUpdatePlan(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            return Response::error("Plan not found.", "NOT_FOUND", null, 404);
        }

        $name = trim((string)$request->input('name', ''));
        $price = (float)$request->input('price', 0);
        $interval = (string)$request->input('billing_interval', 'monthly');
        $description = trim((string)$request->input('description', ''));

        $maxFunnels = (int)$request->input('max_funnels', 1);
        $maxSubmissions = (int)$request->input('max_submissions_per_month', 100);
        $maxTeamMembers = (int)$request->input('max_team_members', 1);

        if (empty($name)) {
            return Response::error("Plan name cannot be empty.", "VALIDATION_FAILED", null, 400);
        }

        $limits = [
            'max_funnels' => $maxFunnels,
            'max_submissions_per_month' => $maxSubmissions,
            'max_team_members' => $maxTeamMembers,
        ];

        $catalog = UsageLimitService::getFeatureCatalog();
        $features = [];
        $inputFeatures = $request->input('features');
        if (is_array($inputFeatures)) {
            foreach ($catalog as $key => $meta) {
                $features[$key] = !empty($inputFeatures[$key]);
            }
        } else {
            foreach ($catalog as $key => $meta) {
                $features[$key] = !empty($request->input("feature_{$key}"));
            }
        }

        Database::execute(
            "UPDATE plans SET name = ?, price = ?, billing_interval = ?, description = ?, limits_json = ?, features_json = ?, is_active = ?, is_visible = ? WHERE id = ?",
            [
                $name,
                $price,
                $interval,
                $description,
                json_encode($limits),
                json_encode($features),
                !empty($request->input('is_active', 1)) ? 1 : 0,
                !empty($request->input('is_visible', 1)) ? 1 : 0,
                $planId
            ]
        );

        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'update_plan', 'plan', $planId, ['name' => $name]);

        return Response::success(['id' => $planId, 'name' => $name], "Plan '{$name}' updated successfully.");
    }

    public function superadminTogglePlanVisibility(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            return Response::error("Plan not found.", "NOT_FOUND", null, 404);
        }

        $newVal = empty($plan['is_visible']) ? 1 : 0;
        Database::execute("UPDATE plans SET is_visible = ? WHERE id = ?", [$newVal, $planId]);

        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'toggle_plan_visibility', 'plan', $planId, [
            'name' => $plan['name'],
            'is_visible' => $newVal,
        ]);

        return Response::success([
            'id' => $planId,
            'is_visible' => $newVal,
        ], $newVal ? "Plan '{$plan['name']}' is now visible on pricing page." : "Plan '{$plan['name']}' is now hidden from pricing page.");
    }

    public function superadminDeletePlan(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            return Response::error("Plan not found.", "NOT_FOUND", null, 404);
        }

        $activeSubCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM tenant_subscriptions WHERE plan_id = ? AND status IN ('active', 'trial')",
            [$planId]
        );

        if ($activeSubCount > 0) {
            Database::execute("UPDATE plans SET is_active = 0 WHERE id = ?", [$planId]);
            return Response::success([
                'id' => $planId,
                'is_active' => 0,
            ], "Plan '{$plan['name']}' has {$activeSubCount} active subscriptions and has been deactivated instead of deleted.");
        }

        Database::execute("DELETE FROM plans WHERE id = ?", [$planId]);
        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'delete_plan', 'plan', $planId, ['name' => $plan['name']]);

        return Response::success(['id' => $planId], "Plan '{$plan['name']}' was permanently removed.");
    }

    public function superadminPaymentGateways(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        if ($request->isMethod('POST')) {
            $payload = [
                'stripe' => [
                    'enabled' => !empty($request->input('stripe_enabled')),
                    'mode' => (string)$request->input('stripe_mode', 'test'),
                    'publishable_key' => trim((string)$request->input('stripe_publishable_key', '')),
                    'secret_key' => trim((string)$request->input('stripe_secret_key', '')),
                    'webhook_secret' => trim((string)$request->input('stripe_webhook_secret', '')),
                ],
                'paypal' => [
                    'enabled' => !empty($request->input('paypal_enabled')),
                    'mode' => (string)$request->input('paypal_mode', 'sandbox'),
                    'client_id' => trim((string)$request->input('paypal_client_id', '')),
                    'client_secret' => trim((string)$request->input('paypal_client_secret', '')),
                ],
                'razorpay' => [
                    'enabled' => !empty($request->input('razorpay_enabled')),
                    'key_id' => trim((string)$request->input('razorpay_key_id', '')),
                    'key_secret' => trim((string)$request->input('razorpay_key_secret', '')),
                ],
                'bank_transfer' => [
                    'enabled' => !empty($request->input('bank_enabled')),
                    'bank_name' => trim((string)$request->input('bank_name', '')),
                    'account_holder' => trim((string)$request->input('bank_account_holder', '')),
                    'account_number' => trim((string)$request->input('bank_account_number', '')),
                    'routing_code' => trim((string)$request->input('bank_routing_code', '')),
                    'instructions' => trim((string)$request->input('bank_instructions', '')),
                ],
                'manual_custom' => [
                    'enabled' => !empty($request->input('manual_enabled')),
                    'title' => trim((string)$request->input('manual_title', 'Offline / Cash / Cheque Payment')),
                    'instructions' => trim((string)$request->input('manual_instructions', '')),
                    'qr_code_url' => trim((string)$request->input('manual_qr_code_url', '')),
                ],
            ];

            $warnings = [];
            if (!empty($payload['stripe']['enabled']) && (empty($payload['stripe']['publishable_key']) || empty($payload['stripe']['secret_key']))) {
                $warnings[] = "Stripe is enabled with missing keys.";
            }
            if (!empty($payload['razorpay']['enabled']) && (empty($payload['razorpay']['key_id']) || empty($payload['razorpay']['key_secret']))) {
                $warnings[] = "Razorpay is enabled without complete credentials.";
            }
            if (!empty($payload['paypal']['enabled']) && empty($payload['paypal']['client_id'])) {
                $warnings[] = "PayPal is enabled without Client ID.";
            }
            if (!empty($payload['bank_transfer']['enabled']) && empty($payload['bank_transfer']['account_number'])) {
                $warnings[] = "Bank Transfer is enabled without account number.";
            }

            PlatformSettingService::set('payment_gateways', $payload);

            $currentUser = current_user();
            AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'update_payment_gateways', 'settings', null, [
                'stripe_enabled' => $payload['stripe']['enabled'],
                'paypal_enabled' => $payload['paypal']['enabled'],
                'razorpay_enabled' => $payload['razorpay']['enabled'],
                'bank_enabled' => $payload['bank_transfer']['enabled'],
                'manual_enabled' => $payload['manual_custom']['enabled'],
            ]);

            $msg = !empty($warnings)
                ? "Saved with warnings: " . implode(" ", $warnings)
                : "Payment gateway settings successfully updated.";

            return Response::success(['gateways' => $payload, 'warnings' => $warnings], $msg);
        }

        $gateways = PlatformSettingService::getPaymentGateways();
        return Response::success(['gateways' => $gateways]);
    }

    public function superadminUploadGatewayQr(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        try {
            $uploadedFile = $request->file('qr_file') ?? $request->file('file') ?? $request->file('image');
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return Response::error("Please select a valid image file to upload.", "NO_FILE", null, 400);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            $mimeType = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                    if (\PHP_VERSION_ID < 80100) {
                        finfo_close($finfo);
                    }
                }
            }
            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($uploadedFile['tmp_name']);
            }

            if (!in_array($mimeType, $allowedTypes, true)) {
                return Response::error("Invalid file format. Allowed types: PNG, JPG, WebP, SVG.", "INVALID_FORMAT", null, 400);
            }

            if ($uploadedFile['size'] > 5 * 1024 * 1024) {
                return Response::error("QR code file size must be less than 5MB.", "FILE_TOO_LARGE", null, 400);
            }

            $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) ?: 'png';
            $cleanExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
            $fileName = 'qr_payment_' . bin2hex(random_bytes(6)) . '.' . $cleanExt;
            $targetDir = dirname(__DIR__, 2) . '/public/uploads/gateways';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $fileName;
            $saved = is_uploaded_file($uploadedFile['tmp_name'])
                ? @move_uploaded_file($uploadedFile['tmp_name'], $targetPath)
                : @copy($uploadedFile['tmp_name'], $targetPath);

            if (!$saved) {
                return Response::error("Failed to save uploaded QR code file.", "UPLOAD_SAVE_FAILED", null, 500);
            }

            $qrUrl = url('/uploads/gateways/' . $fileName);

            return Response::success([
                'qr_code_url' => $qrUrl,
                'file_name' => $fileName,
            ], "QR code image uploaded successfully.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), "UPLOAD_FAILED", null, 400);
        }
    }

    public function superadminLegalPages(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $pages = LegalPageService::getAllPages();
        return Response::success(['pages' => $pages]);
    }

    public function superadminCreateLegalPage(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $title = trim((string)$request->input('title', ''));
        $slug = trim((string)$request->input('slug', ''));
        $metaDescription = trim((string)$request->input('meta_description', ''));
        $content = (string)$request->input('content', '');
        $isPublished = !empty($request->input('is_published', 1)) ? 1 : 0;

        if (empty($title)) {
            return Response::error("Page title is required.", "VALIDATION_FAILED", null, 400);
        }

        try {
            $newPageId = LegalPageService::createPage([
                'title' => $title,
                'slug' => $slug,
                'meta_description' => $metaDescription,
                'content' => $content,
                'is_published' => $isPublished,
            ]);

            $currentUser = current_user();
            AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'create_legal_page', 'legal_page', $newPageId, [
                'title' => $title,
                'slug' => $slug,
            ]);

            return Response::success(['id' => $newPageId], "New legal page created successfully.");
        } catch (\Throwable $e) {
            return Response::error("Failed to create legal page: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminUpdateLegalPage(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $pageId = (int)$id;

        try {
            LegalPageService::updatePage($pageId, [
                'title' => (string)$request->input('title'),
                'slug' => (string)$request->input('slug'),
                'meta_description' => (string)$request->input('meta_description'),
                'content' => (string)$request->input('content'),
                'is_published' => !empty($request->input('is_published')) ? 1 : 0,
            ]);

            $currentUser = current_user();
            AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'update_legal_page', 'legal_page', $pageId, [
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
            ]);

            return Response::success(['id' => $pageId], "Legal page successfully updated.");
        } catch (\Throwable $e) {
            return Response::error("Error updating legal page: " . $e->getMessage(), "ERROR", null, 400);
        }
    }

    public function superadminDeleteLegalPage(Request $request, string $id): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $pageId = (int)$id;
        $page = LegalPageService::getPageById($pageId);

        if (!$page) {
            return Response::error("Legal page not found.", "NOT_FOUND", null, 404);
        }

        if (in_array($page['slug'], ['privacy', 'terms', 'refund-policy', 'cookie-policy'])) {
            return Response::error("Cannot delete core system legal policy '{$page['title']}'. You may edit or unpublish it instead.", "ACTION_NOT_ALLOWED", null, 400);
        }

        LegalPageService::deletePage($pageId);

        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'delete_legal_page', 'legal_page', $pageId, [
            'title' => $page['title'],
            'slug' => $page['slug'],
        ]);

        return Response::success(['id' => $pageId], "Legal page '{$page['title']}' permanently deleted.");
    }

    public function superadminDocs(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $sampleTenant = Database::fetchOne("SELECT id, name, slug FROM tenants WHERE status = 'active' LIMIT 1");
        $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels WHERE status = 'published' LIMIT 1");
        if (!$sampleFunnel) {
            $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels LIMIT 1");
        }

        $baseUrl = rtrim(url('/'), '/');

        return Response::success([
            'base_url' => $baseUrl,
            'sample_api_key' => $sampleTenant['api_key'] ?? 'qfn_live_abc123xyz789demo',
            'sample_funnel_slug' => $sampleFunnel['slug'] ?? 'sample-lead-quiz',
            'sample_funnel_name' => $sampleFunnel['name'] ?? 'Sample Quiz Funnel',
            'sample_funnel_id' => $sampleFunnel['id'] ?? 1,
            'endpoints' => [
                'quiz_manifest' => "{$baseUrl}/api/v1/quiz/{slug}",
                'submissions_api' => "{$baseUrl}/api/v1/funnels/{id}/submissions",
                'webhooks' => "{$baseUrl}/api/v1/webhooks/receive",
            ]
        ]);
    }

    public function superadminProfile(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $currentUser = current_user();
        $user = Database::fetchOne("SELECT id, name, email, is_platform_admin, status, created_at FROM users WHERE id = ?", [(int)$currentUser['id']]);

        $auditCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE user_id = ?", [(int)$currentUser['id']]);

        return Response::success([
            'user' => $user ?: $currentUser,
            'audit_count' => $auditCount,
        ]);
    }

    public function superadminUpdateProfile(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $userId = (int)current_user()['id'];
        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));

        if (empty($name) || empty($email)) {
            return Response::error("Name and email are required fields.", "VALIDATION_FAILED", null, 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error("Invalid email address format.", "VALIDATION_FAILED", null, 400);
        }

        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existing) {
            return Response::error("Email address '{$email}' is already in use by another account.", "EMAIL_TAKEN", null, 400);
        }

        Database::execute("UPDATE users SET name = ?, email = ? WHERE id = ?", [$name, $email, $userId]);

        $updatedUser = Database::fetchOne("SELECT id, name, email, is_platform_admin, status FROM users WHERE id = ?", [$userId]);
        Session::set('user', $updatedUser);

        AuditLogService::log(null, $userId, 'update_admin_profile', 'user', $userId, ['email' => $email, 'name' => $name]);

        return Response::success($updatedUser, "Profile details successfully updated.");
    }

    public function superadminUpdatePassword(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $userId = (int)current_user()['id'];
        $currentPassword = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');
        $confirmPassword = (string)$request->input('confirm_password', '');

        if (empty($currentPassword) || empty($newPassword)) {
            return Response::error("All password fields are required.", "VALIDATION_FAILED", null, 400);
        }

        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return Response::error("Current password is incorrect.", "INVALID_PASSWORD", null, 400);
        }

        if (strlen($newPassword) < 8) {
            return Response::error("New password must be at least 8 characters long.", "PASSWORD_TOO_SHORT", null, 400);
        }

        if ($newPassword !== $confirmPassword) {
            return Response::error("New password and password confirmation do not match.", "PASSWORDS_DO_NOT_MATCH", null, 400);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);

        AuditLogService::log(null, $userId, 'change_admin_password', 'user', $userId, []);

        return Response::success(null, "Password changed successfully.");
    }

    public function superadminLogs(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $logs = Database::query(
            "SELECT al.*, u.name as user_name, u.email as user_email, t.name as tenant_name 
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             LEFT JOIN tenants t ON al.tenant_id = t.id
             ORDER BY al.id DESC LIMIT 150"
        );

        return Response::success($logs);
    }

    // ==========================================
    // SUPER ADMIN EMAIL & SMTP MANAGEMENT
    // ==========================================

    public function superadminEmailSettings(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $smtp = MailService::getSmtpSettings();
        // Hide actual password string for security, return mask indicator
        $smtpSafe = $smtp;
        $smtpSafe['has_password'] = !empty($smtp['password']);
        $smtpSafe['password'] = '';

        $templates = MailService::getTemplates();
        foreach ($templates as &$tpl) {
            if (!empty($tpl['available_tags']) && is_string($tpl['available_tags'])) {
                $tpl['available_tags'] = json_decode($tpl['available_tags'], true) ?: [];
            }
        }
        unset($tpl);

        $recentLogs = MailService::getRecentLogs(30);

        // Stats summary
        $totalSent = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM email_logs WHERE status = 'sent'")['c'] ?? 0);
        $totalSimulated = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM email_logs WHERE status = 'simulated'")['c'] ?? 0);
        $totalFailed = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM email_logs WHERE status = 'failed'")['c'] ?? 0);

        return Response::success([
            'smtp' => $smtpSafe,
            'templates' => $templates,
            'logs' => $recentLogs,
            'stats' => [
                'total_sent' => $totalSent,
                'total_simulated' => $totalSimulated,
                'total_failed' => $totalFailed,
                'active_templates' => count($templates),
            ]
        ]);
    }

    public function superadminSaveSmtp(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $admin = current_user();
        $input = [
            'enabled' => (bool)$request->input('enabled', false),
            'host' => trim((string)$request->input('host', '')),
            'port' => (int)$request->input('port', 587),
            'encryption' => (string)$request->input('encryption', 'tls'),
            'username' => trim((string)$request->input('username', '')),
            'password' => (string)$request->input('password', ''),
            'from_email' => trim((string)$request->input('from_email', '')),
            'from_name' => trim((string)$request->input('from_name', '')),
            'reply_to' => trim((string)$request->input('reply_to', '')),
        ];

        if (empty($input['host']) && $input['enabled']) {
            return Response::error("SMTP Host is required when enabling outbound email.", "VALIDATION_FAILED", null, 422);
        }

        MailService::saveSmtpSettings($input);
        AuditLogService::log(null, (int)$admin['id'], 'update_smtp_settings', 'platform_settings', null, [
            'enabled' => $input['enabled'],
            'host' => $input['host'],
            'port' => $input['port'],
            'encryption' => $input['encryption'],
            'from_email' => $input['from_email'],
        ]);

        return Response::success(null, "Global SMTP configuration saved successfully.");
    }

    public function superadminTestSmtp(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $email = trim((string)$request->input('email', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error("Please provide a valid recipient email address for testing.", "VALIDATION_FAILED", null, 422);
        }

        $result = MailService::testSmtpConnection($email);
        if (!$result['success']) {
            return Response::error($result['message'], "SMTP_TEST_FAILED", null, 400);
        }

        return Response::success($result, $result['message']);
    }

    public function superadminSaveTemplate(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $key = (string)$request->param('key');
        $admin = current_user();

        $data = [
            'name' => (string)$request->input('name', ''),
            'description' => (string)$request->input('description', ''),
            'subject' => (string)$request->input('subject', ''),
            'preheader' => (string)$request->input('preheader', ''),
            'body_html' => (string)$request->input('body_html', ''),
            'body_text' => (string)$request->input('body_text', ''),
            'is_active' => (int)$request->input('is_active', 1),
        ];

        if (empty($data['subject']) || empty($data['body_html'])) {
            return Response::error("Subject and HTML body content are required.", "VALIDATION_FAILED", null, 422);
        }

        $saved = MailService::saveTemplate($key, $data);
        if (!$saved) {
            return Response::error("Template '{$key}' not found.", "NOT_FOUND", null, 404);
        }

        AuditLogService::log(null, (int)$admin['id'], 'update_email_template', 'email_templates', null, [
            'template_key' => $key,
            'subject' => $data['subject']
        ]);

        return Response::success(null, "Email template '{$key}' updated successfully.");
    }

    public function superadminResetTemplate(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $key = (string)$request->param('key');
        $reset = MailService::resetTemplateToDefault($key);
        if (!$reset) {
            return Response::error("Could not reset template '{$key}'.", "NOT_FOUND", null, 404);
        }

        return Response::success(null, "Template '{$key}' restored to system defaults.");
    }

    public function superadminPreviewTestEmail(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $key = (string)$request->param('key');
        $email = trim((string)$request->input('email', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::error("Please provide a valid recipient email address.", "VALIDATION_FAILED", null, 422);
        }

        $sampleTags = [
            'user_name' => 'John Doe',
            'user_email' => $email,
            'otp_code' => '749102',
            'otp_expiry_minutes' => 15,
            'tenant_name' => 'Acme Growth Labs',
            'workspace_slug' => 'acme-labs',
            'invoice_number' => 'INV-2026-0042',
            'plan_name' => 'Pro Unlimited',
            'amount' => '4,999.00',
            'currency_symbol' => '₹',
            'payment_method' => 'Razorpay / UPI',
            'billing_interval' => 'monthly',
            'paid_at' => date('Y-m-d H:i:s'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'suspension_reason' => 'Overdue subscription renewal past 7-day grace period',
        ];

        $result = MailService::sendTemplate($key, $email, 'Administrator Preview', $sampleTags);
        return Response::success($result, $result['message']);
    }

    public function superadminEmailLogs(Request $request): Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            return Response::error("Super admin privilege required.", "FORBIDDEN", null, 403);
        }

        $limit = min(200, max(10, (int)$request->input('limit', 100)));
        $logs = MailService::getRecentLogs($limit);
        return Response::success($logs);
    }
}


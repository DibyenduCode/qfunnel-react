<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FunnelService;
use App\Services\FunnelPublishService;
use App\Services\FormSchemaService;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;
use App\Services\UsageLimitService;

class FunnelController
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $search = $request->query('search');
        $status = $request->query('status');
        $page = (int)$request->query('page', 1);

        $funnels = FunnelService::listFunnels($tenantId, $search, $status, $page, 10);

        return view('funnels.index', [
            'title' => 'Funnels — Quiz Funnel SaaS',
            'funnels' => $funnels,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function showCreate(Request $request): Response
    {
        if (!AuthorizationService::canCreateFunnel()) {
            Session::setFlash('error', 'You do not have permission to create funnels.');
            return Response::redirect(url('/admin/funnels'));
        }

        return view('funnels.create', [
            'title' => 'Create New Funnel — Quiz Funnel SaaS',
        ]);
    }

    public function store(Request $request): Response
    {
        if (!AuthorizationService::canCreateFunnel()) {
            return Response::error("Permission denied.", "FORBIDDEN", null, 403);
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $funnel = FunnelService::createFunnel($tenantId, $request->all(), (int)$user['id']);
            Session::setFlash('success', "Funnel '{$funnel['name']}' created successfully.");
            return Response::redirect(url("/admin/funnels/{$funnel['id']}/builder"));
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
            Session::set('_old_input', $request->all());
            return Response::redirect(url('/admin/funnels/create'));
        }
    }

    public function editSettings(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $funnel = FunnelService::getFunnel($tenantId, (int)$id);

        if (!$funnel) {
            Session::setFlash('error', 'Funnel not found.');
            return Response::redirect(url('/admin/funnels'));
        }

        $formFields = FormSchemaService::getFormFields($tenantId, (int)$id);
        $settings = json_decode($funnel['settings_json'] ?? '{}', true) ?: [];
        $planFeatures = UsageLimitService::getTenantFeatures($tenantId);

        return view('funnels.settings', [
            'title' => "Settings: {$funnel['name']} — Quiz Funnel SaaS",
            'funnel' => $funnel,
            'formFields' => $formFields,
            'settings' => $settings,
            'planFeatures' => $planFeatures,
            'isPlatformAdmin' => AuthorizationService::isPlatformAdmin(),
        ]);
    }

    public function updateSettings(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $existingFunnel = FunnelService::getFunnel($tenantId, (int)$id);
            $existingSettings = json_decode($existingFunnel['settings_json'] ?? '{}', true) ?: [];

            // Handle Logo File Upload if provided
            $logoUrl = trim($request->input('logo_url', ''));
            $uploadedFile = $request->file('logo_file');
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
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
                    throw new \RuntimeException('Invalid logo format. Allowed types: PNG, JPG, WebP, SVG, GIF.');
                }

                if ($uploadedFile['size'] > 3 * 1024 * 1024) {
                    throw new \RuntimeException('Logo file size must be less than 3MB.');
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
                if ($saved) {
                    $logoUrl = url('/uploads/logos/' . $fileName);
                }
            }

            // If user did not upload a new file and left logo_url empty, check if they wanted to keep existing or clear
            if (empty($logoUrl) && empty($uploadedFile) && !empty($existingSettings['logo_url']) && empty($request->input('remove_logo'))) {
                $logoUrl = $existingSettings['logo_url'];
            }

            // Check white_label feature permission
            $canWhiteLabel = UsageLimitService::hasFeature($tenantId, 'white_label') || AuthorizationService::isPlatformAdmin();
            $requestedWhiteLabel = !empty($request->input('white_label'));
            $whiteLabel = $requestedWhiteLabel && $canWhiteLabel;

            if ($requestedWhiteLabel && !$canWhiteLabel) {
                Session::setFlash('warning', 'White-labeling requires a Pro subscription plan and was skipped.');
            }

            $data = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'funnel_mode' => $request->input('funnel_mode', $existingFunnel['funnel_mode'] ?? 'quiz'),
                'settings' => [
                    'theme' => $request->input('theme', 'light'),
                    'accent_color' => $request->input('accent_color', '#5B5BD6'),
                    'logo_url' => $logoUrl,
                    'logo_position' => in_array($request->input('logo_position'), ['left', 'center', 'right'], true) ? $request->input('logo_position') : 'left',
                    'logo_height' => max(24, min(90, (int)$request->input('logo_height', 36))),
                    'company_name' => trim($request->input('company_name', '')),
                    'white_label' => $whiteLabel,
                    'footer_text' => trim($request->input('footer_text', '')),
                    'trust_badge_text' => trim($request->input('trust_badge_text', '')),
                    'card_radius' => in_array($request->input('card_radius'), ['rounded-none', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-3xl'], true) ? $request->input('card_radius') : 'rounded-2xl',
                    'enable_back_button' => !empty($request->input('enable_back_button')),
                    'show_progress_bar' => !empty($request->input('show_progress_bar')),
                    'auto_redirect' => !empty($request->input('auto_redirect')),
                    'redirect_delay_seconds' => (int)$request->input('redirect_delay_seconds', 3),
                    'privacy_policy_url' => $request->input('privacy_policy_url', ''),
                    'terms_url' => $request->input('terms_url', ''),
                ]
            ];

            FunnelService::updateFunnel($tenantId, (int)$id, $data, (int)$user['id']);
            Session::setFlash('success', 'Funnel branding and settings updated successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url("/admin/funnels/{$id}/settings"));
    }

    public function saveFormFields(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $fields = $request->input('fields', []);

        try {
            $saved = FormSchemaService::saveFormFields($tenantId, (int)$id, $fields, (int)$user['id']);
            return Response::success($saved, 'Pre-quiz form fields saved successfully.');
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 'SAVE_ERROR', null, 400);
        }
    }

    public function duplicate(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        if (!UsageLimitService::hasFeature($tenantId, 'funnel_cloning') && !AuthorizationService::isPlatformAdmin()) {
            Session::setFlash('error', "Funnel Cloning is not included in your subscription plan. Please upgrade to unlock 1-click duplication.");
            return Response::redirect(url('/admin/funnels'));
        }

        try {
            $copy = FunnelService::duplicateFunnel($tenantId, (int)$id, (int)$user['id']);
            Session::setFlash('success', "Funnel duplicated as '{$copy['name']}'.");
            return Response::redirect(url("/admin/funnels/{$copy['id']}/builder"));
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
            return Response::redirect(url('/admin/funnels'));
        }
    }

    public function publish(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            $res = FunnelPublishService::publish($tenantId, (int)$id, (int)$user['id']);
            if ($request->isAjax()) {
                return Response::success($res, "Funnel published successfully.");
            }
            Session::setFlash('success', "Funnel published successfully.");
            return Response::redirect(url("/admin/funnels/{$id}/builder"));
        } catch (\Throwable $e) {
            if ($request->isAjax()) {
                return Response::error($e->getMessage(), 'PUBLISH_ERROR', null, 400);
            }
            Session::setFlash('error', $e->getMessage());
            return Response::redirect(url("/admin/funnels/{$id}/builder"));
        }
    }

    public function unpublish(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            FunnelService::unpublishFunnel($tenantId, (int)$id, (int)$user['id']);
            if ($request->isAjax()) {
                return Response::success(null, 'Funnel unpublished and reverted to draft status.');
            }
            Session::setFlash('success', 'Funnel unpublished and reverted to draft status.');
            return Response::redirect(url("/admin/funnels/{$id}/builder"));
        } catch (\Throwable $e) {
            if ($request->isAjax()) {
                return Response::error($e->getMessage(), 'UNPUBLISH_ERROR', null, 400);
            }
            Session::setFlash('error', $e->getMessage());
            return Response::redirect(url("/admin/funnels/{$id}/builder"));
        }
    }

    public function delete(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        try {
            FunnelService::deleteFunnel($tenantId, (int)$id, (int)$user['id']);
            Session::setFlash('success', 'Funnel deleted successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/funnels'));
    }

    public function updateMode(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $mode = $request->input('funnel_mode');

        if (!in_array($mode, ['quiz', 'calculator'], true)) {
            return Response::error('Invalid funnel mode.', 'INVALID_MODE', null, 400);
        }

        if ($mode === 'calculator' && !UsageLimitService::hasFeature($tenantId, 'calculator_mode') && !AuthorizationService::isPlatformAdmin()) {
            return Response::error("Calculator & Quote Mode is not included in your current subscription plan. Please upgrade to unlock.", "FEATURE_LOCKED", null, 403);
        }

        try {
            $updated = FunnelService::updateFunnel($tenantId, (int)$id, ['funnel_mode' => $mode], (int)$user['id']);
            return Response::success(['funnel_mode' => $mode], "Funnel mode updated to '{$mode}'.");
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 'UPDATE_ERROR', null, 400);
        }
    }
}

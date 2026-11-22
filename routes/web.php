<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\ApiSpaController;
use App\Controllers\ApiController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\PlatformAdminMiddleware;

/** @var Router $router */

// ==========================================
// PUBLIC REST API ENDPOINTS
// ==========================================

// CSRF & Heartbeat / Background Automation
$router->get('/api/csrf-token', [ApiSpaController::class, 'csrfToken']);
$router->get('/api/cron/billing', [ApiSpaController::class, 'cronBillingEvaluation']);

// Authentication
$router->post('/api/auth/login', [ApiSpaController::class, 'login']);
$router->post('/api/auth/register', [ApiSpaController::class, 'register']);
$router->post('/api/auth/verify-otp', [ApiSpaController::class, 'verifyOtp']);
$router->post('/api/auth/resend-otp', [ApiSpaController::class, 'resendOtp']);
$router->post('/api/auth/logout', [ApiSpaController::class, 'logout']);
$router->get('/api/auth/me', [ApiSpaController::class, 'me']);
$router->post('/api/auth/switch-workspace', [ApiSpaController::class, 'switchWorkspace']);

// Cashfree & Webhooks
$router->post('/api/webhook/cashfree', [ApiSpaController::class, 'cashfreeWebhook']);

// Public Legal & Company CMS Pages
$router->get('/api/public/legal-pages', [ApiSpaController::class, 'publicLegalPages']);
$router->get('/api/public/legal-pages/{slug}', [ApiSpaController::class, 'publicLegalPageDetail']);

// Public Support Request & Direct Channels API
$router->get('/api/public/support-channels', [ApiSpaController::class, 'publicSupportChannels']);
$router->post('/api/public/support-tickets', [ApiSpaController::class, 'publicSubmitSupportTicket']);

// Public Pricing Plans API
$router->get('/api/public/plans', [ApiSpaController::class, 'publicPlans']);

// Public Quiz Runner API
$router->get('/api/quiz/{slug}/manifest', [ApiSpaController::class, 'quizManifest']);
$router->post('/api/quiz/{slug}/init', [ApiSpaController::class, 'quizInit']);
$router->get('/api/quiz/step', [ApiSpaController::class, 'quizStep']);
$router->post('/api/quiz/form', [ApiSpaController::class, 'quizSubmitForm']);
$router->post('/api/quiz/answer', [ApiSpaController::class, 'quizSubmitAnswer']);
$router->post('/api/quiz/back', [ApiSpaController::class, 'quizBack']);
$router->post('/api/quiz/video-event', [ApiSpaController::class, 'quizVideoEvent']);
$router->post('/api/quiz/video-gate-submit', [ApiSpaController::class, 'quizSubmitVideoGate']);
$router->post('/api/quiz/video-advance', [ApiSpaController::class, 'quizVideoAdvance']);

// External Workspace REST API (v1) - Bearer / API-Key Token Authenticated
$router->get('/api/v1/me', [ApiController::class, 'me']);
$router->get('/api/v1/funnels', [ApiController::class, 'funnels']);
$router->get('/api/v1/submissions', [ApiController::class, 'submissions']);

// ==========================================
// AUTHENTICATED SPA API ENDPOINTS
// ==========================================
$router->group([AuthMiddleware::class, CsrfMiddleware::class], function (Router $router) {

    // Tenant-Scoped Workspace API Group
    $router->group([TenantMiddleware::class], function (Router $router) {
        // Dashboard & Analytics
        $router->get('/api/dashboard', [ApiSpaController::class, 'dashboard']);
        $router->get('/api/analytics', [ApiSpaController::class, 'analytics']);

        // Funnels CRUD, Graph & Visual Builder
        $router->get('/api/funnels', [ApiSpaController::class, 'funnels']);
        $router->post('/api/funnels', [ApiSpaController::class, 'createFunnel']);
        $router->get('/api/funnels/{id}', [ApiSpaController::class, 'getFunnel']);
        $router->post('/api/funnels/{id}/settings', [ApiSpaController::class, 'updateFunnelSettings']);
        $router->post('/api/funnels/{id}/upload-logo', [ApiSpaController::class, 'uploadFunnelLogo']);
        $router->post('/api/funnels/{id}/upload-media', [ApiSpaController::class, 'uploadFunnelMedia']);
        $router->post('/api/media/upload', [ApiSpaController::class, 'uploadGeneralMedia']);
        $router->post('/api/funnels/{id}/duplicate', [ApiSpaController::class, 'duplicateFunnel']);
        $router->post('/api/funnels/{id}/publish', [ApiSpaController::class, 'publishFunnel']);
        $router->post('/api/funnels/{id}/unpublish', [ApiSpaController::class, 'unpublishFunnel']);
        $router->post('/api/funnels/{id}/delete', [ApiSpaController::class, 'deleteFunnel']);
        $router->get('/api/funnels/{id}/graph', [ApiSpaController::class, 'getFunnelGraph']);
        $router->post('/api/funnels/{id}/graph', [ApiSpaController::class, 'saveFunnelGraph']);
        $router->get('/api/funnels/{id}/validate', [ApiSpaController::class, 'validateFunnelGraph']);
        $router->post('/api/funnels/{id}/mode', [ApiSpaController::class, 'updateFunnelMode']);
        $router->post('/api/funnels/{id}/form-fields', [ApiSpaController::class, 'saveFunnelFormFields']);

        // Workflow Templates Library (Tenant Workspace)
        $router->get('/api/templates', [ApiSpaController::class, 'templates']);
        $router->get('/api/templates/{id}', [ApiSpaController::class, 'templateDetail']);
        $router->post('/api/templates/{id}/use', [ApiSpaController::class, 'useTemplate']);

        // Submissions & Leads CRM
        $router->get('/api/submissions/export', [ApiSpaController::class, 'exportSubmissions']);
        $router->get('/admin/submissions/export', [ApiSpaController::class, 'exportSubmissions']);
        $router->get('/api/submissions', [ApiSpaController::class, 'submissions']);
        $router->get('/api/submissions/{id}', [ApiSpaController::class, 'submissionDetail']);
        $router->post('/api/submissions/{id}/status', [ApiSpaController::class, 'updateSubmissionStatus']);
        $router->post('/api/submissions/bulk-action', [ApiSpaController::class, 'bulkSubmissionsAction']);
        $router->get('/api/submissions-stages', [ApiSpaController::class, 'listLeadStages']);
        $router->post('/api/submissions-stages', [ApiSpaController::class, 'createLeadStage']);
        $router->post('/api/submissions-stages/{id}/delete', [ApiSpaController::class, 'deleteLeadStage']);

        // Team Management
        $router->get('/api/team', [ApiSpaController::class, 'team']);
        $router->post('/api/team', [ApiSpaController::class, 'inviteTeamMember']);
        $router->post('/api/team/{id}/update', [ApiSpaController::class, 'updateTeamMember']);
        $router->post('/api/team/{id}/remove', [ApiSpaController::class, 'removeTeamMember']);

        // Webhooks
        $router->get('/api/webhooks', [ApiSpaController::class, 'webhooks']);
        $router->post('/api/webhooks', [ApiSpaController::class, 'createWebhook']);
        $router->post('/api/webhooks/{id}/delete', [ApiSpaController::class, 'deleteWebhook']);

        // Billing & Subscription Upgrades
        $router->get('/api/billing', [ApiSpaController::class, 'billing']);
        $router->post('/api/billing/upgrade', [ApiSpaController::class, 'upgradePlan']);
        $router->post('/api/billing/cashfree/create-order', [ApiSpaController::class, 'createCashfreeOrder']);
        $router->post('/api/billing/cashfree/verify', [ApiSpaController::class, 'verifyCashfreePayment']);

        // Workspace Settings & User Profile
        $router->post('/api/settings/workspace', [ApiSpaController::class, 'updateWorkspace']);
        $router->post('/api/settings/workspace/logo', [ApiSpaController::class, 'uploadWorkspaceLogo']);
        $router->post('/api/settings/profile', [ApiSpaController::class, 'updateProfile']);
        $router->post('/api/settings/profile/avatar', [ApiSpaController::class, 'uploadUserAvatar']);
    });

    // Super Admin Platform API Group
    $router->group([PlatformAdminMiddleware::class], function (Router $router) {
        $router->get('/api/platform/dashboard', [ApiSpaController::class, 'superadminDashboard']);

        // Customers & Tenant Lifecycle
        $router->get('/api/platform/customers', [ApiSpaController::class, 'superadminCustomers']);
        $router->post('/api/platform/tenants/{id}/toggle', [ApiSpaController::class, 'superadminToggleTenant']);
        $router->post('/api/platform/tenants/{id}/assign-plan', [ApiSpaController::class, 'superadminAssignPlan']);
        $router->post('/api/platform/tenants/{id}/billing-cycle', [ApiSpaController::class, 'superadminBillingCycle']);
        $router->post('/api/platform/tenants/{id}/record-payment', [ApiSpaController::class, 'superadminRecordPayment']);
        $router->post('/api/platform/tenants/{id}/suspend', [ApiSpaController::class, 'superadminSuspendTenant']);
        $router->post('/api/platform/tenants/{id}/unsuspend', [ApiSpaController::class, 'superadminUnsuspendTenant']);
        $router->post('/api/platform/tenants/{id}/mark-termination-due', [ApiSpaController::class, 'superadminMarkTerminationDue']);
        $router->post('/api/platform/tenants/{id}/dismiss-termination-due', [ApiSpaController::class, 'superadminDismissTerminationDue']);
        $router->post('/api/platform/tenants/{id}/terminate', [ApiSpaController::class, 'superadminTerminateTenant']);
        $router->post('/api/platform/billing/evaluate', [ApiSpaController::class, 'superadminEvaluateBilling']);
        $router->post('/api/platform/users/{id}/toggle-status', [ApiSpaController::class, 'superadminToggleUserStatus']);

        // Plans Management
        $router->get('/api/platform/plans', [ApiSpaController::class, 'superadminPlans']);
        $router->post('/api/platform/plans', [ApiSpaController::class, 'superadminCreatePlan']);
        $router->post('/api/platform/plans/{id}/update', [ApiSpaController::class, 'superadminUpdatePlan']);
        $router->post('/api/platform/plans/{id}/toggle-visibility', [ApiSpaController::class, 'superadminTogglePlanVisibility']);
        $router->post('/api/platform/plans/{id}/delete', [ApiSpaController::class, 'superadminDeletePlan']);

        // Payment Gateways
        $router->get('/api/platform/payment-gateways', [ApiSpaController::class, 'superadminPaymentGateways']);
        $router->post('/api/platform/payment-gateways', [ApiSpaController::class, 'superadminPaymentGateways']);
        $router->post('/api/platform/payment-gateways/upload-qr', [ApiSpaController::class, 'superadminUploadGatewayQr']);

        // Legal Pages CMS
        $router->get('/api/platform/legal-pages', [ApiSpaController::class, 'superadminLegalPages']);
        $router->post('/api/platform/legal-pages', [ApiSpaController::class, 'superadminCreateLegalPage']);
        $router->post('/api/platform/legal-pages/{id}/update', [ApiSpaController::class, 'superadminUpdateLegalPage']);
        $router->post('/api/platform/legal-pages/{id}/delete', [ApiSpaController::class, 'superadminDeleteLegalPage']);

        // Support Tickets & Help Desk CMS
        $router->get('/api/platform/support-tickets', [ApiSpaController::class, 'superadminSupportTickets']);
        $router->post('/api/platform/support-tickets/{id}/status', [ApiSpaController::class, 'superadminUpdateSupportTicketStatus']);
        $router->post('/api/platform/support-tickets/{id}/delete', [ApiSpaController::class, 'superadminDeleteSupportTicket']);
        $router->post('/api/platform/support-channels', [ApiSpaController::class, 'superadminSaveSupportChannels']);

        // Platform Documentation
        $router->get('/api/platform/docs', [ApiSpaController::class, 'superadminDocs']);

        // Super Admin Profile & Credentials
        $router->get('/api/platform/profile', [ApiSpaController::class, 'superadminProfile']);
        $router->post('/api/platform/profile', [ApiSpaController::class, 'superadminUpdateProfile']);
        $router->post('/api/platform/profile/avatar', [ApiSpaController::class, 'uploadUserAvatar']);
        $router->post('/api/platform/profile/password', [ApiSpaController::class, 'superadminUpdatePassword']);

        // Global Email SMTP & Templates CMS
        $router->get('/api/platform/email-settings', [ApiSpaController::class, 'superadminEmailSettings']);
        $router->post('/api/platform/email-settings/smtp', [ApiSpaController::class, 'superadminSaveSmtp']);
        $router->post('/api/platform/email-settings/test-smtp', [ApiSpaController::class, 'superadminTestSmtp']);
        $router->post('/api/platform/email-settings/templates/{key}', [ApiSpaController::class, 'superadminSaveTemplate']);
        $router->post('/api/platform/email-settings/templates/{key}/reset', [ApiSpaController::class, 'superadminResetTemplate']);
        $router->post('/api/platform/email-settings/templates/{key}/preview-test', [ApiSpaController::class, 'superadminPreviewTestEmail']);
        $router->get('/api/platform/email-settings/logs', [ApiSpaController::class, 'superadminEmailLogs']);

        // Global Workflow Templates Manager (Super Admin)
        $router->get('/api/platform/templates', [ApiSpaController::class, 'superadminTemplates']);
        $router->post('/api/platform/templates', [ApiSpaController::class, 'superadminCreateTemplate']);
        $router->post('/api/platform/templates/from-funnel', [ApiSpaController::class, 'superadminCreateTemplateFromFunnel']);
        $router->post('/api/platform/templates/{id}/update', [ApiSpaController::class, 'superadminUpdateTemplate']);
        $router->post('/api/platform/templates/{id}/delete', [ApiSpaController::class, 'superadminDeleteTemplate']);
        $router->get('/api/platform/templates/{id}/export', [ApiSpaController::class, 'superadminExportTemplate']);

        // System Audit Logs
        $router->get('/api/platform/logs', [ApiSpaController::class, 'superadminLogs']);
    });
});

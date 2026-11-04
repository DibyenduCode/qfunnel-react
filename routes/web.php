<?php

use App\Core\Router;
use App\Core\Response;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FunnelController;
use App\Controllers\BuilderController;
use App\Controllers\SubmissionController;
use App\Controllers\AnalyticsController;
use App\Controllers\SettingsController;
use App\Controllers\TeamController;
use App\Controllers\WebhookController;
use App\Controllers\PlatformAdminController;
use App\Controllers\QuizController;
use App\Controllers\ApiController;
use App\Controllers\LegalController;
use App\Controllers\BillingController;
use App\Controllers\ApiSpaController;
use App\Middleware\AuthMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\PlatformAdminMiddleware;
use App\Middleware\CsrfMiddleware;

/** @var Router $router */

// Home Landing Page
$router->get('/', [HomeController::class, 'index']);

// Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// Public Quiz Routes
$router->get('/quiz/{slug}', [QuizController::class, 'show']);
$router->get('/quiz/{slug}/result/{token}', [QuizController::class, 'result']);

// Public Quiz API Endpoints
$router->post('/api/quiz/{slug}/init', [QuizController::class, 'apiInit']);
$router->get('/api/quiz/step', [QuizController::class, 'apiStep']);
$router->post('/api/quiz/form', [QuizController::class, 'apiSubmitForm']);
$router->post('/api/quiz/answer', [QuizController::class, 'apiSubmitAnswer']);
$router->post('/api/quiz/back', [QuizController::class, 'apiBack']);
$router->post('/api/quiz/video-event', [QuizController::class, 'apiVideoEvent']);
$router->post('/api/quiz/video-gate-submit', [QuizController::class, 'apiSubmitVideoGate']);
$router->post('/api/quiz/video-advance', [QuizController::class, 'apiVideoAdvance']);

// Public Dynamic Legal & Compliance Pages
$router->get('/terms', [LegalController::class, 'terms']);
$router->get('/privacy', [LegalController::class, 'privacy']);
$router->get('/refund-policy', [LegalController::class, 'refundPolicy']);
$router->get('/cookie-policy', [LegalController::class, 'cookiePolicy']);
$router->get('/legal/{slug}', [LegalController::class, 'show']);

// Public Documentation Page (API, Webhooks & Embedding)
$router->get('/docs', [HomeController::class, 'docs']);
$router->get('/documentation', [HomeController::class, 'docs']);

// External Workspace REST API (v1) - Bearer / API-Key Token Authenticated
$router->get('/api/v1/me', [ApiController::class, 'me']);
$router->get('/api/v1/funnels', [ApiController::class, 'funnels']);
$router->get('/api/v1/submissions', [ApiController::class, 'submissions']);

// Protected Admin Routes Group
$router->group([AuthMiddleware::class], function (Router $router) {
    // Switch tenant
    $router->post('/admin/switch-workspace', [AuthController::class, 'switchWorkspace']);

    // Platform Administration (Super Admin Exclusive)
    $router->group([PlatformAdminMiddleware::class], function (Router $router) {
        // Overview Dashboard
        $router->get('/admin/platform', [PlatformAdminController::class, 'index']);
        
        // Customers & Users Management
        $router->get('/admin/platform/customers', [PlatformAdminController::class, 'customers']);
        $router->post('/admin/platform/tenants/{id}/toggle', [PlatformAdminController::class, 'toggleTenantStatus'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/assign-plan', [PlatformAdminController::class, 'assignPlan'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/billing-cycle', [PlatformAdminController::class, 'updateBillingCycle'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/record-payment', [PlatformAdminController::class, 'recordPayment'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/suspend', [PlatformAdminController::class, 'suspendTenant'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/unsuspend', [PlatformAdminController::class, 'unsuspendTenant'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/mark-termination-due', [PlatformAdminController::class, 'markTerminationDue'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/dismiss-termination-due', [PlatformAdminController::class, 'dismissTerminationDue'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/tenants/{id}/terminate', [PlatformAdminController::class, 'terminateTenant'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/users/{id}/toggle-status', [PlatformAdminController::class, 'toggleUserStatus'], [CsrfMiddleware::class]);
        
        // Plans Management (Make plans for users)
        $router->get('/admin/platform/plans', [PlatformAdminController::class, 'plans']);
        $router->post('/admin/platform/plans/create', [PlatformAdminController::class, 'createPlan'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/plans/{id}/update', [PlatformAdminController::class, 'updatePlan'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/plans/{id}/toggle-visibility', [PlatformAdminController::class, 'togglePlanVisibility'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/plans/{id}/delete', [PlatformAdminController::class, 'deletePlan'], [CsrfMiddleware::class]);
        
        // Payment Gateways Settings (Auto & Manual)
        $router->get('/admin/platform/payment-gateways', [PlatformAdminController::class, 'paymentGateways']);
        $router->post('/admin/platform/payment-gateways', [PlatformAdminController::class, 'updatePaymentGateways'], [CsrfMiddleware::class]);
        
        // Admin Profile (Change email and password)
        $router->get('/admin/platform/profile', [PlatformAdminController::class, 'profile']);
        $router->post('/admin/platform/profile', [PlatformAdminController::class, 'updateProfile'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/profile/password', [PlatformAdminController::class, 'updatePassword'], [CsrfMiddleware::class]);
        
        // Audit Logs
        $router->get('/admin/platform/logs', [PlatformAdminController::class, 'logs']);

        // Legal Pages & Compliance CMS
        $router->get('/admin/platform/legal-pages', [PlatformAdminController::class, 'legalPages']);
        $router->post('/admin/platform/legal-pages/create', [PlatformAdminController::class, 'createLegalPage'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/legal-pages/{id}/update', [PlatformAdminController::class, 'updateLegalPage'], [CsrfMiddleware::class]);
        $router->post('/admin/platform/legal-pages/{id}/delete', [PlatformAdminController::class, 'deleteLegalPage'], [CsrfMiddleware::class]);

        // API, Webhook & Embedding Documentation Hub
        $router->get('/admin/platform/docs', [PlatformAdminController::class, 'docs']);
    });

    // Tenant-Scoped Workspace Routes
    $router->group([TenantMiddleware::class], function (Router $router) {
        // Dashboard
        $router->get('/admin/dashboard', [DashboardController::class, 'index']);
        $router->get('/admin/workspace/suspended', [DashboardController::class, 'workspaceSuspended']);

        // Funnels
        $router->get('/admin/funnels', [FunnelController::class, 'index']);
        $router->get('/admin/funnels/create', [FunnelController::class, 'showCreate']);
        $router->post('/admin/funnels/create', [FunnelController::class, 'store'], [CsrfMiddleware::class]);
        $router->get('/admin/funnels/{id}/settings', [FunnelController::class, 'editSettings']);
        $router->post('/admin/funnels/{id}/settings', [FunnelController::class, 'updateSettings'], [CsrfMiddleware::class]);
        $router->post('/admin/funnels/{id}/duplicate', [FunnelController::class, 'duplicate'], [CsrfMiddleware::class]);
        $router->post('/admin/funnels/{id}/publish', [FunnelController::class, 'publish'], [CsrfMiddleware::class]);
        $router->post('/admin/funnels/{id}/unpublish', [FunnelController::class, 'unpublish'], [CsrfMiddleware::class]);
        $router->post('/admin/funnels/{id}/delete', [FunnelController::class, 'delete'], [CsrfMiddleware::class]);

        // Visual Builder Workspace & API
        $router->get('/admin/funnels/{id}/builder', [BuilderController::class, 'index']);
        $router->get('/api/funnels/{id}/graph', [BuilderController::class, 'getGraph']);
        $router->post('/api/funnels/{id}/graph', [BuilderController::class, 'saveGraph']);
        $router->get('/api/funnels/{id}/validate', [BuilderController::class, 'validateGraph']);
        $router->post('/api/funnels/{id}/mode', [FunnelController::class, 'updateMode']);
        $router->post('/api/funnels/{id}/form-fields', [FunnelController::class, 'saveFormFields']);

        // Submissions & Journey Inspector
        $router->get('/admin/submissions', [SubmissionController::class, 'index']);
        $router->get('/admin/submissions/export', [SubmissionController::class, 'export']);
        $router->post('/admin/submissions/stages/create', [SubmissionController::class, 'createStage'], [CsrfMiddleware::class]);
        $router->post('/admin/submissions/stages/{id}/delete', [SubmissionController::class, 'deleteStage'], [CsrfMiddleware::class]);
        $router->post('/admin/submissions/bulk-action', [SubmissionController::class, 'bulkAction'], [CsrfMiddleware::class]);
        $router->post('/admin/submissions/{id}/status', [SubmissionController::class, 'updateStatus'], [CsrfMiddleware::class]);
        $router->get('/admin/submissions/{id}', [SubmissionController::class, 'show']);

        // Analytics
        $router->get('/admin/analytics', [AnalyticsController::class, 'index']);

        // Team Management
        $router->get('/admin/team', [TeamController::class, 'index']);
        $router->post('/admin/team', [TeamController::class, 'store'], [CsrfMiddleware::class]);
        $router->post('/admin/team/{id}/update', [TeamController::class, 'update'], [CsrfMiddleware::class]);
        $router->post('/admin/team/{id}/remove', [TeamController::class, 'remove'], [CsrfMiddleware::class]);

        // Webhooks & Integrations
        $router->get('/admin/webhooks', [WebhookController::class, 'index']);
        $router->post('/admin/webhooks', [WebhookController::class, 'store'], [CsrfMiddleware::class]);
        $router->post('/admin/webhooks/{id}/update', [WebhookController::class, 'update'], [CsrfMiddleware::class]);
        $router->post('/admin/webhooks/{id}/delete', [WebhookController::class, 'delete'], [CsrfMiddleware::class]);
        $router->post('/admin/webhooks/{id}/test', [WebhookController::class, 'test'], [CsrfMiddleware::class]);
        $router->post('/admin/webhooks/deliveries/{id}/retry', [WebhookController::class, 'retryDelivery'], [CsrfMiddleware::class]);

        // Settings
        $router->get('/admin/settings/workspace', [SettingsController::class, 'workspace']);
        $router->post('/admin/settings/workspace', [SettingsController::class, 'updateWorkspace'], [CsrfMiddleware::class]);
        $router->post('/admin/settings/workspace/invite', [SettingsController::class, 'inviteMember'], [CsrfMiddleware::class]);
        $router->post('/admin/settings/workspace/members/{id}/remove', [SettingsController::class, 'removeMember'], [CsrfMiddleware::class]);

        // Profile
        $router->get('/admin/settings/profile', [SettingsController::class, 'profile']);
        $router->post('/admin/settings/profile', [SettingsController::class, 'updateProfile'], [CsrfMiddleware::class]);

        // Billing & Plan Upgrade (Self-service customer portal)
        $router->get('/admin/billing', [BillingController::class, 'index']);
        $router->post('/admin/billing/upgrade', [BillingController::class, 'upgrade'], [CsrfMiddleware::class]);
    });
});

// ==========================================
// REACT SPA REST API ROUTES
// ==========================================
$router->get('/api/csrf-token', [ApiSpaController::class, 'csrfToken']);
$router->post('/api/auth/login', [ApiSpaController::class, 'login']);
$router->post('/api/auth/register', [ApiSpaController::class, 'register']);
$router->post('/api/auth/logout', [ApiSpaController::class, 'logout']);
$router->get('/api/auth/me', [ApiSpaController::class, 'me']);
$router->post('/api/auth/switch-workspace', [ApiSpaController::class, 'switchWorkspace']);
$router->get('/api/quiz/{slug}/manifest', [ApiSpaController::class, 'quizManifest']);

// Authenticated SPA Routes Group
$router->group([AuthMiddleware::class], function (Router $router) {
    // Tenant-Scoped SPA Routes Group
    $router->group([TenantMiddleware::class], function (Router $router) {
        $router->get('/api/dashboard', [ApiSpaController::class, 'dashboard']);
        $router->get('/api/analytics', [ApiSpaController::class, 'analytics']);

        // Funnels CRUD & Publishing
        $router->get('/api/funnels', [ApiSpaController::class, 'funnels']);
        $router->post('/api/funnels', [ApiSpaController::class, 'createFunnel']);
        $router->get('/api/funnels/{id}', [ApiSpaController::class, 'getFunnel']);
        $router->post('/api/funnels/{id}/settings', [ApiSpaController::class, 'updateFunnelSettings']);
        $router->post('/api/funnels/{id}/upload-logo', [ApiSpaController::class, 'uploadFunnelLogo']);
        $router->post('/api/funnels/{id}/duplicate', [ApiSpaController::class, 'duplicateFunnel']);
        $router->post('/api/funnels/{id}/publish', [ApiSpaController::class, 'publishFunnel']);
        $router->post('/api/funnels/{id}/unpublish', [ApiSpaController::class, 'unpublishFunnel']);
        $router->post('/api/funnels/{id}/delete', [ApiSpaController::class, 'deleteFunnel']);

        // Submissions & Leads CRM
        $router->get('/api/submissions', [ApiSpaController::class, 'submissions']);
        $router->get('/api/submissions/{id}', [ApiSpaController::class, 'submissionDetail']);
        $router->post('/api/submissions/{id}/status', [ApiSpaController::class, 'updateSubmissionStatus']);
        $router->post('/api/submissions/bulk-action', [ApiSpaController::class, 'bulkSubmissionsAction']);
        $router->get('/api/submissions-stages', [ApiSpaController::class, 'listLeadStages']);
        $router->post('/api/submissions-stages', [ApiSpaController::class, 'createLeadStage']);
        $router->post('/api/submissions-stages/{id}/delete', [ApiSpaController::class, 'deleteLeadStage']);

        // Team
        $router->get('/api/team', [ApiSpaController::class, 'team']);
        $router->post('/api/team', [ApiSpaController::class, 'inviteTeamMember']);
        $router->post('/api/team/{id}/update', [ApiSpaController::class, 'updateTeamMember']);
        $router->post('/api/team/{id}/remove', [ApiSpaController::class, 'removeTeamMember']);

        // Webhooks
        $router->get('/api/webhooks', [ApiSpaController::class, 'webhooks']);
        $router->post('/api/webhooks', [ApiSpaController::class, 'createWebhook']);
        $router->post('/api/webhooks/{id}/delete', [ApiSpaController::class, 'deleteWebhook']);

        // Billing
        $router->get('/api/billing', [ApiSpaController::class, 'billing']);
        $router->post('/api/billing/upgrade', [ApiSpaController::class, 'upgradePlan']);

        // Settings
        $router->post('/api/settings/workspace', [ApiSpaController::class, 'updateWorkspace']);
        $router->post('/api/settings/profile', [ApiSpaController::class, 'updateProfile']);
    });

    // Super Admin Platform API Routes Group
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
        $router->post('/api/platform/users/{id}/toggle-status', [ApiSpaController::class, 'superadminToggleUserStatus']);

        // Plans CRUD
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

        // Documentation Hub
        $router->get('/api/platform/docs', [ApiSpaController::class, 'superadminDocs']);

        // Super Admin Profile & Credentials
        $router->get('/api/platform/profile', [ApiSpaController::class, 'superadminProfile']);
        $router->post('/api/platform/profile', [ApiSpaController::class, 'superadminUpdateProfile']);
        $router->post('/api/platform/profile/password', [ApiSpaController::class, 'superadminUpdatePassword']);

        // Logs
        $router->get('/api/platform/logs', [ApiSpaController::class, 'superadminLogs']);
    });
});

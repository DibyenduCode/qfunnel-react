<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\SubmissionService;
use App\Services\FunnelService;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;
use App\Services\UsageLimitService;

class SubmissionController
{
    public function index(Request $request): Response
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
        $funnels = FunnelService::listFunnels($tenantId);
        $leadStages = \App\Services\LeadStageService::getStagesForTenant($tenantId);

        return view('submissions.index', [
            'title' => 'Submissions & Leads — Quiz Funnel SaaS',
            'submissions' => $submissions,
            'funnels' => $funnels,
            'leadStages' => $leadStages,
            'filters' => $filters,
            'canExport' => UsageLimitService::hasFeature($tenantId, 'csv_export') || AuthorizationService::isPlatformAdmin(),
        ]);
    }

    public function createStage(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $name = (string)$request->input('name');
        $color = (string)$request->input('color', '#6366F1');

        try {
            $stage = \App\Services\LeadStageService::createStage($tenantId, $name, $color);

            if ($request->isAjax()) {
                return Response::json([
                    'success' => true,
                    'stage' => $stage
                ]);
            }
            Session::setFlash('success', "Custom lead stage '{$stage['name']}' created successfully.");
        } catch (\Throwable $e) {
            if ($request->isAjax()) {
                return Response::error($e->getMessage(), 'VALIDATION_ERROR', null, 422);
            }
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/submissions'));
    }

    public function deleteStage(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        try {
            \App\Services\LeadStageService::deleteStage($tenantId, (int)$id);

            if ($request->isAjax()) {
                return Response::json(['success' => true]);
            }
            Session::setFlash('success', "Lead stage deleted successfully.");
        } catch (\Throwable $e) {
            if ($request->isAjax()) {
                return Response::error($e->getMessage(), 'ERROR', null, 400);
            }
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/submissions'));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $leadStatus = (string)$request->input('lead_status');

        $updated = SubmissionService::updateLeadStatus($tenantId, (int)$id, $leadStatus);

        if ($request->isAjax()) {
            return Response::json([
                'success' => $updated,
                'lead_status' => $leadStatus
            ]);
        }

        if ($updated) {
            Session::setFlash('success', "Lead status updated to " . ucfirst($leadStatus) . ".");
        } else {
            Session::setFlash('error', "Invalid lead status provided.");
        }

        return Response::redirect(url('/admin/submissions'));
    }

    public function bulkAction(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $action = (string)$request->input('action');
        $sessionIds = (array)$request->input('session_ids', []);

        if (empty($sessionIds)) {
            Session::setFlash('error', "No leads were selected for bulk action.");
            return Response::redirect(url('/admin/submissions'));
        }

        if (str_starts_with($action, 'mark_')) {
            $newStatus = substr($action, 5);
            $count = SubmissionService::bulkUpdateLeadStatus($tenantId, $sessionIds, $newStatus);
            Session::setFlash('success', "{$count} leads updated to '" . ucfirst(str_replace('_', ' ', $newStatus)) . "'.");
        } elseif ($action === 'delete') {
            $count = SubmissionService::bulkDelete($tenantId, $sessionIds);
            Session::setFlash('success', "{$count} lead submissions deleted successfully.");
        } else {
            Session::setFlash('error', "Unknown bulk action requested.");
        }

        return Response::redirect(url('/admin/submissions'));
    }

    public function show(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $detail = SubmissionService::getSubmissionDetail($tenantId, (int)$id);

        if (!$detail) {
            Session::setFlash('error', 'Submission record not found.');
            return Response::redirect(url('/admin/submissions'));
        }

        return view('submissions.show', [
            'title' => "Journey Detail: Submission #{$id} — Quiz Funnel SaaS",
            'detail' => $detail,
        ]);
    }

    public function export(Request $request): Response
    {
        if (!AuthorizationService::canExportSubmissions()) {
            Session::setFlash('error', 'Permission denied to export submissions.');
            return Response::redirect(url('/admin/submissions'));
        }

        $tenantId = TenantContextService::getActiveTenantId();

        if (!UsageLimitService::hasFeature($tenantId, 'csv_export') && !AuthorizationService::isPlatformAdmin()) {
            Session::setFlash('error', 'CSV Export is a premium feature. Please upgrade your subscription plan to unlock lead data exports.');
            return Response::redirect(url('/admin/submissions'));
        }
        $funnelId = $request->query('funnel_id') ? (int)$request->query('funnel_id') : null;
        $tab = (string)$request->query('tab', 'qualified');
        $filters = [
            'date_range' => $request->query('date_range')
        ];

        $csv = SubmissionService::exportCsv($tenantId, $funnelId, $tab, $filters);
        $filename = 'leads-export-' . ($tab ?: 'all') . '-' . date('Y-m-d-His') . '.csv';

        $response = new Response($csv, 200);
        $response->header('Content-Type', 'text/csv; charset=utf-8');
        $response->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        return $response;
    }
}

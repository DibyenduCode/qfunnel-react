<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LegalPageService;

class LegalController
{
    public function terms(Request $request): Response
    {
        return $this->show($request, 'terms');
    }

    public function privacy(Request $request): Response
    {
        return $this->show($request, 'privacy');
    }

    public function refundPolicy(Request $request): Response
    {
        return $this->show($request, 'refund-policy');
    }

    public function cookiePolicy(Request $request): Response
    {
        return $this->show($request, 'cookie-policy');
    }

    /**
     * Render public legal page dynamically
     */
    public function show(Request $request, string $slug = 'terms'): Response
    {
        $page = LegalPageService::getPageBySlug($slug, true);

        if (!$page) {
            // If page is not found or unpublished, return 404
            return Response::html(
                view('errors.404', [
                    'title' => 'Legal Page Not Found — QFunnel',
                    'message' => "The requested policy page ('{$slug}') was not found or is currently unavailable."
                ], null)->getContent(),
                404
            );
        }

        // Fetch all published legal pages for side navigation
        $allPages = LegalPageService::getPublishedPages();

        return view('legal.show', [
            'title' => e($page['title']) . ' — QFunnel Legal & Compliance',
            'page' => $page,
            'allPages' => $allPages,
            'activeSlug' => $slug,
        ], null);
    }
}

<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\TenantContextService;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        if (current_user()) {
            if (is_platform_admin()) {
                return Response::redirect(url('/admin/platform'));
            }
            return Response::redirect(url('/admin/dashboard'));
        }
        return view('auth.login', ['title' => 'Sign In — Quiz Funnel SaaS'], 'auth');
    }

    public function login(Request $request): Response
    {
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $result = AuthService::attempt($email, $password);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Session::set('_old_input', ['email' => $email]);
            return Response::redirect(url('/login'));
        }

        Session::setFlash('success', "Welcome back, {$result['user']['name']}!");

        if (!empty($result['user']['is_platform_admin'])) {
            return Response::redirect(url('/admin/platform'));
        }

        return Response::redirect(url('/admin/dashboard'));
    }

    public function showRegister(Request $request): Response
    {
        if (current_user()) {
            return Response::redirect(url('/admin/dashboard'));
        }
        return view('auth.register', ['title' => 'Create Your Account — Quiz Funnel SaaS'], 'auth');
    }

    public function register(Request $request): Response
    {
        $name = (string)$request->input('name', '');
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');
        $workspace = (string)$request->input('workspace', '');

        $result = AuthService::register($name, $email, $password, $workspace);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Session::set('_old_input', ['name' => $name, 'email' => $email, 'workspace' => $workspace]);
            return Response::redirect(url('/register'));
        }

        Session::setFlash('success', "Workspace created! Welcome to Quiz Funnel Builder.");
        return Response::redirect(url('/admin/dashboard'));
    }

    public function logout(Request $request): Response
    {
        AuthService::logout();
        return Response::redirect(url('/login'));
    }

    public function switchWorkspace(Request $request): Response
    {
        $tenantId = (int)$request->input('tenant_id');
        $user = current_user();

        if ($user && $tenantId) {
            TenantContextService::setActiveTenant($tenantId, (int)$user['id']);
        }

        return Response::redirect(url('/admin/dashboard'));
    }
}

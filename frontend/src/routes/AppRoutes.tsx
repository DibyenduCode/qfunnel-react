import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { PublicLayout } from '../layouts/PublicLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { AdminLayout } from '../layouts/AdminLayout';
import { SuperAdminLayout } from '../layouts/SuperAdminLayout';
import { ProtectedRoute } from './ProtectedRoute';
import { SuperAdminRoute } from './SuperAdminRoute';

import { HomePage } from '../modules/home/HomePage';
import { AboutPage } from '../modules/company/AboutPage';
import { SupportPage } from '../modules/company/SupportPage';
import { PrivacyPage } from '../modules/legal/PrivacyPage';
import { TermsPage } from '../modules/legal/TermsPage';
import { CookiePolicyPage } from '../modules/legal/CookiePolicyPage';

import { LoginPage } from '../modules/auth/LoginPage';
import { RegisterPage } from '../modules/auth/RegisterPage';

import { DashboardPage } from '../modules/dashboard/DashboardPage';
import { FunnelsListPage } from '../modules/funnels/FunnelsListPage';
import { CreateFunnelPage } from '../modules/funnels/CreateFunnelPage';
import { FunnelSettingsPage } from '../modules/funnels/FunnelSettingsPage';
import { BuilderPage } from '../modules/builder/BuilderPage';

import { SubmissionsPage } from '../modules/submissions/SubmissionsPage';
import { LeadDetailPage } from '../modules/submissions/LeadDetailPage';
import { AnalyticsPage } from '../modules/analytics/AnalyticsPage';
import { TeamPage } from '../modules/team/TeamPage';
import { WebhooksPage } from '../modules/webhooks/WebhooksPage';
import { BillingPage } from '../modules/billing/BillingPage';
import { SettingsPage } from '../modules/settings/SettingsPage';

import { SuperadminDashboard } from '../modules/platform/SuperadminDashboard';
import { SuperadminCustomers } from '../modules/platform/SuperadminCustomers';
import { SuperadminPlans } from '../modules/platform/SuperadminPlans';
import { SuperadminGateways } from '../modules/platform/SuperadminGateways';
import { SuperadminLegalPages } from '../modules/platform/SuperadminLegalPages';
import { SuperadminDocs } from '../modules/platform/SuperadminDocs';
import { SuperadminProfile } from '../modules/platform/SuperadminProfile';
import { SuperadminLogs } from '../modules/platform/SuperadminLogs';

import { QuizRunnerPage } from '../modules/runner/QuizRunnerPage';

import { useAuthStore } from '../store/authStore';

const RouteFallback: React.FC = () => {
  const { isPlatformAdmin } = useAuthStore();
  return <Navigate to={isPlatformAdmin ? "/admin/platform" : "/admin/dashboard"} replace />;
};

export const AppRoutes: React.FC = () => {
  return (
    <Routes>
      {/* Public Pages with Marketing Header & Comprehensive Footer */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/support" element={<SupportPage />} />
        <Route path="/contact" element={<SupportPage />} />
        <Route path="/privacy" element={<PrivacyPage />} />
        <Route path="/terms" element={<TermsPage />} />
        <Route path="/cookies" element={<CookiePolicyPage />} />
      </Route>

      {/* Public Visitor Quiz Runner */}
      <Route path="/quiz/:slug" element={<QuizRunnerPage />} />
      <Route path="/funnel/:slug" element={<QuizRunnerPage />} />

      {/* Authentication */}
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>

      {/* Fullscreen Visual Builder */}
      <Route
        path="/admin/funnels/:id/builder"
        element={
          <ProtectedRoute>
            <BuilderPage />
          </ProtectedRoute>
        }
      />

      {/* Protected Tenant Admin Workspace */}
      <Route
        element={
          <ProtectedRoute>
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/admin/dashboard" element={<DashboardPage />} />
        <Route path="/admin/funnels" element={<FunnelsListPage />} />
        <Route path="/admin/funnels/create" element={<CreateFunnelPage />} />
        <Route path="/admin/funnels/:id/settings" element={<FunnelSettingsPage />} />
        <Route path="/admin/submissions" element={<SubmissionsPage />} />
        <Route path="/admin/submissions/:id" element={<LeadDetailPage />} />
        <Route path="/admin/analytics" element={<AnalyticsPage />} />
        <Route path="/admin/team" element={<TeamPage />} />
        <Route path="/admin/webhooks" element={<WebhooksPage />} />
        <Route path="/admin/billing" element={<BillingPage />} />
        <Route path="/admin/settings" element={<SettingsPage />} />
      </Route>

      {/* Super Admin Platform Console */}
      <Route
        element={
          <SuperAdminRoute>
            <SuperAdminLayout />
          </SuperAdminRoute>
        }
      >
        <Route path="/admin/platform" element={<SuperadminDashboard />} />
        <Route path="/admin/platform/customers" element={<SuperadminCustomers />} />
        <Route path="/admin/platform/plans" element={<SuperadminPlans />} />
        <Route path="/admin/platform/payment-gateways" element={<SuperadminGateways />} />
        <Route path="/admin/platform/legal-pages" element={<SuperadminLegalPages />} />
        <Route path="/admin/platform/docs" element={<SuperadminDocs />} />
        <Route path="/admin/platform/profile" element={<SuperadminProfile />} />
        <Route path="/admin/platform/logs" element={<SuperadminLogs />} />
      </Route>

      {/* Fallback */}
      <Route path="*" element={<RouteFallback />} />
    </Routes>
  );
};
import React, { useState } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import {
  LayoutDashboard,
  GitFork,
  Users,
  BarChart3,
  Users2,
  Webhook,
  CreditCard,
  Settings,
  ShieldCheck,
  ChevronDown,
  LogOut,
  Plus,
  Menu,
  X,
  CheckCircle2,
  AlertTriangle
} from 'lucide-react';

export const AdminLayout: React.FC = () => {
  const { user, activeTenant, userTenants, switchWorkspace, logout, isPlatformAdmin, limits } = useAuthStore();
  const location = useLocation();
  const navigate = useNavigate();

  const [workspaceMenuOpen, setWorkspaceMenuOpen] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, chip: 'bg-[#dcf1ff] text-[#1d5fd6]' },
    { name: 'Funnels', href: '/admin/funnels', icon: GitFork, chip: 'bg-[#e0e7ff] text-[#3730a3]' },
    { name: 'Leads & Submissions', href: '/admin/submissions', icon: Users, chip: 'bg-[#dcfae5] text-[#2f7a45]' },
    { name: 'Analytics', href: '/admin/analytics', icon: BarChart3, chip: 'bg-[#fef3c7] text-[#b45309]' },
    { name: 'Team', href: '/admin/team', icon: Users2, chip: 'bg-[#ccfbf1] text-[#0f766e]' },
    { name: 'Webhooks', href: '/admin/webhooks', icon: Webhook, chip: 'bg-[#e0f2fe] text-[#0369a1]' },
    { name: 'Billing', href: '/admin/billing', icon: CreditCard, chip: 'bg-[#dcfce7] text-[#166534]' },
    { name: 'Settings', href: '/admin/settings', icon: Settings, chip: 'bg-[#f1f5f9] text-[#475569]' },
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const getPageTitle = () => {
    const path = location.pathname;
    if (path.includes('/dashboard')) return 'Dashboard';
    if (path.includes('/funnels')) return 'Funnels';
    if (path.includes('/submissions')) return 'Leads & Submissions';
    if (path.includes('/analytics')) return 'Analytics';
    if (path.includes('/team')) return 'Team';
    if (path.includes('/webhooks')) return 'Webhooks';
    if (path.includes('/billing')) return 'Billing';
    if (path.includes('/settings')) return 'Settings';
    return 'Overview';
  };

  return (
    <div className="min-h-screen bg-[#f8fafc] text-[#131416] flex font-sans">
      {/* Mobile Sidebar Backdrop */}
      {mobileSidebarOpen && (
        <div 
          className="fixed inset-0 bg-slate-900/30 z-40 lg:hidden transition-opacity"
          onClick={() => setMobileSidebarOpen(false)}
        />
      )}

      {/* Clean Solid Sidebar (240px with 1px right border) */}
      <aside className={`
        fixed inset-y-0 left-0 z-50 w-[240px] h-screen bg-white border-r border-[#e2e5e9] flex flex-col justify-between transition-transform duration-150 ease-out
        ${mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}>
        <div className="flex flex-col h-full overflow-hidden">
          {/* Workspace Switcher Header */}
          <div className="h-14 flex items-center px-4 border-b border-[#e2e5e9] bg-white shrink-0 justify-between relative">
            <button 
              onClick={() => setWorkspaceMenuOpen(!workspaceMenuOpen)}
              className="flex items-center gap-2.5 text-left w-full hover:bg-[#f8fafc] p-1.5 rounded-xl transition"
            >
              <div className="w-8 h-8 rounded-lg bg-[#1d5fd6] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                {activeTenant?.name?.charAt(0) || 'L'}
              </div>
              <div className="flex-1 min-w-0">
                <span className="block text-xs font-bold text-[#131416] truncate">
                  {activeTenant?.name || 'Workspace'}
                </span>
                <span className="block text-[11px] text-[#5d5f60] truncate">
                  {limits?.plan_name ? `${limits.plan_name} plan` : 'Pro plan'}
                </span>
              </div>
              <ChevronDown className="w-3.5 h-3.5 text-[#5d5f60] shrink-0" />
            </button>

            <button
              onClick={() => setMobileSidebarOpen(false)}
              className="lg:hidden p-1 text-[#5d5f60] hover:text-[#131416] rounded-md"
            >
              <X className="w-4 h-4" />
            </button>

            {/* Workspace Dropdown */}
            {workspaceMenuOpen && (
              <div className="absolute top-full left-3 right-3 mt-1 bg-white border border-[#e2e5e9] rounded-xl shadow-xs z-50 py-1.5">
                <div className="px-3 py-1 text-[11px] font-bold text-[#5d5f60]">
                  Your Workspaces
                </div>
                {userTenants.map((t) => (
                  <button
                    key={t.tenant_id}
                    onClick={() => {
                      switchWorkspace(t.tenant_id);
                      setWorkspaceMenuOpen(false);
                    }}
                    className={`w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-[#f8fafc] transition ${
                      t.tenant_id === activeTenant?.id ? 'text-[#1d5fd6] font-bold bg-[#dcf1ff]/40' : 'text-[#131416]'
                    }`}
                  >
                    <span className="truncate">{t.tenant_name || `Workspace #${t.tenant_id}`}</span>
                    {t.tenant_id === activeTenant?.id && (
                      <span className="w-1.5 h-1.5 rounded-full bg-[#1d5fd6]" />
                    )}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Navigation Links */}
          <div className="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
            <div className="px-2.5 pb-1.5 text-[11px] font-bold text-[#5d5f60]">
              Workspace Navigation
            </div>

            {navigation.map((item) => {
              const isActive = location.pathname.startsWith(item.href);
              const Icon = item.icon;
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  onClick={() => setMobileSidebarOpen(false)}
                  className={`flex items-center gap-2.5 px-3 h-9 rounded-xl text-xs transition ${
                    isActive
                      ? 'bg-[#1d5fd6] text-white font-semibold shadow-xs'
                      : 'text-[#131416] hover:bg-[#f4f6f8] font-medium'
                  }`}
                >
                  <div className={`w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition ${
                    isActive ? 'bg-white/20 text-white' : item.chip
                  }`}>
                    <Icon className="w-3.5 h-3.5 stroke-[2]" />
                  </div>
                  <span className="truncate">{item.name}</span>
                </Link>
              );
            })}

            {isPlatformAdmin && (
              <div className="pt-3 mt-3 border-t border-[#e2e5e9]">
                <div className="px-2.5 pb-1 text-[11px] font-bold text-[#5d5f60]">
                  Super Admin
                </div>
                <Link
                  to="/admin/platform"
                  onClick={() => setMobileSidebarOpen(false)}
                  className="flex items-center gap-2.5 px-3 h-9 rounded-xl text-xs transition text-[#131416] hover:bg-[#f4f6f8] font-medium"
                >
                  <div className="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 bg-[#fef3c7] text-[#b45309]">
                    <ShieldCheck className="w-3.5 h-3.5 stroke-[2]" />
                  </div>
                  <span>Platform Console</span>
                </Link>
              </div>
            )}
          </div>

          {/* Quota & Usage Widget */}
          {(() => {
            const isUnlimited = !limits || limits.max_funnels <= 0 || limits.plan_slug === 'pro';
            const currentFunnels = limits?.current_funnels ?? 0;
            const maxFunnels = limits?.max_funnels ?? 1;
            const pct = maxFunnels > 0 ? Math.min(100, Math.max(4, Math.round((currentFunnels / maxFunnels) * 100))) : 0;

            return (
              <div className="px-3 pb-3">
                <div className="p-3 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-2">
                  <div className="flex items-center justify-between text-[11px]">
                    <div className="flex items-center gap-1.5 min-w-0">
                      <span className="font-bold text-[#131416] truncate">
                        {limits?.plan_name ? `${limits.plan_name} Plan` : 'Pro Plan'}
                      </span>
                      {isUnlimited && (
                        <span className="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#dcfae5] text-[#2f7a45] uppercase tracking-wider shrink-0">
                          Active
                        </span>
                      )}
                    </div>
                    <Link
                      to="/admin/billing"
                      className="text-[#1d5fd6] font-semibold hover:underline shrink-0 text-[11px]"
                    >
                      {isUnlimited ? 'Manage' : 'Upgrade'}
                    </Link>
                  </div>

                  {isUnlimited ? (
                    <div className="flex items-center gap-1.5 text-[10px] text-[#2f7a45] font-medium pt-0.5">
                      <CheckCircle2 className="w-3.5 h-3.5 text-[#2f7a45] shrink-0" />
                      <span className="truncate">Unlimited funnels & workflows</span>
                    </div>
                  ) : (
                    <>
                      <div className="w-full bg-[#e2e5e9] h-1.5 rounded-full overflow-hidden">
                        <div
                          className="bg-[#1d5fd6] h-full rounded-full transition-[width] duration-300"
                          style={{ width: `${pct}%` }}
                        />
                      </div>
                      <span className="text-[10px] text-[#5d5f60] block truncate">
                        {currentFunnels} of {maxFunnels} funnels used
                      </span>
                    </>
                  )}
                </div>
              </div>
            );
          })()}

          {/* User Profile Footer */}
          <div className="p-3 border-t border-[#e2e5e9] bg-white shrink-0">
            <div className="flex items-center justify-between p-2 rounded-xl bg-[#f8fafc] border border-[#e2e5e9]">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="w-7 h-7 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] font-bold text-xs flex items-center justify-center shrink-0 border border-[#b9e6fe]">
                  {user?.name?.charAt(0) || 'U'}
                </div>
                <div className="min-w-0">
                  <p className="text-xs font-bold text-[#131416] truncate">{user?.name || 'User'}</p>
                  <p className="text-[11px] text-[#5d5f60] truncate">{user?.email}</p>
                </div>
              </div>
              <button
                onClick={handleLogout}
                title="Sign Out"
                className="p-1.5 text-[#5d5f60] hover:text-[#991b1b] hover:bg-[#fee2e2]/40 rounded-lg transition"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col lg:pl-[240px] min-w-0">
        {/* Sticky Top Header */}
        <header className="h-14 border-b border-[#e2e5e9] px-6 flex items-center justify-between shrink-0 bg-white sticky top-0 z-30">
          <div className="flex items-center gap-3">
            <button
              onClick={() => setMobileSidebarOpen(true)}
              className="lg:hidden p-1.5 rounded-lg text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition"
              title="Open Navigation Menu"
            >
              <Menu className="w-4 h-4" />
            </button>
            <div className="flex items-center gap-2 text-xs">
              <span className="text-[#5d5f60]">{activeTenant?.name || 'Workspace'}</span>
              <span className="text-[#5d5f60]">/</span>
              <span className="font-bold text-[#131416]">
                {getPageTitle()}
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <Link
              to="/admin/funnels/create"
              className="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
            >
              <Plus className="w-3.5 h-3.5" />
              <span>Create Funnel</span>
            </Link>
          </div>
        </header>

        {/* Global Workspace Suspended Warning Banner */}
        {activeTenant?.status === 'suspended' && (
          <div className="bg-[#fef2f2] border-b border-[#fecaca] px-6 py-2.5 flex items-center justify-between text-xs text-[#991b1b] shrink-0">
            <div className="flex items-center gap-2">
              <AlertTriangle className="w-4 h-4 shrink-0 text-[#dc2626]" />
              <span>
                <strong>Workspace Suspended:</strong> This workspace has been suspended. All active funnels are currently paused and public visitor submissions are disabled.
              </span>
            </div>
            <Link
              to="/admin/billing"
              className="px-2.5 py-1 rounded-md bg-[#dc2626] text-white font-semibold text-[11px] hover:bg-[#b91c1c] transition shrink-0"
            >
              Resolve Subscription
            </Link>
          </div>
        )}

        {/* Content Sheet */}
        <main className="flex-1 p-6 sm:p-8 overflow-y-auto min-w-0 bg-[#f8fafc]">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

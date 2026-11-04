import React, { useState } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import {
  ShieldCheck,
  LayoutDashboard,
  Users,
  Layers,
  CreditCard,
  FileText,
  LogOut,
  Menu,
  X,
  Activity,
  CheckCircle2,
  Scale,
  Code2,
  UserCheck
} from 'lucide-react';

export const SuperAdminLayout: React.FC = () => {
  const { user, logout } = useAuthStore();
  const location = useLocation();
  const navigate = useNavigate();
  const [mobileOpen, setMobileOpen] = useState(false);

  const navigation = [
    { name: 'Platform Overview', href: '/admin/platform', icon: LayoutDashboard, chip: 'bg-[#dcf1ff] text-[#1d5fd6]' },
    { name: 'Customers & Workspaces', href: '/admin/platform/customers', icon: Users, chip: 'bg-[#dcfae5] text-[#2f7a45]' },
    { name: 'Plans & Feature Quotas', href: '/admin/platform/plans', icon: Layers, chip: 'bg-[#fef3c7] text-[#b45309]' },
    { name: 'Payment Gateways', href: '/admin/platform/payment-gateways', icon: CreditCard, chip: 'bg-[#e0e7ff] text-[#3730a3]' },
    { name: 'Compliance & Legal CMS', href: '/admin/platform/legal-pages', icon: Scale, chip: 'bg-[#fce7f3] text-[#9d174d]' },
    { name: 'Developer & API Docs', href: '/admin/platform/docs', icon: Code2, chip: 'bg-[#ede9fe] text-[#5b21b6]' },
    { name: 'System Audit Logs', href: '/admin/platform/logs', icon: FileText, chip: 'bg-[#ccfbf1] text-[#0f766e]' },
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen bg-[#f8fafc] text-[#131416] flex font-sans">
      {/* Mobile Backdrop */}
      {mobileOpen && (
        <div
          className="fixed inset-0 bg-slate-900/30 z-40 lg:hidden transition-opacity"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Modern Super Admin Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-[240px] h-screen bg-white border-r border-[#e2e5e9] flex flex-col justify-between transition-transform duration-150 ease-out ${
          mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <div className="flex flex-col h-full overflow-hidden">
          {/* Brand Header */}
          <div className="h-14 flex items-center px-4 border-b border-[#e2e5e9] bg-white shrink-0 justify-between">
            <Link to="/admin/platform" className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-[#1d5fd6] text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                <ShieldCheck className="w-4 h-4" />
              </div>
              <div className="min-w-0">
                <div className="flex items-center gap-1.5">
                  <span className="font-bold text-xs text-[#131416] tracking-tight">QFunnel</span>
                  <span className="text-[10px] font-bold text-[#1d5fd6] bg-[#dcf1ff] px-1.5 py-0.5 rounded border border-[#b9e6fe]">
                    Super Admin
                  </span>
                </div>
                <span className="text-[10px] text-[#5d5f60] block truncate">Platform Console</span>
              </div>
            </Link>

            <button
              onClick={() => setMobileOpen(false)}
              className="lg:hidden p-1 text-[#5d5f60] hover:text-[#131416] rounded-md"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          {/* Navigation Links */}
          <div className="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
            <div className="px-2.5 pb-1.5 text-[11px] font-bold text-[#5d5f60]">
              Platform Management
            </div>

            {navigation.map((item) => {
              const isActive = location.pathname === item.href;
              const Icon = item.icon;
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  onClick={() => setMobileOpen(false)}
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
          </div>

          {/* Super Admin Profile Footer */}
          <div className="p-3 border-t border-[#e2e5e9] bg-white shrink-0">
            <div className="flex items-center justify-between p-2 rounded-xl bg-[#f8fafc] border border-[#e2e5e9]">
              <Link
                to="/admin/platform/profile"
                className="flex items-center gap-2.5 min-w-0 group hover:opacity-80 transition"
                title="Super Admin Profile & Security"
              >
                <div className="w-7 h-7 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] font-bold text-xs flex items-center justify-center shrink-0 border border-[#b9e6fe]">
                  {user?.name?.charAt(0) || 'S'}
                </div>
                <div className="min-w-0">
                  <p className="text-xs font-bold text-[#131416] truncate group-hover:text-[#1d5fd6]">{user?.name || 'Super Admin'}</p>
                  <p className="text-[11px] text-[#5d5f60] truncate">{user?.email}</p>
                </div>
              </Link>
              <button
                onClick={handleLogout}
                title="Sign Out"
                className="p-1.5 text-[#5d5f60] hover:text-[#991b1b] hover:bg-[#fee2e2]/40 rounded-lg transition shrink-0"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col lg:pl-[240px] min-w-0">
        {/* Top Header */}
        <header className="h-14 border-b border-[#e2e5e9] px-6 flex items-center justify-between shrink-0 bg-white sticky top-0 z-30">
          <div className="flex items-center gap-3">
            <button
              onClick={() => setMobileOpen(true)}
              className="lg:hidden p-1.5 rounded-lg text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition"
              title="Toggle Menu"
            >
              <Menu className="w-4 h-4" />
            </button>
            <div className="flex items-center gap-2">
              <span className="text-xs font-bold text-[#131416]">Platform Administration Console</span>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2f7a45]" />
              <span>Multi-Tenant Cluster Operational</span>
            </span>
          </div>
        </header>

        {/* Content Sheet */}
        <main className="flex-1 p-6 sm:p-8 overflow-y-auto min-w-0">
          <Outlet />
        </main>
      </div>
    </div>
  );
};
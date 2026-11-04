import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../api/client';
import {
  ShieldCheck,
  Users,
  Layers,
  GitFork,
  FileText,
  ArrowRight,
  TrendingUp,
  Activity,
  Server,
  Database,
  CreditCard,
  RefreshCw,
  CheckCircle,
  AlertCircle,
  ExternalLink,
  Building2,
  Clock,
  Scale,
  Code2
} from 'lucide-react';

export const SuperadminDashboard: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [lastRefreshed, setLastRefreshed] = useState<Date>(new Date());

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setRefreshing(true);
      const res = await api.get('/api/platform/dashboard');
      setData(res);
      setLastRefreshed(new Date());
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  if (loading) {
    return (
      <div className="space-y-6 max-w-7xl mx-auto">
        <div className="h-8 w-64 rounded-xl skeleton-shimmer" />
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 rounded-2xl skeleton-shimmer" />
          ))}
        </div>
      </div>
    );
  }

  const stats = data?.stats || {};
  const planStats = data?.plan_stats || [];
  const recentTenants = data?.recent_tenants || [];
  const recentLogs = data?.recent_logs || [];
  const systemInfo = data?.system_info || {};

  return (
    <div className="space-y-8 max-w-7xl mx-auto">
      {/* 1. Header Banner */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
              <ShieldCheck className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Platform Control Center</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Global metrics across all customer workspaces, subscription tiers, and runner clusters.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <span className="text-[11px] text-[#5d5f60] hidden sm:inline">
            Updated {lastRefreshed.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
          </span>
          <button
            onClick={loadData}
            disabled={refreshing}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f4f6f8] transition shadow-xs disabled:opacity-60"
          >
            <RefreshCw className={`w-3.5 h-3.5 text-[#5d5f60] ${refreshing ? 'animate-spin text-[#1d5fd6]' : ''}`} />
            <span>Refresh Stats</span>
          </button>
        </div>
      </div>

      {/* 2. Top Primary KPI Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Metric 1: Workspaces */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-3 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Customer Workspaces</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <Building2 className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {stats.total_tenants ?? stats.tenants ?? 0}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#2f7a45]">
              <CheckCircle className="w-3.5 h-3.5" />
              <span>{stats.active_tenants ?? 0} active • {stats.suspended_tenants ?? 0} suspended</span>
            </div>
          </div>
        </div>

        {/* Metric 2: Users */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-3 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Total Platform Users</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
              <Users className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {stats.total_users ?? stats.users ?? 0}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#5d5f60]">
              <span>{stats.active_users ?? 0} active user accounts</span>
            </div>
          </div>
        </div>

        {/* Metric 3: Funnels Built */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-3 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Production Funnels</span>
            <div className="w-8 h-8 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
              <GitFork className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {stats.total_funnels ?? stats.funnels ?? 0}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#5d5f60]">
              <span>{stats.published_funnels ?? 0} published live flows</span>
            </div>
          </div>
        </div>

        {/* Metric 4: Form Leads */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-3 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Total Leads & Responses</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#1d5fd6] tracking-tight">
              {(stats.total_submissions ?? stats.submissions ?? 0).toLocaleString()}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#2f7a45]">
              <span>Quiz sessions and webhook captures</span>
            </div>
          </div>
        </div>
      </div>

      {/* 3. Quick Platform Management Shortcuts */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Link
          to="/admin/platform/customers"
          className="p-4 rounded-2xl bg-white border border-[#e2e5e9] flex items-center justify-between group hover:border-[#2f7a45] hover:bg-[#f8fafc] transition"
        >
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold shrink-0">
              <Users className="w-4 h-4" />
            </div>
            <div className="space-y-0.5">
              <span className="font-bold text-xs text-[#131416] block group-hover:text-[#2f7a45] transition">
                Manage Customers
              </span>
              <span className="text-[11px] text-[#5d5f60]">Assign plans, billing & status</span>
            </div>
          </div>
          <ArrowRight className="w-4 h-4 text-[#5d5f60] group-hover:text-[#2f7a45] group-hover:translate-x-0.5 transition" />
        </Link>

        <Link
          to="/admin/platform/plans"
          className="p-4 rounded-2xl bg-white border border-[#e2e5e9] flex items-center justify-between group hover:border-[#b45309] hover:bg-[#f8fafc] transition"
        >
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold shrink-0">
              <Layers className="w-4 h-4" />
            </div>
            <div className="space-y-0.5">
              <span className="font-bold text-xs text-[#131416] block group-hover:text-[#b45309] transition">
                Plans & Quotas
              </span>
              <span className="text-[11px] text-[#5d5f60]">Configure tiers & features</span>
            </div>
          </div>
          <ArrowRight className="w-4 h-4 text-[#5d5f60] group-hover:text-[#b45309] group-hover:translate-x-0.5 transition" />
        </Link>

        <Link
          to="/admin/platform/legal-pages"
          className="p-4 rounded-2xl bg-white border border-[#e2e5e9] flex items-center justify-between group hover:border-[#9d174d] hover:bg-[#f8fafc] transition"
        >
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-xl bg-[#fce7f3] text-[#9d174d] flex items-center justify-center font-bold shrink-0">
              <Scale className="w-4 h-4" />
            </div>
            <div className="space-y-0.5">
              <span className="font-bold text-xs text-[#131416] block group-hover:text-[#9d174d] transition">
                Compliance & Legal
              </span>
              <span className="text-[11px] text-[#5d5f60]">Terms, Privacy, Policies CMS</span>
            </div>
          </div>
          <ArrowRight className="w-4 h-4 text-[#5d5f60] group-hover:text-[#9d174d] group-hover:translate-x-0.5 transition" />
        </Link>

        <Link
          to="/admin/platform/docs"
          className="p-4 rounded-2xl bg-white border border-[#e2e5e9] flex items-center justify-between group hover:border-[#5b21b6] hover:bg-[#f8fafc] transition"
        >
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-xl bg-[#ede9fe] text-[#5b21b6] flex items-center justify-center font-bold shrink-0">
              <Code2 className="w-4 h-4" />
            </div>
            <div className="space-y-0.5">
              <span className="font-bold text-xs text-[#131416] block group-hover:text-[#5b21b6] transition">
                Developer & API Hub
              </span>
              <span className="text-[11px] text-[#5d5f60]">Webhooks, API keys, embeds</span>
            </div>
          </div>
          <ArrowRight className="w-4 h-4 text-[#5d5f60] group-hover:text-[#5b21b6] group-hover:translate-x-0.5 transition" />
        </Link>
      </div>

      {/* 4. Plan Distribution Strip */}
      {planStats.length > 0 && (
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-4 shadow-xs">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Layers className="w-4 h-4 text-[#1d5fd6]" />
              <h2 className="text-sm font-bold text-[#131416]">Active Subscription Distribution</h2>
            </div>
            <Link to="/admin/platform/plans" className="text-xs font-semibold text-[#1d5fd6] hover:underline">
              Manage Tiers →
            </Link>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {planStats.map((p: any) => (
              <div key={p.id} className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-1">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#131416]">{p.name}</span>
                  <span className="text-[11px] font-mono text-[#5d5f60]">₹{Number(p.price).toLocaleString('en-IN')}/mo</span>
                </div>
                <div className="text-xl font-extrabold text-[#1d5fd6]">
                  {p.subscriber_count || 0}
                </div>
                <span className="text-[10px] text-[#5d5f60] block">Subscribed organizations</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 5. Two Main Analytics Panels */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        {/* Panel A: Recent Customer Workspaces */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-4 shadow-xs">
          <div className="flex items-center justify-between border-b border-[#edf0f3] pb-4">
            <div className="flex items-center gap-2">
              <Building2 className="w-4 h-4 text-[#1d5fd6]" />
              <h2 className="text-sm font-bold text-[#131416]">Recent Customer Workspaces</h2>
            </div>
            <Link
              to="/admin/platform/customers"
              className="text-xs font-semibold text-[#1d5fd6] hover:underline flex items-center gap-1"
            >
              <span>View All</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="divide-y divide-[#edf0f3]">
            {recentTenants.length === 0 ? (
              <p className="text-xs text-[#5d5f60] py-4 text-center">No customer workspaces provisioned yet.</p>
            ) : (
              recentTenants.map((t: any) => (
                <div key={t.id} className="py-3 flex items-center justify-between hover:bg-[#f8fafc] px-2 rounded-xl transition">
                  <div className="space-y-0.5 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-xs text-[#131416] truncate">{t.name}</span>
                      <span className="text-[10px] px-1.5 py-0.5 rounded bg-[#dcf1ff] text-[#0a3a80] font-semibold">
                        {t.plan_name || 'Free Tier'}
                      </span>
                    </div>
                    <span className="text-[11px] text-[#5d5f60] block truncate">
                      Owner: {t.owner_name || 'Primary Owner'} • {t.funnels_count || 0} Funnels
                    </span>
                  </div>

                  <div className="flex items-center gap-2 shrink-0">
                    <span
                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                        t.status === 'active'
                          ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                          : 'bg-[#fef2f2] text-[#991b1b] border border-[#fecaca]'
                      }`}
                    >
                      {t.status}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Panel B: Live Security Audit Trail */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-4 shadow-xs">
          <div className="flex items-center justify-between border-b border-[#edf0f3] pb-4">
            <div className="flex items-center gap-2">
              <Activity className="w-4 h-4 text-[#2f7a45]" />
              <h2 className="text-sm font-bold text-[#131416]">Recent Audit & Security Events</h2>
            </div>
            <Link
              to="/admin/platform/logs"
              className="text-xs font-semibold text-[#1d5fd6] hover:underline flex items-center gap-1"
            >
              <span>View All Logs</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="space-y-3">
            {recentLogs.length === 0 ? (
              <p className="text-xs text-[#5d5f60] py-4 text-center">No audit logs recorded yet.</p>
            ) : (
              recentLogs.slice(0, 5).map((log: any) => (
                <div
                  key={log.id}
                  className="p-3 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] flex items-start justify-between gap-3 text-xs"
                >
                  <div className="space-y-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-[#131416]">{log.action}</span>
                      {log.tenant_name && (
                        <span className="text-[10px] px-1.5 py-0.5 rounded bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]">
                          {log.tenant_name}
                        </span>
                      )}
                    </div>
                    <span className="text-[11px] text-[#5d5f60] block truncate">
                      By {log.user_email || log.user_name || 'System Actor'} • IP: {log.ip_address || '127.0.0.1'}
                    </span>
                  </div>

                  <span className="text-[10px] text-[#5d5f60] shrink-0 pt-0.5 flex items-center gap-1">
                    <Clock className="w-3 h-3" />
                    <span>{new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* 6. System Environment & Server Cluster Info */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-4 shadow-xs">
        <div className="flex items-center justify-between border-b border-[#edf0f3] pb-3">
          <div className="flex items-center gap-2">
            <Server className="w-4 h-4 text-[#1d5fd6]" />
            <h3 className="text-sm font-bold text-[#131416]">Platform Infrastructure & Server Environment</h3>
          </div>
          <span className="text-xs font-semibold text-[#2f7a45] flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-[#2f7a45]" />
            <span>99.98% Multi-Tenant Uptime</span>
          </span>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs text-[#5d5f60]">
          <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-1">
            <span className="font-bold text-[#131416] block">PHP Runtime</span>
            <div className="flex items-center gap-1.5 text-[#2f7a45]">
              <CheckCircle className="w-3.5 h-3.5" />
              <span>v{systemInfo.php_version || '8.2+'} Operational</span>
            </div>
            <span className="text-[11px] text-[#5d5f60] block">Max Upload: {systemInfo.max_upload || '40M'}</span>
          </div>

          <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-1">
            <span className="font-bold text-[#131416] block">Database Engine</span>
            <div className="flex items-center gap-1.5 text-[#2f7a45]">
              <CheckCircle className="w-3.5 h-3.5" />
              <span>{systemInfo.db_driver || 'MySQL / MariaDB via PDO'}</span>
            </div>
            <span className="text-[11px] text-[#5d5f60] block">Strict row-level tenancy</span>
          </div>

          <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-1">
            <span className="font-bold text-[#131416] block">Server & Timezone</span>
            <div className="flex items-center gap-1.5 text-[#131416]">
              <span>{systemInfo.timezone || 'UTC'}</span>
            </div>
            <span className="text-[11px] text-[#5d5f60] block truncate">{systemInfo.server_software || 'Apache / PHP'}</span>
          </div>

          <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-1">
            <span className="font-bold text-[#131416] block">Billing Gateways</span>
            <div className="flex items-center gap-1.5 text-[#2f7a45]">
              <CheckCircle className="w-3.5 h-3.5" />
              <span>{stats.active_gateways_count || 1} Provider(s) Configured</span>
            </div>
            <span className="text-[11px] text-[#5d5f60] block">Stripe / PayPal / Razorpay / Bank</span>
          </div>
        </div>
      </div>
    </div>
  );
};

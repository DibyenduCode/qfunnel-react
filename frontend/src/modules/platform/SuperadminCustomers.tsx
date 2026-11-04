import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { TablePagination } from '../../components/TablePagination';
import {
  Users,
  Building2,
  Search,
  Filter,
  CheckCircle,
  AlertTriangle,
  CreditCard,
  Calendar,
  DollarSign,
  ShieldAlert,
  RotateCcw,
  Trash2,
  UserCheck,
  UserX,
  FileText,
  Clock,
  MoreVertical,
  Layers,
  ChevronDown,
  X
} from 'lucide-react';

export const SuperadminCustomers: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'tenants' | 'termination' | 'invoices' | 'users'>('tenants');
  const [customers, setCustomers] = useState<any[]>([]);
  const [plans, setPlans] = useState<any[]>([]);
  const [terminationDueList, setTerminationDueList] = useState<any[]>([]);
  const [invoices, setInvoices] = useState<any[]>([]);
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'suspended'>('all');
  const [userSearch, setUserSearch] = useState('');

  // Pagination states for all 4 tabs
  const [tenantsPage, setTenantsPage] = useState(1);
  const [tenantsPageSize, setTenantsPageSize] = useState(10);

  const [terminationPage, setTerminationPage] = useState(1);
  const [terminationPageSize, setTerminationPageSize] = useState(10);

  const [invoicesPage, setInvoicesPage] = useState(1);
  const [invoicesPageSize, setInvoicesPageSize] = useState(10);

  const [usersPage, setUsersPage] = useState(1);
  const [usersPageSize, setUsersPageSize] = useState(10);

  // Messages
  const [msg, setMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  // Modals state
  const [assignPlanModal, setAssignPlanModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [selectedPlanId, setSelectedPlanId] = useState<number>(0);
  const [assignStatus, setAssignStatus] = useState<string>('active');

  const [billingCycleModal, setBillingCycleModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [cyclePlanId, setCyclePlanId] = useState<number>(0);
  const [cycleInterval, setCycleInterval] = useState<string>('monthly');
  const [cycleStartDate, setCycleStartDate] = useState<string>('');
  const [cycleEndDate, setCycleEndDate] = useState<string>('');
  const [cycleStatus, setCycleStatus] = useState<string>('active');
  const [cycleAutoRenew, setCycleAutoRenew] = useState<boolean>(true);
  const [cycleAmount, setCycleAmount] = useState<number>(0);
  const [cycleCurrency, setCycleCurrency] = useState<string>('INR');

  const [recordPaymentModal, setRecordPaymentModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [payPlanId, setPayPlanId] = useState<number>(0);
  const [payAmount, setPayAmount] = useState<number>(0);
  const [payCurrency, setPayCurrency] = useState<string>('INR');
  const [payInterval, setPayInterval] = useState<string>('monthly');
  const [payMethod, setPayMethod] = useState<string>('bank_transfer');
  const [payTxRef, setPayTxRef] = useState<string>('');
  const [payNotes, setPayNotes] = useState<string>('');

  const [suspendModal, setSuspendModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [suspendReason, setSuspendReason] = useState<string>('Overdue subscription invoice payment');
  const [suspendNotes, setSuspendNotes] = useState<string>('');

  const [unsuspendModal, setUnsuspendModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [extendUntil, setExtendUntil] = useState<string>('');

  const [terminateModal, setTerminateModal] = useState<{ open: boolean; tenant: any | null }>({ open: false, tenant: null });
  const [confirmSlugInput, setConfirmSlugInput] = useState<string>('');
  const [terminateReason, setTerminateReason] = useState<string>('Administrative manual contract termination');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/customers');
      setCustomers(res.customers || []);
      setPlans(res.plans || []);
      setTerminationDueList(res.termination_due_list || []);
      setInvoices(res.invoices || []);
      setUsers(res.users || []);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load customer records');
    } finally {
      setLoading(false);
    }
  };

  const handleToggleTenant = async (tenant: any) => {
    try {
      setActionLoading(true);
      setMsg('');
      setErrorMsg('');
      const res = await api.post(`/api/platform/tenants/${tenant.id}/toggle`, {});
      setMsg(res.message || 'Tenant status updated');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to toggle status');
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleUser = async (user: any) => {
    try {
      setActionLoading(true);
      setMsg('');
      setErrorMsg('');
      const res = await api.post(`/api/platform/users/${user.id}/toggle-status`, {});
      setMsg(res.message || 'User account status updated');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to toggle user status');
    } finally {
      setActionLoading(false);
    }
  };

  const handleAssignPlanSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!assignPlanModal.tenant) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${assignPlanModal.tenant.id}/assign-plan`, {
        plan_id: selectedPlanId,
        status: assignStatus,
      });
      setAssignPlanModal({ open: false, tenant: null });
      setMsg(res.message || 'Plan assigned successfully');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to assign plan');
    } finally {
      setActionLoading(false);
    }
  };

  const handleBillingCycleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!billingCycleModal.tenant) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${billingCycleModal.tenant.id}/billing-cycle`, {
        plan_id: cyclePlanId,
        billing_interval: cycleInterval,
        current_period_start: cycleStartDate,
        current_period_end: cycleEndDate,
        status: cycleStatus,
        auto_renew: cycleAutoRenew,
        amount: cycleAmount,
        currency: cycleCurrency,
      });
      setBillingCycleModal({ open: false, tenant: null });
      setMsg(res.message || 'Billing cycle updated');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to update billing cycle');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRecordPaymentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!recordPaymentModal.tenant) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${recordPaymentModal.tenant.id}/record-payment`, {
        plan_id: payPlanId,
        amount: payAmount,
        currency: payCurrency,
        billing_interval: payInterval,
        payment_method: payMethod,
        transaction_reference: payTxRef,
        notes: payNotes,
      });
      setRecordPaymentModal({ open: false, tenant: null });
      setMsg(res.message || 'Offline payment recorded and invoice generated');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to record payment');
    } finally {
      setActionLoading(false);
    }
  };

  const handleSuspendSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!suspendModal.tenant) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${suspendModal.tenant.id}/suspend`, {
        reason: suspendReason,
        notes: suspendNotes,
      });
      setSuspendModal({ open: false, tenant: null });
      setMsg(res.message || 'Workspace suspended');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to suspend workspace');
    } finally {
      setActionLoading(false);
    }
  };

  const handleUnsuspendSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!unsuspendModal.tenant) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${unsuspendModal.tenant.id}/unsuspend`, {
        extend_until: extendUntil,
      });
      setUnsuspendModal({ open: false, tenant: null });
      setMsg(res.message || 'Workspace reactivated');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to unsuspend workspace');
    } finally {
      setActionLoading(false);
    }
  };

  const handleMarkTerminationDue = async (tenantId: number) => {
    const reason = prompt('Reason for marking termination due:', 'Overdue subscription past grace period');
    if (!reason) return;
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${tenantId}/mark-termination-due`, { reason });
      setMsg(res.message || 'Workspace marked for termination review');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to mark termination due');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDismissTerminationDue = async (tenantId: number) => {
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${tenantId}/dismiss-termination-due`, {});
      setMsg(res.message || 'Workspace dismissed from termination review');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to dismiss');
    } finally {
      setActionLoading(false);
    }
  };

  const handleTerminateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!terminateModal.tenant) return;
    if (confirmSlugInput.toLowerCase() !== terminateModal.tenant.slug.toLowerCase()) {
      alert(`Confirmation failed: You must type '${terminateModal.tenant.slug}' exactly.`);
      return;
    }
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/tenants/${terminateModal.tenant.id}/terminate`, {
        confirm_slug: confirmSlugInput,
        reason: terminateReason,
      });
      setTerminateModal({ open: false, tenant: null });
      setConfirmSlugInput('');
      setMsg(res.message || 'Workspace terminated');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to terminate workspace');
    } finally {
      setActionLoading(false);
    }
  };

  const filteredCustomers = customers.filter((c) => {
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.slug.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (c.owner_name && c.owner_name.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (c.owner_email && c.owner_email.toLowerCase().includes(searchQuery.toLowerCase()));
    const matchesStatus = statusFilter === 'all' || c.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const filteredUsers = users.filter((u) => {
    return (
      u.name.toLowerCase().includes(userSearch.toLowerCase()) ||
      u.email.toLowerCase().includes(userSearch.toLowerCase())
    );
  });

  const paginatedCustomers = filteredCustomers.slice(
    (tenantsPage - 1) * tenantsPageSize,
    tenantsPage * tenantsPageSize
  );

  const paginatedTermination = terminationDueList.slice(
    (terminationPage - 1) * terminationPageSize,
    terminationPage * terminationPageSize
  );

  const paginatedInvoices = invoices.slice(
    (invoicesPage - 1) * invoicesPageSize,
    invoicesPage * invoicesPageSize
  );

  const paginatedUsers = filteredUsers.slice(
    (usersPage - 1) * usersPageSize,
    usersPage * usersPageSize
  );

  const handleTenantSearchChange = (val: string) => {
    setSearchQuery(val);
    setTenantsPage(1);
  };

  const handleStatusFilterChange = (status: 'all' | 'active' | 'suspended') => {
    setStatusFilter(status);
    setTenantsPage(1);
  };

  const handleUserSearchChange = (val: string) => {
    setUserSearch(val);
    setUsersPage(1);
  };

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
              <Building2 className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Customer & Workspace Management</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Super admin controls for customer accounts, plan assignment, billing terms, manual payments, and account lifecycle.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <span className="px-3 py-1 rounded-full bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416]">
            Total: {customers.length} Workspaces • {users.length} Users
          </span>
        </div>
      </div>

      {msg && (
        <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 shrink-0" />
            <span>{msg}</span>
          </div>
          <button onClick={() => setMsg('')} className="text-xs opacity-70 hover:opacity-100 p-1" title="Dismiss">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {errorMsg && (
        <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{errorMsg}</span>
          </div>
          <button onClick={() => setErrorMsg('')} className="text-xs opacity-70 hover:opacity-100 p-1" title="Dismiss">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-[#e2e5e9] flex gap-2 sm:gap-6 text-xs font-semibold overflow-x-auto">
        <button
          type="button"
          onClick={() => setActiveTab('tenants')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'tenants'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          Workspaces ({customers.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('termination')}
          className={`pb-3 border-b-2 transition whitespace-nowrap flex items-center gap-1.5 ${
            activeTab === 'termination'
              ? 'border-[#991b1b] text-[#991b1b] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          <span>Termination Review Queue</span>
          {terminationDueList.length > 0 && (
            <span className="px-1.5 py-0.2 rounded-full text-[10px] bg-[#fef2f2] text-[#991b1b] border border-[#fecaca] font-bold">
              {terminationDueList.length}
            </span>
          )}
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('invoices')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'invoices'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          Billing & Invoices ({invoices.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('users')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'users'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          Platform Users ({users.length})
        </button>
      </div>

      {/* TAB 1: WORKSPACES */}
      {activeTab === 'tenants' && (
        <div className="space-y-4">
          {/* Search & Filter Bar */}
          <div className="flex flex-col sm:flex-row items-center gap-3">
            <div className="relative flex-1 w-full">
              <Search className="w-4 h-4 text-[#5d5f60] absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search by workspace, slug, or owner email..."
                value={searchQuery}
                onChange={(e) => handleTenantSearchChange(e.target.value)}
                className="w-full pl-9 pr-3.5 py-2 rounded-xl border border-[#e2e5e9] bg-white text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              {(['all', 'active', 'suspended'] as const).map((status) => (
                <button
                  key={status}
                  type="button"
                  onClick={() => handleStatusFilterChange(status)}
                  className={`px-3 py-1.5 rounded-xl text-xs font-semibold capitalize border transition ${
                    statusFilter === status
                      ? 'bg-[#1d5fd6] text-white border-[#1d5fd6]'
                      : 'bg-white text-[#5d5f60] border-[#e2e5e9] hover:bg-[#f8fafc]'
                  }`}
                >
                  {status}
                </button>
              ))}
            </div>
          </div>

          {/* Table */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
            {loading ? (
              <div className="p-6 divide-y divide-[#edf0f3]">
                {[1, 2, 3, 4, 5].map((i) => (
                  <div key={i} className="py-3 flex items-center justify-between">
                    <div className="h-4 w-40 rounded skeleton-shimmer" />
                    <div className="h-4 w-24 rounded skeleton-shimmer" />
                    <div className="h-4 w-16 rounded skeleton-shimmer" />
                  </div>
                ))}
              </div>
            ) : filteredCustomers.length === 0 ? (
              <div className="p-12 text-center text-[#5d5f60] space-y-2">
                <Building2 className="w-8 h-8 mx-auto opacity-50" />
                <p className="text-xs">No workspaces match your query.</p>
              </div>
            ) : (
              <>
                <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                  <thead>
                    <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                      <th className="py-3 px-4">Workspace & Owner</th>
                      <th className="py-3 px-4">Plan & Subscription</th>
                      <th className="py-3 px-4">Funnels / Leads</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4">Billing Period</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#edf0f3]">
                    {paginatedCustomers.map((t) => (
                      <tr key={t.id} className="hover:bg-[#f8fafc] transition">
                        <td className="py-3.5 px-4">
                          <div className="font-bold text-[#131416]">{t.name}</div>
                          <div className="text-[11px] font-mono text-[#1d5fd6]">/{t.slug}</div>
                          {t.owner_email && (
                            <div className="text-[10px] text-[#5d5f60] mt-0.5">
                              Owner: {t.owner_name || 'Admin'} ({t.owner_email})
                            </div>
                          )}
                        </td>

                        <td className="py-3.5 px-4">
                          <div className="font-semibold text-[#131416]">
                            {t.plan_name || 'Free Tier'}
                          </div>
                          <div className="text-[11px] text-[#5d5f60]">
                            {t.billing_interval || 'monthly'} • {t.subscription_amount ? `${t.subscription_currency || 'INR'} ${t.subscription_amount}` : 'Free'}
                          </div>
                        </td>

                        <td className="py-3.5 px-4">
                          <div className="font-bold text-[#131416]">{t.funnels_count || 0} Funnels</div>
                          <div className="text-[11px] text-[#5d5f60]">{(t.sessions_count || 0).toLocaleString()} Submissions</div>
                        </td>

                        <td className="py-3.5 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              t.status === 'active'
                                ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                                : 'bg-[#fef2f2] text-[#991b1b] border border-[#fecaca]'
                            }`}
                          >
                            {t.status}
                          </span>
                        </td>

                        <td className="py-3.5 px-4 text-[11px] text-[#5d5f60]">
                          {t.current_period_end ? (
                            <div>
                              <span>Renews: {new Date(t.current_period_end).toLocaleDateString()}</span>
                              <span className="block text-[10px]">{t.auto_renew ? 'Auto-renew ON' : 'Manual'}</span>
                            </div>
                          ) : (
                            <span>-</span>
                          )}
                        </td>

                        <td className="py-3.5 px-4 text-right">
                          <div className="inline-flex items-center gap-1.5 flex-wrap justify-end">
                            {/* Assign Plan */}
                            <button
                              type="button"
                              onClick={() => {
                                setAssignPlanModal({ open: true, tenant: t });
                                setSelectedPlanId(t.current_plan_id || (plans[0]?.id || 1));
                                setAssignStatus(t.subscription_status || 'active');
                              }}
                              title="Assign Plan"
                              className="px-2 py-1 rounded-lg border border-[#e2e5e9] bg-white hover:bg-[#f4f6f8] text-[#131416] text-[11px] font-semibold transition"
                            >
                              Assign Plan
                            </button>

                            {/* Billing Cycle */}
                            <button
                              type="button"
                              onClick={() => {
                                setBillingCycleModal({ open: true, tenant: t });
                                setCyclePlanId(t.current_plan_id || (plans[0]?.id || 1));
                                setCycleInterval(t.billing_interval || 'monthly');
                                setCycleStartDate(t.current_period_start ? t.current_period_start.split(' ')[0] : '');
                                setCycleEndDate(t.current_period_end ? t.current_period_end.split(' ')[0] : '');
                                setCycleStatus(t.subscription_status || 'active');
                                setCycleAutoRenew(Boolean(t.auto_renew));
                                setCycleAmount(Number(t.subscription_amount || 0));
                                setCycleCurrency(t.subscription_currency || 'INR');
                              }}
                              title="Modify Billing Cycle"
                              className="px-2 py-1 rounded-lg border border-[#e2e5e9] bg-white hover:bg-[#f4f6f8] text-[#131416] text-[11px] font-semibold transition"
                            >
                              Billing Cycle
                            </button>

                            {/* Record Payment */}
                            <button
                              type="button"
                              onClick={() => {
                                setRecordPaymentModal({ open: true, tenant: t });
                                setPayPlanId(t.current_plan_id || (plans[0]?.id || 1));
                                setPayAmount(Number(t.subscription_amount || t.plan_price || 0));
                                setPayCurrency(t.subscription_currency || 'INR');
                                setPayInterval(t.billing_interval || 'monthly');
                                setPayMethod('bank_transfer');
                                setPayTxRef('');
                                setPayNotes('');
                              }}
                              title="Record Offline Payment"
                              className="px-2 py-1 rounded-lg border border-[#e2e5e9] bg-[#f0fdf4] hover:bg-[#dcfce7] text-[#166534] text-[11px] font-semibold transition"
                            >
                              + Payment
                            </button>

                            {/* Suspend / Unsuspend */}
                            {t.status === 'active' ? (
                              <button
                                type="button"
                                onClick={() => {
                                  setSuspendModal({ open: true, tenant: t });
                                  setSuspendReason('Overdue subscription invoice payment');
                                  setSuspendNotes('');
                                }}
                                title="Suspend Workspace"
                                className="px-2 py-1 rounded-lg border border-[#fecaca] bg-[#fef2f2] hover:bg-[#fee2e2] text-[#991b1b] text-[11px] font-semibold transition"
                              >
                                Suspend
                              </button>
                            ) : (
                              <button
                                type="button"
                                onClick={() => {
                                  setUnsuspendModal({ open: true, tenant: t });
                                  setExtendUntil('');
                                }}
                                title="Reactivate Workspace"
                                className="px-2 py-1 rounded-lg border border-[#bbf7d0] bg-[#dcfae5] hover:bg-[#bbf7d0] text-[#166534] text-[11px] font-semibold transition"
                              >
                                Reactivate
                              </button>
                            )}

                            {/* Mark Termination Due */}
                            <button
                              type="button"
                              onClick={() => handleMarkTerminationDue(t.id)}
                              title="Flag for Termination Review"
                              className="p-1 rounded-lg border border-[#e2e5e9] hover:bg-[#fef2f2] hover:text-[#991b1b] text-[#5d5f60] transition"
                            >
                              <ShieldAlert className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <TablePagination
                currentPage={tenantsPage}
                totalItems={filteredCustomers.length}
                pageSize={tenantsPageSize}
                onPageChange={setTenantsPage}
                onPageSizeChange={setTenantsPageSize}
                pageSizeOptions={[10, 25, 50]}
                itemLabel="workspaces"
              />
            </>
          )}
          </div>
        </div>
      )}

      {/* TAB 2: TERMINATION DUE QUEUE */}
      {activeTab === 'termination' && (
        <div className="space-y-4">
          <div className="p-4 rounded-2xl bg-[#fffbeb] border border-[#fde68a] text-xs text-[#92400e] space-y-1">
            <h3 className="font-bold flex items-center gap-1.5">
              <AlertTriangle className="w-4 h-4" />
              <span>Super Admin Manual Review Queue</span>
            </h3>
            <p>
              Workspaces listed here have been flagged due to overdue subscriptions or non-payment. Workspaces are NEVER automatedly deleted by cron. Termination requires deliberate manual review and confirmation.
            </p>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
            {terminationDueList.length === 0 ? (
              <div className="p-12 text-center text-[#5d5f60] space-y-2">
                <CheckCircle className="w-8 h-8 text-[#2f7a45] mx-auto opacity-70" />
                <p className="text-xs font-semibold text-[#131416]">Termination Queue Empty</p>
                <p className="text-[11px]">All customer workspaces are in good standing.</p>
              </div>
            ) : (
              <>
                <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                  <thead>
                    <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                      <th className="py-3 px-4">Workspace</th>
                      <th className="py-3 px-4">Plan & Due Date</th>
                      <th className="py-3 px-4">Days Overdue</th>
                      <th className="py-3 px-4">Flagged Reason</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#edf0f3]">
                    {paginatedTermination.map((td) => (
                      <tr key={td.id} className="hover:bg-[#f8fafc] transition">
                        <td className="py-3.5 px-4 font-bold text-[#131416]">
                          {td.name}
                          <div className="text-[11px] font-mono text-[#5d5f60]">/{td.slug}</div>
                        </td>
                        <td className="py-3.5 px-4">
                          <span className="font-semibold text-[#131416]">{td.plan_name || 'Standard'}</span>
                          <span className="block text-[11px] text-[#5d5f60]">Due: {td.next_billing_date || td.current_period_end || 'N/A'}</span>
                        </td>
                        <td className="py-3.5 px-4 font-bold text-[#991b1b]">
                          {td.days_overdue || td.overdue_days || '0'} days
                        </td>
                        <td className="py-3.5 px-4 text-[11px] text-[#5d5f60]">
                          {td.reason || 'Overdue payment grace period exceeded'}
                        </td>
                        <td className="py-3.5 px-4 text-right">
                          <div className="inline-flex items-center gap-2">
                            <button
                              type="button"
                              onClick={() => handleDismissTerminationDue(td.id)}
                              className="px-2.5 py-1 rounded-lg border border-[#e2e5e9] bg-white text-xs font-semibold hover:bg-[#f8fafc] transition"
                            >
                              Dismiss Queue
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setUnsuspendModal({ open: true, tenant: td });
                                setExtendUntil('');
                              }}
                              className="px-2.5 py-1 rounded-lg border border-[#bbf7d0] bg-[#dcfae5] text-[#166534] text-xs font-semibold hover:bg-[#bbf7d0] transition"
                            >
                              Reactivate
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setTerminateModal({ open: true, tenant: td });
                                setConfirmSlugInput('');
                                setTerminateReason('Administrative manual contract termination');
                              }}
                              className="px-2.5 py-1 rounded-lg border border-[#fecaca] bg-[#fef2f2] text-[#991b1b] text-xs font-semibold hover:bg-[#fee2e2] transition"
                            >
                              Terminate...
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <TablePagination
                currentPage={terminationPage}
                totalItems={terminationDueList.length}
                pageSize={terminationPageSize}
                onPageChange={setTerminationPage}
                onPageSizeChange={setTerminationPageSize}
                pageSizeOptions={[10, 25, 50]}
                itemLabel="flagged accounts"
              />
            </>
          )}
          </div>
        </div>
      )}

      {/* TAB 3: BILLING & INVOICES LEDGER */}
      {activeTab === 'invoices' && (
        <div className="space-y-4">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
            {invoices.length === 0 ? (
              <div className="p-12 text-center text-[#5d5f60] space-y-2">
                <CreditCard className="w-8 h-8 mx-auto opacity-50" />
                <p className="text-xs">No invoices generated yet.</p>
              </div>
            ) : (
              <>
                <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                  <thead>
                    <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                      <th className="py-3 px-4">Invoice #</th>
                      <th className="py-3 px-4">Customer Workspace</th>
                      <th className="py-3 px-4">Plan / Interval</th>
                      <th className="py-3 px-4">Amount</th>
                      <th className="py-3 px-4">Payment Method</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4 text-right">Date</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#edf0f3]">
                    {paginatedInvoices.map((inv) => (
                      <tr key={inv.id} className="hover:bg-[#f8fafc] transition">
                        <td className="py-3.5 px-4 font-bold font-mono text-[#131416]">
                          #{inv.invoice_number || `INV-${inv.id}`}
                        </td>
                        <td className="py-3.5 px-4 font-semibold text-[#131416]">
                          {inv.tenant_name || `Tenant #${inv.tenant_id}`}
                        </td>
                        <td className="py-3.5 px-4">
                          {inv.plan_name || 'Standard'} ({inv.billing_interval || 'monthly'})
                        </td>
                        <td className="py-3.5 px-4 font-extrabold text-[#131416]">
                          {inv.currency || 'INR'} {Number(inv.amount || 0).toLocaleString()}
                        </td>
                        <td className="py-3.5 px-4 capitalize">
                          {inv.payment_method ? inv.payment_method.replace('_', ' ') : 'Manual'}
                        </td>
                        <td className="py-3.5 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              inv.status === 'paid'
                                ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                                : 'bg-[#fffbeb] text-[#b45309] border border-[#fde68a]'
                            }`}
                          >
                            {inv.status}
                          </span>
                        </td>
                        <td className="py-3.5 px-4 text-right text-[11px] text-[#5d5f60]">
                          {inv.created_at ? new Date(inv.created_at).toLocaleDateString() : '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <TablePagination
                currentPage={invoicesPage}
                totalItems={invoices.length}
                pageSize={invoicesPageSize}
                onPageChange={setInvoicesPage}
                onPageSizeChange={setInvoicesPageSize}
                pageSizeOptions={[10, 25, 50]}
                itemLabel="invoices"
              />
            </>
          )}
          </div>
        </div>
      )}

      {/* TAB 4: PLATFORM USERS */}
      {activeTab === 'users' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between gap-3">
            <div className="relative flex-1 max-w-md">
              <Search className="w-4 h-4 text-[#5d5f60] absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search users by name or email..."
                value={userSearch}
                onChange={(e) => handleUserSearchChange(e.target.value)}
                className="w-full pl-9 pr-3.5 py-2 rounded-xl border border-[#e2e5e9] bg-white text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>
            <span className="text-xs font-semibold text-[#5d5f60]">
              Showing {filteredUsers.length} user accounts
            </span>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
            {filteredUsers.length === 0 ? (
              <div className="p-12 text-center text-[#5d5f60] space-y-2">
                <Users className="w-8 h-8 mx-auto opacity-50" />
                <p className="text-xs">No users matching your search.</p>
              </div>
            ) : (
              <>
                <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                  <thead>
                    <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                      <th className="py-3 px-4">User</th>
                      <th className="py-3 px-4">Workspace Memberships</th>
                      <th className="py-3 px-4">Role / Permissions</th>
                      <th className="py-3 px-4">Account Status</th>
                      <th className="py-3 px-4">Joined Date</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#edf0f3]">
                    {paginatedUsers.map((u) => (
                      <tr key={u.id} className="hover:bg-[#f8fafc] transition">
                        <td className="py-3.5 px-4">
                          <div className="font-bold text-[#131416]">{u.name}</div>
                          <div className="text-[11px] text-[#5d5f60]">{u.email}</div>
                        </td>

                        <td className="py-3.5 px-4 text-[11px] text-[#131416]">
                          {u.workspace_names || `${u.workspace_count || 1} Workspace(s)`}
                        </td>

                        <td className="py-3.5 px-4">
                          {u.is_platform_admin ? (
                            <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-[#dcf1ff] text-[#1d5fd6] border border-[#b9e6fe]">
                              Super Admin
                            </span>
                          ) : (
                            <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#f4f6f8] text-[#5d5f60]">
                              Standard User
                            </span>
                          )}
                        </td>

                        <td className="py-3.5 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              u.status === 'active'
                                ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                                : 'bg-[#fef2f2] text-[#991b1b] border border-[#fecaca]'
                            }`}
                          >
                            {u.status}
                          </span>
                        </td>

                        <td className="py-3.5 px-4 text-[11px] text-[#5d5f60]">
                          {u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}
                        </td>

                        <td className="py-3.5 px-4 text-right">
                          {!u.is_platform_admin && (
                            <button
                              type="button"
                              onClick={() => handleToggleUser(u)}
                              className={`px-2.5 py-1 rounded-lg border text-xs font-semibold transition ${
                                u.status === 'active'
                                    ? 'border-[#fecaca] bg-[#fef2f2] text-[#991b1b] hover:bg-[#fee2e2]'
                                    : 'border-[#bbf7d0] bg-[#dcfae5] text-[#166534] hover:bg-[#bbf7d0]'
                              }`}
                            >
                              {u.status === 'active' ? 'Suspend' : 'Reactivate'}
                            </button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <TablePagination
                currentPage={usersPage}
                totalItems={filteredUsers.length}
                pageSize={usersPageSize}
                onPageChange={setUsersPage}
                onPageSizeChange={setUsersPageSize}
                pageSizeOptions={[10, 25, 50]}
                itemLabel="user accounts"
              />
            </>
          )}
          </div>
        </div>
      )}

      {/* MODAL 1: ASSIGN PLAN */}
      {assignPlanModal.open && assignPlanModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#131416]">Assign Plan to Workspace</h3>
              <button
                type="button"
                onClick={() => setAssignPlanModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-[#5d5f60]">
              Assigning a plan immediately updates quota limits for <strong>{assignPlanModal.tenant.name}</strong>.
            </p>

            <form onSubmit={handleAssignPlanSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Select Subscription Plan *</label>
                <select
                  value={selectedPlanId}
                  onChange={(e) => setSelectedPlanId(Number(e.target.value))}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white focus:outline-hidden focus:border-[#1d5fd6]"
                >
                  {plans.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} (₹{Number(p.price).toLocaleString('en-IN')}/{p.billing_interval || 'mo'})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Subscription Status</label>
                <select
                  value={assignStatus}
                  onChange={(e) => setAssignStatus(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white focus:outline-hidden focus:border-[#1d5fd6]"
                >
                  <option value="active">Active (Paid)</option>
                  <option value="trial">Trial Period</option>
                </select>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setAssignPlanModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] disabled:opacity-60"
                >
                  {actionLoading ? 'Assigning...' : 'Assign Plan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 2: BILLING CYCLE */}
      {billingCycleModal.open && billingCycleModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#131416]">Modify Billing Cycle & Terms</h3>
              <button
                type="button"
                onClick={() => setBillingCycleModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-[#5d5f60]">
              Modify period dates, recurring price, and auto-renew behavior for <strong>{billingCycleModal.tenant.name}</strong>.
            </p>

            <form onSubmit={handleBillingCycleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Plan</label>
                  <select
                    value={cyclePlanId}
                    onChange={(e) => setCyclePlanId(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    {plans.map((p) => (
                      <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Interval</label>
                  <select
                    value={cycleInterval}
                    onChange={(e) => setCycleInterval(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">3 Months (Quarterly)</option>
                    <option value="semi_annual">6 Months (Semi-Annual)</option>
                    <option value="yearly">Yearly</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Period Start</label>
                  <input
                    type="date"
                    value={cycleStartDate}
                    onChange={(e) => setCycleStartDate(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Period End</label>
                  <input
                    type="date"
                    value={cycleEndDate}
                    onChange={(e) => setCycleEndDate(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Amount</label>
                  <input
                    type="number"
                    step="0.01"
                    value={cycleAmount}
                    onChange={(e) => setCycleAmount(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Currency</label>
                  <select
                    value={cycleCurrency}
                    onChange={(e) => setCycleCurrency(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="INR">INR (₹)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                  </select>
                </div>
              </div>

              <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                <input
                  type="checkbox"
                  checked={cycleAutoRenew}
                  onChange={(e) => setCycleAutoRenew(e.target.checked)}
                  className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                />
                <span>Auto-Renew Subscription at Period End</span>
              </label>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setBillingCycleModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] disabled:opacity-60"
                >
                  {actionLoading ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 3: RECORD PAYMENT */}
      {recordPaymentModal.open && recordPaymentModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#131416]">Record Offline / Manual Payment</h3>
              <button
                type="button"
                onClick={() => setRecordPaymentModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-[#5d5f60]">
              Recording an offline payment automatically issues an official invoice and extends the subscription cycle for <strong>{recordPaymentModal.tenant.name}</strong>.
            </p>

            <form onSubmit={handleRecordPaymentSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Plan</label>
                  <select
                    value={payPlanId}
                    onChange={(e) => setPayPlanId(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    {plans.map((p) => (
                      <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Billing Interval</label>
                  <select
                    value={payInterval}
                    onChange={(e) => setPayInterval(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="monthly">Monthly (+1 Month)</option>
                    <option value="quarterly">Quarterly (+3 Months)</option>
                    <option value="semi_annual">Semi-Annual (+6 Months)</option>
                    <option value="yearly">Yearly (+1 Year)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Amount Paid *</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={payAmount}
                    onChange={(e) => setPayAmount(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Currency</label>
                  <select
                    value={payCurrency}
                    onChange={(e) => setPayCurrency(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="INR">INR (₹)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Payment Method</label>
                  <select
                    value={payMethod}
                    onChange={(e) => setPayMethod(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="bank_transfer">Bank Wire / Transfer</option>
                    <option value="upi">UPI / QR Code</option>
                    <option value="cash">Cash in Hand</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other / Custom</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Transaction Ref / UTR #</label>
                  <input
                    type="text"
                    placeholder="e.g. UTR-982341278"
                    value={payTxRef}
                    onChange={(e) => setPayTxRef(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Internal Notes</label>
                <textarea
                  rows={2}
                  placeholder="e.g. Paid via corporate NEFT verified by accounts"
                  value={payNotes}
                  onChange={(e) => setPayNotes(e.target.value)}
                  className="w-full p-2.5 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setRecordPaymentModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#166534] text-white text-xs font-semibold hover:bg-[#14532d] disabled:opacity-60"
                >
                  {actionLoading ? 'Recording...' : 'Record Payment & Issue Invoice'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 4: SUSPEND */}
      {suspendModal.open && suspendModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#991b1b] flex items-center gap-1.5">
                <AlertTriangle className="w-4 h-4" />
                <span>Suspend Workspace</span>
              </h3>
              <button
                type="button"
                onClick={() => setSuspendModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-xs text-[#991b1b] space-y-1">
              <p className="font-semibold">Zero Data Deletion Guarantee</p>
              <p>
                Suspending <strong>{suspendModal.tenant.name}</strong> temporarily pauses public quiz runner access. All funnels, configurations, and historical lead records remain 100% securely preserved.
              </p>
            </div>

            <form onSubmit={handleSuspendSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Reason for Suspension *</label>
                <input
                  type="text"
                  required
                  value={suspendReason}
                  onChange={(e) => setSuspendReason(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Internal Notes</label>
                <textarea
                  rows={2}
                  value={suspendNotes}
                  onChange={(e) => setSuspendNotes(e.target.value)}
                  className="w-full p-2.5 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setSuspendModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#991b1b] text-white text-xs font-semibold hover:bg-[#7f1d1d] disabled:opacity-60"
                >
                  {actionLoading ? 'Suspending...' : 'Confirm Suspension'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 5: REACTIVATE (UNSUSPEND) */}
      {unsuspendModal.open && unsuspendModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#166534] flex items-center gap-1.5">
                <CheckCircle className="w-4 h-4" />
                <span>Reactivate Workspace</span>
              </h3>
              <button
                type="button"
                onClick={() => setUnsuspendModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-[#5d5f60]">
              Reactivating <strong>{unsuspendModal.tenant.name}</strong> immediately restores public quiz runner links and dashboard access for all team members.
            </p>

            <form onSubmit={handleUnsuspendSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Extend Access Until (Optional)</label>
                <input
                  type="date"
                  value={extendUntil}
                  onChange={(e) => setExtendUntil(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
                <span className="text-[10px] text-[#5d5f60] mt-1 block">Leave blank to resume standard recurring billing cycle.</span>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setUnsuspendModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#166534] text-white text-xs font-semibold hover:bg-[#14532d] disabled:opacity-60"
                >
                  {actionLoading ? 'Reactivating...' : 'Reactivate Workspace'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 6: PERMANENT TERMINATION */}
      {terminateModal.open && terminateModal.tenant && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#991b1b] flex items-center gap-1.5">
                <Trash2 className="w-4 h-4" />
                <span>Manual Workspace Termination</span>
              </h3>
              <button
                type="button"
                onClick={() => setTerminateModal({ open: false, tenant: null })}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-xs text-[#991b1b] space-y-1">
              <p className="font-bold">Permanent Contract Termination</p>
              <p>
                This action archives <strong>{terminateModal.tenant.name}</strong> and closes all active services. To confirm this action, you must type the workspace slug <code className="font-bold font-mono bg-white px-1 py-0.5 rounded text-[#991b1b] border border-[#fecaca]">{terminateModal.tenant.slug}</code> below.
              </p>
            </div>

            <form onSubmit={handleTerminateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">
                  Type <span className="font-mono text-[#991b1b]">{terminateModal.tenant.slug}</span> to confirm:
                </label>
                <input
                  type="text"
                  required
                  value={confirmSlugInput}
                  onChange={(e) => setConfirmSlugInput(e.target.value)}
                  placeholder={terminateModal.tenant.slug}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] font-mono"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Administrative Reason</label>
                <input
                  type="text"
                  value={terminateReason}
                  onChange={(e) => setTerminateReason(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setTerminateModal({ open: false, tenant: null })}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading || confirmSlugInput.toLowerCase() !== terminateModal.tenant.slug.toLowerCase()}
                  className="px-4 py-1.5 rounded-xl bg-[#991b1b] text-white text-xs font-semibold hover:bg-[#7f1d1d] disabled:opacity-40"
                >
                  {actionLoading ? 'Terminating...' : 'Terminate Workspace'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

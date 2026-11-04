import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import {
  Layers,
  Plus,
  Check,
  ShieldCheck,
  Edit2,
  Trash2,
  Eye,
  EyeOff,
  Users,
  CheckCircle,
  AlertTriangle,
  Zap,
  Star,
  X
} from 'lucide-react';

interface FeatureMeta {
  name: string;
  category: string;
  is_premium: boolean;
  description: string;
}

const formatIntervalLabel = (interval?: string) => {
  switch (interval) {
    case 'quarterly':
      return '3mo';
    case 'semi_annual':
      return '6mo';
    case 'yearly':
      return 'yr';
    case 'monthly':
    default:
      return 'mo';
  }
};

export const SuperadminPlans: React.FC = () => {
  const [plans, setPlans] = useState<any[]>([]);
  const [featureCatalog, setFeatureCatalog] = useState<Record<string, FeatureMeta>>({});
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [msg, setMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  // Plan Create / Edit Modal State
  const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
  const [planModalOpen, setPlanModalOpen] = useState(false);
  const [editingPlanId, setEditingPlanId] = useState<number | null>(null);

  // Form Fields
  const [planName, setPlanName] = useState('');
  const [planSlug, setPlanSlug] = useState('');
  const [planPrice, setPlanPrice] = useState(0);
  const [planInterval, setPlanInterval] = useState('monthly');
  const [planDescription, setPlanDescription] = useState('');
  const [maxFunnels, setMaxFunnels] = useState(1);
  const [maxSubmissions, setMaxSubmissions] = useState(100);
  const [maxTeamMembers, setMaxTeamMembers] = useState(1);
  const [selectedFeatures, setSelectedFeatures] = useState<Record<string, boolean>>({});
  const [isActive, setIsActive] = useState(true);
  const [isVisible, setIsVisible] = useState(true);

  useEffect(() => {
    loadPlans();
  }, []);

  const loadPlans = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/plans');
      setPlans(res.plans || []);
      setFeatureCatalog(res.feature_catalog || {});
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load plans');
    } finally {
      setLoading(false);
    }
  };

  const openCreateModal = () => {
    setModalMode('create');
    setEditingPlanId(null);
    setPlanName('');
    setPlanSlug('');
    setPlanPrice(0);
    setPlanInterval('monthly');
    setPlanDescription('');
    setMaxFunnels(1);
    setMaxSubmissions(100);
    setMaxTeamMembers(1);

    // Default features
    const initialFeat: Record<string, boolean> = {};
    Object.keys(featureCatalog).forEach((k) => {
      initialFeat[k] = !featureCatalog[k].is_premium;
    });
    setSelectedFeatures(initialFeat);

    setIsActive(true);
    setIsVisible(true);
    setPlanModalOpen(true);
  };

  const openEditModal = (p: any) => {
    setModalMode('edit');
    setEditingPlanId(p.id);
    setPlanName(p.name);
    setPlanSlug(p.slug);
    setPlanPrice(Number(p.price || 0));
    setPlanInterval(p.billing_interval || 'monthly');
    setPlanDescription(p.description || '');

    const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
    setMaxFunnels(limits.max_funnels !== undefined ? limits.max_funnels : 1);
    setMaxSubmissions(limits.max_submissions_per_month !== undefined ? limits.max_submissions_per_month : 100);
    setMaxTeamMembers(limits.max_team_members !== undefined ? limits.max_team_members : 1);

    const feats = typeof p.features_json === 'string' ? JSON.parse(p.features_json || '{}') : (p.features_json || {});
    const featState: Record<string, boolean> = {};
    const isPro = ['pro', 'enterprise'].includes(p.slug);
    Object.keys(featureCatalog).forEach((k) => {
      featState[k] = feats[k] !== undefined ? Boolean(feats[k]) : (isPro || !featureCatalog[k].is_premium);
    });
    setSelectedFeatures(featState);

    setIsActive(Boolean(p.is_active));
    setIsVisible(Boolean(p.is_visible));
    setPlanModalOpen(true);
  };

  const handleModalSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!planName.trim()) return;
    try {
      setActionLoading(true);
      if (modalMode === 'create') {
        const res = await api.post('/api/platform/plans', {
          name: planName.trim(),
          slug: planSlug.trim(),
          price: planPrice,
          billing_interval: planInterval,
          description: planDescription,
          max_funnels: maxFunnels,
          max_submissions_per_month: maxSubmissions,
          max_team_members: maxTeamMembers,
          features: selectedFeatures,
          is_active: isActive ? 1 : 0,
          is_visible: isVisible ? 1 : 0,
        });
        setMsg(res.message || `Plan '${planName}' created`);
      } else if (editingPlanId) {
        const res = await api.post(`/api/platform/plans/${editingPlanId}/update`, {
          name: planName.trim(),
          price: planPrice,
          billing_interval: planInterval,
          description: planDescription,
          max_funnels: maxFunnels,
          max_submissions_per_month: maxSubmissions,
          max_team_members: maxTeamMembers,
          features: selectedFeatures,
          is_active: isActive ? 1 : 0,
          is_visible: isVisible ? 1 : 0,
        });
        setMsg(res.message || `Plan '${planName}' updated`);
      }
      setPlanModalOpen(false);
      await loadPlans();
    } catch (err: any) {
      alert(err.message || 'Failed to save plan');
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleVisibility = async (p: any) => {
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/plans/${p.id}/toggle-visibility`, {});
      setMsg(res.message || 'Visibility updated');
      await loadPlans();
    } catch (err: any) {
      alert(err.message || 'Failed to toggle visibility');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeletePlan = async (p: any) => {
    if (!window.confirm(`Are you sure you want to delete or deactivate plan '${p.name}'?`)) {
      return;
    }
    try {
      setActionLoading(true);
      const res = await api.post(`/api/platform/plans/${p.id}/delete`, {});
      setMsg(res.message || `Plan '${p.name}' processed`);
      await loadPlans();
    } catch (err: any) {
      alert(err.message || 'Failed to remove plan');
    } finally {
      setActionLoading(false);
    }
  };



  if (loading) {
    return (
      <div className="space-y-6 max-w-7xl mx-auto">
        <div className="h-10 w-64 rounded-xl skeleton-shimmer" />
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-48 rounded-2xl skeleton-shimmer" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-8 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
              <Layers className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Subscription Plans & Feature Quotas</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Configure subscription tiers, price points, hard quota limits, and premium capability flags.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={openCreateModal}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition"
          >
            <Plus className="w-4 h-4" />
            <span>Create New Plan</span>
          </button>
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

      {/* 1. Plans Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
        {plans.map((p) => {
          const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
          const features = typeof p.features_json === 'string' ? JSON.parse(p.features_json || '{}') : (p.features_json || {});
          const isPro = ['pro', 'enterprise'].includes(p.slug);

          let unlockedCount = 0;
          Object.keys(featureCatalog).forEach((k) => {
            if (features[k] || (isPro && features[k] === undefined)) {
              unlockedCount++;
            }
          });

          return (
            <div
              key={p.id}
              className={`bg-white border rounded-2xl p-5 flex flex-col justify-between space-y-4 shadow-xs transition ${
                p.is_active ? 'border-[#e2e5e9]' : 'border-[#e2e5e9] opacity-70 bg-[#f8fafc]'
              }`}
            >
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-1.5">
                    <h3 className="text-sm font-bold text-[#131416]">{p.name}</h3>
                    {!p.is_active && (
                      <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#fef2f2] text-[#991b1b] font-bold">
                        Inactive
                      </span>
                    )}
                  </div>
                  <span className="text-xs font-mono font-bold text-[#1d5fd6] px-2 py-0.5 rounded bg-[#dcf1ff] border border-[#b9e6fe]">
                    ₹{Number(p.price || 0).toLocaleString('en-IN')}/{formatIntervalLabel(p.billing_interval)}
                  </span>
                </div>

                <p className="text-xs text-[#5d5f60] leading-relaxed line-clamp-2">
                  {p.description || 'Configured platform subscription tier'}
                </p>

                <div className="flex items-center gap-2 pt-1 text-[11px] text-[#5d5f60]">
                  <Users className="w-3.5 h-3.5 text-[#1d5fd6]" />
                  <span>
                    <strong>{p.active_subscribers_count || 0}</strong> active subscribers
                  </span>
                </div>
              </div>

              {/* Limits Summary */}
              <div className="space-y-2 pt-3 border-t border-[#edf0f3] text-xs text-[#5d5f60]">
                <div className="flex justify-between">
                  <span>Max Funnels:</span>
                  <strong className="text-[#131416]">
                    {Number(limits.max_funnels || 0) <= 0 ? 'Unlimited' : limits.max_funnels}
                  </strong>
                </div>
                <div className="flex justify-between">
                  <span>Monthly Leads:</span>
                  <strong className="text-[#131416]">
                    {Number(limits.max_submissions_per_month || 0) <= 0
                      ? 'Unlimited'
                      : Number(limits.max_submissions_per_month).toLocaleString()}
                  </strong>
                </div>
                <div className="flex justify-between">
                  <span>Team Members:</span>
                  <strong className="text-[#131416]">
                    {Number(limits.max_team_members || 0) <= 0 ? 'Unlimited' : limits.max_team_members}
                  </strong>
                </div>
                <div className="flex justify-between">
                  <span>Features:</span>
                  <strong className="text-[#1d5fd6]">
                    {unlockedCount} / {Object.keys(featureCatalog).length} Unlocked
                  </strong>
                </div>
              </div>

              {/* Card Actions */}
              <div className="pt-3 border-t border-[#edf0f3] flex items-center justify-between gap-2">
                <button
                  type="button"
                  onClick={() => handleToggleVisibility(p)}
                  title={p.is_visible ? 'Visible on homepage (Click to hide)' : 'Hidden from homepage (Click to show)'}
                  className={`px-2.5 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1 transition ${
                    p.is_visible
                      ? 'border-[#bbf7d0] bg-[#dcfae5] text-[#2f7a45] hover:bg-[#bbf7d0]'
                      : 'border-[#e2e5e9] bg-[#f8fafc] text-[#5d5f60] hover:bg-[#edf0f3]'
                  }`}
                >
                  {p.is_visible ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                  <span>{p.is_visible ? 'Visible' : 'Hidden'}</span>
                </button>

                <div className="flex items-center gap-1">
                  <button
                    type="button"
                    onClick={() => openEditModal(p)}
                    className="p-1.5 rounded-lg border border-[#e2e5e9] text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f8fafc] transition"
                    title="Edit Plan"
                  >
                    <Edit2 className="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDeletePlan(p)}
                    className="p-1.5 rounded-lg border border-[#e2e5e9] text-[#5d5f60] hover:text-[#991b1b] hover:bg-[#fef2f2] transition"
                    title="Delete / Deactivate Plan"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* 2. Comprehensive Feature Matrix Comparison */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs space-y-4 p-6">
        <div>
          <h2 className="text-sm font-bold text-[#131416]">System Feature Entitlement Matrix</h2>
          <p className="text-xs text-[#5d5f60] mt-0.5">
            Key quotas and premium capabilities configured across each platform subscription tier.
          </p>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
            <thead>
              <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                <th className="py-3 px-4">Feature / Quota</th>
                <th className="py-3 px-4">Type</th>
                {plans.map((p) => (
                  <th key={p.id} className="py-3 px-4 text-center">
                    <span className="font-bold text-[#131416]">{p.name}</span>
                    <span className="block text-[10px] font-normal text-[#5d5f60] mt-0.5">
                      ₹{Number(p.price || 0).toLocaleString('en-IN')}/{formatIntervalLabel(p.billing_interval)}
                    </span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[#edf0f3]">
              {/* Hard Quotas Section */}
              <tr className="bg-[#f8fafc]/50 font-bold text-[#131416] text-[11px]">
                <td colSpan={2 + plans.length} className="py-2 px-4 uppercase tracking-wider text-[#1d5fd6]">
                  Usage Limits & Quotas
                </td>
              </tr>
              <tr className="hover:bg-[#f8fafc] transition">
                <td className="py-3 px-4 font-semibold text-[#131416]">Active Funnels</td>
                <td className="py-3 px-4 text-[11px] text-[#5d5f60]">Hard Quota</td>
                {plans.map((p) => {
                  const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
                  const val = Number(limits.max_funnels || 0);
                  return (
                    <td key={p.id} className="py-3 px-4 text-center font-semibold text-[#131416]">
                      {val <= 0 ? 'Unlimited' : val}
                    </td>
                  );
                })}
              </tr>
              <tr className="hover:bg-[#f8fafc] transition">
                <td className="py-3 px-4 font-semibold text-[#131416]">Monthly Leads / Submissions</td>
                <td className="py-3 px-4 text-[11px] text-[#5d5f60]">Hard Quota</td>
                {plans.map((p) => {
                  const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
                  const val = Number(limits.max_submissions_per_month || 0);
                  return (
                    <td key={p.id} className="py-3 px-4 text-center font-semibold text-[#131416]">
                      {val <= 0 ? 'Unlimited' : val.toLocaleString()}
                    </td>
                  );
                })}
              </tr>
              <tr className="hover:bg-[#f8fafc] transition">
                <td className="py-3 px-4 font-semibold text-[#131416]">Team Member Seats</td>
                <td className="py-3 px-4 text-[11px] text-[#5d5f60]">Hard Quota</td>
                {plans.map((p) => {
                  const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
                  const val = Number(limits.max_team_members || 0);
                  return (
                    <td key={p.id} className="py-3 px-4 text-center font-semibold text-[#131416]">
                      {val <= 0 ? 'Unlimited' : val}
                    </td>
                  );
                })}
              </tr>

              {/* Premium Features Section */}
              <tr className="bg-[#f8fafc]/50 font-bold text-[#131416] text-[11px]">
                <td colSpan={2 + plans.length} className="py-2 px-4 uppercase tracking-wider text-[#1d5fd6]">
                  Premium Capabilities
                </td>
              </tr>
              {Object.entries(featureCatalog).map(([fKey, fMeta]) => (
                <tr key={fKey} className="hover:bg-[#f8fafc] transition">
                  <td className="py-3 px-4">
                    <div className="font-bold text-[#131416] flex items-center gap-1.5">
                      <span>{fMeta.name}</span>
                      <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                        PRO
                      </span>
                    </div>
                    <div className="text-[10px] text-[#5d5f60] mt-0.5">{fMeta.description}</div>
                  </td>
                  <td className="py-3 px-4 text-[11px] text-[#5d5f60]">
                    {fMeta.category}
                  </td>
                  {plans.map((p) => {
                    const feats = typeof p.features_json === 'string' ? JSON.parse(p.features_json || '{}') : (p.features_json || {});
                    const isPro = ['pro', 'enterprise'].includes(p.slug);
                    const isEnabled = feats[fKey] !== undefined ? Boolean(feats[fKey]) : (isPro || !fMeta.is_premium);

                    return (
                      <td key={p.id} className="py-3 px-4 text-center">
                        {isEnabled ? (
                          <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#dcfae5] text-[#2f7a45]">
                            <Check className="w-3.5 h-3.5 stroke-[2.5]" />
                          </span>
                        ) : (
                          <span className="text-[#a0a4a8] font-mono">—</span>
                        )}
                      </td>
                    );
                  })}
                </tr>
              ))}

              {/* Standard Platform Inclusions */}
              <tr className="bg-[#f8fafc]/50 font-bold text-[#131416] text-[11px]">
                <td colSpan={2 + plans.length} className="py-2 px-4 uppercase tracking-wider text-[#2f7a45]">
                  Standard Platform Essentials
                </td>
              </tr>
              <tr className="hover:bg-[#f8fafc] transition">
                <td className="py-3 px-4">
                  <div className="font-bold text-[#131416]">Visual Drag-and-Drop Canvas, Logic Routing, Lead Capture & Embeds</div>
                  <div className="text-[10px] text-[#5d5f60] mt-0.5">
                    Branching question logic, dynamic scoring, lead capture forms, back navigation, traffic analytics, and iFrame embed widgets.
                  </div>
                </td>
                <td className="py-3 px-4 text-[11px] text-[#2f7a45] font-semibold">Standard Core</td>
                {plans.map((p) => (
                  <td key={p.id} className="py-3 px-4 text-center text-[11px] text-[#2f7a45] font-medium">
                    Included
                  </td>
                ))}
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {/* 3. Create / Edit Plan Modal */}
      {planModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#131416]">
                {modalMode === 'create' ? 'Create New Subscription Plan' : `Edit Plan: ${planName}`}
              </h3>
              <button
                type="button"
                onClick={() => setPlanModalOpen(false)}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
                title="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleModalSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Plan Name *</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. Enterprise Tier"
                    value={planName}
                    onChange={(e) => {
                      setPlanName(e.target.value);
                      if (modalMode === 'create' && !planSlug) {
                        setPlanSlug(e.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
                      }
                    }}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Slug *</label>
                  <input
                    type="text"
                    required
                    disabled={modalMode === 'edit'}
                    placeholder="e.g. enterprise"
                    value={planSlug}
                    onChange={(e) => setPlanSlug(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] font-mono disabled:bg-[#f4f6f8]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Price (₹ INR) *</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={planPrice}
                    onChange={(e) => setPlanPrice(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Billing Interval</label>
                  <select
                    value={planInterval}
                    onChange={(e) => setPlanInterval(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                  >
                    <option value="monthly">Monthly (1 Month)</option>
                    <option value="quarterly">Quarterly (3 Months)</option>
                    <option value="semi_annual">Semi-Annual (6 Months)</option>
                    <option value="yearly">Yearly (12 Months)</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Description</label>
                <input
                  type="text"
                  placeholder="Short marketing headline for this plan..."
                  value={planDescription}
                  onChange={(e) => setPlanDescription(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416]"
                />
              </div>

              {/* Hard Quotas */}
              <div className="p-4 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-3">
                <span className="text-xs font-bold text-[#131416] block">Usage Quotas (Use 0 for Unlimited)</span>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-[#5d5f60] mb-1">Max Funnels</label>
                    <input
                      type="number"
                      value={maxFunnels}
                      onChange={(e) => setMaxFunnels(Number(e.target.value))}
                      className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-[#5d5f60] mb-1">Monthly Leads</label>
                    <input
                      type="number"
                      value={maxSubmissions}
                      onChange={(e) => setMaxSubmissions(Number(e.target.value))}
                      className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-[#5d5f60] mb-1">Team Members</label>
                    <input
                      type="number"
                      value={maxTeamMembers}
                      onChange={(e) => setMaxTeamMembers(Number(e.target.value))}
                      className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] bg-white"
                    />
                  </div>
                </div>
              </div>

              {/* Standard Core Capabilities Box */}
              <div className="p-3.5 rounded-xl bg-[#f0fdf4] border border-[#bbf7d0] space-y-1">
                <div className="flex items-center gap-1.5 text-xs font-bold text-[#166534]">
                  <ShieldCheck className="w-4 h-4 text-[#16a34a] shrink-0" />
                  <span>Standard Platform Essentials (Always Active)</span>
                </div>
                <p className="text-[11px] text-[#15803d] leading-relaxed">
                  Visual Canvas Builder, Multi-Branch Question Routing, Lead Forms, Dynamic Scoring, Back Navigation, Conversion Analytics, and Website Embeds are standard and active across all subscription tiers.
                </p>
              </div>

              {/* 6 High-Impact Premium Capabilities */}
              <div className="space-y-3">
                <div>
                  <span className="text-xs font-bold text-[#131416] block">Premium Upgrade Capabilities</span>
                  <p className="text-[11px] text-[#5d5f60]">
                    Select which advanced features are unlocked for workspaces on this tier:
                  </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  {Object.entries(featureCatalog).map(([fKey, fMeta]) => {
                    const isChecked = Boolean(selectedFeatures[fKey]);
                    return (
                      <label
                        key={fKey}
                        className={`flex items-start gap-2.5 p-3 rounded-xl border transition cursor-pointer select-none ${
                          isChecked
                            ? 'border-[#b9e6fe] bg-[#f0f9ff]/60'
                            : 'border-[#e2e5e9] bg-white hover:bg-[#f8fafc]'
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={(e) =>
                            setSelectedFeatures({
                              ...selectedFeatures,
                              [fKey]: e.target.checked,
                            })
                          }
                          className="mt-0.5 rounded border-[#cbd5e1] text-[#1d5fd6] focus:ring-[#1d5fd6]"
                        />
                        <div className="min-w-0">
                          <div className="flex items-center gap-1.5">
                            <span className="font-bold text-xs text-[#131416]">{fMeta.name}</span>
                            <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                              PRO
                            </span>
                          </div>
                          <p className="text-[11px] text-[#5d5f60] leading-snug mt-0.5">
                            {fMeta.description}
                          </p>
                        </div>
                      </label>
                    );
                  })}
                </div>
              </div>

              {/* Visibility and Active Toggles */}
              <div className="flex items-center gap-6 pt-2">
                <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                  <input
                    type="checkbox"
                    checked={isActive}
                    onChange={(e) => setIsActive(e.target.checked)}
                    className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                  />
                  <span>Active for subscription</span>
                </label>

                <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                  <input
                    type="checkbox"
                    checked={isVisible}
                    onChange={(e) => setIsVisible(e.target.checked)}
                    className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                  />
                  <span>Visible on pricing page</span>
                </label>
              </div>

              <div className="flex justify-end gap-2 pt-4 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setPlanModalOpen(false)}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] disabled:opacity-60"
                >
                  {actionLoading ? 'Saving...' : modalMode === 'create' ? 'Create Plan' : 'Update Plan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

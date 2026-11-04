import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../../api/client';
import { Funnel } from '../../types';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import {
  GitFork,
  Plus,
  Search,
  Settings,
  Copy,
  ExternalLink,
  Trash2,
  CheckCircle,
  AlertCircle,
  MoreVertical,
  Upload,
  Loader2,
  AlertTriangle,
  Lock
} from 'lucide-react';

export const FunnelsListPage: React.FC = () => {
  const { activeTenant, features, isPlatformAdmin } = useAuthStore();
  const [funnels, setFunnels] = useState<Funnel[]>([]);
  const [usage, setUsage] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(true);
  const [actionLoadingId, setActionLoadingId] = useState<number | null>(null);

  // Upgrade Modal
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);
  const [upgradeFeature, setUpgradeFeature] = useState({ name: '', desc: '' });

  const navigate = useNavigate();

  useEffect(() => {
    loadFunnels();
  }, [statusFilter]);

  const loadFunnels = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/funnels', {
        search: search || undefined,
        status: statusFilter || undefined,
      });
      setFunnels(res.funnels || []);
      setUsage(res.usage || null);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    loadFunnels();
  };

  const handleDuplicate = async (id: number) => {
    const canClone = Boolean(features?.funnel_cloning || isPlatformAdmin);
    if (!canClone) {
      setUpgradeFeature({
        name: '1-Click Funnel Duplication',
        desc: 'Quickly duplicate complete node graphs, questions, calculations, and destination settings with 1 click.',
      });
      setUpgradeModalOpen(true);
      return;
    }

    try {
      setActionLoadingId(id);
      const clone = await api.post(`/api/funnels/${id}/duplicate`);
      loadFunnels();
    } catch (err: any) {
      alert(err.message || 'Duplicate failed');
    } finally {
      setActionLoadingId(null);
    }
  };

  const handlePublishToggle = async (funnel: Funnel) => {
    try {
      setActionLoadingId(funnel.id);
      if (funnel.status === 'published') {
        await api.post(`/api/funnels/${funnel.id}/unpublish`);
      } else {
        await api.post(`/api/funnels/${funnel.id}/publish`);
      }
      loadFunnels();
    } catch (err: any) {
      alert(err.message || 'Publish toggle failed');
    } finally {
      setActionLoadingId(null);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this funnel?')) return;
    try {
      setActionLoadingId(id);
      await api.post(`/api/funnels/${id}/delete`);
      loadFunnels();
    } catch (err: any) {
      alert(err.message || 'Delete failed');
    } finally {
      setActionLoadingId(null);
    }
  };

  return (
    <div className="space-y-6">
      {/* Top Header & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold text-[#131416]">Funnels</h1>
          <p className="text-xs text-[#5d5f60] mt-0.5">Manage, design, and publish your branching logic funnels</p>
        </div>

        <Link
          to="/admin/funnels/create"
          className="inline-flex items-center gap-1.5 px-3 h-8 rounded-md bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] transition self-start sm:self-auto shadow-xs"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>Create Funnel</span>
        </Link>
      </div>

      {/* Workspace Suspended Alert */}
      {activeTenant?.status === 'suspended' && (
        <div className="p-4 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0 text-[#dc2626]" />
            <span>
              <strong>Workspace Suspended:</strong> All funnels are paused and public visitor submissions are blocked. Please resolve your billing to reactivate your workspace.
            </span>
          </div>
          <Link
            to="/admin/billing"
            className="px-3 py-1.5 rounded-lg bg-[#dc2626] text-white text-xs font-semibold hover:bg-[#b91c1c] transition shrink-0"
          >
            Resolve Subscription
          </Link>
        </div>
      )}

      {/* Monthly Lead Quota Exhausted Alert */}
      {usage?.is_lead_quota_exhausted && (
        <div className="p-4 rounded-xl bg-[#fffbeb] border border-[#fde68a] text-[#92400e] text-xs flex items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0 text-[#d97706]" />
            <span>
              <strong>Monthly Lead Quota Reached:</strong> You have captured {usage.submissions?.used} of {usage.submissions?.limit} leads this month. New lead submissions are paused.
            </span>
          </div>
          <Link
            to="/admin/billing"
            className="px-3 py-1.5 rounded-lg bg-[#d97706] text-white text-xs font-semibold hover:bg-[#b45309] transition shrink-0"
          >
            Upgrade Quota
          </Link>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="bg-white border border-[#e8eaec] rounded-lg p-2.5 flex flex-col sm:flex-row items-center justify-between gap-2.5">
        <form onSubmit={handleSearchSubmit} className="relative w-full sm:w-72">
          <Search className="w-3.5 h-3.5 text-[#5d5f60] absolute left-3 top-2.5" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search funnels..."
            className="w-full pl-8 pr-3 h-8 rounded-md border border-[#cdced1] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
          />
        </form>

        <div className="flex items-center gap-2 w-full sm:w-auto">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-2.5 h-8 rounded-md border border-[#cdced1] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
          >
            <option value="">All statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
          </select>
        </div>
      </div>

      {/* Funnels List Grid */}
      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div
              key={i}
              className="bg-white border border-[#e2e5e9] rounded-2xl p-5 flex flex-col justify-between space-y-4"
            >
              <div className="space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2.5 flex-1">
                    <div className="w-8 h-8 rounded-xl skeleton-shimmer shrink-0" />
                    <div className="space-y-1.5 flex-1">
                      <div className="h-4 w-3/4 rounded-md skeleton-shimmer" />
                      <div className="h-3 w-1/2 rounded-md skeleton-shimmer" />
                    </div>
                  </div>
                  <div className="w-14 h-5 rounded-full skeleton-shimmer" />
                </div>
                <div className="h-3 w-full rounded-md skeleton-shimmer" />
                <div className="h-3 w-4/5 rounded-md skeleton-shimmer" />
              </div>

              <div className="pt-3 border-t border-[#f0f1f2] flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                  <div className="w-6 h-6 rounded-lg skeleton-shimmer" />
                  <div className="w-6 h-6 rounded-lg skeleton-shimmer" />
                  <div className="w-6 h-6 rounded-lg skeleton-shimmer" />
                </div>
                <div className="flex items-center gap-1.5">
                  <div className="w-16 h-7 rounded-lg skeleton-shimmer" />
                  <div className="w-16 h-7 rounded-lg skeleton-shimmer" />
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : funnels.length === 0 ? (
        <div className="bg-white border border-[#e8eaec] rounded-lg p-12 text-center">
          <GitFork className="w-8 h-8 text-[#5d5f60] mx-auto mb-2.5" />
          <h3 className="text-sm font-semibold text-[#131416]">No funnels found</h3>
          <p className="text-xs text-[#5d5f60] mt-1">Get started by building your first interactive branching funnel.</p>
          <Link
            to="/admin/funnels/create"
            className="mt-4 inline-flex items-center gap-1.5 px-3 h-8 rounded-md bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] shadow-xs"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Create Funnel</span>
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {funnels.map((f) => (
            <div
              key={f.id}
              className="bg-white border border-[#e2e5e9] rounded-2xl p-5 transition flex flex-col justify-between hover:border-[#1d5fd6]/50 hover:shadow-xs"
            >
              <div>
                <div className="flex items-start justify-between gap-2 mb-2">
                  <div className="flex items-center gap-2.5 min-w-0 flex-1">
                    <div className="w-8 h-8 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold shrink-0">
                      <GitFork className="w-4 h-4" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-sm font-bold text-[#131416] truncate">{f.name}</h3>
                      <span className="text-[11px] text-[#5d5f60] font-mono truncate block">/quiz/{f.slug}</span>
                    </div>
                  </div>
                  {activeTenant?.status === 'suspended' ? (
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#fef2f2] text-[#991b1b] border border-[#fecaca] flex items-center gap-1">
                      <AlertTriangle className="w-3 h-3 text-[#dc2626]" />
                      <span>Suspended</span>
                    </span>
                  ) : (
                    <span
                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                        f.status === 'published'
                          ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                          : 'bg-[#f1f5f9] text-[#475569] border border-[#e2e5e9]'
                      }`}
                    >
                      {f.status === 'published' ? 'Published' : 'Draft'}
                    </span>
                  )}
                </div>

                <div className="flex items-center gap-1.5 mb-3 flex-wrap">
                  {f.funnel_mode === 'calculator' && (
                    <span className="px-2 py-0.5 rounded-full text-[10px] bg-[#fef3c7] text-[#b45309] font-bold border border-[#fde68a]">
                      Calculator Quote
                    </span>
                  )}
                  {f.has_unpublished_changes && (
                    <span className="px-2 py-0.5 rounded-full text-[10px] bg-[#ffedea] text-[#831c1e] font-bold border border-[#ffcfc9]">
                      Unpublished edits
                    </span>
                  )}
                </div>

                {f.description && (
                  <p className="text-xs text-[#5d5f60] line-clamp-2 mb-3 leading-relaxed">{f.description}</p>
                )}
              </div>

              <div className="pt-3 border-t border-[#f0f1f2] flex items-center justify-between">
                <div className="flex items-center gap-1">
                  <Link
                    to={`/admin/funnels/${f.id}/settings`}
                    className="p-1.5 text-[#5d5f60] hover:text-[#1d5fd6] rounded-lg hover:bg-[#dcf1ff]/40 transition"
                    title="Funnel Settings"
                  >
                    <Settings className="w-3.5 h-3.5" />
                  </Link>
                  <button
                    onClick={() => handleDuplicate(f.id)}
                    disabled={actionLoadingId === f.id}
                    className="p-1.5 text-[#5d5f60] hover:text-[#1d5fd6] rounded-lg hover:bg-[#dcf1ff]/40 transition disabled:opacity-50"
                    title={!Boolean(features?.funnel_cloning || isPlatformAdmin) ? 'Upgrade to Pro to Clone Funnels' : 'Duplicate Funnel'}
                  >
                    {actionLoadingId === f.id ? (
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-[#1d5fd6]" />
                    ) : !Boolean(features?.funnel_cloning || isPlatformAdmin) ? (
                      <span className="flex items-center gap-0.5">
                        <Copy className="w-3.5 h-3.5 opacity-60" />
                        <span className="text-[9px] font-bold text-[#1d5fd6] bg-[#dcf1ff] px-1 rounded">PRO</span>
                      </span>
                    ) : (
                      <Copy className="w-3.5 h-3.5" />
                    )}
                  </button>
                  {f.status === 'published' && (
                    <a
                      href={`/quiz/${f.slug}`}
                      target="_blank"
                      rel="noreferrer"
                      className="p-1.5 text-[#5d5f60] hover:text-[#2f7a45] rounded-lg hover:bg-[#dcfae5]/40 transition"
                      title="Preview Public Quiz"
                    >
                      <ExternalLink className="w-3.5 h-3.5" />
                    </a>
                  )}
                  <button
                    onClick={() => handleDelete(f.id)}
                    disabled={actionLoadingId === f.id}
                    className="p-1.5 text-[#c41c29] hover:text-[#831c1e] rounded-lg hover:bg-[#ffedea] transition disabled:opacity-50"
                    title="Delete Funnel"
                  >
                    {actionLoadingId === f.id ? (
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-[#c41c29]" />
                    ) : (
                      <Trash2 className="w-3.5 h-3.5" />
                    )}
                  </button>
                </div>

                <div className="flex items-center gap-1.5">
                  <button
                    onClick={() => handlePublishToggle(f)}
                    disabled={actionLoadingId === f.id}
                    className={`px-2.5 h-7 rounded-lg text-xs font-semibold transition flex items-center gap-1 disabled:opacity-50 ${
                      f.status === 'published'
                        ? 'border border-[#e2e5e9] text-[#5d5f60] hover:bg-[#f4f6f8]'
                        : 'border border-[#b9e6fe] bg-[#dcf1ff] text-[#0a3a80] hover:bg-[#b9e6fe]'
                    }`}
                  >
                    {actionLoadingId === f.id && (
                      <Loader2 className="w-3 h-3 animate-spin shrink-0" />
                    )}
                    <span>
                      {actionLoadingId === f.id
                        ? 'Updating...'
                        : f.status === 'published'
                        ? 'Unpublish'
                        : 'Publish'}
                    </span>
                  </button>

                  <Link
                    to={`/admin/funnels/${f.id}/builder`}
                    className="px-3 h-7 inline-flex items-center rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
                  >
                    Builder
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
      {/* Upgrade Modal */}
      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName={upgradeFeature.name}
        description={upgradeFeature.desc}
      />
    </div>
  );
};


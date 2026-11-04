import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { api } from '../../api/client';
import { Submission, LeadStage } from '../../types';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import {
  Users,
  Search,
  Download,
  Eye,
  CheckCircle2,
  AlertCircle,
  Clock,
  Phone,
  MessageCircle,
  Copy,
  Check,
  ChevronLeft,
  ChevronRight,
  Plus,
  X,
  Settings2,
  Trash2,
  Sparkles,
  ArrowRight,
  Loader2
} from 'lucide-react';

interface SubmissionsResponse {
  submissions: Submission[];
  counts: {
    all: number;
    qualified: number;
    abandoned: number;
  };
  pagination: {
    current_page: number;
    total_pages: number;
    total_records: number;
    per_page: number;
  };
  funnels: Array<{ id: number; name: string }>;
  lead_stages: LeadStage[];
  filters: any;
}

export const SubmissionsPage: React.FC = () => {
  const { features, isPlatformAdmin, activeTenant, limits } = useAuthStore();
  const [searchParams, setSearchParams] = useSearchParams();
  const currentTab = (searchParams.get('tab') as 'qualified' | 'all' | 'abandoned') || 'qualified';

  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);
  const canExportCsv = Boolean(features?.csv_export || isPlatformAdmin);

  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [counts, setCounts] = useState({ all: 0, qualified: 0, abandoned: 0 });
  const [pagination, setPagination] = useState({
    current_page: 1,
    total_pages: 1,
    total_records: 0,
    per_page: 15,
  });
  const [funnels, setFunnels] = useState<Array<{ id: number; name: string }>>([]);
  const [leadStages, setLeadStages] = useState<LeadStage[]>([]);

  // Filter states
  const [search, setSearch] = useState(searchParams.get('search') || '');
  const [selectedFunnel, setSelectedFunnel] = useState(searchParams.get('funnel_id') || '');
  const [selectedLeadStatus, setSelectedLeadStatus] = useState(searchParams.get('lead_status') || '');
  const [selectedDateRange, setSelectedDateRange] = useState(searchParams.get('date_range') || '');
  const [currentPage, setCurrentPage] = useState(Number(searchParams.get('page') || 1));

  // Row selection & Bulk actions
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [bulkAction, setBulkAction] = useState<string>('');
  const [bulkProcessing, setBulkProcessing] = useState(false);

  // Copy feedback
  const [copiedId, setCopiedId] = useState<string | null>(null);

  // Stages Modal
  const [isStagesModalOpen, setIsStagesModalOpen] = useState(false);
  const [newStageName, setNewStageName] = useState('');
  const [newStageColor, setNewStageColor] = useState('#1b6c80');
  const [creatingStage, setCreatingStage] = useState(false);

  const [loading, setLoading] = useState(true);

  // Load data whenever filters or tab changes
  useEffect(() => {
    loadSubmissions();
  }, [currentTab, selectedFunnel, selectedLeadStatus, selectedDateRange, currentPage]);

  const loadSubmissions = async () => {
    try {
      setLoading(true);
      const params: Record<string, any> = {
        tab: currentTab,
        page: currentPage,
      };
      if (selectedFunnel) params.funnel_id = selectedFunnel;
      if (selectedLeadStatus) params.lead_status = selectedLeadStatus;
      if (selectedDateRange) params.date_range = selectedDateRange;
      if (search.trim()) params.search = search.trim();

      const res: SubmissionsResponse = await api.get('/api/submissions', params);
      setSubmissions(res.submissions || []);
      setCounts(res.counts || { all: 0, qualified: 0, abandoned: 0 });
      setPagination(
        res.pagination || {
          current_page: 1,
          total_pages: 1,
          total_records: res.submissions?.length || 0,
          per_page: 15,
        }
      );
      setFunnels(res.funnels || []);
      setLeadStages(res.lead_stages || []);
      setSelectedIds([]);
    } catch (err) {
      console.error('Failed to load submissions:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleTabChange = (tab: 'qualified' | 'all' | 'abandoned') => {
    setCurrentPage(1);
    const newParams = new URLSearchParams(searchParams);
    newParams.set('tab', tab);
    newParams.delete('page');
    setSearchParams(newParams);
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentPage(1);
    loadSubmissions();
  };

  const handleResetFilters = () => {
    setSearch('');
    setSelectedFunnel('');
    setSelectedLeadStatus('');
    setSelectedDateRange('');
    setCurrentPage(1);
    const newParams = new URLSearchParams();
    newParams.set('tab', currentTab);
    setSearchParams(newParams);
    setTimeout(() => {
      loadSubmissions();
    }, 0);
  };

  const handleStageChange = async (submissionId: number, newStage: string) => {
    try {
      await api.post(`/api/submissions/${submissionId}/status`, { lead_status: newStage });
      setSubmissions((prev) =>
        prev.map((sub) => (sub.id === submissionId ? { ...sub, lead_status: newStage } : sub))
      );
    } catch (err: any) {
      alert(err.message || 'Failed to update lead stage');
    }
  };

  // Row selection
  const handleToggleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelectedIds(submissions.map((s) => s.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleToggleRow = (id: number) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  // Bulk action execution
  const handleExecuteBulkAction = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!bulkAction) {
      alert('Please select a bulk action.');
      return;
    }
    if (selectedIds.length === 0) {
      alert('Please select at least one lead.');
      return;
    }
    if (bulkAction === 'delete' && !window.confirm(`Are you sure you want to delete ${selectedIds.length} lead(s)?`)) {
      return;
    }

    try {
      setBulkProcessing(true);
      await api.post('/api/submissions/bulk-action', {
        action: bulkAction,
        session_ids: selectedIds,
      });
      setSelectedIds([]);
      setBulkAction('');
      await loadSubmissions();
    } catch (err: any) {
      alert(err.message || 'Bulk action failed');
    } finally {
      setBulkProcessing(false);
    }
  };

  // Copy to clipboard helper
  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => {
      setCopiedId(null);
    }, 2000);
  };

  // Clean label helper for stages
  const getStageEmoji = (_slug: string) => '';

  // Create Custom Lead Stage
  const handleCreateStage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newStageName.trim()) return;

    try {
      setCreatingStage(true);
      const res = await api.post('/api/submissions-stages', {
        name: newStageName.trim(),
        color: newStageColor,
      });
      setLeadStages((prev) => [...prev, res]);
      setNewStageName('');
      setIsStagesModalOpen(false);
    } catch (err: any) {
      alert(err.message || 'Failed to create lead stage');
    } finally {
      setCreatingStage(false);
    }
  };

  // Delete Custom Stage
  const handleDeleteStage = async (stageId: number) => {
    if (!window.confirm('Delete this custom stage? Leads currently in this stage will not be deleted.')) return;
    try {
      await api.post(`/api/submissions-stages/${stageId}/delete`, {});
      setLeadStages((prev) => prev.filter((s) => s.id !== stageId));
    } catch (err: any) {
      alert(err.message || 'Failed to delete stage');
    }
  };

  const hasActiveFilters = Boolean(
    search.trim() || selectedFunnel || selectedLeadStatus || selectedDateRange
  );

  const AVATAR_PALETTE = [
    'bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]',
    'bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]',
    'bg-[#fef3c7] text-[#92400e] border border-[#fde68a]',
    'bg-[#e0e7ff] text-[#3730a3] border border-[#c7d2fe]',
    'bg-[#ccfbf1] text-[#0f766e] border border-[#99f6e4]',
    'bg-[#ffedea] text-[#831c1e] border border-[#fecdd3]',
  ];

  const getStageBadgeStyle = (status: string) => {
    switch (status) {
      case 'won':
      case 'qualified':
        return 'bg-[#dcfae5] text-[#04250e] border-[#bbf7d0] font-semibold';
      case 'contacted':
        return 'bg-[#fef3c7] text-[#92400e] border-[#fde68a] font-semibold';
      case 'lost':
        return 'bg-[#ffedea] text-[#831c1e] border-[#fecdd3] font-semibold';
      case 'new':
      default:
        return 'bg-[#dcf1ff] text-[#0a3a80] border-[#b9e6fe] font-semibold';
    }
  };

  return (
    <div className="space-y-5">
      {/* Top Agency Navigation Tabs & Action Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E4E7EC] pb-3">
        <div className="flex flex-wrap items-center gap-2">
          {/* Qualified Leads Tab */}
          <button
            type="button"
            onClick={() => handleTabChange('qualified')}
            className={`flex items-center gap-2 px-3 py-1.5 rounded-[6px] text-xs font-medium transition-colors ${
              currentTab === 'qualified'
                ? 'bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]'
                : 'bg-white border border-[#e8eaec] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8]'
            }`}
          >
            <CheckCircle2
              className={`w-4 h-4 ${currentTab === 'qualified' ? 'text-[#0a3a80]' : 'text-[#5d5f60]'}`}
            />
            <span>Qualified Leads</span>
            <span className="px-1.5 py-0.5 rounded-[4px] text-[10px] bg-[#dcfae5] text-[#04250e] font-mono">
              {counts.qualified}
            </span>
          </button>

          {/* All Submissions Tab */}
          <button
            type="button"
            onClick={() => handleTabChange('all')}
            className={`flex items-center gap-2 px-3 py-1.5 rounded-[6px] text-xs font-medium transition-colors ${
              currentTab === 'all'
                ? 'bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]'
                : 'bg-white border border-[#e8eaec] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8]'
            }`}
          >
            <Users
              className={`w-4 h-4 ${currentTab === 'all' ? 'text-[#0a3a80]' : 'text-[#5d5f60]'}`}
            />
            <span>All Submissions</span>
            <span className="px-1.5 py-0.5 rounded-[4px] text-[10px] bg-[#f4f6f8] text-[#5d5f60] font-mono">
              {counts.all}
            </span>
          </button>

          {/* Drop-offs Tab */}
          <button
            type="button"
            onClick={() => handleTabChange('abandoned')}
            title="Visitors who started but dropped before submitting lead contact info"
            className={`flex items-center gap-2 px-3 py-1.5 rounded-[6px] text-xs font-medium transition-colors ${
              currentTab === 'abandoned'
                ? 'bg-[#fffbeb] text-[#92400e] border border-[#fde68a]'
                : 'bg-white border border-[#e8eaec] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8]'
            }`}
          >
            <AlertCircle
              className={`w-4 h-4 ${currentTab === 'abandoned' ? 'text-[#92400e]' : 'text-[#5d5f60]'}`}
            />
            <span>Drop-offs</span>
            <span className="px-1.5 py-0.5 rounded-[4px] text-[10px] bg-[#fffbeb] text-[#92400e] font-mono">
              {counts.abandoned}
            </span>
          </button>
        </div>

        {/* Right Side Utility Controls */}
        <div className="flex items-center gap-2 self-start sm:self-auto">
          {/* Manage Custom Lead Stages Button */}
          <button
            type="button"
            onClick={() => setIsStagesModalOpen(true)}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-medium text-[#131416] hover:bg-[#f4f6f8] transition-colors"
          >
            <Settings2 className="w-3.5 h-3.5 text-[#5d5f60]" />
            <span>Customize Lead Stages</span>
          </button>

          {/* Export CSV Button */}
          {canExportCsv ? (
            <a
              href={`/admin/submissions/export?tab=${currentTab}${selectedFunnel ? `&funnel_id=${selectedFunnel}` : ''}${selectedDateRange ? `&date_range=${selectedDateRange}` : ''}`}
              download
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-medium text-[#131416] hover:bg-[#f4f6f8] transition-colors"
            >
              <Download className="w-3.5 h-3.5 text-[#5d5f60]" />
              <span>Export CSV</span>
            </a>
          ) : (
            <button
              type="button"
              onClick={() => setUpgradeModalOpen(true)}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-medium text-[#131416] hover:bg-[#f4f6f8] transition-colors"
              title="Upgrade to Pro to export leads as CSV"
            >
              <Download className="w-3.5 h-3.5 text-[#5d5f60]" />
              <span>Export CSV</span>
              <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                PRO
              </span>
            </button>
          )}
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white border border-[#e8eaec] rounded-[8px] p-3">
        <form onSubmit={handleSearchSubmit} className="flex flex-wrap items-center gap-2.5">
          {/* Search box */}
          <div className="relative flex-1 min-w-[200px] max-w-sm">
            <Search className="w-3.5 h-3.5 text-[#5d5f60] absolute left-3 top-2.5" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by name, email, phone, or ID..."
              className="w-full pl-9 pr-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] placeholder-[#5d5f60] focus:outline-none focus:border-[#1b6c80]"
            />
          </div>

          {/* Funnel Filter */}
          <select
            value={selectedFunnel}
            onChange={(e) => setSelectedFunnel(e.target.value)}
            className="h-8 px-2.5 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
          >
            <option value="">All Funnels</option>
            {funnels.map((f) => (
              <option key={f.id} value={f.id}>
                {f.name}
              </option>
            ))}
          </select>

          {/* Dynamic CRM Lead Stages Filter */}
          <select
            value={selectedLeadStatus}
            onChange={(e) => setSelectedLeadStatus(e.target.value)}
            className="h-8 px-2.5 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
          >
            <option value="">All Lead Stages</option>
            {leadStages.map((st) => (
              <option key={st.id || st.slug} value={st.slug}>
                {st.name}
              </option>
            ))}
          </select>

          {/* Date Range Filter */}
          <select
            value={selectedDateRange}
            onChange={(e) => setSelectedDateRange(e.target.value)}
            className="h-8 px-2.5 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
          >
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="7days">Last 7 Days</option>
            <option value="30days">Last 30 Days</option>
          </select>

          <button
            type="submit"
            className="h-8 px-3.5 rounded-[6px] bg-[#1d1f20] text-white font-medium text-xs hover:bg-black transition-colors"
          >
            Filter
          </button>

          {hasActiveFilters && (
            <button
              type="button"
              onClick={handleResetFilters}
              className="text-xs font-medium text-[#c4242a] hover:underline px-1 py-1"
            >
              Reset
            </button>
          )}
        </form>
      </div>

      {/* Bulk Action Bar (Visible when rows are selected) */}
      {selectedIds.length > 0 && (
        <div className="bg-[#131416] text-white rounded-[8px] p-2.5 px-4 flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-2 text-xs">
            <span className="w-2 h-2 rounded-full bg-[#1e7b4f]" />
            <span className="font-medium">{selectedIds.length} leads selected</span>
          </div>

          <form onSubmit={handleExecuteBulkAction} className="flex flex-wrap items-center gap-2">
            <select
              value={bulkAction}
              onChange={(e) => setBulkAction(e.target.value)}
              className="px-2.5 py-1 rounded-[4px] bg-[#1d1f20] border border-[#353736] text-xs font-medium text-white focus:outline-none"
            >
              <option value="">Bulk Actions...</option>
              {leadStages.map((st) => (
                <option key={st.id || st.slug} value={`mark_${st.slug}`}>
                  Move to: {st.name}
                </option>
              ))}
              <option value="delete">Delete Selected</option>
            </select>

            <button
              type="submit"
              disabled={bulkProcessing}
              className="px-3 py-1 rounded-[4px] bg-white text-[#131416] font-medium text-xs hover:bg-[#ebedee] transition-colors disabled:opacity-50"
            >
              {bulkProcessing ? 'Applying...' : 'Apply Action'}
            </button>
            <button
              type="button"
              onClick={() => setSelectedIds([])}
              className="px-2 py-1 text-xs text-[#cdced1] hover:text-white"
            >
              Cancel
            </button>
          </form>
        </div>
      )}

      {/* Submissions Table Card */}
      <div className="bg-white border border-[#e8eaec] rounded-[8px] overflow-hidden">
        {loading ? (
          <div className="divide-y divide-[#f0f1f2]">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="py-3.5 px-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-3 flex-1 min-w-[200px]">
                  <div className="w-8 h-8 rounded-full skeleton-shimmer shrink-0" />
                  <div className="space-y-1.5 flex-1">
                    <div className="h-3.5 w-36 rounded-md skeleton-shimmer" />
                    <div className="h-2.5 w-24 rounded-md skeleton-shimmer" />
                  </div>
                </div>
                <div className="h-3.5 w-28 rounded-md skeleton-shimmer hidden md:block" />
                <div className="h-6 w-20 rounded-full skeleton-shimmer" />
                <div className="h-3.5 w-20 rounded-md skeleton-shimmer hidden lg:block" />
                <div className="flex items-center gap-1.5">
                  <div className="w-6 h-6 rounded-md skeleton-shimmer" />
                  <div className="w-6 h-6 rounded-md skeleton-shimmer" />
                </div>
              </div>
            ))}
          </div>
        ) : submissions.length === 0 ? (
          <div className="p-16 text-center">
            <div className="w-10 h-10 rounded-[8px] bg-[#f4f6f8] text-[#5d5f60] flex items-center justify-center mx-auto mb-3">
              <Users className="w-5 h-5" />
            </div>
            <h3 className="text-sm font-semibold text-[#131416] mb-1">No Leads Found</h3>
            <p className="text-xs text-[#5d5f60] max-w-sm mx-auto">
              {hasActiveFilters
                ? 'No leads matched your search query. Try broadening your filters.'
                : 'New submissions will appear here in real-time as visitors complete your funnels.'}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-[#f0f1f2] bg-[#f9fbfc] text-xs font-medium text-[#5d5f60]">
                  <th className="py-2.5 px-3 w-10 text-center">
                    <input
                      type="checkbox"
                      checked={
                        submissions.length > 0 && selectedIds.length === submissions.length
                      }
                      onChange={handleToggleSelectAll}
                      className="w-3.5 h-3.5 rounded text-[#1b6c80] accent-[#1b6c80] border-[#cdced1]"
                    />
                  </th>
                  <th className="py-2.5 px-3 min-w-[200px]">Lead & Contact</th>
                  <th className="py-2.5 px-3 min-w-[170px]">Funnel & Key Answers</th>
                  <th className="py-2.5 px-3 min-w-[110px]">Instant Quote</th>
                  <th className="py-2.5 px-3 min-w-[150px]">Lead Stage</th>
                  <th className="py-2.5 px-3 min-w-[130px]">Destination</th>
                  <th className="py-2.5 px-3 min-w-[120px]">Time</th>
                  <th className="py-2.5 px-3 text-right min-w-[130px]">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#f0f1f2] text-xs">
                {submissions.map((sub) => {
                  const leadName =
                    sub.lead_name ||
                    (sub.lead_email ? sub.lead_email.split('@')[0] : `Visitor #${sub.id}`);
                  const avatarLetter = leadName.charAt(0).toUpperCase();
                  const phone = sub.lead_phone || '';
                  const cleanPhone = phone.replace(/[^0-9]/g, '');
                  const currentLeadStatus = sub.lead_status || 'new';
                  const answers = sub.answers || [];

                  const isChecked = selectedIds.includes(sub.id);
                  const isEmailCopied = copiedId === `email-${sub.id}`;
                  const isPhoneCopied = copiedId === `phone-${sub.id}`;

                  const waMsg = encodeURIComponent(
                    `Hi ${leadName}, reaching out regarding your submission on ${sub.funnel_name}!`
                  );

                  return (
                    <tr
                      key={sub.id}
                      className={`hover:bg-[#f4f6f8] transition-colors ${
                        isChecked ? 'bg-[#f4f6f8]' : ''
                      }`}
                    >
                      {/* Checkbox */}
                      <td className="py-2.5 px-3 text-center">
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={() => handleToggleRow(sub.id)}
                          className="w-3.5 h-3.5 rounded text-[#1b6c80] accent-[#1b6c80] border-[#cdced1]"
                        />
                      </td>

                      {/* Lead Name & Contact Details */}
                      <td className="py-2.5 px-3">
                        <div className="flex items-start gap-2.5">
                          {/* Avatar with Rotated Category Hue Tint */}
                          <div className={`w-7 h-7 rounded-lg font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs ${AVATAR_PALETTE[sub.id % AVATAR_PALETTE.length]}`}>
                            {avatarLetter}
                          </div>

                          <div className="min-w-0 space-y-0.5">
                            <div className="flex items-center gap-1.5">
                              <Link
                                to={`/admin/submissions/${sub.id}`}
                                className="font-semibold text-[#131416] hover:text-[#1d5fd6] truncate transition-colors"
                              >
                                {leadName}
                              </Link>
                              <span className="text-[11px] text-[#5d5f60] font-mono">
                                #{sub.id}
                              </span>
                            </div>

                            {/* Email */}
                            {sub.lead_email ? (
                              <div className="flex items-center gap-1 text-[11px] text-[#5d5f60] truncate">
                                <span className="truncate">{sub.lead_email}</span>
                                <button
                                  type="button"
                                  onClick={() =>
                                    copyToClipboard(sub.lead_email!, `email-${sub.id}`)
                                  }
                                  title="Copy email"
                                  className="text-[#5d5f60] hover:text-[#131416] p-0.5"
                                >
                                  {isEmailCopied ? (
                                    <Check className="w-3 h-3 text-[#2f7a45]" />
                                  ) : (
                                    <Copy className="w-3 h-3" />
                                  )}
                                </button>
                              </div>
                            ) : (
                              <span className="text-[11px] text-[#5d5f60] block">
                                No email provided
                              </span>
                            )}

                            {/* Phone */}
                            {phone && (
                              <div className="flex items-center gap-1 font-mono text-[11px] text-[#131416]">
                                <span>{phone}</span>
                                <button
                                  type="button"
                                  onClick={() => copyToClipboard(cleanPhone, `phone-${sub.id}`)}
                                  title="Copy phone"
                                  className="text-[#5d5f60] hover:text-[#131416] p-0.5"
                                >
                                  {isPhoneCopied ? (
                                    <Check className="w-3 h-3 text-[#2f7a45]" />
                                  ) : (
                                    <Copy className="w-3 h-3" />
                                  )}
                                </button>
                              </div>
                            )}
                          </div>
                        </div>
                      </td>

                      {/* Funnel & Key Qualification Answers */}
                      <td className="py-2.5 px-3">
                        <div className="space-y-1">
                          <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]">
                            {sub.funnel_name}
                          </span>

                          {/* Key answers previews */}
                          {answers.length > 0 ? (
                            <div className="flex flex-wrap gap-1 max-w-[220px]">
                              {answers.slice(0, 2).map((ans, idx) => (
                                <span
                                  key={idx}
                                  className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] bg-[#f8fafc] text-[#131416] border border-[#e2e5e9] truncate max-w-[200px]"
                                  title={`${ans.question}: ${ans.answer}`}
                                >
                                  <span className="truncate">{ans.answer}</span>
                                </span>
                              ))}
                              {answers.length > 2 && (
                                <span className="text-[10px] text-[#5d5f60] self-center">
                                  +{answers.length - 2}
                                </span>
                              )}
                            </div>
                          ) : (
                            <span className="text-[10px] text-[#5d5f60] block">
                              No answers yet
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Instant Quote / Calculator Value */}
                      <td className="py-2.5 px-3">
                        {sub.formatted_quote ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]">
                            {sub.formatted_quote}
                          </span>
                        ) : sub.calculated_value !== null && sub.calculated_value !== undefined ? (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]">
                            ${Number(sub.calculated_value).toLocaleString(undefined, {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2,
                            })}
                          </span>
                        ) : (
                          <span className="text-[#cdced1] font-mono">—</span>
                        )}
                      </td>

                      {/* Lead Stage (CRM Pipeline) */}
                      <td className="py-2.5 px-3">
                        <select
                          value={currentLeadStatus}
                          onChange={(e) => handleStageChange(sub.id, e.target.value)}
                          className={`h-7 px-2.5 rounded-lg border text-xs cursor-pointer focus:outline-none transition ${getStageBadgeStyle(currentLeadStatus)}`}
                        >
                          {leadStages.map((st) => (
                            <option key={st.id || st.slug} value={st.slug} className="bg-white text-[#131416]">
                              {st.name}
                            </option>
                          ))}
                        </select>
                      </td>

                      {/* Destination Reached */}
                      <td className="py-2.5 px-3 text-[#5d5f60] truncate max-w-[130px]">
                        <span
                          className="font-medium text-[#131416] block truncate"
                          title={sub.destination_name || 'None'}
                        >
                          {sub.destination_name || '—'}
                        </span>
                        {sub.status === 'form_pending_video_gate' ? (
                          <span className="inline-flex items-center gap-1 text-[10px] text-[#92400e] bg-[#fffbeb] px-1.5 py-0.5 rounded border border-[#fef3c7]">
                            Video gate pending
                          </span>
                        ) : (
                          <span className="text-[10px] text-[#5d5f60] capitalize block">
                            {sub.status.replace(/_/g, ' ')}
                          </span>
                        )}
                      </td>

                      {/* Date & Time */}
                      <td className="py-2.5 px-3 text-[#5d5f60]">
                        <span className="block text-[#131416]">
                          {sub.created_at || sub.started_at
                            ? new Date(sub.created_at || sub.started_at!).toLocaleDateString(
                                undefined,
                                {
                                  month: 'short',
                                  day: 'numeric',
                                  hour: '2-digit',
                                  minute: '2-digit',
                                }
                              )
                            : '—'}
                        </span>
                      </td>

                      {/* 1-Click Outreach Actions */}
                      <td className="py-2.5 px-3 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          {cleanPhone && (
                            <>
                              <a
                                href={`https://wa.me/${cleanPhone}?text=${waMsg}`}
                                target="_blank"
                                rel="noreferrer"
                                title="Chat on WhatsApp"
                                className="p-1.5 rounded-lg bg-[#dcfae5] hover:bg-[#bbf0cc] text-[#2f7a45] border border-[#bbf7d0] transition-colors"
                              >
                                <MessageCircle className="w-3.5 h-3.5" />
                              </a>

                              <a
                                href={`tel:${cleanPhone}`}
                                title="Call lead"
                                className="p-1.5 rounded-lg bg-[#dcf1ff] hover:bg-[#b9e6fe] text-[#1d5fd6] border border-[#b9e6fe] transition-colors"
                              >
                                <Phone className="w-3.5 h-3.5" />
                              </a>
                            </>
                          )}

                          <Link
                            to={`/admin/submissions/${sub.id}`}
                            title="Inspect Full Journey"
                            className="inline-flex items-center gap-1 px-2 py-1 rounded-[4px] border border-[#e8eaec] hover:bg-[#f4f6f8] text-[11px] font-medium text-[#131416] transition-colors"
                          >
                            <span>Inspect</span>
                            <ArrowRight className="w-3 h-3" />
                          </Link>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination Bar */}
        {pagination.total_pages > 1 && (
          <div className="p-3 border-t border-[#f0f1f2] flex items-center justify-between text-xs text-[#5d5f60]">
            <span>
              Page {pagination.current_page} of {pagination.total_pages} ({pagination.total_records} total)
            </span>

            <div className="flex items-center gap-1">
              <button
                type="button"
                disabled={pagination.current_page <= 1}
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                className="p-1 rounded-[4px] border border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8] disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <ChevronLeft className="w-3.5 h-3.5" />
              </button>

              {Array.from({ length: pagination.total_pages }, (_, i) => i + 1)
                .filter(
                  (p) =>
                    p === 1 ||
                    p === pagination.total_pages ||
                    Math.abs(p - pagination.current_page) <= 2
                )
                .map((pageNum, idx, arr) => (
                  <React.Fragment key={pageNum}>
                    {idx > 0 && arr[idx - 1] !== pageNum - 1 && (
                      <span className="px-1 text-[#5d5f60]">...</span>
                    )}
                    <button
                      type="button"
                      onClick={() => setCurrentPage(pageNum)}
                      className={`w-6 h-6 rounded-[4px] text-xs font-medium transition-colors ${
                        pagination.current_page === pageNum
                          ? 'bg-[#1d1f20] text-white'
                          : 'border border-[#e8eaec] text-[#131416] hover:bg-[#f4f6f8]'
                      }`}
                    >
                      {pageNum}
                    </button>
                  </React.Fragment>
                ))}

              <button
                type="button"
                disabled={pagination.current_page >= pagination.total_pages}
                onClick={() => setCurrentPage((p) => Math.min(pagination.total_pages, p + 1))}
                className="p-1 rounded-[4px] border border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8] disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Customize Lead Stages Modal */}
      {isStagesModalOpen && (
        <div className="fixed inset-0 z-50 bg-[#131416]/40 flex items-center justify-center p-4">
          <div className="bg-white rounded-[12px] max-w-lg w-full overflow-hidden border border-[#e8eaec]">
            <div className="px-5 py-3.5 border-b border-[#f0f1f2] flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Settings2 className="w-4 h-4 text-[#5d5f60]" />
                <h3 className="text-sm font-semibold text-[#131416]">Custom Lead Stages</h3>
              </div>
              <button
                type="button"
                onClick={() => setIsStagesModalOpen(false)}
                className="text-[#5d5f60] hover:text-[#131416]"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-5 space-y-4">
              {/* Existing Stages List */}
              <div className="space-y-1.5">
                <label className="text-xs font-medium text-[#131416] block">Existing Stages</label>
                <div className="space-y-1 max-h-56 overflow-y-auto pr-1">
                  {leadStages.map((stage) => (
                    <div
                      key={stage.id || stage.slug}
                      className="flex items-center justify-between p-2 rounded-[6px] border border-[#e8eaec] bg-[#f4f6f8] text-xs"
                    >
                      <div className="flex items-center gap-2">
                        <span
                          className="w-2.5 h-2.5 rounded-full"
                          style={{ backgroundColor: stage.color || '#1b6c80' }}
                        />
                        <span className="font-medium text-[#131416]">{stage.name}</span>
                        <span className="text-[10px] text-[#5d5f60] font-mono">({stage.slug})</span>
                      </div>
                      <div className="flex items-center gap-2">
                        {stage.is_system ? (
                          <span className="text-[10px] text-[#5d5f60] bg-[#ebedee] px-1.5 py-0.5 rounded">
                            System
                          </span>
                        ) : (
                          <button
                            type="button"
                            onClick={() => handleDeleteStage(stage.id)}
                            className="text-[#c4242a] hover:underline p-1 text-xs"
                            title="Delete custom stage"
                          >
                            Delete
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Add New Custom Stage */}
              <form onSubmit={handleCreateStage} className="border-t border-[#f0f1f2] pt-3 space-y-2">
                <label className="text-xs font-medium text-[#131416] block">Create New Stage</label>
                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    value={newStageName}
                    onChange={(e) => setNewStageName(e.target.value)}
                    placeholder="e.g. Qualified Specialist"
                    className="flex-1 px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
                  />
                  <input
                    type="color"
                    value={newStageColor}
                    onChange={(e) => setNewStageColor(e.target.value)}
                    title="Choose stage color"
                    className="w-8 h-8 p-0.5 rounded-[6px] border border-[#e8eaec] cursor-pointer"
                  />
                  <button
                    type="submit"
                    disabled={creatingStage || !newStageName.trim()}
                    className="px-3 py-1.5 rounded-[6px] bg-[#1d1f20] hover:bg-black font-medium text-xs text-white transition-colors disabled:opacity-50"
                  >
                    {creatingStage ? 'Adding...' : 'Add'}
                  </button>
                </div>
              </form>
            </div>

            <div className="px-5 py-2.5 bg-[#f4f6f8] border-t border-[#f0f1f2] flex justify-end">
              <button
                type="button"
                onClick={() => setIsStagesModalOpen(false)}
                className="px-3 py-1 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416] hover:bg-white"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
      {/* Upgrade Modal */}
      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName="1-Click CSV Lead Export"
        description="Downloading lead records, form submission answers, and visitor timeline records as CSV files is available on Pro and higher plans. Upgrade your workspace to export leads anytime."
        requiredPlan="Pro"
      />
    </div>
  );
};

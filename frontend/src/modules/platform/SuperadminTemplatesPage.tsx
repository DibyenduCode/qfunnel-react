import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { showConfirm, showAlert, showToast } from '../../utils/swal';
import {
  LayoutTemplate,
  Plus,
  Upload,
  Download,
  Trash2,
  Edit2,
  CheckCircle,
  AlertCircle,
  Star,
  Eye,
  GitFork,
  Calculator,
  Search,
  Sparkles,
  Layers,
  ArrowRight,
  X,
  FileCode,
  Check,
  RefreshCw,
  FolderPlus,
  ChevronRight,
  ChevronDown,
  Hash,
  Sliders,
  Tag,
  SearchX,
  RotateCcw,
  Filter
} from 'lucide-react';

interface TemplateItem {
  id: number;
  name: string;
  slug: string;
  description: string;
  category: string;
  funnel_mode: 'quiz' | 'calculator';
  thumbnail_url: string | null;
  badge: string | null;
  node_count: number;
  is_active: boolean;
  is_featured: boolean;
  display_order: number;
  created_at: string;
  creator_name?: string;
  graph?: {
    nodes?: any[];
    edges?: any[];
  };
}

interface SourceFunnelItem {
  id: number;
  name: string;
  slug: string;
  funnel_mode: string;
  tenant_name: string;
  node_count: number;
}

interface CategoryItem {
  id: string;
  label: string;
  count: number;
}

const DEFAULT_CATEGORIES: CategoryItem[] = [
  { id: 'lead_generation', label: 'Lead Generation', count: 0 },
  { id: 'calculator', label: 'Calculators & Quotes', count: 0 },
  { id: 'real_estate', label: 'Real Estate', count: 0 },
  { id: 'b2b_saas', label: 'B2B & SaaS', count: 0 },
  { id: 'health_wellness', label: 'Health & Wellness', count: 0 },
  { id: 'marketing', label: 'Marketing & VSL', count: 0 },
  { id: 'ecommerce', label: 'E-Commerce & Retail', count: 0 },
  { id: 'education', label: 'Education & Coaching', count: 0 },
  { id: 'agency', label: 'Agencies & Consulting', count: 0 },
  { id: 'finance', label: 'Finance & Insurance', count: 0 },
  { id: 'healthcare', label: 'Dental & Healthcare', count: 0 },
  { id: 'home_services', label: 'Home & Contractor Services', count: 0 },
];

export const SuperadminTemplatesPage: React.FC = () => {
  const [templates, setTemplates] = useState<TemplateItem[]>([]);
  const [sourceFunnels, setSourceFunnels] = useState<SourceFunnelItem[]>([]);
  const [categories, setCategories] = useState<CategoryItem[]>(DEFAULT_CATEGORIES);
  const [stats, setStats] = useState({ total: 0, active: 0, featured: 0, calculator: 0 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [modeFilter, setModeFilter] = useState<'all' | 'quiz' | 'calculator'>('all');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
  const [moreCategoriesOpen, setMoreCategoriesOpen] = useState(false);
  const [feedbackMsg, setFeedbackMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Modals
  const [uploadModalOpen, setUploadModalOpen] = useState(false);
  const [convertModalOpen, setConvertModalOpen] = useState(false);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [previewModalOpen, setPreviewModalOpen] = useState(false);
  const [categoryModalOpen, setCategoryModalOpen] = useState(false);
  const [activeTemplate, setActiveTemplate] = useState<TemplateItem | null>(null);

  // Category Manager State
  const [newCategoryLabel, setNewCategoryLabel] = useState('');
  const [categorySubmitting, setCategorySubmitting] = useState(false);

  // Upload JSON Modal State
  const [jsonText, setJsonText] = useState('');
  const [uploadName, setUploadName] = useState('');
  const [uploadCategory, setUploadCategory] = useState('lead_generation');
  const [uploadCustomCategory, setUploadCustomCategory] = useState(false);
  const [uploadNewCategoryName, setUploadNewCategoryName] = useState('');
  const [uploadMode, setUploadMode] = useState<'quiz' | 'calculator'>('quiz');
  const [uploadBadge, setUploadBadge] = useState('');
  const [uploadSubmitting, setUploadSubmitting] = useState(false);

  // Convert Funnel Modal State
  const [selectedFunnelId, setSelectedFunnelId] = useState<number | ''>('');
  const [convertName, setConvertName] = useState('');
  const [convertCategory, setConvertCategory] = useState('lead_generation');
  const [convertCustomCategory, setConvertCustomCategory] = useState(false);
  const [convertNewCategoryName, setConvertNewCategoryName] = useState('');
  const [convertBadge, setConvertBadge] = useState('');
  const [convertDescription, setConvertDescription] = useState('');
  const [convertSubmitting, setConvertSubmitting] = useState(false);

  // Edit Modal State
  const [editName, setEditName] = useState('');
  const [editSlug, setEditSlug] = useState('');
  const [editDesc, setEditDesc] = useState('');
  const [editCategory, setEditCategory] = useState('lead_generation');
  const [editCustomCategory, setEditCustomCategory] = useState(false);
  const [editNewCategoryName, setEditNewCategoryName] = useState('');
  const [editMode, setEditMode] = useState<'quiz' | 'calculator'>('quiz');
  const [editBadge, setEditBadge] = useState('');
  const [editThumbnailUrl, setEditThumbnailUrl] = useState('');
  const [editOrder, setEditOrder] = useState(0);
  const [editIsActive, setEditIsActive] = useState(true);
  const [editIsFeatured, setEditIsFeatured] = useState(false);
  const [editSyncFunnelId, setEditSyncFunnelId] = useState<number | ''>('');
  const [editSubmitting, setEditSubmitting] = useState(false);

  useEffect(() => {
    loadTemplates();
  }, [search]);

  const loadTemplates = async () => {
    try {
      setLoading(true);
      const query = search ? `?search=${encodeURIComponent(search)}` : '';
      const res = await api.get(`/api/platform/templates${query}`);
      setTemplates(res.templates || []);
      setSourceFunnels(res.source_funnels || []);
      if (res.categories && res.categories.length > 0) {
        setCategories(res.categories);
      }
      setStats(res.stats || { total: 0, active: 0, featured: 0, calculator: 0 });
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Failed to load templates' });
    } finally {
      setLoading(false);
    }
  };

  const handleCreateCategory = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCategoryLabel.trim()) return;

    setCategorySubmitting(true);
    try {
      const res = await api.post('/api/platform/template-categories', {
        label: newCategoryLabel.trim(),
      });
      setFeedbackMsg({ type: 'success', text: `Category "${res.label}" created successfully!` });
      setNewCategoryLabel('');
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Failed to create category' });
    } finally {
      setCategorySubmitting(false);
    }
  };

  const handleDeleteCategory = async (cat: CategoryItem) => {
    if (cat.count > 0) {
      await showAlert(
        'Cannot Delete Category',
        `Category "${cat.label}" is currently assigned to ${cat.count} template(s). Reassign or delete those templates first.`,
        'warning'
      );
      return;
    }

    const confirmed = await showConfirm(
      'Delete Category',
      `Are you sure you want to delete category "${cat.label}"?`,
      'Yes, Delete',
      true
    );
    if (!confirmed) {
      return;
    }

    try {
      await api.post(`/api/platform/template-categories/${cat.id}/delete`);
      setFeedbackMsg({ type: 'success', text: `Category "${cat.label}" deleted.` });
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Could not delete category' });
    }
  };

  const handleToggleActive = async (template: TemplateItem) => {
    try {
      const nextState = !template.is_active;
      await api.post(`/api/platform/templates/${template.id}/update`, { is_active: nextState ? 1 : 0 });
      setTemplates((prev) =>
        prev.map((t) => (t.id === template.id ? { ...t, is_active: nextState } : t))
      );
      setFeedbackMsg({
        type: 'success',
        text: `Template '${template.name}' ${nextState ? 'published to' : 'hidden from'} library.`,
      });
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Could not update status' });
    }
  };

  const handleToggleFeatured = async (template: TemplateItem) => {
    try {
      const nextState = !template.is_featured;
      await api.post(`/api/platform/templates/${template.id}/update`, { is_featured: nextState ? 1 : 0 });
      setTemplates((prev) =>
        prev.map((t) => (t.id === template.id ? { ...t, is_featured: nextState } : t))
      );
      setFeedbackMsg({
        type: 'success',
        text: `Template '${template.name}' ${nextState ? 'marked as Featured' : 'unstarred'}.`,
      });
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Could not toggle featured' });
    }
  };

  const handleDelete = async (template: TemplateItem) => {
    const confirmed = await showConfirm(
      'Delete Template',
      `Are you sure you want to permanently delete template "${template.name}"? This action cannot be undone.`,
      'Yes, Delete Template',
      true
    );
    if (!confirmed) {
      return;
    }

    try {
      await api.post(`/api/platform/templates/${template.id}/delete`);
      setTemplates((prev) => prev.filter((t) => t.id !== template.id));
      setFeedbackMsg({ type: 'success', text: `Template '${template.name}' removed.` });
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Could not delete template' });
    }
  };

  const handleExport = (template: TemplateItem) => {
    window.open(`/api/platform/templates/${template.id}/export`, '_blank');
  };

  const handleUploadSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setUploadSubmitting(true);
    setFeedbackMsg(null);

    try {
      let parsedGraph: any = null;
      if (jsonText.trim()) {
        const decoded = JSON.parse(jsonText.trim());
        parsedGraph = decoded.graph || decoded;
      }

      const finalCat = uploadCustomCategory && uploadNewCategoryName.trim()
        ? uploadNewCategoryName.trim()
        : uploadCategory;

      await api.post('/api/platform/templates', {
        name: uploadName,
        category: finalCat,
        funnel_mode: uploadMode,
        badge: uploadBadge || null,
        graph: parsedGraph || { nodes: [], edges: [] },
      });

      setFeedbackMsg({ type: 'success', text: `Template '${uploadName}' uploaded successfully.` });
      setUploadModalOpen(false);
      setJsonText('');
      setUploadName('');
      setUploadBadge('');
      setUploadCustomCategory(false);
      setUploadNewCategoryName('');
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Failed to upload template' });
    } finally {
      setUploadSubmitting(false);
    }
  };

  const handleConvertSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedFunnelId) return;

    setConvertSubmitting(true);
    setFeedbackMsg(null);

    try {
      const finalCat = convertCustomCategory && convertNewCategoryName.trim()
        ? convertNewCategoryName.trim()
        : convertCategory;

      const res = await api.post('/api/platform/templates/from-funnel', {
        funnel_id: selectedFunnelId,
        name: convertName || undefined,
        description: convertDescription || undefined,
        category: finalCat,
        badge: convertBadge || null,
      });

      setFeedbackMsg({
        type: 'success',
        text: `Funnel successfully converted to global template '${res.name}'!`,
      });
      setConvertModalOpen(false);
      setSelectedFunnelId('');
      setConvertName('');
      setConvertDescription('');
      setConvertBadge('');
      setConvertCustomCategory(false);
      setConvertNewCategoryName('');
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Failed to convert funnel' });
    } finally {
      setConvertSubmitting(false);
    }
  };

  const openEditModal = (tpl: TemplateItem) => {
    setActiveTemplate(tpl);
    setEditName(tpl.name);
    setEditSlug(tpl.slug);
    setEditDesc(tpl.description || '');
    setEditCategory(tpl.category);
    setEditCustomCategory(false);
    setEditNewCategoryName('');
    setEditMode(tpl.funnel_mode || 'quiz');
    setEditBadge(tpl.badge || '');
    setEditThumbnailUrl(tpl.thumbnail_url || '');
    setEditOrder(tpl.display_order || 0);
    setEditIsActive(tpl.is_active);
    setEditIsFeatured(tpl.is_featured);
    setEditSyncFunnelId('');
    setEditModalOpen(true);
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeTemplate) return;

    setEditSubmitting(true);
    try {
      const finalCat = editCustomCategory && editNewCategoryName.trim()
        ? editNewCategoryName.trim()
        : editCategory;

      await api.post(`/api/platform/templates/${activeTemplate.id}/update`, {
        name: editName,
        slug: editSlug || undefined,
        description: editDesc,
        category: finalCat,
        funnel_mode: editMode,
        badge: editBadge || null,
        thumbnail_url: editThumbnailUrl || null,
        display_order: editOrder,
        is_active: editIsActive ? 1 : 0,
        is_featured: editIsFeatured ? 1 : 0,
        source_funnel_id: editSyncFunnelId || undefined,
      });

      setFeedbackMsg({ type: 'success', text: `Template '${editName}' updated successfully.` });
      setEditModalOpen(false);
      await loadTemplates();
    } catch (err: any) {
      setFeedbackMsg({ type: 'error', text: err.message || 'Failed to update template' });
    } finally {
      setEditSubmitting(false);
    }
  };

  const openPreview = async (tpl: TemplateItem) => {
    try {
      const full = await api.get(`/api/templates/${tpl.id}`);
      setActiveTemplate(full);
      setPreviewModalOpen(true);
    } catch {
      setActiveTemplate(tpl);
      setPreviewModalOpen(true);
    }
  };

  const selectedFunnelDetail = sourceFunnels.find((f) => f.id === selectedFunnelId);

  const filteredTemplates = templates.filter((t) => {
    if (categoryFilter !== 'all' && t.category !== categoryFilter) return false;
    if (modeFilter !== 'all' && t.funnel_mode !== modeFilter) return false;
    if (statusFilter === 'active' && !t.is_active) return false;
    if (statusFilter === 'inactive' && t.is_active) return false;
    if (search.trim()) {
      const q = search.toLowerCase();
      const matchName = t.name.toLowerCase().includes(q);
      const matchSlug = t.slug.toLowerCase().includes(q);
      const matchDesc = Boolean(t.description && t.description.toLowerCase().includes(q));
      if (!matchName && !matchSlug && !matchDesc) return false;
    }
    return true;
  });

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center shadow-xs">
              <LayoutTemplate className="w-5 h-5" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-[#131416] tracking-tight">
                Global Workflow Templates
              </h1>
              <p className="text-xs text-[#5d5f60]">
                Super Admin control panel to convert platform funnels into global templates, organize categories, and manage workflows.
              </p>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2.5 flex-wrap">
          {/* Dedicated Category Manager Button */}
          <button
            type="button"
            onClick={() => setCategoryModalOpen(true)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-white border border-[#e2e5e9] hover:bg-[#f8fafc] hover:text-[#131416] transition shadow-xs"
          >
            <FolderPlus className="w-3.5 h-3.5 text-[#1d5fd6]" />
            <span>Manage Categories ({categories.length})</span>
          </button>

          {/* Upload JSON Button */}
          <button
            type="button"
            onClick={() => setUploadModalOpen(true)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-white border border-[#e2e5e9] hover:bg-[#f8fafc] hover:text-[#131416] transition shadow-xs"
          >
            <Upload className="w-3.5 h-3.5" />
            <span>Upload JSON</span>
          </button>

          {/* Primary Convert Funnel to Template Button */}
          <button
            type="button"
            onClick={() => setConvertModalOpen(true)}
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>+ Convert Funnel to Template</span>
          </button>
        </div>
      </div>

      {/* Toast Feedback */}
      {feedbackMsg && (
        <div
          className={`p-3.5 rounded-xl border text-xs flex items-center justify-between transition-all ${
            feedbackMsg.type === 'success'
              ? 'bg-[#ecfdf5] border-[#a7f3d0] text-[#065f46]'
              : 'bg-[#fef2f2] border-[#fecaca] text-[#991b1b]'
          }`}
        >
          <div className="flex items-center gap-2">
            {feedbackMsg.type === 'success' ? (
              <CheckCircle className="w-4 h-4 text-[#10b981]" />
            ) : (
              <AlertCircle className="w-4 h-4 text-[#ef4444]" />
            )}
            <span>{feedbackMsg.text}</span>
          </div>
          <button
            type="button"
            onClick={() => setFeedbackMsg(null)}
            className="p-1 hover:opacity-70 rounded"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {/* Metrics Row */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-[#5d5f60]">Total Templates</span>
            <div className="w-7 h-7 rounded-lg bg-[#eff6ff] text-[#1d5fd6] flex items-center justify-center">
              <Layers className="w-3.5 h-3.5" />
            </div>
          </div>
          <p className="text-2xl font-bold text-[#131416] mt-2">{stats.total}</p>
          <p className="text-[11px] text-[#5d5f60] mt-0.5">Platform catalog templates</p>
        </div>

        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-[#5d5f60]">Published & Active</span>
            <div className="w-7 h-7 rounded-lg bg-[#ecfdf5] text-[#059669] flex items-center justify-center">
              <Check className="w-3.5 h-3.5" />
            </div>
          </div>
          <p className="text-2xl font-bold text-[#131416] mt-2">{stats.active}</p>
          <p className="text-[11px] text-[#5d5f60] mt-0.5">Visible in User Library</p>
        </div>

        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-[#5d5f60]">Featured Starred</span>
            <div className="w-7 h-7 rounded-lg bg-[#fef3c7] text-[#d97706] flex items-center justify-center">
              <Star className="w-3.5 h-3.5" />
            </div>
          </div>
          <p className="text-2xl font-bold text-[#131416] mt-2">{stats.featured}</p>
          <p className="text-[11px] text-[#5d5f60] mt-0.5">Pinned to top of library</p>
        </div>

        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-[#5d5f60]">Total Categories</span>
            <div className="w-7 h-7 rounded-lg bg-[#eff6ff] text-[#1d5fd6] flex items-center justify-center">
              <FolderPlus className="w-3.5 h-3.5" />
            </div>
          </div>
          <p className="text-2xl font-bold text-[#131416] mt-2">{categories.length}</p>
          <p className="text-[11px] text-[#5d5f60] mt-0.5">Organized categories</p>
        </div>
      </div>

      {/* Redesigned Clean Filter & Search Controls */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-3">
        {/* Top Controls: Search Bar + Mode Filter + Status Filter + Quick Reset */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
          {/* Search Box with Clear Button */}
          <div className="relative flex-1 max-w-md">
            <Search className="w-4 h-4 text-[#5d5f60] absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search templates by title, slug, or keywords..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-9 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] text-[#131416] outline-hidden transition shadow-2xs placeholder:text-[#94a3b8]"
            />
            {search.trim() && (
              <button
                type="button"
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#131416] p-0.5 rounded transition cursor-pointer"
                title="Clear search"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          {/* Filters & Actions */}
          <div className="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            {/* Mode Filter */}
            <div className="relative">
              <select
                value={modeFilter}
                onChange={(e) => setModeFilter(e.target.value as any)}
                aria-label="Filter templates by workflow mode"
                className="px-3 py-2 text-xs font-semibold bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] rounded-xl text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition cursor-pointer"
              >
                <option value="all">All Modes ({templates.length})</option>
                <option value="quiz">Quiz / Funnel ({templates.filter(t => t.funnel_mode !== 'calculator').length})</option>
                <option value="calculator">Interactive Calculator ({templates.filter(t => t.funnel_mode === 'calculator').length})</option>
              </select>
            </div>

            {/* Status Filter */}
            <div className="relative">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value as any)}
                aria-label="Filter templates by publication status"
                className="px-3 py-2 text-xs font-semibold bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] rounded-xl text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition cursor-pointer"
              >
                <option value="all">All Status</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive / Draft</option>
              </select>
            </div>

            {/* Reset Filters button if any active */}
            {(search.trim() || categoryFilter !== 'all' || modeFilter !== 'all' || statusFilter !== 'all') && (
              <button
                type="button"
                onClick={() => {
                  setSearch('');
                  setCategoryFilter('all');
                  setModeFilter('all');
                  setStatusFilter('all');
                }}
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#ef4444] bg-[#fee2e2]/60 hover:bg-[#fee2e2] border border-[#fecaca] transition cursor-pointer"
                title="Reset all filters"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                <span>Reset</span>
              </button>
            )}
          </div>
        </div>

        {/* Category Pills Navigation Strip */}
        <div className="pt-2.5 border-t border-[#edf0f3] flex items-center gap-1.5 flex-wrap">
          {/* All Categories */}
          <button
            onClick={() => setCategoryFilter('all')}
            className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
              categoryFilter === 'all'
                ? 'bg-[#1d5fd6] text-white shadow-xs'
                : 'bg-[#f8fafc] text-[#5d5f60] hover:text-[#131416] hover:bg-[#edf0f3] border border-transparent'
            }`}
          >
            All Categories ({templates.length})
          </button>

          {/* Active Categories (with templates) */}
          {categories
            .filter((c) => c.count > 0)
            .map((cat) => (
              <button
                key={cat.id}
                onClick={() => setCategoryFilter(cat.id)}
                className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 ${
                  categoryFilter === cat.id
                    ? 'bg-[#1d5fd6] text-white shadow-xs'
                    : 'bg-[#f8fafc] text-[#5d5f60] hover:text-[#131416] hover:bg-[#edf0f3] border border-transparent'
                }`}
              >
                <span>{cat.label}</span>
                <span
                  className={`px-1.5 py-0.2 rounded-full text-[10px] font-bold ${
                    categoryFilter === cat.id
                      ? 'bg-white/20 text-white'
                      : 'bg-[#e2e5e9] text-[#5d5f60]'
                  }`}
                >
                  {cat.count}
                </span>
              </button>
            ))}

          {/* Selected Empty Category Pill if active */}
          {categories.filter(c => c.count === 0).some(c => c.id === categoryFilter) && (
            <span className="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#eff6ff] text-[#1d5fd6] border border-[#bfdbfe] flex items-center gap-1.5">
              <span>{categories.find(c => c.id === categoryFilter)?.label}</span>
              <span className="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-[#bfdbfe]/50 text-[#1d5fd6]">0</span>
              <button
                type="button"
                onClick={() => setCategoryFilter('all')}
                className="hover:text-[#ef4444] ml-0.5 cursor-pointer"
                title="Clear category filter"
              >
                <X className="w-3 h-3" />
              </button>
            </span>
          )}

          {/* Other Categories Dropdown inline */}
          {categories.some((c) => c.count === 0) && (
            <select
              value={categories.filter(c => c.count === 0).some(c => c.id === categoryFilter) ? categoryFilter : ''}
              onChange={(e) => {
                if (e.target.value) setCategoryFilter(e.target.value);
              }}
              aria-label="Select an empty or inactive category"
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer outline-hidden border ${
                categories.filter(c => c.count === 0).some(c => c.id === categoryFilter)
                  ? 'bg-[#eff6ff] text-[#1d5fd6] border-[#bfdbfe]'
                  : 'bg-[#f8fafc] text-[#5d5f60] hover:text-[#131416] hover:bg-[#edf0f3] border-[#e2e5e9]'
              }`}
            >
              <option value="" disabled>
                {categories.filter(c => c.count === 0).some(c => c.id === categoryFilter)
                  ? `Category: ${categories.find(c => c.id === categoryFilter)?.label}`
                  : `Other Categories (${categories.filter(c => c.count === 0).length}) ▾`}
              </option>
              {categories.filter(c => c.count === 0).map((c) => (
                <option key={c.id} value={c.id}>
                  {c.label} (0)
                </option>
              ))}
            </select>
          )}
        </div>
      </div>

      {/* Templates Table / List */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl shadow-xs overflow-hidden">
        {loading ? (
          <div className="p-16 text-center text-xs text-[#5d5f60] flex flex-col items-center justify-center gap-3">
            <RefreshCw className="w-5 h-5 animate-spin text-[#1d5fd6]" />
            <span className="font-medium">Loading workflow templates catalog...</span>
          </div>
        ) : filteredTemplates.length === 0 ? (
          <div className="p-14 text-center space-y-4">
            <div className="w-14 h-14 rounded-2xl bg-[#f8fafc] border border-[#e2e5e9] text-[#5d5f60] mx-auto flex items-center justify-center shadow-2xs">
              {search.trim() || categoryFilter !== 'all' || modeFilter !== 'all' || statusFilter !== 'all' ? (
                <SearchX className="w-7 h-7 text-[#5d5f60]" />
              ) : (
                <LayoutTemplate className="w-7 h-7 text-[#1d5fd6]" />
              )}
            </div>

            <div className="space-y-1">
              <h3 className="text-base font-bold text-[#131416]">
                {search.trim()
                  ? `No templates matching "${search}"`
                  : categoryFilter !== 'all'
                  ? `No templates in "${categories.find(c => c.id === categoryFilter)?.label || categoryFilter}"`
                  : modeFilter !== 'all' || statusFilter !== 'all'
                  ? 'No templates match the selected filters'
                  : 'Workflow Template Library is Empty'}
              </h3>
              <p className="text-xs text-[#5d5f60] max-w-md mx-auto leading-relaxed">
                {search.trim() || categoryFilter !== 'all' || modeFilter !== 'all' || statusFilter !== 'all'
                  ? 'Try searching with different terms, selecting another category, or resetting your filters.'
                  : 'Get started by converting an existing funnel from any workspace into a reusable global template, or upload a JSON template.'}
              </p>
            </div>

            {search.trim() || categoryFilter !== 'all' || modeFilter !== 'all' || statusFilter !== 'all' ? (
              <div className="pt-1">
                <button
                  type="button"
                  onClick={() => {
                    setSearch('');
                    setCategoryFilter('all');
                    setModeFilter('all');
                    setStatusFilter('all');
                  }}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-[#1d5fd6] bg-[#eff6ff] hover:bg-[#dbeafe] border border-[#bfdbfe] transition cursor-pointer shadow-2xs"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Reset All Filters</span>
                </button>
              </div>
            ) : (
              <div className="flex items-center justify-center gap-2.5 pt-1">
                <button
                  type="button"
                  onClick={() => setConvertModalOpen(true)}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] shadow-xs transition cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" />
                  <span>+ Convert Funnel to Template</span>
                </button>
                <button
                  type="button"
                  onClick={() => setUploadModalOpen(true)}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] shadow-xs transition cursor-pointer"
                >
                  <Upload className="w-3.5 h-3.5" />
                  <span>Upload JSON</span>
                </button>
              </div>
            )}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="border-b border-[#edf0f3] bg-[#f8fafc] text-[#5d5f60] font-semibold">
                  <th className="py-3 px-4">Template Name & Summary</th>
                  <th className="py-3 px-3">Category</th>
                  <th className="py-3 px-3">Mode</th>
                  <th className="py-3 px-3">Steps</th>
                  <th className="py-3 px-3 text-center">Featured</th>
                  <th className="py-3 px-3 text-center">Active</th>
                  <th className="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#edf0f3]">
                {filteredTemplates.map((tpl) => {
                  const catItem = categories.find((c) => c.id === tpl.category);
                  const catLabel = catItem ? catItem.label : tpl.category.replace(/_/g, ' ');

                  return (
                    <tr key={tpl.id} className="hover:bg-[#f8fafc]/70 transition">
                      <td className="py-3.5 px-4">
                        <div className="flex items-start gap-2.5">
                          <div className="w-8 h-8 rounded-lg bg-[#f1f5f9] text-[#1d5fd6] flex items-center justify-center shrink-0 border border-[#e2e5e9]">
                            {tpl.funnel_mode === 'calculator' ? (
                              <Calculator className="w-4 h-4 text-[#10b981]" />
                            ) : (
                              <GitFork className="w-4 h-4 text-[#1d5fd6]" />
                            )}
                          </div>
                          <div>
                            <div className="flex items-center gap-1.5">
                              <span className="font-bold text-[#131416] hover:text-[#1d5fd6] transition cursor-pointer" onClick={() => openEditModal(tpl)}>
                                {tpl.name}
                              </span>
                              {tpl.badge && (
                                <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                                  {tpl.badge}
                                </span>
                              )}
                            </div>
                            <p className="text-[11px] text-[#5d5f60] line-clamp-1 mt-0.5 max-w-md">
                              {tpl.description || 'Pre-configured workflow canvas.'}
                            </p>
                            <span className="text-[10px] text-[#94a3b8] font-mono">
                              slug: {tpl.slug}
                            </span>
                          </div>
                        </div>
                      </td>

                      <td className="py-3.5 px-3">
                        <span className="inline-block px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#f1f5f9] text-[#475569] border border-[#e2e5e9] capitalize">
                          {catLabel}
                        </span>
                      </td>

                      <td className="py-3.5 px-3">
                        <span
                          className={`inline-block px-2 py-0.5 rounded text-[10.5px] font-bold uppercase tracking-wider ${
                            tpl.funnel_mode === 'calculator'
                              ? 'bg-[#ecfdf5] text-[#059669]'
                              : 'bg-[#eff6ff] text-[#1d5fd6]'
                          }`}
                        >
                          {tpl.funnel_mode === 'calculator' ? 'Calculator' : 'Quiz'}
                        </span>
                      </td>

                      <td className="py-3.5 px-3 font-semibold text-[#131416]">
                        {tpl.node_count} nodes
                      </td>

                      <td className="py-3.5 px-3 text-center">
                        <button
                          type="button"
                          onClick={() => handleToggleFeatured(tpl)}
                          className={`p-1.5 rounded-lg transition ${
                            tpl.is_featured
                              ? 'text-[#f59e0b] hover:bg-[#fef3c7]'
                              : 'text-[#94a3b8] hover:text-[#f59e0b] hover:bg-[#f8fafc]'
                          }`}
                          title={tpl.is_featured ? 'Unstar featured' : 'Mark as featured'}
                        >
                          <Star className={`w-4 h-4 ${tpl.is_featured ? 'fill-current' : ''}`} />
                        </button>
                      </td>

                      <td className="py-3.5 px-3 text-center">
                        <button
                          type="button"
                          onClick={() => handleToggleActive(tpl)}
                          className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold transition ${
                            tpl.is_active
                              ? 'bg-[#ecfdf5] text-[#059669] hover:bg-[#d1fae5]'
                              : 'bg-[#f1f5f9] text-[#64748b] hover:bg-[#e2e8f0]'
                          }`}
                        >
                          {tpl.is_active ? 'Active' : 'Draft'}
                        </button>
                      </td>

                      <td className="py-3.5 px-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            type="button"
                            onClick={() => openPreview(tpl)}
                            className="p-1.5 text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#eff6ff] rounded-lg transition"
                            title="Preview Step Sequence"
                          >
                            <Eye className="w-3.5 h-3.5" />
                          </button>

                          <button
                            type="button"
                            onClick={() => openEditModal(tpl)}
                            className="p-1.5 text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#eff6ff] rounded-lg transition"
                            title="Edit Template"
                          >
                            <Edit2 className="w-3.5 h-3.5" />
                          </button>

                          <button
                            type="button"
                            onClick={() => handleExport(tpl)}
                            className="p-1.5 text-[#5d5f60] hover:text-[#131416] hover:bg-[#f1f5f9] rounded-lg transition"
                            title="Export JSON"
                          >
                            <Download className="w-3.5 h-3.5" />
                          </button>

                          <button
                            type="button"
                            onClick={() => handleDelete(tpl)}
                            className="p-1.5 text-[#5d5f60] hover:text-[#ef4444] hover:bg-[#fee2e2] rounded-lg transition"
                            title="Delete Template"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* ========================================================================= */}
      {/* MODAL 1: Convert Funnel to Global Template (1-Click Convert) */}
      {/* ========================================================================= */}
      {convertModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <div className="flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  Convert Funnel to Global Template
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setConvertModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleConvertSubmit} className="space-y-4">
              {/* Source Funnel Picker */}
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Select Source Funnel *
                </label>
                <select
                  required
                  value={selectedFunnelId}
                  onChange={(e) => {
                    const id = Number(e.target.value);
                    setSelectedFunnelId(id || '');
                    const found = sourceFunnels.find((f) => f.id === id);
                    if (found) {
                      if (!convertName) {
                        setConvertName(`${found.name} Template`);
                      }
                      if (found.funnel_mode === 'calculator') {
                        setConvertCategory('calculator');
                      }
                    }
                  }}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                >
                  <option value="">-- Choose a funnel to clone as template --</option>
                  {sourceFunnels.map((sf) => (
                    <option key={sf.id} value={sf.id}>
                      #{sf.id} — {sf.name} ({sf.node_count} nodes • {sf.funnel_mode} • {sf.tenant_name})
                    </option>
                  ))}
                </select>
              </div>

              {/* Live Preview Card of Selected Funnel */}
              {selectedFunnelDetail && (
                <div className="p-3 bg-[#eff6ff] rounded-xl border border-[#bfdbfe] text-xs space-y-1">
                  <div className="font-bold text-[#1d5fd6] flex items-center justify-between">
                    <span>Source: {selectedFunnelDetail.name}</span>
                    <span className="uppercase text-[10px] font-bold px-1.5 py-0.5 rounded bg-white text-[#1d5fd6] border border-[#93c5fd]">
                      {selectedFunnelDetail.funnel_mode}
                    </span>
                  </div>
                  <p className="text-[11px] text-[#5d5f60]">
                    Workspace: <strong className="text-[#131416]">{selectedFunnelDetail.tenant_name}</strong> • Total Canvas Nodes: <strong className="text-[#131416]">{selectedFunnelDetail.node_count} steps</strong>
                  </p>
                </div>
              )}

              {/* Template Name */}
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Template Title *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. High-Converting Dental Booking Funnel"
                  value={convertName}
                  onChange={(e) => setConvertName(e.target.value)}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              {/* Category Selector with Dynamic List + Inline Add New Category */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-semibold text-[#131416]">
                    Category *
                  </label>
                  <button
                    type="button"
                    onClick={() => {
                      setConvertCustomCategory(!convertCustomCategory);
                      if (!convertCustomCategory) setConvertNewCategoryName('');
                    }}
                    className="text-[11px] font-semibold text-[#1d5fd6] hover:underline flex items-center gap-1"
                  >
                    <FolderPlus className="w-3 h-3" />
                    <span>{convertCustomCategory ? '← Choose Existing' : '+ Add New Category'}</span>
                  </button>
                </div>

                {convertCustomCategory ? (
                  <div className="space-y-1">
                    <input
                      type="text"
                      required
                      placeholder="Type new category name (e.g. Dental & Healthcare, Solar Services, etc.)"
                      value={convertNewCategoryName}
                      onChange={(e) => setConvertNewCategoryName(e.target.value)}
                      className="w-full px-3 py-2 text-xs bg-[#eff6ff] border border-[#1d5fd6] rounded-xl focus:bg-white outline-hidden transition"
                    />
                    <p className="text-[10px] text-[#5d5f60]">
                      This will automatically register a new category in the template library.
                    </p>
                  </div>
                ) : (
                  <select
                    value={convertCategory}
                    onChange={(e) => {
                      if (e.target.value === '__new__') {
                        setConvertCustomCategory(true);
                      } else {
                        setConvertCategory(e.target.value);
                      }
                    }}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.label} {c.count > 0 ? `(${c.count})` : ''}
                      </option>
                    ))}
                    <option value="__new__">+ Enter New Custom Category...</option>
                  </select>
                )}
              </div>

              {/* Badge & Mode */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Badge (Optional)
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Popular, High-Converting, Pro"
                    value={convertBadge}
                    onChange={(e) => setConvertBadge(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Funnel Mode
                  </label>
                  <input
                    type="text"
                    disabled
                    value={selectedFunnelDetail?.funnel_mode === 'calculator' ? 'Calculator' : 'Quiz'}
                    className="w-full px-3 py-2 text-xs bg-[#edf0f3] text-[#5d5f60] border border-[#e2e5e9] rounded-xl outline-hidden uppercase font-bold"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Summary Description (Optional)
                </label>
                <textarea
                  rows={2}
                  placeholder="Short overview describing the purpose and audience of this workflow..."
                  value={convertDescription}
                  onChange={(e) => setConvertDescription(e.target.value)}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <div className="p-3 bg-[#f8fafc] rounded-xl border border-[#e2e5e9] text-[11px] text-[#5d5f60] leading-relaxed">
                💡 <strong>1-Click Conversion:</strong> The system automatically extracts all nodes, questions, choices, formulas, branching rules, and settings from the source funnel, removes all tenant IDs, and publishes it into the global library for all users to clone in 1 click!
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setConvertModalOpen(false)}
                  className="px-3.5 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={convertSubmitting || !selectedFunnelId}
                  className="px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] disabled:opacity-50 transition shadow-xs flex items-center gap-1.5"
                >
                  {convertSubmitting ? (
                    <>
                      <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                      <span>Converting...</span>
                    </>
                  ) : (
                    <>
                      <Sparkles className="w-3.5 h-3.5" />
                      <span>Convert & Save as Template</span>
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 2: Upload JSON Template */}
      {/* ========================================================================= */}
      {uploadModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <div className="flex items-center gap-2">
                <Upload className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  Upload Workflow Template (JSON)
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setUploadModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleUploadSubmit} className="space-y-3.5">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Template Name *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. B2B Client Onboarding Workflow"
                  value={uploadName}
                  onChange={(e) => setUploadName(e.target.value)}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                {/* Mode Selector */}
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Funnel Mode
                  </label>
                  <select
                    value={uploadMode}
                    onChange={(e) => setUploadMode(e.target.value as any)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    <option value="quiz">Interactive Quiz</option>
                    <option value="calculator">Calculator & Quotes</option>
                  </select>
                </div>

                {/* Badge */}
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Badge (Optional)
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Featured"
                    value={uploadBadge}
                    onChange={(e) => setUploadBadge(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              {/* Category Selector with Inline Add New */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-semibold text-[#131416]">
                    Category *
                  </label>
                  <button
                    type="button"
                    onClick={() => setUploadCustomCategory(!uploadCustomCategory)}
                    className="text-[11px] font-semibold text-[#1d5fd6] hover:underline flex items-center gap-1"
                  >
                    <FolderPlus className="w-3 h-3" />
                    <span>{uploadCustomCategory ? '← Choose Existing' : '+ Add New Category'}</span>
                  </button>
                </div>

                {uploadCustomCategory ? (
                  <input
                    type="text"
                    required
                    placeholder="Type new category name..."
                    value={uploadNewCategoryName}
                    onChange={(e) => setUploadNewCategoryName(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#eff6ff] border border-[#1d5fd6] rounded-xl focus:bg-white outline-hidden transition"
                  />
                ) : (
                  <select
                    value={uploadCategory}
                    onChange={(e) => {
                      if (e.target.value === '__new__') {
                        setUploadCustomCategory(true);
                      } else {
                        setUploadCategory(e.target.value);
                      }
                    }}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.label} {c.count > 0 ? `(${c.count})` : ''}
                      </option>
                    ))}
                    <option value="__new__">+ Enter New Custom Category...</option>
                  </select>
                )}
              </div>

              {/* JSON Payload Text */}
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Workflow JSON Payload (Nodes & Edges)
                </label>
                <textarea
                  rows={6}
                  placeholder='{"nodes": [...], "edges": [...]}'
                  value={jsonText}
                  onChange={(e) => setJsonText(e.target.value)}
                  className="w-full px-3 py-2 text-xs font-mono bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setUploadModalOpen(false)}
                  className="px-3.5 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={uploadSubmitting}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] disabled:opacity-50 transition shadow-xs"
                >
                  {uploadSubmitting ? 'Uploading...' : 'Create Template'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 3: Edit Template Metadata & Workflow Re-Sync */}
      {/* ========================================================================= */}
      {editModalOpen && activeTemplate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <div className="flex items-center gap-2">
                <Edit2 className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  Edit Workflow Template #{activeTemplate.id}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setEditModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleEditSubmit} className="space-y-3.5">
              {/* Template Name & Slug */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Template Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Slug (URL identifier)
                  </label>
                  <input
                    type="text"
                    value={editSlug}
                    onChange={(e) => setEditSlug(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              {/* Description */}
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Summary Description
                </label>
                <textarea
                  rows={2}
                  value={editDesc}
                  onChange={(e) => setEditDesc(e.target.value)}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              {/* Category Selector with Inline Add New Category */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-semibold text-[#131416]">
                    Category *
                  </label>
                  <button
                    type="button"
                    onClick={() => {
                      setEditCustomCategory(!editCustomCategory);
                      if (!editCustomCategory) setEditNewCategoryName('');
                    }}
                    className="text-[11px] font-semibold text-[#1d5fd6] hover:underline flex items-center gap-1"
                  >
                    <FolderPlus className="w-3 h-3" />
                    <span>{editCustomCategory ? '← Choose Existing' : '+ Add New Category'}</span>
                  </button>
                </div>

                {editCustomCategory ? (
                  <input
                    type="text"
                    required
                    placeholder="Enter new category name..."
                    value={editNewCategoryName}
                    onChange={(e) => setEditNewCategoryName(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#eff6ff] border border-[#1d5fd6] rounded-xl focus:bg-white outline-hidden transition"
                  />
                ) : (
                  <select
                    value={editCategory}
                    onChange={(e) => {
                      if (e.target.value === '__new__') {
                        setEditCustomCategory(true);
                      } else {
                        setEditCategory(e.target.value);
                      }
                    }}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.label} {c.count > 0 ? `(${c.count})` : ''}
                      </option>
                    ))}
                    <option value="__new__">+ Enter New Custom Category...</option>
                  </select>
                )}
              </div>

              {/* Mode & Badge */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Funnel Mode
                  </label>
                  <select
                    value={editMode}
                    onChange={(e) => setEditMode(e.target.value as any)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    <option value="quiz">Interactive Quiz</option>
                    <option value="calculator">Calculator & Quotes</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Badge
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Popular, Featured"
                    value={editBadge}
                    onChange={(e) => setEditBadge(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              {/* Display Order & Thumbnail URL */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Display Order (Sorting)
                  </label>
                  <input
                    type="number"
                    value={editOrder}
                    onChange={(e) => setEditOrder(Number(e.target.value))}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-[#131416] mb-1">
                    Thumbnail Image URL
                  </label>
                  <input
                    type="text"
                    placeholder="https://..."
                    value={editThumbnailUrl}
                    onChange={(e) => setEditThumbnailUrl(e.target.value)}
                    className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              {/* Status Toggles */}
              <div className="flex items-center gap-6 pt-1">
                <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                  <input
                    type="checkbox"
                    checked={editIsActive}
                    onChange={(e) => setEditIsActive(e.target.checked)}
                    className="w-4 h-4 rounded text-[#1d5fd6] focus:ring-0"
                  />
                  <span>Publish to Public Template Library (Active)</span>
                </label>

                <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#131416]">
                  <input
                    type="checkbox"
                    checked={editIsFeatured}
                    onChange={(e) => setEditIsFeatured(e.target.checked)}
                    className="w-4 h-4 rounded text-[#f59e0b] focus:ring-0"
                  />
                  <span>Mark as Featured (Pinned)</span>
                </label>
              </div>

              {/* Workflow Graph Re-Sync Option */}
              <div className="p-3 bg-[#f8fafc] rounded-xl border border-[#e2e5e9] space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#131416] flex items-center gap-1.5">
                    <RefreshCw className="w-3.5 h-3.5 text-[#1d5fd6]" />
                    <span>Re-Sync Workflow from a Funnel (Optional)</span>
                  </span>
                  <span className="text-[10px] text-[#5d5f60]">Currently {activeTemplate.node_count} nodes</span>
                </div>
                <select
                  value={editSyncFunnelId}
                  onChange={(e) => setEditSyncFunnelId(Number(e.target.value) || '')}
                  className="w-full px-3 py-1.5 text-xs bg-white border border-[#e2e5e9] rounded-lg focus:border-[#1d5fd6] outline-hidden"
                >
                  <option value="">-- Keep current nodes & question schema --</option>
                  {sourceFunnels.map((sf) => (
                    <option key={sf.id} value={sf.id}>
                      Over-write from #{sf.id} — {sf.name} ({sf.node_count} nodes • {sf.tenant_name})
                    </option>
                  ))}
                </select>
                <p className="text-[10.5px] text-[#5d5f60]">
                  💡 If you made modifications to a funnel in your test account, select it here to update this template's workflow graph.
                </p>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setEditModalOpen(false)}
                  className="px-3.5 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={editSubmitting}
                  className="px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] disabled:opacity-50 transition shadow-xs"
                >
                  {editSubmitting ? 'Saving Changes...' : 'Save Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 4: Step-by-Step Sequence Preview */}
      {/* ========================================================================= */}
      {previewModalOpen && activeTemplate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150 max-h-[85vh] flex flex-col">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3] shrink-0">
              <div className="flex items-center gap-2">
                <Eye className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  {activeTemplate.name} — Workflow Architecture
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setPreviewModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto space-y-3 pr-1">
              <div className="p-3 bg-[#f8fafc] rounded-xl border border-[#e2e5e9] text-xs text-[#5d5f60]">
                {activeTemplate.description || 'Pre-configured visual workflow.'}
              </div>

              <div className="space-y-2">
                <div className="text-xs font-bold text-[#131416]">Sequence Flow:</div>
                {activeTemplate.graph?.nodes && activeTemplate.graph.nodes.length > 0 ? (
                  activeTemplate.graph.nodes.map((n: any, idx: number) => (
                    <div
                      key={idx}
                      className="p-3 rounded-xl border border-[#e2e5e9] bg-white flex items-center justify-between text-xs"
                    >
                      <div className="flex items-center gap-2.5">
                        <span className="w-5 h-5 rounded-md bg-[#edf0f3] text-[#131416] font-bold text-[10px] flex items-center justify-center">
                          {idx + 1}
                        </span>
                        <div>
                          <div className="font-bold text-[#131416]">{n.title || n.node_type}</div>
                          <div className="text-[11px] text-[#5d5f60] capitalize">
                            Type: {n.node_type.replace('_', ' ')}
                            {n.question_text ? ` • "${n.question_text}"` : ''}
                          </div>
                        </div>
                      </div>
                      <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-[#f1f5f9] text-[#475569]">
                        {n.node_type}
                      </span>
                    </div>
                  ))
                ) : (
                  <p className="text-xs text-[#5d5f60]">
                    Node structure will be auto-generated when instantiated.
                  </p>
                )}
              </div>
            </div>

            <div className="flex justify-end pt-3 border-t border-[#edf0f3] shrink-0">
              <button
                type="button"
                onClick={() => setPreviewModalOpen(false)}
                className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3]"
              >
                Close Preview
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 5: Dedicated Category Manager Modal */}
      {/* ========================================================================= */}
      {categoryModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-xl animate-in fade-in duration-150 max-h-[85vh] flex flex-col">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3] shrink-0">
              <div className="flex items-center gap-2">
                <FolderPlus className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  Workflow Template Categories
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setCategoryModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Quick Add Category Form */}
            <form onSubmit={handleCreateCategory} className="bg-[#f8fafc] p-3.5 rounded-xl border border-[#e2e5e9] space-y-2.5 shrink-0">
              <label className="block text-xs font-bold text-[#131416]">
                + Add New Category
              </label>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  required
                  placeholder="e.g. Dental & Healthcare, Financial Services, Gym"
                  value={newCategoryLabel}
                  onChange={(e) => setNewCategoryLabel(e.target.value)}
                  className="flex-1 px-3 py-1.5 text-xs bg-white border border-[#e2e5e9] rounded-xl focus:border-[#1d5fd6] outline-hidden transition"
                />
                <button
                  type="submit"
                  disabled={categorySubmitting || !newCategoryLabel.trim()}
                  className="px-3.5 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] disabled:opacity-50 transition shadow-xs shrink-0"
                >
                  {categorySubmitting ? 'Adding...' : 'Add'}
                </button>
              </div>
              <p className="text-[10.5px] text-[#5d5f60]">
                Added categories will immediately appear in the filter tabs and be available in both Super Admin and the User Template Library.
              </p>
            </form>

            {/* Existing Categories List */}
            <div className="flex-1 overflow-y-auto space-y-2 pr-1">
              <span className="text-xs font-bold text-[#5d5f60] uppercase tracking-wider block mb-1">
                Active Categories ({categories.length})
              </span>
              <div className="divide-y divide-[#edf0f3] border border-[#e2e5e9] rounded-xl overflow-hidden bg-white">
                {categories.map((c) => (
                  <div key={c.id} className="p-3 flex items-center justify-between hover:bg-[#f8fafc] transition">
                    <div>
                      <div className="text-xs font-bold text-[#131416] flex items-center gap-2">
                        <span>{c.label}</span>
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#eff6ff] text-[#1d5fd6] border border-[#dbeafe]">
                          {c.count} {c.count === 1 ? 'template' : 'templates'}
                        </span>
                      </div>
                      <span className="text-[10px] text-[#94a3b8] font-mono">key: {c.id}</span>
                    </div>

                    <div>
                      {c.count === 0 && (
                        <button
                          type="button"
                          onClick={() => handleDeleteCategory(c)}
                          className="p-1.5 text-[#94a3b8] hover:text-[#ef4444] hover:bg-[#fee2e2]/60 rounded-lg transition"
                          title="Delete empty category"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex justify-end pt-3 border-t border-[#edf0f3] shrink-0">
              <button
                type="button"
                onClick={() => setCategoryModalOpen(false)}
                className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3]"
              >
                Done
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

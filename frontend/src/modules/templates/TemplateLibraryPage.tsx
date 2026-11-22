import React, { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import {
  LayoutTemplate,
  Search,
  Sparkles,
  GitFork,
  Calculator,
  ArrowRight,
  Eye,
  CheckCircle2,
  AlertCircle,
  Layers,
  X,
  Play,
  Loader2,
  Check
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
  is_featured: boolean;
  graph?: {
    nodes?: any[];
    edges?: any[];
  };
}

const DEFAULT_CATEGORIES = [
  { id: 'all', label: 'All Templates' },
  { id: 'lead_generation', label: 'Lead Generation' },
  { id: 'calculator', label: 'Calculators & Quotes' },
  { id: 'real_estate', label: 'Real Estate' },
  { id: 'b2b_saas', label: 'B2B & SaaS' },
  { id: 'health_wellness', label: 'Health & Wellness' },
  { id: 'marketing', label: 'Marketing & VSL' },
];

export const TemplateLibraryPage: React.FC = () => {
  const { isPlatformAdmin, features, limits, refreshLimits } = useAuthStore();
  const navigate = useNavigate();

  const [templates, setTemplates] = useState<TemplateItem[]>([]);
  const [categories, setCategories] = useState<{ id: string; label: string }[]>(DEFAULT_CATEGORIES);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedMode, setSelectedMode] = useState<'all' | 'quiz' | 'calculator'>('all');

  // Preview Modal
  const [previewModalOpen, setPreviewModalOpen] = useState(false);
  const [previewTemplate, setPreviewTemplate] = useState<TemplateItem | null>(null);
  const [previewLoading, setPreviewLoading] = useState(false);

  // Instantiate Modal
  const [useModalOpen, setUseModalOpen] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState<TemplateItem | null>(null);
  const [customFunnelName, setCustomFunnelName] = useState('');
  const [instantiating, setInstantiating] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Upgrade Modal
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);
  const [upgradeFeature, setUpgradeFeature] = useState({
    name: 'Plan Limit Reached',
    desc: 'Upgrade your subscription to create additional funnels.',
    plan: 'Pro'
  });

  const isAtLimit = Boolean(
    !isPlatformAdmin &&
    limits &&
    limits.max_funnels > 0 &&
    (limits.current_funnels ?? 0) >= limits.max_funnels
  );

  useEffect(() => {
    loadTemplates();
  }, [selectedCategory, selectedMode, search]);

  const loadTemplates = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (selectedCategory !== 'all') params.append('category', selectedCategory);
      if (selectedMode !== 'all') params.append('mode', selectedMode);
      if (search.trim()) params.append('search', search.trim());

      const qs = params.toString() ? `?${params.toString()}` : '';
      const res = await api.get(`/api/templates${qs}`);
      if (Array.isArray(res)) {
        setTemplates(res);
      } else if (res && res.templates) {
        setTemplates(res.templates);
        if (res.categories && res.categories.length > 0) {
          const dynamicCats = res.categories.map((c: any) => ({ id: c.id, label: c.label }));
          setCategories([{ id: 'all', label: 'All Templates' }, ...dynamicCats]);
        }
      } else {
        setTemplates([]);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load templates');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenPreview = async (tpl: TemplateItem) => {
    try {
      setPreviewLoading(true);
      setPreviewTemplate(tpl);
      setPreviewModalOpen(true);
      const full = await api.get(`/api/templates/${tpl.id}`);
      setPreviewTemplate(full);
    } catch {
      // Use existing cached
    } finally {
      setPreviewLoading(false);
    }
  };

  const handleOpenUseModal = (tpl: TemplateItem) => {
    if (isAtLimit) {
      setUpgradeFeature({
        name: `Funnel Limit Reached (${limits?.current_funnels}/${limits?.max_funnels})`,
        desc: `You have reached the limit of ${limits?.max_funnels} funnels on your ${limits?.plan_name || 'current'} plan. Upgrade to unlock more active funnels.`,
        plan: 'Pro'
      });
      setUpgradeModalOpen(true);
      return;
    }

    if (tpl.funnel_mode === 'calculator' && !features?.calculator_mode && !isPlatformAdmin) {
      setUpgradeFeature({
        name: 'Interactive Calculator Mode',
        desc: 'Interactive formula calculation and instant quote estimation funnels are available on Pro and Growth plans. Upgrade to use this calculator template.',
        plan: 'Pro'
      });
      setUpgradeModalOpen(true);
      return;
    }

    setSelectedTemplate(tpl);
    setCustomFunnelName(`${tpl.name} (My Funnel)`);
    setErrorMsg('');
    setUseModalOpen(true);
  };

  const handleConfirmUse = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTemplate) return;

    setInstantiating(true);
    setErrorMsg('');

    try {
      const res = await api.post(`/api/templates/${selectedTemplate.id}/use`, {
        name: customFunnelName || undefined,
      });

      await refreshLimits();
      setUseModalOpen(false);
      // Route immediately into the visual canvas builder!
      navigate(`/admin/funnels/${res.id}/builder`);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to instantiate template');
      setInstantiating(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header Banner */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div className="max-w-2xl space-y-2 relative z-10">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#eff6ff] text-[#1d5fd6] border border-[#bfdbfe]">
            <Sparkles className="w-3.5 h-3.5" />
            <span>Ready-to-Deploy Workflows</span>
          </div>
          <h1 className="text-xl sm:text-2xl font-bold text-[#131416] tracking-tight">
            Workflow Template Library
          </h1>
          <p className="text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
            Choose from curated, high-converting visual funnels and instant quote calculators. One click clones the entire workflow graph into your workspace so you can customize and publish in minutes.
          </p>
        </div>

        {/* Action button to create blank if preferred */}
        <div className="mt-4 sm:mt-0 sm:absolute sm:right-6 sm:top-8 z-10 flex items-center gap-2">
          <Link
            to="/admin/funnels/create"
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-[#5d5f60] bg-[#f8fafc] hover:bg-[#edf0f3] border border-[#e2e5e9] transition"
          >
            <span>Or Start with Blank Canvas</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      </div>

      {/* Filter and Search Controls */}
      <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-[#e2e5e9] shadow-xs">
        {/* Category Pills */}
        <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id)}
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
                selectedCategory === cat.id
                  ? 'bg-[#1d5fd6] text-white shadow-xs'
                  : 'bg-[#f8fafc] text-[#5d5f60] hover:text-[#131416] hover:bg-[#edf0f3]'
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>

        {/* Right side: Funnel Mode + Search */}
        <div className="flex items-center gap-2.5">
          <select
            value={selectedMode}
            onChange={(e) => setSelectedMode(e.target.value as any)}
            className="px-3 py-1.5 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden font-medium text-[#131416] cursor-pointer"
          >
            <option value="all">All Modes</option>
            <option value="quiz">Quiz / Surveys</option>
            <option value="calculator">Interactive Calculators</option>
          </select>

          <div className="relative min-w-[200px]">
            <Search className="w-3.5 h-3.5 text-[#5d5f60] absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search templates..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-8 pr-8 py-1.5 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition placeholder:text-[#94a3b8]"
            />
            {search.trim() && (
              <button
                type="button"
                onClick={() => setSearch('')}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#131416] p-0.5 rounded transition cursor-pointer"
                title="Clear search"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Template Grid */}
      {loading ? (
        <div className="p-16 text-center text-xs text-[#5d5f60] flex items-center justify-center gap-2">
          <Loader2 className="w-5 h-5 animate-spin text-[#1d5fd6]" />
          <span>Loading workflow templates...</span>
        </div>
      ) : templates.length === 0 ? (
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-12 text-center space-y-3 shadow-xs">
          <div className="w-12 h-12 rounded-2xl bg-[#f8fafc] border border-[#e2e5e9] text-[#5d5f60] mx-auto flex items-center justify-center">
            <LayoutTemplate className="w-6 h-6 text-[#5d5f60]" />
          </div>
          <h3 className="text-sm font-bold text-[#131416]">No matching templates found</h3>
          <p className="text-xs text-[#5d5f60] max-w-sm mx-auto">
            Try resetting your search filters or start with a custom blank canvas.
          </p>
          <button
            onClick={() => {
              setSelectedCategory('all');
              setSelectedMode('all');
              setSearch('');
            }}
            className="px-3.5 py-1.5 rounded-xl bg-[#eff6ff] text-[#1d5fd6] text-xs font-semibold hover:bg-[#dbeafe]"
          >
            Clear Filters
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {templates.map((tpl) => (
            <div
              key={tpl.id}
              className="bg-white border border-[#e2e5e9] rounded-2xl p-5 shadow-xs hover:shadow-md hover:border-[#cbd5e1] transition-all flex flex-col justify-between group"
            >
              <div className="space-y-3">
                {/* Badges & Mode Bar */}
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-1.5 flex-wrap">
                    {tpl.badge && (
                      <span className="px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-[#fef3c7] text-[#92400e] border border-[#fde68a]">
                        {tpl.badge}
                      </span>
                    )}
                    <span
                      className={`px-2 py-0.5 rounded-md text-[10.5px] font-bold uppercase tracking-wider ${
                        tpl.funnel_mode === 'calculator'
                          ? 'bg-[#ecfdf5] text-[#0f766e]'
                          : 'bg-[#eff6ff] text-[#1d5fd6]'
                      }`}
                    >
                      {tpl.funnel_mode === 'calculator' ? 'Calculator' : 'Quiz'}
                    </span>
                  </div>

                  <span className="text-[11px] font-bold text-[#5d5f60] bg-[#f8fafc] px-2 py-0.5 rounded-md border border-[#e2e5e9]">
                    {tpl.node_count} Steps
                  </span>
                </div>

                {/* Title & Desc */}
                <div>
                  <h3 className="text-sm font-bold text-[#131416] group-hover:text-[#1d5fd6] transition tracking-tight">
                    {tpl.name}
                  </h3>
                  <p className="text-xs text-[#5d5f60] mt-1.5 line-clamp-3 leading-relaxed">
                    {tpl.description || 'Pre-configured end-to-end visual workflow.'}
                  </p>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="mt-5 pt-4 border-t border-[#edf0f3] flex items-center justify-between gap-2">
                <button
                  type="button"
                  onClick={() => handleOpenPreview(tpl)}
                  className="inline-flex items-center gap-1 text-xs font-semibold text-[#5d5f60] hover:text-[#131416] p-1.5 rounded-lg hover:bg-[#f8fafc] transition"
                >
                  <Eye className="w-3.5 h-3.5" />
                  <span>Preview</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleOpenUseModal(tpl)}
                  className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs"
                >
                  <span>Use Template</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* MODAL 1: Interactive Preview Modal */}
      {previewModalOpen && previewTemplate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150 max-h-[85vh] flex flex-col">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3] shrink-0">
              <div className="flex items-center gap-2">
                <Eye className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">
                  {previewTemplate.name} — Workflow Preview
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

            <div className="flex-1 overflow-y-auto space-y-3.5 pr-1">
              <div className="p-3 bg-[#f8fafc] rounded-xl border border-[#e2e5e9] text-xs text-[#5d5f60] leading-relaxed">
                {previewTemplate.description}
              </div>

              <div className="space-y-2">
                <div className="text-xs font-bold text-[#131416]">Step-by-Step Flow:</div>
                {previewTemplate.graph?.nodes && previewTemplate.graph.nodes.length > 0 ? (
                  previewTemplate.graph.nodes.map((n: any, idx: number) => (
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
                  <div className="p-6 text-center text-xs text-[#5d5f60]">
                    Pre-configured starter nodes will be cloned on creation.
                  </div>
                )}
              </div>
            </div>

            <div className="flex items-center justify-between pt-3 border-t border-[#edf0f3] shrink-0">
              <button
                type="button"
                onClick={() => setPreviewModalOpen(false)}
                className="px-3.5 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
              >
                Close
              </button>

              <button
                type="button"
                onClick={() => {
                  setPreviewModalOpen(false);
                  handleOpenUseModal(previewTemplate);
                }}
                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs"
              >
                <span>Use This Template</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: 1-Click Clone Confirmation Modal */}
      {useModalOpen && selectedTemplate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-in fade-in duration-150">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <div className="flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-[#1d5fd6]" />
                <h3 className="text-sm font-bold text-[#131416]">Instantiate Workflow Template</h3>
              </div>
              <button
                type="button"
                onClick={() => setUseModalOpen(false)}
                className="p-1 text-[#5d5f60] hover:text-[#131416] rounded"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {errorMsg && (
              <div className="p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{errorMsg}</span>
              </div>
            )}

            <form onSubmit={handleConfirmUse} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">
                  Name Your New Funnel *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. My Lead Quiz"
                  value={customFunnelName}
                  onChange={(e) => setCustomFunnelName(e.target.value)}
                  className="w-full px-3 py-2 text-xs bg-[#f8fafc] border border-[#e2e5e9] rounded-xl focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <div className="p-3 bg-[#f8fafc] rounded-xl border border-[#e2e5e9] text-[11.5px] text-[#5d5f60] space-y-1">
                <div className="font-semibold text-[#131416]">What happens next:</div>
                <ul className="list-disc list-inside space-y-0.5">
                  <li>Creates a new funnel in your workspace</li>
                  <li>Clones all {selectedTemplate.node_count} nodes, questions, & branching paths</li>
                  <li>Opens directly in the Visual Builder for instant editing</li>
                </ul>
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t border-[#edf0f3]">
                <button
                  type="button"
                  onClick={() => setUseModalOpen(false)}
                  className="px-3.5 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc]"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={instantiating || !customFunnelName.trim()}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] disabled:opacity-50 transition shadow-xs"
                >
                  {instantiating ? (
                    <>
                      <Loader2 className="w-3.5 h-3.5 animate-spin" />
                      <span>Creating Funnel...</span>
                    </>
                  ) : (
                    <>
                      <span>Create & Open Builder</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Upgrade Modal */}
      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName={upgradeFeature.name}
        description={upgradeFeature.desc}
        requiredPlan={upgradeFeature.plan}
      />
    </div>
  );
};

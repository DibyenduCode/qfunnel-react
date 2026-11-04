import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import { GitFork, Calculator, ArrowLeft, Plus, Loader2, Sparkles } from 'lucide-react';

export const CreateFunnelPage: React.FC = () => {
  const { features, isPlatformAdmin } = useAuthStore();
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [mode, setMode] = useState<'quiz' | 'calculator'>('quiz');
  const [starterMode, setStarterMode] = useState<'blank' | 'demo'>('blank');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);

  const navigate = useNavigate();
  const canUseCalculator = Boolean(features?.calculator_mode || isPlatformAdmin);

  const handleNameChange = (val: string) => {
    setName(val);
    if (!slug || slug === name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')) {
      setSlug(val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');

    try {
      const funnel = await api.post('/api/funnels', {
        name,
        slug,
        description,
        funnel_mode: mode,
        template: starterMode,
        blank_canvas: starterMode === 'blank',
      });
      navigate(`/admin/funnels/${funnel.id}/builder`);
    } catch (err: any) {
      setError(err.message || 'Failed to create funnel.');
      setSubmitting(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <Link to="/admin/funnels" className="inline-flex items-center gap-1.5 text-xs text-[#687080] hover:text-[#171A21] transition">
        <ArrowLeft className="w-4 h-4" />
        <span>Back to Funnels</span>
      </Link>

      <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 sm:p-8">
        <h1 className="text-lg font-bold text-[#131416]">Create a New Funnel</h1>
        <p className="text-xs text-[#5c6066] mt-1">Configure your funnel type and metadata</p>

        {error && (
          <div className="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="mt-6 space-y-5">
          {/* Funnel Type Selector */}
          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-2">Select Funnel Mode</label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setMode('quiz')}
                className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${
                  mode === 'quiz'
                    ? 'border-[#1d5fd6] bg-[#dcf1ff]/40 ring-1 ring-[#1d5fd6]'
                    : 'border-[#e8eaec] hover:bg-[#f9fbfc]'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className="w-8 h-8 rounded-lg bg-[#dcf1ff] text-[#0a3a80] flex items-center justify-center">
                    <GitFork className="w-4 h-4" />
                  </div>
                  {mode === 'quiz' && <span className="w-2 h-2 rounded-full bg-[#1d5fd6]" />}
                </div>
                <div>
                  <span className="block text-xs font-semibold text-[#131416]">Branching Quiz</span>
                  <span className="block text-[11px] text-[#5c6066] mt-0.5">
                    Multi-question questionnaire with dynamic conditional pathways and lead capture.
                  </span>
                </div>
              </button>

              <button
                type="button"
                onClick={() => {
                  if (!canUseCalculator) {
                    setUpgradeModalOpen(true);
                    return;
                  }
                  setMode('calculator');
                }}
                className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${
                  mode === 'calculator'
                    ? 'border-[#1d5fd6] bg-[#dcf1ff]/40 ring-1 ring-[#1d5fd6]'
                    : 'border-[#e8eaec] hover:bg-[#f9fbfc]'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className="w-8 h-8 rounded-lg bg-[#dcfae5] text-[#04250e] flex items-center justify-center">
                    <Calculator className="w-4 h-4" />
                  </div>
                  {mode === 'calculator' ? (
                    <span className="w-2 h-2 rounded-full bg-[#1d5fd6]" />
                  ) : !canUseCalculator ? (
                    <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                      PRO
                    </span>
                  ) : null}
                </div>
                <div>
                  <div className="flex items-center gap-1.5">
                    <span className="block text-xs font-semibold text-[#131416]">Price / Quote Calculator</span>
                    {!canUseCalculator && (
                      <span className="inline-flex items-center gap-1 text-[10px] text-[#1d5fd6] font-semibold">
                        <Sparkles className="w-2.5 h-2.5" />
                        <span>Pro Plan</span>
                      </span>
                    )}
                  </div>
                  <span className="block text-[11px] text-[#5c6066] mt-0.5">
                    Evaluates user choices with mathematical formulas to output real-time estimates.
                  </span>
                </div>
              </button>
            </div>
          </div>

          {/* Workflow Starter Mode */}
          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-2">Canvas Starter</label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setStarterMode('blank')}
                className={`p-3.5 rounded-xl border text-left transition flex flex-col justify-between ${
                  starterMode === 'blank'
                    ? 'border-[#1d5fd6] bg-[#dcf1ff]/40 ring-1 ring-[#1d5fd6]'
                    : 'border-[#e8eaec] hover:bg-[#f9fbfc]'
                }`}
              >
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-semibold text-[#131416]">Clean Canvas (Recommended)</span>
                  {starterMode === 'blank' && <span className="w-2 h-2 rounded-full bg-[#1d5fd6]" />}
                </div>
                <p className="text-[11px] text-[#5c6066]">
                  Start with a clean canvas (only entry point). Zero pre-filled demo questions, fake choices, or clutter.
                </p>
              </button>

              <button
                type="button"
                onClick={() => setStarterMode('demo')}
                className={`p-3.5 rounded-xl border text-left transition flex flex-col justify-between ${
                  starterMode === 'demo'
                    ? 'border-[#1d5fd6] bg-[#dcf1ff]/40 ring-1 ring-[#1d5fd6]'
                    : 'border-[#e8eaec] hover:bg-[#f9fbfc]'
                }`}
              >
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-semibold text-[#131416]">Sample Demo Workflow</span>
                  {starterMode === 'demo' && <span className="w-2 h-2 rounded-full bg-[#1d5fd6]" />}
                </div>
                <p className="text-[11px] text-[#5c6066]">
                  Pre-populate sample question steps, lead form, and outcome destination for demonstration.
                </p>
              </button>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-1">Funnel Name</label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => handleNameChange(e.target.value)}
              placeholder="e.g. Health Risk Assessment"
              className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-1">Public URL Slug</label>
            <div className="flex rounded-xl border border-[#e8eaec] overflow-hidden focus-within:border-[#1d5fd6]">
              <span className="px-3 py-2 bg-[#f9fbfc] text-[11px] text-[#5c6066] border-r border-[#e8eaec] select-none">
                /quiz/
              </span>
              <input
                type="text"
                required
                value={slug}
                onChange={(e) => setSlug(e.target.value)}
                placeholder="health-risk-assessment"
                className="w-full px-3 py-2 text-xs text-[#131416] focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-1">Description (Optional)</label>
            <textarea
              rows={3}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Internal notes or objective of this funnel..."
              className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
            />
          </div>

          <div className="pt-2 flex items-center justify-end gap-3">
            <Link
              to="/admin/funnels"
              className="px-4 py-2 rounded-xl border border-[#e8eaec] text-xs font-semibold text-[#5c6066] hover:bg-[#f9fbfc]"
            >
              Cancel
            </Link>
            <button
              type="submit"
              disabled={submitting}
              className="px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition disabled:opacity-50 flex items-center gap-2 shadow-xs"
            >
              {submitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4" />}
              <span>{submitting ? 'Creating...' : 'Create & Open Builder'}</span>
            </button>
          </div>
        </form>
      </div>

      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName="Interactive Calculator & Instant Quotes"
        description="Dynamic formula calculation, pricing estimators, and instant quote cards are available on Pro and Growth plans. Upgrade to build formula-driven quiz funnels."
        requiredPlan="Pro"
      />
    </div>
  );
};


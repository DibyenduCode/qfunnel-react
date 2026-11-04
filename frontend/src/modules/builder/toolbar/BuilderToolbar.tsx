import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowLeft,
  Save,
  CheckCircle,
  HelpCircle,
  Share2,
  ExternalLink,
  LayoutGrid,
  Calculator,
  Check,
  Plus,
  Layers,
  Sparkles,
  Loader2
} from 'lucide-react';

interface BuilderToolbarProps {
  funnel: any;
  isDirty: boolean;
  isSaving: boolean;
  isPublishing: boolean;
  funnelMode: 'quiz' | 'calculator';
  onModeChange: (mode: 'quiz' | 'calculator') => void;
  onSave: () => void;
  onPublish: () => void;
  onAddNode: (type: string, subType?: string) => void;
  onAutoArrange?: () => void;
  isPaletteOpen?: boolean;
  onTogglePalette?: () => void;
}

export const BuilderToolbar: React.FC<BuilderToolbarProps> = ({
  funnel,
  isDirty,
  isSaving,
  isPublishing,
  funnelMode,
  onModeChange,
  onSave,
  onPublish,
  onAutoArrange,
  isPaletteOpen = true,
  onTogglePalette,
}) => {
  const [copied, setCopied] = useState(false);

  const publicUrl = funnel?.slug
    ? `${window.location.origin}/qfunnel2/funnel/${funnel.slug}`
    : '';

  const handleCopyUrl = () => {
    if (!publicUrl) return;
    navigator.clipboard.writeText(publicUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <header className="h-11 bg-[#f9fbfc] border-b border-[#e8eaec] flex items-center justify-between px-3 sm:px-4 z-20 shrink-0 gap-2 w-full max-w-full overflow-hidden">
      {/* Left: Back, Title & Mode */}
      <div className="flex items-center gap-2 sm:gap-2.5 min-w-0 shrink">
        <Link
          to="/admin/funnels"
          className="p-1 rounded-md text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition shrink-0"
          title="Back to funnels"
        >
          <ArrowLeft className="w-4 h-4" />
        </Link>
        <div className="h-3.5 w-[1px] bg-[#e8eaec] shrink-0" />

        <div className="flex items-center gap-2 min-w-0 shrink">
          <h1
            className="text-xs font-semibold text-[#131416] truncate max-w-[120px] sm:max-w-[160px] md:max-w-[220px] lg:max-w-[280px]"
            title={funnel?.name || 'Funnel Builder'}
          >
            {funnel?.name || 'Funnel Builder'}
          </h1>
          <span
            className={`px-2 py-0.2 rounded text-[10px] font-medium shrink-0 ${
              funnel?.status === 'published'
                ? 'bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]'
                : 'bg-[#eef1f4] text-[#3a3d40] border border-[#e2e5e9]'
            }`}
          >
            {funnel?.status === 'published' ? 'Published' : 'Draft'}
          </span>
          {isDirty && (
            <span
              className="w-1.5 h-1.5 rounded-full bg-[#b45309] shrink-0"
              title="Unsaved changes"
            />
          )}
        </div>

        {/* Mode Switcher Toggle */}
        <div className="hidden lg:flex items-center bg-[#eef1f4] p-0.5 rounded-md text-xs shrink-0 ml-1 border border-[#e2e5e9]">
          <button
            type="button"
            onClick={() => onModeChange('quiz')}
            className={`px-2 py-0.5 rounded font-normal transition flex items-center gap-1 ${
              funnelMode === 'quiz'
                ? 'bg-[#1d5fd6] text-white font-medium shadow-xs'
                : 'text-[#5d5f60] hover:text-[#131416]'
            }`}
            title="Standard branching question flow"
          >
            <HelpCircle className="w-3.5 h-3.5" />
            <span>Quiz</span>
          </button>
          <button
            type="button"
            onClick={() => onModeChange('calculator')}
            className={`px-2 py-0.5 rounded font-normal transition flex items-center gap-1 ${
              funnelMode === 'calculator'
                ? 'bg-[#1d5fd6] text-white font-medium shadow-xs'
                : 'text-[#5d5f60] hover:text-[#131416]'
            }`}
            title="Calculator math formula & quote generation"
          >
            <Calculator className="w-3.5 h-3.5 text-amber-300" />
            <span>Calculator</span>
          </button>
        </div>
      </div>

      {/* Center: Toggle Node Library Button */}
      {onTogglePalette && (
        <div className="flex items-center gap-2 shrink-0">
          <button
            type="button"
            onClick={onTogglePalette}
            className={`px-2.5 h-7 rounded-md border text-xs font-normal transition flex items-center gap-1.5 ${
              isPaletteOpen
                ? 'bg-[#dcf1ff] text-[#0a3a80] font-medium border-[#b9e6fe]'
                : 'bg-white border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8]'
            }`}
            title="Toggle Node Library Palette"
          >
            <Layers className="w-3.5 h-3.5" />
            <span className="hidden sm:inline">Node library</span>
            <span className="sm:hidden">Nodes</span>
          </button>
        </div>
      )}

      {/* Right: Actions */}
      <div className="flex items-center gap-1.5 shrink-0">
        {onAutoArrange && (
          <button
            onClick={onAutoArrange}
            className="px-2 h-7 rounded-md border border-[#e8eaec] text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f4f6f8] transition shrink-0 flex items-center gap-1 text-xs font-normal"
            title="Auto-arrange layout into clean tree hierarchy (L)"
          >
            <LayoutGrid className="w-3.5 h-3.5 text-[#1d5fd6]" />
            <span className="hidden xl:inline">Auto-align</span>
          </button>
        )}

        {publicUrl && (
          <div className="hidden sm:flex items-center gap-1 shrink-0">
            <button
              onClick={handleCopyUrl}
              className="px-2 h-7 rounded-md border border-[#e8eaec] text-xs font-normal text-[#5d5f60] hover:bg-[#f4f6f8] transition flex items-center gap-1 shrink-0"
              title="Copy public link"
            >
              {copied ? <Check className="w-3.5 h-3.5 text-[#2f7a45]" /> : <Share2 className="w-3.5 h-3.5" />}
              <span className="hidden md:inline">{copied ? 'Copied' : 'Share'}</span>
            </button>
            <a
              href={publicUrl}
              target="_blank"
              rel="noreferrer"
              className="p-1.5 rounded-md border border-[#e8eaec] text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f4f6f8] transition shrink-0"
              title="Open public funnel in new tab"
            >
              <ExternalLink className="w-3.5 h-3.5" />
            </a>
          </div>
        )}

        <button
          onClick={onSave}
          disabled={isSaving}
          className={`px-2.5 h-7 rounded-md border text-xs font-normal transition flex items-center gap-1.5 whitespace-nowrap shrink-0 disabled:opacity-50 ${
            isDirty
              ? 'border-[#1d5fd6] text-[#1d5fd6] bg-[#dcf1ff]/30 hover:bg-[#dcf1ff]/50'
              : 'border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8]'
          }`}
        >
          {isSaving ? (
            <Loader2 className="w-3.5 h-3.5 shrink-0 animate-spin text-[#1d5fd6]" />
          ) : (
            <Save className="w-3.5 h-3.5 shrink-0" />
          )}
          <span>{isSaving ? 'Saving...' : isDirty ? 'Save canvas' : 'Saved'}</span>
        </button>

        <button
          onClick={onPublish}
          disabled={isPublishing}
          className="px-3 h-7 rounded-md bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-medium transition flex items-center gap-1.5 whitespace-nowrap shrink-0 disabled:opacity-50 shadow-xs"
        >
          {isPublishing ? (
            <Loader2 className="w-3.5 h-3.5 shrink-0 animate-spin text-white" />
          ) : (
            <CheckCircle className="w-3.5 h-3.5 shrink-0 text-white" />
          )}
          <span>{isPublishing ? 'Publishing...' : 'Publish'}</span>
        </button>
      </div>
    </header>
  );
};

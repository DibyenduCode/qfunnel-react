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
  Loader2,
  Keyboard,
  Undo2,
  Redo2
} from 'lucide-react';

interface BuilderToolbarProps {
  funnel: any;
  isDirty: boolean;
  isSaving: boolean;
  isJustSaved?: boolean;
  isPublishing: boolean;
  funnelMode: 'quiz' | 'calculator';
  onModeChange: (mode: 'quiz' | 'calculator') => void;
  onSave: () => void;
  onPublish: () => void;
  onAddNode: (type: string, subType?: string) => void;
  onAutoArrange?: () => void;
  isPaletteOpen?: boolean;
  onTogglePalette?: () => void;
  onOpenShortcuts?: () => void;
  canUndo?: boolean;
  canRedo?: boolean;
  onUndo?: () => void;
  onRedo?: () => void;
}

export const BuilderToolbar: React.FC<BuilderToolbarProps> = ({
  funnel,
  isDirty,
  isSaving,
  isJustSaved = false,
  isPublishing,
  funnelMode,
  onModeChange,
  onSave,
  onPublish,
  onAutoArrange,
  isPaletteOpen = true,
  onTogglePalette,
  onOpenShortcuts,
  canUndo = false,
  canRedo = false,
  onUndo,
  onRedo,
}) => {
  const [copied, setCopied] = useState(false);

  const basePath = window.location.pathname.startsWith('/qfunnel2') ? '/qfunnel2' : '';
  const publicPath = funnel?.slug ? `${basePath}/quiz/${funnel.slug}` : '';
  const publicUrl = funnel?.slug ? `${window.location.origin}${publicPath}` : '';

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
        {/* Undo / Redo Buttons */}
        {(onUndo || onRedo) && (
          <div className="flex items-center rounded-md border border-[#e8eaec] bg-white overflow-hidden shrink-0">
            <button
              type="button"
              onClick={onUndo}
              disabled={!canUndo}
              className="p-1.5 h-7 w-7 text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f4f6f8] transition flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none"
              title="Undo (Ctrl+Z / ⌘Z)"
            >
              <Undo2 className="w-3.5 h-3.5" />
            </button>
            <div className="w-[1px] h-3.5 bg-[#e8eaec]" />
            <button
              type="button"
              onClick={onRedo}
              disabled={!canRedo}
              className="p-1.5 h-7 w-7 text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f4f6f8] transition flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none"
              title="Redo (Ctrl+Y / ⌘⇧Z)"
            >
              <Redo2 className="w-3.5 h-3.5" />
            </button>
          </div>
        )}

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

        {onOpenShortcuts && (
          <button
            type="button"
            onClick={onOpenShortcuts}
            className="p-1.5 h-7 w-7 rounded-md border border-[#e8eaec] text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f4f6f8] transition shrink-0 flex items-center justify-center"
            title="Keyboard Shortcuts (?)"
          >
            <Keyboard className="w-3.5 h-3.5" />
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
          className={`w-[78px] h-7 rounded-md border text-xs transition-colors duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap shrink-0 select-none disabled:opacity-75 ${
            isSaving
              ? 'border-[#1d5fd6]/40 text-[#1d5fd6] bg-[#dcf1ff]/40 cursor-wait font-normal'
              : isJustSaved && !isDirty
              ? 'border-[#bbf0cc] text-[#04250e] bg-[#dcfae5]/70 shadow-xs font-medium'
              : isDirty
              ? 'border-[#1d5fd6] text-[#1d5fd6] bg-[#dcf1ff]/30 hover:bg-[#dcf1ff]/50 font-medium'
              : 'border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8] hover:text-[#131416] font-normal'
          }`}
          title={isDirty ? 'Save canvas changes (Ctrl+S)' : 'All changes saved to database'}
        >
          {isSaving ? (
            <span className="w-3.5 h-3.5 flex items-center justify-center shrink-0">
              <svg className="animate-spin-smooth w-3.5 h-3.5 text-[#1d5fd6]" viewBox="0 0 24 24" fill="none">
                <circle className="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
                <path className="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </span>
          ) : isJustSaved && !isDirty ? (
            <span className="w-3.5 h-3.5 flex items-center justify-center shrink-0 text-[#2f7a45] animate-check-pop">
              <svg className="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7" className="fill-[#dcfae5] stroke-[#2f7a45] stroke-[1.2]" />
                <path
                  d="M4.8 8.2L7 10.4L11.4 5.6"
                  fill="none"
                  stroke="#04250e"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className="animate-check-stroke"
                />
              </svg>
            </span>
          ) : isDirty ? (
            <Save className="w-3.5 h-3.5 shrink-0 text-[#1d5fd6]" />
          ) : (
            <Check className="w-3.5 h-3.5 shrink-0 text-[#5d5f60]" />
          )}
          <span>
            {isSaving
              ? 'Saving...'
              : isJustSaved && !isDirty
              ? 'Saved!'
              : isDirty
              ? 'Save'
              : 'Saved'}
          </span>
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

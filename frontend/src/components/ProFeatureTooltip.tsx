import React, { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Sparkles, ArrowRight, Lock, Check } from 'lucide-react';
import { useAuthStore } from '../store/authStore';
import { PlanFeatures } from '../types';

export const FEATURE_DESCRIPTIONS: Record<string, { title: string; desc: string }> = {
  white_label: {
    title: 'Complete White-Labeling',
    desc: 'Remove the "Powered by FilterFunnel" badge and brand watermarks from all live visitor quiz flows.',
  },
  calculator_mode: {
    title: 'Calculator & Dynamic Quote Engine',
    desc: 'Unlock mathematical formula logic, variable multipliers, and instant pricing quotes on completion.',
  },
  webhooks: {
    title: 'Outbound HTTP Webhooks',
    desc: 'Stream verified lead payloads and funnel drop-off telemetry in real-time to Zapier, Make, and CRMs.',
  },
  csv_export: {
    title: '1-Click CSV Lead Export',
    desc: 'Download all visitor submissions, custom form responses, and timeline timestamps as structured CSV files.',
  },
  custom_tracking_pixels: {
    title: 'Ad Tracking Pixels',
    desc: 'Deploy Meta Pixel, Google Tag Manager, and TikTok Pixel IDs for accurate conversion tracking and attribution.',
  },
  funnel_cloning: {
    title: '1-Click Funnel Duplication',
    desc: 'Instantly clone any branching canvas graph, questions, and form logic into a new funnel draft.',
  },
  step_dropoff_analytics: {
    title: 'Bottleneck & Drop-Off Analytics',
    desc: 'Visual step drop-off funnels pinpointing the exact question node where visitors abandon your quiz.',
  },
  api_access: {
    title: 'Developer REST API',
    desc: 'Programmatic API tokens to query funnels, fetch submission records, and automate workspace workflows.',
  },
};

interface ProFeatureTooltipProps {
  feature?: keyof PlanFeatures | string;
  isLocked?: boolean;
  featureName?: string;
  description?: string;
  children?: React.ReactNode;
  position?: 'top' | 'bottom' | 'left' | 'right';
  inlineBadge?: boolean;
  className?: string;
  onUpgradeClick?: () => void;
}

export const ProFeatureTooltip: React.FC<ProFeatureTooltipProps> = ({
  feature,
  isLocked: isLockedProp,
  featureName,
  description,
  children,
  position = 'top',
  inlineBadge = false,
  className = '',
  onUpgradeClick,
}) => {
  const { features, isPlatformAdmin } = useAuthStore();
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);
  const timerRef = useRef<any>(null);

  const defaultMeta = feature ? FEATURE_DESCRIPTIONS[feature] : null;
  const title = featureName || defaultMeta?.title || 'Pro Feature';
  const desc = description || defaultMeta?.desc || 'Upgrade your workspace to the Pro plan to unlock this advanced feature and expand your capacity.';

  // Determine lock state: explicit prop takes precedence, otherwise check authStore features
  const locked = isLockedProp !== undefined
    ? isLockedProp
    : !isPlatformAdmin && Boolean(feature && !features?.[feature as keyof PlanFeatures]);

  const handleMouseEnter = () => {
    if (timerRef.current) clearTimeout(timerRef.current);
    if (locked) {
      setIsOpen(true);
    }
  };

  const handleMouseLeave = () => {
    timerRef.current = setTimeout(() => {
      setIsOpen(false);
    }, 200);
  };

  useEffect(() => {
    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, []);

  // Position classes
  const positionClasses = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    right: 'left-full top-1/2 -translate-y-1/2 ml-2',
  }[position];

  const arrowClasses = {
    top: 'top-full left-1/2 -translate-x-1/2 border-t-slate-200 border-x-transparent border-b-transparent border-4',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-slate-200 border-x-transparent border-t-transparent border-4',
    left: 'left-full top-1/2 -translate-y-1/2 border-l-slate-200 border-y-transparent border-r-transparent border-4',
    right: 'right-full top-1/2 -translate-y-1/2 border-r-slate-200 border-y-transparent border-l-transparent border-4',
  }[position];

  // If not locked and no inline badge requested, render children as-is
  if (!locked && !inlineBadge) {
    return <>{children}</>;
  }

  return (
    <div
      ref={containerRef}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
      className={`relative inline-flex items-center ${className}`}
    >
      {/* Trigger element or inline PRO badge */}
      {children}

      {inlineBadge && (
        <span
          className={`ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold tracking-wider transition-colors inline-flex items-center gap-1 cursor-help select-none ${
            locked
              ? 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 hover:border-amber-300'
              : 'bg-emerald-50 text-emerald-800 border border-emerald-200'
          }`}
          title={locked ? `${title} (Click or hover to learn more)` : `${title} (Unlocked)`}
        >
          {locked ? (
            <>
              <Lock className="w-2.5 h-2.5 text-amber-700" />
              <span>PRO</span>
            </>
          ) : (
            <>
              <Check className="w-2.5 h-2.5 text-emerald-700" />
              <span>PRO</span>
            </>
          )}
        </span>
      )}

      {/* Floating Informative Tooltip Popover */}
      {locked && isOpen && (
        <div
          role="tooltip"
          onMouseEnter={handleMouseEnter}
          onMouseLeave={handleMouseLeave}
          className={`absolute z-50 ${positionClasses} w-72 bg-white rounded-xl border border-slate-200 shadow-xl p-3.5 space-y-2.5 text-left animate-in fade-in zoom-in-95 duration-150 pointer-events-auto`}
        >
          {/* Header */}
          <div className="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
            <div className="flex items-center gap-1.5 min-w-0">
              <span className="p-1 rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 shrink-0">
                <Sparkles className="w-3.5 h-3.5" />
              </span>
              <h4 className="text-xs font-bold text-slate-900 truncate">
                {title}
              </h4>
            </div>
            <span className="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
              Pro Plan
            </span>
          </div>

          {/* Description */}
          <p className="text-[11px] text-slate-600 leading-relaxed">
            {desc}
          </p>

          {/* Micro Action Bar */}
          <div className="flex items-center justify-between pt-1 border-t border-slate-100 gap-2">
            <span className="text-[10px] text-slate-600 font-medium">
              Available on Pro & Lifetime
            </span>
            <Link
              to="/admin/billing"
              onClick={() => {
                setIsOpen(false);
                if (onUpgradeClick) onUpgradeClick();
              }}
              className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#1d5fd6] text-white text-[11px] font-semibold hover:bg-[#1853bf] transition shadow-xs shrink-0 cursor-pointer"
            >
              <span>Upgrade</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          </div>

          {/* Arrow Indicator */}
          <div className={`absolute w-0 h-0 pointer-events-none ${arrowClasses}`} />
        </div>
      )}
    </div>
  );
};

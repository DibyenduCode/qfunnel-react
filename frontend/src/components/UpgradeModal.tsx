import React from 'react';
import { Link } from 'react-router-dom';
import { Sparkles, ArrowRight, X, ShieldCheck } from 'lucide-react';

interface UpgradeModalProps {
  isOpen: boolean;
  onClose: () => void;
  featureName: string;
  description?: string;
  requiredPlan?: string;
}

export const UpgradeModal: React.FC<UpgradeModalProps> = ({
  isOpen,
  onClose,
  featureName,
  description,
  requiredPlan = 'Pro',
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-5 shadow-xl relative">
        <button
          type="button"
          onClick={onClose}
          className="absolute top-4 right-4 text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc] transition"
          title="Close"
        >
          <X className="w-4 h-4" />
        </button>

        <div className="flex items-center gap-2">
          <span className="p-2 rounded-xl bg-[#dcf1ff] text-[#1d5fd6]">
            <Sparkles className="w-5 h-5" />
          </span>
          <div>
            <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-[#dcf1ff] text-[#1d5fd6] uppercase tracking-wider inline-block">
              {requiredPlan} Feature
            </span>
            <h3 className="text-base font-bold text-[#131416] mt-0.5">
              Enable {featureName}
            </h3>
          </div>
        </div>

        <p className="text-xs text-[#5d5f60] leading-relaxed">
          {description ||
            `This capability is available exclusively on the ${requiredPlan} tier and higher. Upgrade your workspace subscription to activate this feature, increase your monthly lead capacity, and expand your quiz funnels.`}
        </p>

        <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] space-y-2 text-xs">
          <div className="flex items-center gap-2 text-[#131416]">
            <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
            <span>Instant activation across all active funnels</span>
          </div>
          <div className="flex items-center gap-2 text-[#131416]">
            <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
            <span>Higher monthly lead quotas & priority webhooks</span>
          </div>
        </div>

        <div className="flex items-center justify-end gap-2 pt-2 border-t border-[#edf0f3]">
          <button
            type="button"
            onClick={onClose}
            className="px-3.5 py-2 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc] transition"
          >
            Maybe Later
          </button>
          <Link
            to="/admin/billing"
            onClick={onClose}
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition shadow-xs"
          >
            <span>Upgrade to {requiredPlan}</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      </div>
    </div>
  );
};

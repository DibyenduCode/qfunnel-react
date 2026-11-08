import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Layout, Image as ImageIcon, Video, ArrowRight, CheckCircle2, Star } from 'lucide-react';
import { resolveMediaUrl } from '../../../api/client';

export const ContentNode = memo(({ data, selected }: NodeProps) => {
  const d = data as any;
  const content = d.content || d.config?.content || d.config || {};
  const title = (d.title as string) || content.headline || 'Landing Page & Story';
  const badgeText = content.badge_text || '';
  const subheadline = content.subheadline || '';
  const mediaType = content.media_type || (content.image_url ? 'image' : 'none');
  const imageUrl = content.image_url || '';
  const videoUrl = content.video_url || '';
  const bodyText = content.body_text || content.subtext || '';
  const showBenefits = Boolean(content.show_benefits);
  const benefits = Array.isArray(content.benefits) ? content.benefits : [];
  const showSocialProof = Boolean(content.show_social_proof);
  const socialProofText = content.social_proof_text || '';
  const buttonText = content.button_text || 'Continue to Next Step ➔';

  return (
    <div
      className={`w-[320px] bg-white rounded-[12px] border transition-all duration-150 shadow-xs ${
        selected ? 'border-[#1d5fd6] ring-2 ring-[#dcf1ff]' : 'border-[#e2e5e9]'
      }`}
    >
      {/* Input Port (Left) */}
      <Handle
        type="target"
        position={Position.Left}
        id="input"
        className="!w-3 !h-3 !bg-[#1d5fd6] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe] flex items-center justify-center">
            <Layout className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Landing Page</span>
        </div>
        <span className="text-[10px] font-medium text-[#0a3a80] bg-[#dcf1ff] px-2 py-0.5 rounded-[4px] border border-[#b9e6fe] font-mono">
          Bridge / LP
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          {badgeText && (
            <span className="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded uppercase tracking-wider block w-fit mb-1">
              {badgeText}
            </span>
          )}
          <h4 className="text-sm font-semibold text-[#131416] line-clamp-1">{title}</h4>
          {subheadline ? (
            <p className="text-[11px] text-[#5d5f60] line-clamp-2 mt-0.5 leading-relaxed">
              {subheadline}
            </p>
          ) : bodyText ? (
            <p className="text-[11px] text-[#5d5f60] line-clamp-2 mt-0.5 leading-relaxed">
              {bodyText}
            </p>
          ) : null}
        </div>

        {/* Media Preview: Image or Video */}
        {mediaType === 'image' && imageUrl && (
          <div className="rounded-[8px] overflow-hidden border border-[#e8eaec] h-24 bg-slate-50 relative">
            <img
              src={resolveMediaUrl(imageUrl)}
              alt={title}
              className="w-full h-full object-cover"
              onError={(e) => {
                (e.target as HTMLElement).style.display = 'none';
              }}
            />
          </div>
        )}

        {mediaType === 'video' && videoUrl && (
          <div className="rounded-[8px] p-2 border border-slate-200 bg-slate-900 text-white flex items-center gap-2 text-xs">
            <Video className="w-4 h-4 text-rose-400 shrink-0" />
            <span className="truncate font-mono text-[11px] text-slate-300">{videoUrl}</span>
          </div>
        )}

        {/* Benefits & Social Proof Pills */}
        {(showBenefits || showSocialProof) && (
          <div className="flex items-center gap-1.5 flex-wrap pt-0.5">
            {showBenefits && benefits.length > 0 && (
              <span className="text-[10px] text-emerald-800 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded flex items-center gap-1 font-medium">
                <CheckCircle2 className="w-2.5 h-2.5 text-emerald-600" />
                {benefits.length} Benefits
              </span>
            )}
            {showSocialProof && (
              <span className="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1 font-medium">
                <Star className="w-2.5 h-2.5 text-amber-500 fill-amber-500" />
                Social Proof
              </span>
            )}
          </div>
        )}

        {/* Action Button Badge */}
        <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] flex items-center justify-between text-xs">
          <span className="text-[#5d5f60] text-[11px]">Primary CTA</span>
          <span className="font-medium text-[#131416] text-[11px] flex items-center gap-1 truncate max-w-[170px]">
            <span className="truncate">{buttonText}</span>
            <ArrowRight className="w-3 h-3 text-[#1d5fd6] shrink-0" />
          </span>
        </div>
      </div>

      {/* Footer / Output Handle (Right) */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60] flex items-center gap-1.5">
          <span className="w-1.5 h-1.5 rounded-full bg-[#1d5fd6]" />
          <span>Advance to next step</span>
        </span>

        <Handle
          type="source"
          position={Position.Right}
          id="output"
          className="!w-3 !h-3 !bg-[#1d5fd6] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
        />
      </div>
    </div>
  );
});

ContentNode.displayName = 'ContentNode';

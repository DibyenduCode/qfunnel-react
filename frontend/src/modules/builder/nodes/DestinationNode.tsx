import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Flag, ExternalLink, Calculator, Zap, Image as ImageIcon, Video as VideoIcon } from 'lucide-react';

export const DestinationNode = memo(({ data, selected }: NodeProps) => {
  const dest = (data.destination as any) || {};
  const title = (data.title as string) || dest.name || 'Final Outcome';
  const qc = (data.quick_connect as any) || {};
  const destMode = (data.destination_mode as string) || (qc.enabled ? 'direct_connect' : 'redirect');
  const isDirectConnect = destMode === 'direct_connect';
  const media = (data.media as any) || (data.config as any)?.media || {};
  const mediaType = media.media_type || 'none';

  const calc = (data.calculator as any) || {};
  const isCalc = Boolean(calc.enabled);
  const hasQC = isDirectConnect || Boolean(qc.enabled);

  let badgeText = 'Destination';
  if (hasQC && isCalc) badgeText = 'Quote and Connect';
  else if (hasQC) badgeText = 'Direct Connect';
  else if (isCalc) badgeText = 'Calculated Quote';

  const hasValidUrl = dest.url && dest.url.trim() !== '' && dest.url.trim() !== '#';

  return (
    <div
      className={`w-[320px] bg-white rounded-[12px] border transition-all duration-150 shadow-xs ${
        selected ? 'border-[#1d5fd6] ring-2 ring-[#dcf1ff]' : 'border-[#e2e5e9]'
      }`}
    >
      {/* Input Port */}
      <Handle
        type="target"
        position={Position.Left}
        id="input"
        className="!w-3 !h-3 !bg-[#2f7a45] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div
            className={`w-6 h-6 rounded-[6px] flex items-center justify-center border ${
              isCalc
                ? 'bg-[#fef3c7] text-[#b45309] border-[#fde68a]'
                : hasQC
                ? 'bg-[#dcfae5] text-[#2f7a45] border-[#bbf7d0]'
                : 'bg-[#dcf1ff] text-[#1d5fd6] border-[#b9e6fe]'
            }`}
          >
            {isCalc ? <Calculator className="w-3.5 h-3.5" /> : hasQC ? <Zap className="w-3.5 h-3.5" /> : <Flag className="w-3.5 h-3.5" />}
          </div>
          <span className="text-xs font-semibold text-[#131416]">{badgeText}</span>
        </div>
        <span
          className={`text-[10px] font-medium px-2 py-0.5 rounded-[4px] border ${
            isCalc
              ? 'bg-[#fef3c7] text-[#b45309] border-[#fde68a]'
              : hasQC
              ? 'bg-[#dcfae5] text-[#2f7a45] border-[#bbf7d0]'
              : 'bg-[#dcf1ff] text-[#1d5fd6] border-[#b9e6fe]'
          }`}
        >
          Outcome
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{title}</h4>
          {!isDirectConnect && (
            hasValidUrl ? (
              <div className="flex items-center gap-1.5 text-xs text-[#5d5f60] bg-[#f4f6f8] px-2 py-1 rounded-[6px] border border-[#e8eaec] mt-1 truncate">
                <ExternalLink className="w-3 h-3 shrink-0 text-[#5d5f60]" />
                <span className="truncate font-mono text-[11px]">{dest.url}</span>
              </div>
            ) : (
              <div className="text-xs text-[#1e7b4f] bg-[#dcfae5] border border-[#bbf0cc] px-2 py-1 rounded-[6px] mt-1 flex items-center gap-1.5">
                <span className="w-1.5 h-1.5 rounded-full bg-[#1e7b4f] shrink-0" />
                <span>Direct on-page outcome</span>
              </div>
            )
          )}

          {/* Media Attached Indicator */}
          {mediaType === 'image' && media.image_url && (
            <div className="flex items-center gap-1.5 text-xs text-[#0f766e] bg-[#f0fdfa] px-2 py-1 rounded-[6px] border border-[#ccfbf1] mt-1 truncate">
              <ImageIcon className="w-3.5 h-3.5 text-[#0f766e] shrink-0" />
              <span className="truncate font-medium text-[11px]">Image attached</span>
            </div>
          )}

          {mediaType === 'video' && media.video_url && (
            <div className="flex items-center gap-1.5 text-xs text-[#c2410c] bg-[#fff7ed] px-2 py-1 rounded-[6px] border border-[#ffedd5] mt-1 truncate">
              <VideoIcon className="w-3.5 h-3.5 text-[#c2410c] shrink-0" />
              <span className="truncate font-medium text-[11px]">Video presentation attached</span>
            </div>
          )}
        </div>

        {/* Calculator Formula Display */}
        {isCalc && (
          <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] space-y-1">
            <div className="flex items-center justify-between text-xs">
              <span className="text-[#5d5f60] flex items-center gap-1">
                <Calculator className="w-3 h-3 text-[#1b6c80]" />
                <span>Formula</span>
              </span>
              <span className="text-[10px] font-mono text-[#1b6c80] bg-white px-1.5 py-0.5 rounded border border-[#e8eaec]">
                {calc.result_prefix || '₹'} Quote
              </span>
            </div>
            <div className="font-mono text-xs text-[#131416] bg-white px-2 py-1 rounded-[6px] border border-[#e8eaec] truncate">
              f(x) = {calc.formula_expression || '@Q1 * 100'}
            </div>
          </div>
        )}

        {/* Direct Connect WhatsApp / Call Display */}
        {hasQC && (
          <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] space-y-1.5">
            <div className="flex items-center justify-between text-xs">
              <span className="font-medium text-[#131416] flex items-center gap-1">
                <Zap className="w-3 h-3 text-[#1e7b4f]" />
                <span>
                  {qc.action_type === 'call'
                    ? 'Instant Call'
                    : qc.action_type === 'whatsapp'
                    ? 'WhatsApp Chat'
                    : 'Call and WhatsApp'}
                </span>
              </span>
              <span className="inline-flex items-center gap-1 text-[10px] font-medium text-[#1e7b4f] bg-[#dcfae5] px-1.5 py-0.5 rounded border border-[#bbf0cc]">
                <span>Direct</span>
              </span>
            </div>

            <div className="space-y-1 text-xs font-mono">
              {(qc.action_type === 'both' || !qc.action_type || qc.action_type === 'call') && qc.phone_number && (
                <div className="px-2 py-1 rounded-[6px] bg-white border border-[#e8eaec] text-[#131416] flex items-center gap-1.5 truncate">
                  <span className="text-[#5d5f60] text-[10px]">Call:</span>
                  <span className="truncate">{qc.phone_number}</span>
                </div>
              )}
              {(qc.action_type === 'both' || qc.action_type === 'whatsapp') && qc.whatsapp_number && (
                <div className="px-2 py-1 rounded-[6px] bg-white border border-[#e8eaec] text-[#131416] flex items-center gap-1.5 truncate">
                  <span className="text-[#5d5f60] text-[10px]">WA:</span>
                  <span className="truncate">{qc.whatsapp_number}</span>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
});

DestinationNode.displayName = 'DestinationNode';

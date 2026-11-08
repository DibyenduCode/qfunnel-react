import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Image as ImageIcon, ArrowRight } from 'lucide-react';
import { resolveMediaUrl } from '../../../api/client';

export const ContentNode = memo(({ data, selected }: NodeProps) => {
  const d = data as any;
  const content = d.content || d.config?.content || d.config || {};
  const title = (d.title as string) || content.headline || 'Content Slide';
  const imageUrl = content.image_url || '';
  const bodyText = content.body_text || content.subtext || '';
  const buttonText = content.button_text || 'Continue to Next Step';

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
            <ImageIcon className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Content Slide</span>
        </div>
        <span className="text-[10px] font-medium text-[#0a3a80] bg-[#dcf1ff] px-2 py-0.5 rounded-[4px] border border-[#b9e6fe] font-mono">
          Story / Info
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-semibold text-[#131416] line-clamp-1">{title}</h4>
          {bodyText && (
            <p className="text-[11px] text-[#5d5f60] line-clamp-2 mt-0.5 leading-relaxed">
              {bodyText}
            </p>
          )}
        </div>

        {/* Thumbnail Preview */}
        {imageUrl ? (
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
        ) : (
          <div className="p-2.5 rounded-[8px] border border-dashed border-[#cdced1] bg-[#f9fbfc] text-center text-[11px] text-[#5d5f60] flex items-center justify-center gap-1.5">
            <ImageIcon className="w-3.5 h-3.5 text-[#5d5f60]" />
            <span>No image attached yet</span>
          </div>
        )}

        {/* Action Button Badge */}
        <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] flex items-center justify-between text-xs">
          <span className="text-[#5d5f60] text-[11px]">Action Button</span>
          <span className="font-medium text-[#131416] text-[11px] flex items-center gap-1">
            <span>{buttonText}</span>
            <ArrowRight className="w-3 h-3 text-[#1d5fd6]" />
          </span>
        </div>
      </div>

      {/* Footer / Output Handle (Right) */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60] flex items-center gap-1.5">
          <span className="w-1.5 h-1.5 rounded-full bg-[#1d5fd6]" />
          <span>Advance after continue</span>
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

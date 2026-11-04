import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Sparkles, Clock, ArrowRight, Loader2 } from 'lucide-react';

export const LoaderNode = memo(({ data, selected }: NodeProps) => {
  const loader = (data.loader as any) || (data.config as any) || {};
  const headline = (data.title as string) || loader.headline || 'Analyzing your responses...';
  const subtext = loader.subtext || 'Matching the best options based on your answers...';
  const duration = Number(loader.duration_seconds ?? 2.5);

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
        className="!w-3 !h-3 !bg-[#b45309] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#fef3c7] text-[#b45309] border border-[#fde68a] flex items-center justify-center">
            <Sparkles className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Calculating Loader</span>
        </div>
        <span className="text-[10px] font-medium text-[#b45309] bg-[#fef3c7] px-2 py-0.5 rounded-[4px] border border-[#fde68a] flex items-center gap-1 font-mono">
          <Clock className="w-2.5 h-2.5" />
          <span>{duration}s</span>
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{headline}</h4>
          <p className="text-xs text-[#5d5f60] line-clamp-1 mt-0.5">{subtext}</p>
        </div>

        {/* Progress Interstitial Bar */}
        <div className="p-2.5 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] space-y-2">
          <div className="flex items-center justify-between text-xs">
            <span className="text-[#131416] font-medium flex items-center gap-1">
              <Loader2 className="w-3 h-3 text-[#a86f0c] animate-spin" />
              <span>Auto-advancing...</span>
            </span>
            <span className="text-[#5d5f60] font-mono text-xs">100%</span>
          </div>
          <div className="w-full bg-[#e8eaec] h-1.5 rounded-full overflow-hidden">
            <div className="bg-[#a86f0c] h-full rounded-full w-3/4" />
          </div>
        </div>
      </div>

      {/* Footer / Output Handle */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60]">Advances to next step</span>
        <div className="flex items-center gap-1 text-[#1b6c80] font-medium">
          <span>Next</span>
          <ArrowRight className="w-3 h-3" />
        </div>

        <Handle
          type="source"
          position={Position.Right}
          id="output"
          className="!w-3 !h-3 !bg-[#1b6c80] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
        />
      </div>
    </div>
  );
});

LoaderNode.displayName = 'LoaderNode';

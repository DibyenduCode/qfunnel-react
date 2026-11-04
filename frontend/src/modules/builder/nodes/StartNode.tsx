import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Play } from 'lucide-react';

export const StartNode = memo(({ data, selected }: NodeProps) => {
  const title = (data.title as string) || 'Welcome Screen';
  const subtitle = (data.config as any)?.subtitle || 'Public entry point. Connects visitors to the first step.';

  return (
    <div
      className={`w-[320px] bg-white rounded-[12px] border transition-all duration-150 shadow-xs ${
        selected ? 'border-[#1d5fd6] ring-2 ring-[#dcf1ff]' : 'border-[#e2e5e9]'
      }`}
    >
      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0] flex items-center justify-center">
            <Play className="w-3.5 h-3.5 fill-current" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Start Step</span>
        </div>
        <span className="text-[10px] font-medium text-[#2f7a45] bg-[#dcfae5] px-2 py-0.5 rounded-[4px] border border-[#bbf7d0]">
          Flow entry
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-1">
        <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{title}</h4>
        <p className="text-xs text-[#5d5f60] leading-relaxed">{subtitle}</p>
      </div>

      {/* Footer / Output Port */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60] flex items-center gap-1.5">
          <span className="w-1.5 h-1.5 rounded-full bg-[#2f7a45]" />
          <span>Proceed to flow</span>
        </span>

        <Handle
          type="source"
          position={Position.Right}
          id="output"
          className="!w-3 !h-3 !bg-[#2f7a45] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
        />
      </div>
    </div>
  );
});

StartNode.displayName = 'StartNode';

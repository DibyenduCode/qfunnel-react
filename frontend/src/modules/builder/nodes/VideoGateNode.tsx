import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Video, Lock, PlayCircle, Check } from 'lucide-react';

export const VideoGateNode = memo(({ data, selected }: NodeProps) => {
  const vg = (data.video_gate as any) || (data.config as any) || {};
  const title = (data.title as string) || vg.gate_headline || 'Video Turnstile Gate';
  const videoUrl = vg.video_url || '';
  const gateSeconds = Number(vg.gate_timestamp_seconds ?? 45);
  const gateMin = Math.floor(gateSeconds / 60);
  const gateSec = Math.floor(gateSeconds % 60);
  const formattedTime = gateMin > 0 ? `${gateMin}m ${gateSec}s` : `${gateSec}s`;
  const allowSkip = Boolean(vg.allow_skip_gate);

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
        className="!w-3 !h-3 !bg-[#c41c29] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#ffedea] text-[#c41c29] border border-[#fad2cd] flex items-center justify-center">
            <Video className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Video Gate</span>
        </div>
        <span className="text-[10px] font-medium text-[#c41c29] bg-[#ffedea] px-2 py-0.5 rounded-[4px] border border-[#fad2cd] flex items-center gap-1 font-mono">
          <Lock className="w-2.5 h-2.5" />
          <span>{formattedTime}</span>
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{title}</h4>
          {videoUrl ? (
            <p className="text-[11px] text-[#5d5f60] font-mono truncate mt-0.5 flex items-center gap-1">
              <PlayCircle className="w-3 h-3 text-[#c4242a] shrink-0" />
              <span className="truncate">{videoUrl}</span>
            </p>
          ) : (
            <p className="text-[11px] text-[#92400e] mt-0.5">No video URL linked yet</p>
          )}
        </div>

        <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] space-y-1 text-xs text-[#5d5f60]">
          <div className="flex items-center justify-between">
            <span>Turnstile pause</span>
            <span className="font-mono text-[#131416]">{formattedTime}</span>
          </div>
          <div className="flex items-center justify-between">
            <span>Skip option</span>
            <span className="font-medium text-[#131416] flex items-center gap-1">
              {allowSkip ? (
                <>
                  <Check className="w-3 h-3 text-[#1e7b4f]" /> Optional
                </>
              ) : (
                <>
                  <Lock className="w-3 h-3 text-[#92400e]" /> Required
                </>
              )}
            </span>
          </div>
        </div>
      </div>

      {/* Footer / Output Handle */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60] flex items-center gap-1.5">
          <span className="w-1.5 h-1.5 rounded-full bg-[#c4242a]" />
          <span>Advance after gate</span>
        </span>

        <Handle
          type="source"
          position={Position.Right}
          id="output"
          className="!w-3 !h-3 !bg-[#c4242a] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
        />
      </div>
    </div>
  );
});

VideoGateNode.displayName = 'VideoGateNode';

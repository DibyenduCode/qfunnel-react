import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { Calendar, ExternalLink, ArrowRight, CheckCircle } from 'lucide-react';

export const BookingNode = memo(({ data, selected }: NodeProps) => {
  const booking = (data.booking as any) || (data.config as any) || {};
  const title = (data.title as string) || booking.headline || 'Schedule Consultation';
  const provider = booking.provider || 'calendly';
  const bookingUrl = booking.booking_url || '';

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
        className="!w-3 !h-3 !bg-[#0f766e] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#ccfbf1] text-[#0f766e] border border-[#99f6e4] flex items-center justify-center">
            <Calendar className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Booking Calendar</span>
        </div>
        <span className="text-[10px] font-medium text-[#0f766e] bg-[#ccfbf1] px-2 py-0.5 rounded-[4px] border border-[#99f6e4] capitalize">
          {provider}
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{title}</h4>
          {bookingUrl ? (
            <p className="text-[11px] text-[#5d5f60] font-mono truncate mt-0.5 flex items-center gap-1">
              <ExternalLink className="w-3 h-3 text-[#1e7b4f] shrink-0" />
              <span className="truncate">{bookingUrl}</span>
            </p>
          ) : (
            <p className="text-[11px] text-[#92400e] mt-0.5">No calendar URL configured</p>
          )}
        </div>

        <div className="p-2 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] text-xs text-[#5d5f60] space-y-1">
          <div className="flex items-center justify-between">
            <span>Embed format</span>
            <span className="font-medium text-[#131416]">
              {booking.embed_mode === 'inline' ? 'Inline Calendar' : 'Popup Modal'}
            </span>
          </div>
          <div className="flex items-center justify-between">
            <span>Pre-fill contact</span>
            <span className="font-medium text-[#1e7b4f] flex items-center gap-0.5">
              <CheckCircle className="w-3 h-3" /> Yes (from lead step)
            </span>
          </div>
        </div>
      </div>

      {/* Footer / Output Handle */}
      <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
        <span className="text-[#5d5f60]">Post-booking step</span>
        <div className="flex items-center gap-1 text-[#1b6c80] font-medium">
          <span>Completed</span>
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

BookingNode.displayName = 'BookingNode';

import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { FileText, CheckSquare, MessageSquare } from 'lucide-react';

export const FormNode = memo(({ data, selected }: NodeProps) => {
  const form = (data.form as any) || {};
  const title = form.heading || (data.title as string) || 'Lead Capture Form';
  const subheading = form.subheading || '';
  const fields = (form.fields as any[]) || (data.fields as any[]) || [
    { label: 'Full Name', field_type: 'text', is_required: 1 },
    { label: 'Email Address', field_type: 'email', is_required: 1 },
  ];
  const submitAction = form.submit_action || 'redirect';
  const isMessage = submitAction === 'message';

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
        className="!w-3 !h-3 !bg-[#1d5fd6] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#e0e7ff] text-[#3730a3] border border-[#c7d2fe] flex items-center justify-center">
            <FileText className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">Lead Form</span>
        </div>
        <span
          className={`text-[10px] font-medium px-2 py-0.5 rounded-[4px] border capitalize ${
            isMessage
              ? 'text-[#2f7a45] bg-[#dcfae5] border-[#bbf7d0]'
              : 'text-[#3730a3] bg-[#e0e7ff] border-[#c7d2fe]'
          }`}
        >
          {isMessage ? 'Confirmation note' : 'Capture gate'}
        </span>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-2.5">
        <div>
          <h4 className="text-sm font-medium text-[#131416] line-clamp-1">{title}</h4>
          {subheading && (
            <p className="text-xs text-[#5d5f60] line-clamp-1 mt-0.5">{subheading}</p>
          )}
          <span className="text-[11px] text-[#5d5f60] mt-1 block">
            {fields.length} field{fields.length === 1 ? '' : 's'} •{' '}
            {isMessage ? 'Shows confirmation message' : 'Forwards to next step'}
          </span>
        </div>

        {/* Fields list */}
        <div className="space-y-1">
          {fields.slice(0, 4).map((f: any, idx: number) => (
            <div
              key={f.id || idx}
              className="flex items-center justify-between text-xs text-[#131416] bg-[#f4f6f8] px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec]"
            >
              <div className="flex items-center gap-1.5 truncate max-w-[190px]">
                <CheckSquare className="w-3 h-3 text-[#5d5f60] shrink-0" />
                <span className="truncate">{f.label || f.name || f.field_key}</span>
              </div>
              <span className="text-[10px] font-mono text-[#5d5f60]">
                {f.field_type || 'text'}
                {f.is_required ? '*' : ''}
              </span>
            </div>
          ))}
          {fields.length > 4 && (
            <span className="text-[11px] text-[#5d5f60] block text-center pt-0.5">
              + {fields.length - 4} more fields
            </span>
          )}
          {fields.length === 0 && (
            <div className="p-2 text-center text-xs text-[#92400e] bg-[#fffbeb] rounded-[6px] border border-[#fef3c7]">
              No fields configured yet
            </div>
          )}
        </div>

        {/* Message Mode Preview */}
        {isMessage && (
          <div className="p-2 rounded-[6px] bg-[#dcfae5] border border-[#bbf0cc] text-xs flex items-start gap-1.5 text-[#04250e]">
            <MessageSquare className="w-3.5 h-3.5 text-[#1e7b4f] shrink-0 mt-0.5" />
            <div className="truncate">
              <span className="font-semibold block truncate">{form.success_heading || 'Thank you'}</span>
              <span className="text-[11px] text-[#1e7b4f] truncate block">
                {form.success_message || 'Your submission has been received.'}
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Output Port for Route Mode */}
      {!isMessage && (
        <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
          <span className="text-[#5d5f60] flex items-center gap-1.5">
            <span className="w-1.5 h-1.5 rounded-full bg-[#1b6c80]" />
            <span>Forward on submit</span>
          </span>
          <Handle
            type="source"
            position={Position.Right}
            id="output"
            className="!w-3 !h-3 !bg-[#1b6c80] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
          />
        </div>
      )}
    </div>
  );
});

FormNode.displayName = 'FormNode';

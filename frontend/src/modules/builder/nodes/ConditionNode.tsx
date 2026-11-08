import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { GitBranch, Check, CornerDownRight, HelpCircle } from 'lucide-react';

export const ConditionNode = memo(({ data, selected }: NodeProps) => {
  const cond = (data.condition as any) || (data.config as any) || {};
  const title = (data.title as string) || 'Conditional Router';
  const condType = cond.condition_type || 'score';
  const operator = cond.operator || '>=';
  const value = cond.value ?? '50';
  const variableKey = cond.variable_key || 'Q1';
  const matchLabel = cond.match_label || 'Match (True)';
  const defaultLabel = cond.default_label || 'Default (Else)';

  // Build human-friendly rule description
  let ruleText = '';
  if (condType === 'score') {
    ruleText = `IF Total Score ${operator} ${value}`;
  } else if (condType === 'slider') {
    ruleText = `IF Slider ${operator} ${value}`;
  } else if (condType === 'variable') {
    ruleText = `IF @${variableKey.replace('@', '')} ${operator} ${value}`;
  } else {
    ruleText = `IF Answer ${operator} "${value}"`;
  }

  return (
    <div
      className={`w-[310px] bg-white rounded-[12px] border transition-all duration-150 shadow-xs ${
        selected ? 'border-[#c2410c] ring-2 ring-[#ffedd5]' : 'border-[#e2e5e9]'
      }`}
    >
      {/* Input Port */}
      <Handle
        type="target"
        position={Position.Left}
        id="input"
        className="!w-3 !h-3 !bg-[#c2410c] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-left-1.5"
      />

      {/* Header */}
      <div className="p-3 border-b border-[#f0f1f2] flex items-center justify-between rounded-t-[12px] bg-white">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-[6px] bg-[#ffedd5] text-[#c2410c] border border-[#fed7aa] flex items-center justify-center">
            <GitBranch className="w-3.5 h-3.5" />
          </div>
          <span className="text-xs font-semibold text-[#131416]">{title}</span>
        </div>
        <span className="text-[10px] font-medium text-[#c2410c] bg-[#ffedd5] px-2 py-0.5 rounded-[4px] border border-[#fed7aa]">
          Logic Gate
        </span>
      </div>

      {/* Body: Active Rule Card */}
      <div className="p-3 space-y-2.5">
        <div className="p-2.5 rounded-[8px] bg-[#fff7ed] border border-[#ffedd5] space-y-1">
          <div className="flex items-center justify-between text-[10px] font-medium text-[#9a3412] uppercase tracking-wider">
            <span>Evaluation Rule</span>
            <span className="capitalize">{condType} check</span>
          </div>
          <p className="text-xs font-mono font-semibold text-[#c2410c] truncate">
            {ruleText}
          </p>
        </div>

        {/* Output Branching Ports */}
        <div className="space-y-1.5 pt-0.5">
          {/* 1. MATCH / TRUE Branch */}
          <div className="relative flex items-center justify-between text-xs font-medium text-[#131416] bg-[#f0fdf4] hover:bg-[#dcfce7] px-2.5 py-2 rounded-[8px] border border-[#bbf7d0] transition-colors">
            <div className="flex items-center gap-1.5 truncate max-w-[220px]">
              <span className="w-4 h-4 rounded-[4px] bg-emerald-500 text-white text-[10px] font-bold flex items-center justify-center shrink-0">
                <Check className="w-3 h-3 stroke-[3]" />
              </span>
              <div className="truncate">
                <span className="font-semibold text-emerald-800">{matchLabel}</span>
                <span className="text-[10px] text-emerald-600 block">When rule is MET</span>
              </div>
            </div>

            <Handle
              type="source"
              position={Position.Right}
              id="match"
              className="!w-3 !h-3 !bg-emerald-600 !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
            />
          </div>

          {/* 2. DEFAULT / ELSE Branch */}
          <div className="relative flex items-center justify-between text-xs font-medium text-[#131416] bg-[#f8fafc] hover:bg-[#f1f5f9] px-2.5 py-2 rounded-[8px] border border-[#e2e8f0] transition-colors">
            <div className="flex items-center gap-1.5 truncate max-w-[220px]">
              <span className="w-4 h-4 rounded-[4px] bg-slate-400 text-white text-[10px] font-bold flex items-center justify-center shrink-0">
                <CornerDownRight className="w-3 h-3" />
              </span>
              <div className="truncate">
                <span className="font-semibold text-slate-700">{defaultLabel}</span>
                <span className="text-[10px] text-slate-500 block">Otherwise (Fallback)</span>
              </div>
            </div>

            <Handle
              type="source"
              position={Position.Right}
              id="default"
              className="!w-3 !h-3 !bg-slate-500 !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
            />
          </div>
        </div>
      </div>
    </div>
  );
});

ConditionNode.displayName = 'ConditionNode';


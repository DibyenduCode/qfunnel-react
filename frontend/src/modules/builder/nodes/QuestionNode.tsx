import React, { memo } from 'react';
import { Handle, Position, NodeProps } from '@xyflow/react';
import { HelpCircle, Sliders, ChevronRight } from 'lucide-react';

export const QuestionNode = memo(({ data, selected }: NodeProps) => {
  const q = (data.question as any) || {};
  const title = (data.title as string) || q.question_text || 'Untitled Question';
  const inputType = (data.input_type as string) || q.question_type || 'single_choice';
  const isSlider = inputType === 'slider';
  const variableKey = (data.variable_key as string) || 'Q1';

  const options = (data.options as any[]) || q.options || [
    { id: 'opt_1', option_key: 'opt_1', label: 'Option 1' },
    { id: 'opt_2', option_key: 'opt_2', label: 'Option 2' },
  ];

  const sliderConfig = (data.slider_config as any) || {
    min_value: 1,
    max_value: 100,
    step_value: 1,
    default_value: 10,
    unit_label: 'Units',
    unit_prefix: '',
  };

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
          <div
            className={`w-6 h-6 rounded-[6px] flex items-center justify-center border ${
              isSlider
                ? 'bg-[#ccfbf1] text-[#0f766e] border-[#99f6e4]'
                : 'bg-[#dcf1ff] text-[#1d5fd6] border-[#b9e6fe]'
            }`}
          >
            {isSlider ? <Sliders className="w-3.5 h-3.5" /> : <HelpCircle className="w-3.5 h-3.5" />}
          </div>
          <span className="text-xs font-semibold text-[#131416]">
            {isSlider ? 'Numeric Slider' : 'Multiple Choice'}
          </span>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-[10px] font-mono text-[#5d5f60] bg-[#f4f6f8] px-1.5 py-0.5 rounded border border-[#e8eaec]">
            @{variableKey}
          </span>
          <span
            className={`text-[10px] font-medium px-2 py-0.5 rounded-[4px] border capitalize ${
              isSlider
                ? 'text-[#0f766e] bg-[#ccfbf1] border-[#99f6e4]'
                : 'text-[#1d5fd6] bg-[#dcf1ff] border-[#b9e6fe]'
            }`}
          >
            {isSlider ? 'Slider' : inputType.replace('_', ' ')}
          </span>
        </div>
      </div>

      {/* Body Content */}
      <div className="p-3.5 space-y-3">
        <h4 className="text-sm font-medium text-[#131416] line-clamp-2 leading-snug">{title}</h4>

        {isSlider ? (
          /* Slider Specific Preview */
          <div className="p-2.5 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] space-y-2">
            <div className="flex items-center justify-between text-xs text-[#5d5f60]">
              <span>Default Value</span>
              <span className="px-2 py-0.5 rounded-[4px] bg-white text-[#131416] border border-[#e8eaec] font-mono text-xs font-medium">
                {sliderConfig.unit_prefix || ''}
                {sliderConfig.default_value ?? 10} {sliderConfig.unit_label || ''}
              </span>
            </div>
            <div className="w-full bg-[#e8eaec] h-1.5 rounded-full overflow-hidden">
              <div className="bg-[#2f70bb] h-full w-1/2 rounded-full" />
            </div>
            <div className="flex justify-between text-[10px] text-[#5d5f60] font-mono">
              <span>Min: {sliderConfig.min_value ?? 1}</span>
              <span>Step: {sliderConfig.step_value ?? 1}</span>
              <span>Max: {sliderConfig.max_value ?? 100}</span>
            </div>
          </div>
        ) : (
          /* Choice Options List with Branching Handles */
          <div className="space-y-1.5 pt-0.5">
            {options.length === 0 ? (
              <div className="p-2 text-center text-xs text-[#c4242a] bg-[#ffedea] border border-[#fad2cd] rounded-[6px]">
                No options configured yet
              </div>
            ) : (
              options.map((opt: any, idx: number) => {
                const handleId = opt.id ? String(opt.id) : (opt.option_key || opt.key || `opt_${idx}`);
                const letter = String.fromCharCode(65 + (idx % 26));
                const hasValue = opt.value !== undefined && opt.value !== null && opt.value !== '' && opt.value !== opt.label;

                return (
                  <div
                    key={handleId}
                    className="relative flex items-center justify-between text-xs font-medium text-[#131416] bg-[#f4f6f8] hover:bg-[#ebedee] px-2.5 py-1.5 rounded-[8px] border border-[#e8eaec] transition-colors"
                  >
                    <div className="flex items-center gap-1.5 truncate max-w-[220px]">
                      <span className="w-4 h-4 rounded-[4px] bg-white border border-[#e8eaec] text-[10px] font-medium text-[#5d5f60] flex items-center justify-center shrink-0">
                        {letter}
                      </span>
                      <span className="truncate">{opt.label || `Option ${idx + 1}`}</span>
                      {hasValue && (
                        <span className="text-[10px] font-mono text-[#1e7b4f] bg-[#dcfae5] px-1 py-0.5 rounded border border-[#bbf0cc] shrink-0">
                          v:{opt.value}
                        </span>
                      )}
                    </div>

                    <ChevronRight className="w-3.5 h-3.5 text-[#5d5f60] shrink-0" />

                    {/* Output port for this option */}
                    <Handle
                      type="source"
                      position={Position.Right}
                      id={handleId}
                      className="!w-3 !h-3 !bg-[#1b6c80] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
                    />
                  </div>
                );
              })
            )}
          </div>
        )}
      </div>

      {/* Slider Route Output Handle Footer */}
      {isSlider && (
        <div className="px-3 py-2 bg-[#f4f6f8] border-t border-[#f0f1f2] flex items-center justify-between text-xs rounded-b-[12px] relative">
          <span className="text-[#5d5f60] flex items-center gap-1.5">
            <span className="w-1.5 h-1.5 rounded-full bg-[#2f70bb]" />
            <span>Route on slide</span>
          </span>
          <Handle
            type="source"
            position={Position.Right}
            id="output"
            className="!w-3 !h-3 !bg-[#2f70bb] !border-2 !border-white !rounded-full hover:!scale-125 transition-transform !-right-1.5"
          />
        </div>
      )}
    </div>
  );
});

QuestionNode.displayName = 'QuestionNode';

import React, { useState, useEffect } from 'react';
import {
  X,
  Trash2,
  Plus,
  HelpCircle,
  FileText,
  Video,
  Flag,
  Play,
  MessageCircle,
  PhoneCall,
  Sliders,
  Calculator,
  ExternalLink,
  ChevronUp,
  ChevronDown,
  Check,
  Lock,
  Sparkles
} from 'lucide-react';

interface NodeInspectorProps {
  node: any | null;
  allNodes: any[];
  funnelMode: 'quiz' | 'calculator';
  onUpdate: (updatedNode: any) => void;
  onDelete: (nodeId: string) => void;
  onClose: () => void;
}

export const NodeInspector: React.FC<NodeInspectorProps> = ({
  node,
  allNodes,
  funnelMode,
  onUpdate,
  onDelete,
  onClose,
}) => {
  if (!node) return null;

  const nodeType = node.type || node.data?.node_type || 'question';
  const data = node.data || {};

  // Common
  const [title, setTitle] = useState(data.title || '');

  // Question & Slider
  const [questionText, setQuestionText] = useState(data.question?.question_text || data.title || '');
  const [helpText, setHelpText] = useState(data.question?.help_text || '');
  const [inputType, setInputType] = useState(data.input_type || data.question?.question_type || 'single_choice');
  const [variableKey, setVariableKey] = useState(data.variable_key || 'Q1');
  const [options, setOptions] = useState<any[]>(data.options || data.question?.options || []);
  const [sliderConfig, setSliderConfig] = useState(data.slider_config || {
    min_value: 1,
    max_value: 100,
    step_value: 1,
    default_value: 10,
    unit_label: 'Units',
    unit_prefix: '₹',
  });

  // Form Node
  const [formHeading, setFormHeading] = useState(data.form?.heading || data.title || 'Lead Capture Form');
  const [formSubheading, setFormSubheading] = useState(data.form?.subheading || 'Please fill out the form to proceed.');
  const [formButtonText, setFormButtonText] = useState(data.form?.button_text || 'Submit Details');
  const [formSubmitAction, setFormSubmitAction] = useState<'redirect' | 'message'>(data.form?.submit_action || 'redirect');
  const [formSuccessHeading, setFormSuccessHeading] = useState(data.form?.success_heading || 'Thank You!');
  const [formSuccessMessage, setFormSuccessMessage] = useState(data.form?.success_message || 'Your submission has been received.');
  const [formFields, setFormFields] = useState<any[]>(data.form?.fields || data.fields || [
    { id: 1, label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Dr. Sarah Chen', is_required: 1 },
    { id: 2, label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'sarah.chen@apexhealth.org', is_required: 1 },
  ]);

  // Destination Node
  const [destName, setDestName] = useState(data.destination?.name || data.title || 'Outcome Page');
  const [destMode, setDestMode] = useState<'redirect' | 'direct_connect'>(
    data.destination_mode || (data.quick_connect?.enabled ? 'direct_connect' : 'redirect')
  );
  const [destUrl, setDestUrl] = useState(data.destination?.url || '');
  const [calcEnabled, setCalcEnabled] = useState(Boolean(data.calculator?.enabled ?? (funnelMode === 'calculator')));
  const [formulaExpression, setFormulaExpression] = useState(data.calculator?.formula_expression || '@Q1 * 100');
  const [resultLabel, setResultLabel] = useState(data.calculator?.result_label || 'Your Estimated Price');
  const [resultPrefix, setResultPrefix] = useState(data.calculator?.result_prefix || '₹');
  const [resultSuffix, setResultSuffix] = useState(data.calculator?.result_suffix || '');
  const [decimalPlaces, setDecimalPlaces] = useState(data.calculator?.decimal_places ?? 0);

  // Quick Connect
  const [qcActionType, setQcActionType] = useState<'both' | 'whatsapp' | 'call'>(data.quick_connect?.action_type || 'both');
  const [qcPhone, setQcPhone] = useState(data.quick_connect?.phone_number || '');
  const [qcCallBtn, setQcCallBtn] = useState(data.quick_connect?.call_button_text || 'Call Specialist Now');
  const [qcWhatsApp, setQcWhatsApp] = useState(data.quick_connect?.whatsapp_number || '');
  const [qcWhatsAppBtn, setQcWhatsAppBtn] = useState(data.quick_connect?.whatsapp_button_text || 'Chat on WhatsApp');
  const [qcWhatsAppMsg, setQcWhatsAppMsg] = useState(
    data.quick_connect?.whatsapp_message || 'Hi! I just completed the assessment and got my quote: {{formatted_quote}}.'
  );

  // Video Gate Node
  const [videoUrl, setVideoUrl] = useState(data.config?.video_url || data.video_gate?.video_url || '');
  const [gateTimestamp, setGateTimestamp] = useState(data.config?.gate_timestamp_seconds || data.video_gate?.gate_timestamp_seconds || 45);
  const [gateHeadline, setGateHeadline] = useState(data.config?.gate_headline || 'Watch presentation to continue');
  const [allowSkipGate, setAllowSkipGate] = useState(Boolean(data.config?.allow_skip_gate));
  const [onVideoEnd, setOnVideoEnd] = useState(data.config?.on_video_end || 'auto_advance');

  // Loader Node
  const [loaderHeadline, setLoaderHeadline] = useState(data.loader?.headline || data.title || 'Analyzing your responses...');
  const [loaderSubtext, setLoaderSubtext] = useState(data.loader?.subtext || 'Matching the best options based on your answers...');
  const [loaderDuration, setLoaderDuration] = useState(Number(data.loader?.duration_seconds ?? 2.5));

  // Booking Node
  const [bookingHeadline, setBookingHeadline] = useState(data.booking?.headline || data.title || 'Schedule Consultation');
  const [bookingProvider, setBookingProvider] = useState<'calendly' | 'cal_com' | 'custom'>(data.booking?.provider || 'calendly');
  const [bookingUrl, setBookingUrl] = useState(data.booking?.booking_url || '');
  const [bookingEmbedMode, setBookingEmbedMode] = useState<'inline' | 'popup'>(data.booking?.embed_mode || 'inline');
  const [bookingPrefill, setBookingPrefill] = useState(Boolean(data.booking?.prefill_contact ?? true));

  useEffect(() => {
    setTitle(data.title || '');
    setQuestionText(data.question?.question_text || data.title || '');
    setHelpText(data.question?.help_text || '');
    setInputType(data.input_type || data.question?.question_type || 'single_choice');
    setVariableKey(data.variable_key || 'Q1');
    setOptions(data.options || data.question?.options || []);
    setSliderConfig(data.slider_config || {
      min_value: 1,
      max_value: 100,
      step_value: 1,
      default_value: 10,
      unit_label: 'Units',
      unit_prefix: '₹',
    });

    setFormHeading(data.form?.heading || data.title || 'Lead Capture Form');
    setFormSubheading(data.form?.subheading || 'Please fill out the form to proceed.');
    setFormButtonText(data.form?.button_text || 'Submit Details');
    setFormSubmitAction(data.form?.submit_action || 'redirect');
    setFormSuccessHeading(data.form?.success_heading || 'Thank You!');
    setFormSuccessMessage(data.form?.success_message || 'Your submission has been received.');
    setFormFields(data.form?.fields || data.fields || [
      { id: 1, label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Dr. Sarah Chen', is_required: 1 },
      { id: 2, label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'sarah.chen@apexhealth.org', is_required: 1 },
    ]);

    setDestName(data.destination?.name || data.title || 'Outcome Page');
    setDestMode(data.destination_mode || (data.quick_connect?.enabled ? 'direct_connect' : 'redirect'));
    setDestUrl(data.destination?.url || '');
    setCalcEnabled(Boolean(data.calculator?.enabled ?? (funnelMode === 'calculator')));
    setFormulaExpression(data.calculator?.formula_expression || '@Q1 * 100');
    setResultLabel(data.calculator?.result_label || 'Your Estimated Price');
    setResultPrefix(data.calculator?.result_prefix || '₹');
    setResultSuffix(data.calculator?.result_suffix || '');
    setDecimalPlaces(data.calculator?.decimal_places ?? 0);

    setQcActionType(data.quick_connect?.action_type || 'both');
    setQcPhone(data.quick_connect?.phone_number || '');
    setQcCallBtn(data.quick_connect?.call_button_text || 'Call Specialist Now');
    setQcWhatsApp(data.quick_connect?.whatsapp_number || '');
    setQcWhatsAppBtn(data.quick_connect?.whatsapp_button_text || 'Chat on WhatsApp');
    setQcWhatsAppMsg(
      data.quick_connect?.whatsapp_message || 'Hi! I just completed the assessment and got my quote: {{formatted_quote}}.'
    );

    setVideoUrl(data.config?.video_url || data.video_gate?.video_url || '');
    setGateTimestamp(data.config?.gate_timestamp_seconds || data.video_gate?.gate_timestamp_seconds || 45);
    setGateHeadline(data.config?.gate_headline || 'Watch presentation to continue');
    setAllowSkipGate(Boolean(data.config?.allow_skip_gate));
    setOnVideoEnd(data.config?.on_video_end || 'auto_advance');

    setLoaderHeadline(data.loader?.headline || data.title || 'Analyzing your responses...');
    setLoaderSubtext(data.loader?.subtext || 'Matching the best options based on your answers...');
    setLoaderDuration(Number(data.loader?.duration_seconds ?? 2.5));

    setBookingHeadline(data.booking?.headline || data.title || 'Schedule Consultation');
    setBookingProvider(data.booking?.provider || 'calendly');
    setBookingUrl(data.booking?.booking_url || '');
    setBookingEmbedMode(data.booking?.embed_mode || 'inline');
    setBookingPrefill(Boolean(data.booking?.prefill_contact ?? true));
  }, [node.id]);

  const commit = (overrides: Partial<any> = {}) => {
    const updatedData = {
      ...data,
      title: overrides.title ?? title,
      input_type: overrides.inputType ?? inputType,
      variable_key: overrides.variableKey ?? variableKey,
      slider_config: overrides.sliderConfig ?? sliderConfig,
      question: {
        ...(data.question || {}),
        question_text: overrides.questionText ?? questionText,
        help_text: overrides.helpText ?? helpText,
        question_type: overrides.inputType ?? inputType,
        options: overrides.options ?? options,
      },
      options: overrides.options ?? options,
      form: {
        ...(data.form || {}),
        heading: overrides.formHeading ?? formHeading,
        subheading: overrides.formSubheading ?? formSubheading,
        button_text: overrides.formButtonText ?? formButtonText,
        submit_action: overrides.formSubmitAction ?? formSubmitAction,
        success_heading: overrides.formSuccessHeading ?? formSuccessHeading,
        success_message: overrides.formSuccessMessage ?? formSuccessMessage,
        fields: overrides.formFields ?? formFields,
      },
      fields: overrides.formFields ?? formFields,
      destination_mode: overrides.destMode ?? destMode,
      destination: {
        ...(data.destination || {}),
        name: overrides.destName ?? destName,
        url: overrides.destUrl ?? destUrl,
      },
      calculator: {
        ...(data.calculator || {}),
        enabled: overrides.calcEnabled ?? calcEnabled,
        formula_expression: overrides.formulaExpression ?? formulaExpression,
        result_label: overrides.resultLabel ?? resultLabel,
        result_prefix: overrides.resultPrefix ?? resultPrefix,
        result_suffix: overrides.resultSuffix ?? resultSuffix,
        decimal_places: overrides.decimalPlaces ?? decimalPlaces,
      },
      quick_connect: {
        ...(data.quick_connect || {}),
        enabled: (overrides.destMode ?? destMode) === 'direct_connect',
        action_type: overrides.qcActionType ?? qcActionType,
        phone_number: overrides.qcPhone ?? qcPhone,
        call_button_text: overrides.qcCallBtn ?? qcCallBtn,
        whatsapp_number: overrides.qcWhatsApp ?? qcWhatsApp,
        whatsapp_button_text: overrides.qcWhatsAppBtn ?? qcWhatsAppBtn,
        whatsapp_message: overrides.qcWhatsAppMsg ?? qcWhatsAppMsg,
      },
      config: {
        ...(data.config || {}),
        video_url: overrides.videoUrl ?? videoUrl,
        gate_timestamp_seconds: overrides.gateTimestamp ?? gateTimestamp,
        gate_headline: overrides.gateHeadline ?? gateHeadline,
        allow_skip_gate: overrides.allowSkipGate ?? allowSkipGate,
        on_video_end: overrides.onVideoEnd ?? onVideoEnd,
      },
      loader: {
        ...(data.loader || {}),
        headline: overrides.loaderHeadline ?? loaderHeadline,
        subtext: overrides.loaderSubtext ?? loaderSubtext,
        duration_seconds: overrides.loaderDuration ?? loaderDuration,
      },
      booking: {
        ...(data.booking || {}),
        headline: overrides.bookingHeadline ?? bookingHeadline,
        provider: overrides.bookingProvider ?? bookingProvider,
        booking_url: overrides.bookingUrl ?? bookingUrl,
        embed_mode: overrides.bookingEmbedMode ?? bookingEmbedMode,
        prefill_contact: overrides.bookingPrefill ?? bookingPrefill,
      },
    };

    onUpdate({
      ...node,
      data: updatedData,
    });
  };

  // Option Handlers
  const addOption = () => {
    const newOpt = {
      id: Date.now(),
      option_key: `opt_${Date.now()}`,
      label: `Option ${options.length + 1}`,
      value: `${options.length + 1 * 10}`,
    };
    const newOptions = [...options, newOpt];
    setOptions(newOptions);
    commit({ options: newOptions });
  };

  const updateOption = (idx: number, field: string, val: any) => {
    const newOptions = [...options];
    newOptions[idx] = { ...newOptions[idx], [field]: val };
    setOptions(newOptions);
    commit({ options: newOptions });
  };

  const removeOption = (idx: number) => {
    const newOptions = options.filter((_, i) => i !== idx);
    setOptions(newOptions);
    commit({ options: newOptions });
  };

  // Form Field Preset Handlers
  const addPresetField = (type: string) => {
    const presets: Record<string, any> = {
      name: { label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Dr. Sarah Chen', is_required: 1 },
      email: { label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'sarah.chen@apexhealth.org', is_required: 1 },
      phone: { label: 'Phone Number', field_key: 'phone', field_type: 'phone', placeholder: '+1 (555) 234-5678', is_required: 0 },
      company: { label: 'Organization', field_key: 'company', field_type: 'text', placeholder: 'Apex Health Studio', is_required: 0 },
      consent: { label: 'I agree to the Terms & Privacy Policy', field_key: 'consent', field_type: 'consent', placeholder: '', is_required: 1 },
    };

    const newField = {
      id: Date.now(),
      ...(presets[type] || { label: 'Custom Field', field_key: `field_${Date.now()}`, field_type: 'text', placeholder: '', is_required: 0 }),
    };

    const updated = [...formFields, newField];
    setFormFields(updated);
    commit({ formFields: updated });
  };

  const moveField = (idx: number, direction: 'up' | 'down') => {
    if ((direction === 'up' && idx === 0) || (direction === 'down' && idx === formFields.length - 1)) return;
    const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
    const updated = [...formFields];
    const temp = updated[idx];
    updated[idx] = updated[targetIdx];
    updated[targetIdx] = temp;
    setFormFields(updated);
    commit({ formFields: updated });
  };

  const removeField = (id: number) => {
    const updated = formFields.filter((f) => f.id !== id);
    setFormFields(updated);
    commit({ formFields: updated });
  };

  // Question Nodes for Variable Tokens
  const questionNodes = allNodes.filter((n) => n.type === 'question');

  const insertFormulaToken = (token: string) => {
    const newExpr = `${formulaExpression} ${token}`.trim();
    setFormulaExpression(newExpr);
    commit({ formulaExpression: newExpr });
  };

  const insertWhatsAppTag = (tag: string) => {
    const newMsg = `${qcWhatsAppMsg} ${tag}`.trim();
    setQcWhatsAppMsg(newMsg);
    commit({ qcWhatsAppMsg: newMsg });
  };

  return (
    <aside className="w-88 bg-white border-l border-[#e8eaec] flex flex-col h-full z-20 overflow-hidden">
      {/* Drawer Header */}
      <div className="p-3.5 border-b border-[#f0f1f2] flex items-center justify-between bg-white">
        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold text-[#131416]">
            {nodeType.replace('_', ' ')} Inspector
          </span>
          <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-[#ebedee] text-[#131416] font-medium">
            #{node.id}
          </span>
        </div>
        <button
          onClick={onClose}
          className="p-1 rounded-[6px] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition-colors"
        >
          <X className="w-4 h-4" />
        </button>
      </div>

      {/* Drawer Scrollable Body */}
      <div className="flex-1 p-4 overflow-y-auto space-y-4 text-xs">
        {/* Step Title / Identifier */}
        <div>
          <label className="block text-xs font-medium text-[#131416] mb-1">
            Step Title / Identifier
          </label>
          <input
            type="text"
            value={title}
            onChange={(e) => {
              setTitle(e.target.value);
              commit({ title: e.target.value });
            }}
            className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
          />
        </div>

        {/* ========================================================
            QUESTION NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'question' && (
          <div className="space-y-4">
            {/* Variable Key (Calculator Mode) */}
            {(funnelMode === 'calculator' || inputType === 'slider') && (
              <div className="p-2.5 rounded-[8px] bg-[#ebf4f6] border border-[#d0e4e8] flex items-center justify-between">
                <div>
                  <span className="block text-[11px] font-semibold text-[#1b6c80]">
                    Formula Variable
                  </span>
                  <span className="text-[11px] text-[#1b6c80]/80">
                    Reference in formula: <strong>@{variableKey}</strong>
                  </span>
                </div>
                <div className="flex items-center gap-1 bg-white px-2 py-1 rounded-[6px] border border-[#d0e4e8]">
                  <span className="text-xs font-bold text-[#1b6c80]">@</span>
                  <input
                    type="text"
                    value={variableKey}
                    onChange={(e) => {
                      const val = e.target.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
                      setVariableKey(val);
                      commit({ variableKey: val });
                    }}
                    className="w-14 text-xs font-mono font-medium text-[#131416] uppercase outline-none"
                  />
                </div>
              </div>
            )}

            {/* Input Type Selector */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Question Input Type
              </label>
              <select
                value={inputType}
                onChange={(e) => {
                  setInputType(e.target.value);
                  commit({ inputType: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-medium bg-white text-[#131416]"
              >
                <option value="single_choice">Single Choice (select one option)</option>
                <option value="multiple_choice">Multiple Choice (select many options)</option>
                <option value="slider">Number / Range Slider (visitor inputs number)</option>
              </select>
            </div>

            {/* Question Text */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Question Headline *
              </label>
              <textarea
                rows={2}
                value={questionText}
                onChange={(e) => {
                  setQuestionText(e.target.value);
                  commit({ questionText: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
              />
            </div>

            {/* Help Subtitle */}
            <div>
              <label className="block text-[11px] font-bold uppercase text-[#171A21] tracking-wider mb-1">
                Help / Subtitle Note
              </label>
              <input
                type="text"
                value={helpText}
                onChange={(e) => {
                  setHelpText(e.target.value);
                  commit({ helpText: e.target.value });
                }}
                placeholder="Optional explanation for visitors"
                className="w-full px-3 py-1.5 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
              />
            </div>

            {/* If Slider Mode */}
            {inputType === 'slider' ? (
              <div className="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/30 space-y-3">
                <span className="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                  <Sliders className="w-3.5 h-3.5" />
                  <span>Slider Configuration</span>
                </span>

                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Min Value</label>
                    <input
                      type="number"
                      value={sliderConfig.min_value}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, min_value: Number(e.target.value) };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] font-mono text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Max Value</label>
                    <input
                      type="number"
                      value={sliderConfig.max_value}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, max_value: Number(e.target.value) };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] font-mono text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Step Increment</label>
                    <input
                      type="number"
                      value={sliderConfig.step_value}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, step_value: Number(e.target.value) };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] font-mono text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Default Value</label>
                    <input
                      type="number"
                      value={sliderConfig.default_value}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, default_value: Number(e.target.value) };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] font-mono text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Unit Label (Suffix)</label>
                    <input
                      type="text"
                      value={sliderConfig.unit_label}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, unit_label: e.target.value };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      placeholder="Users, Pages, Kg"
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-[#687080] mb-0.5">Currency Prefix</label>
                    <input
                      type="text"
                      value={sliderConfig.unit_prefix}
                      onChange={(e) => {
                        const cfg = { ...sliderConfig, unit_prefix: e.target.value };
                        setSliderConfig(cfg);
                        commit({ sliderConfig: cfg });
                      }}
                      placeholder="₹, Rs"
                      className="w-full px-2 py-1 rounded-lg border border-[#E4E7EC] text-xs"
                    />
                  </div>
                </div>
              </div>
            ) : (
              /* Choice Branching Options */
              <div className="space-y-3 pt-2 border-t border-[#f0f1f2]">
                <div className="flex items-center justify-between">
                  <span className="font-semibold text-xs text-[#131416]">Answer Options ({options.length})</span>
                  <button
                    type="button"
                    onClick={addOption}
                    className="text-xs text-[#1b6c80] font-medium hover:underline flex items-center gap-1"
                  >
                    <Plus className="w-3.5 h-3.5" /> Add Option
                  </button>
                </div>

                <div className="space-y-2">
                  {options.map((opt, idx) => (
                    <div key={opt.id || idx} className="p-2.5 rounded-[8px] border border-[#e8eaec] bg-[#f4f6f8] space-y-2">
                      <div className="flex items-center justify-between text-[11px] font-medium text-[#5d5f60]">
                        <span>Option {idx + 1}</span>
                        {options.length > 1 && (
                          <button
                            type="button"
                            onClick={() => removeOption(idx)}
                            className="text-[#c4242a] hover:underline"
                          >
                            Remove
                          </button>
                        )}
                      </div>

                      <div className="grid grid-cols-12 gap-2">
                        <div className={funnelMode === 'calculator' ? 'col-span-8' : 'col-span-12'}>
                          <input
                            type="text"
                            value={opt.label || ''}
                            onChange={(e) => updateOption(idx, 'label', e.target.value)}
                            placeholder="Option Label"
                            className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                          />
                        </div>

                        {funnelMode === 'calculator' && (
                          <div className="col-span-4">
                            <input
                              type="text"
                              value={opt.value ?? '0'}
                              onChange={(e) => updateOption(idx, 'value', e.target.value)}
                              placeholder="Score"
                              className="w-full px-2 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono font-medium text-[#131416] text-right"
                            />
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* ========================================================
            FORM NODE INSPECTOR (LEAD CAPTURE)
        ======================================================== */}
        {nodeType === 'form' && (
          <div className="space-y-4">
            {/* Header & Copy */}
            <div className="p-3.5 bg-[#f4f6f8] border border-[#e8eaec] rounded-[8px] space-y-3">
              <span className="text-xs font-semibold text-[#131416] block">
                Form Display Text
              </span>
              <div>
                <label className="block text-xs text-[#5d5f60] mb-1">Form Heading</label>
                <input
                  type="text"
                  value={formHeading}
                  onChange={(e) => {
                    setFormHeading(e.target.value);
                    commit({ formHeading: e.target.value });
                  }}
                  className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-medium text-[#131416]"
                />
              </div>

              <div>
                <label className="block text-xs text-[#5d5f60] mb-1">Subheading / Description</label>
                <textarea
                  rows={2}
                  value={formSubheading}
                  onChange={(e) => {
                    setFormSubheading(e.target.value);
                    commit({ formSubheading: e.target.value });
                  }}
                  className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                />
              </div>

              <div>
                <label className="block text-xs text-[#5d5f60] mb-1">Submit Button Text</label>
                <input
                  type="text"
                  value={formButtonText}
                  onChange={(e) => {
                    setFormButtonText(e.target.value);
                    commit({ formButtonText: e.target.value });
                  }}
                  className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                />
              </div>
            </div>

            {/* Post Submission Action */}
            <div className="p-3.5 bg-[#f4f6f8] border border-[#e8eaec] rounded-[8px] space-y-2">
              <span className="text-xs font-semibold text-[#131416] block">
                After Form Submission
              </span>

              <div className="space-y-2">
                <label className="flex items-start gap-2 p-2 bg-white rounded-[6px] border border-[#e8eaec] cursor-pointer">
                  <input
                    type="radio"
                    name="submit_action"
                    value="redirect"
                    checked={formSubmitAction === 'redirect'}
                    onChange={() => {
                      setFormSubmitAction('redirect');
                      commit({ formSubmitAction: 'redirect' });
                    }}
                    className="mt-0.5 accent-[#1b6c80]"
                  />
                  <div>
                    <span className="block font-medium text-xs text-[#131416]">Proceed to Next Step</span>
                    <span className="block text-[11px] text-[#5d5f60]">Navigates to the connected downstream step.</span>
                  </div>
                </label>

                <label className="flex items-start gap-2 p-2 bg-white rounded-[6px] border border-[#e8eaec] cursor-pointer">
                  <input
                    type="radio"
                    name="submit_action"
                    value="message"
                    checked={formSubmitAction === 'message'}
                    onChange={() => {
                      setFormSubmitAction('message');
                      commit({ formSubmitAction: 'message' });
                    }}
                    className="mt-0.5 accent-[#1b6c80]"
                  />
                  <div>
                    <span className="block font-medium text-xs text-[#131416]">Show Confirmation Message</span>
                    <span className="block text-[11px] text-[#5d5f60]">Acts as terminal step; displays confirmation on page.</span>
                  </div>
                </label>
              </div>
            </div>

            {/* Form Fields Presets & Manager */}
            <div className="space-y-3 pt-2 border-t border-[#f0f1f2]">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-xs text-[#131416]">Form Fields ({formFields.length})</span>
                <button
                  type="button"
                  onClick={() => addPresetField('custom')}
                  className="text-xs text-[#1b6c80] font-medium hover:underline"
                >
                  + Custom Field
                </button>
              </div>

              {/* Presets */}
              <div className="flex items-center gap-1.5 flex-wrap">
                <span className="text-[11px] text-[#5d5f60]">Presets:</span>
                <button type="button" onClick={() => addPresetField('name')} className="px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] hover:bg-[#ebedee] text-[11px] text-[#131416]">+ Name</button>
                <button type="button" onClick={() => addPresetField('email')} className="px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] hover:bg-[#ebedee] text-[11px] text-[#131416]">+ Email</button>
                <button type="button" onClick={() => addPresetField('phone')} className="px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] hover:bg-[#ebedee] text-[11px] text-[#131416]">+ Phone</button>
                <button type="button" onClick={() => addPresetField('company')} className="px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] hover:bg-[#ebedee] text-[11px] text-[#131416]">+ Company</button>
                <button type="button" onClick={() => addPresetField('consent')} className="px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] hover:bg-[#ebedee] text-[11px] text-[#131416]">+ Consent</button>
              </div>

              {/* Fields List */}
              <div className="space-y-2">
                {formFields.map((f, idx) => (
                  <div key={f.id || idx} className="p-3 border border-[#e8eaec] rounded-[8px] bg-[#f4f6f8] space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-medium text-[#131416]">#{idx + 1} {f.label}</span>
                      <div className="flex items-center gap-1">
                        <button type="button" onClick={() => moveField(idx, 'up')} disabled={idx === 0} className="p-1 text-[#5d5f60] hover:text-[#131416] disabled:opacity-30">
                          <ChevronUp className="w-3.5 h-3.5" />
                        </button>
                        <button type="button" onClick={() => moveField(idx, 'down')} disabled={idx === formFields.length - 1} className="p-1 text-[#5d5f60] hover:text-[#131416] disabled:opacity-30">
                          <ChevronDown className="w-3.5 h-3.5" />
                        </button>
                        <button type="button" onClick={() => removeField(f.id)} className="p-1 text-[#c4242a] hover:underline ml-1" title="Remove field">
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-xs">
                      <div>
                        <label className="block text-[11px] text-[#5d5f60] mb-0.5">Label</label>
                        <input
                          type="text"
                          value={f.label || ''}
                          onChange={(e) => {
                            const updated = [...formFields];
                            updated[idx].label = e.target.value;
                            setFormFields(updated);
                            commit({ formFields: updated });
                          }}
                          className="w-full px-2 py-1 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                        />
                      </div>

                      <div>
                        <label className="block text-[11px] text-[#5d5f60] mb-0.5">Type</label>
                        <select
                          value={f.field_type || 'text'}
                          onChange={(e) => {
                            const updated = [...formFields];
                            updated[idx].field_type = e.target.value;
                            setFormFields(updated);
                            commit({ formFields: updated });
                          }}
                          className="w-full px-2 py-1 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                        >
                          <option value="text">Text Input</option>
                          <option value="email">Email Address</option>
                          <option value="phone">Phone Number</option>
                          <option value="number">Number</option>
                          <option value="dropdown">Dropdown</option>
                          <option value="consent">Consent Checkbox</option>
                        </select>
                      </div>
                    </div>

                    <div className="flex items-center justify-between text-[11px] pt-1">
                      <label className="flex items-center gap-1.5 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={Boolean(f.is_required)}
                          onChange={(e) => {
                            const updated = [...formFields];
                            updated[idx].is_required = e.target.checked ? 1 : 0;
                            setFormFields(updated);
                            commit({ formFields: updated });
                          }}
                          className="w-3.5 h-3.5 rounded text-[#1b6c80] accent-[#1b6c80]"
                        />
                        <span className="text-[#131416]">Required field</span>
                      </label>
                      <span className="font-mono text-[#5d5f60]">key: {f.field_key}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* ========================================================
            DESTINATION NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'destination' && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Outcome Title
              </label>
              <input
                type="text"
                value={destName}
                onChange={(e) => {
                  setDestName(e.target.value);
                  commit({ destName: e.target.value });
                }}
                className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs font-semibold text-[#131416]"
              />
            </div>

            {/* Destination Action Type Tabs */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1.5">
                Destination Action Type *
              </label>
              <div className="grid grid-cols-2 p-1 rounded-[8px] bg-[#f4f6f8] border border-[#e8eaec] text-xs gap-1">
                <button
                  type="button"
                  onClick={() => {
                    setDestMode('redirect');
                    commit({ destMode: 'redirect' });
                  }}
                  className={`py-1.5 px-3 rounded-[6px] font-medium flex items-center justify-center gap-1.5 transition-colors ${
                    destMode === 'redirect' ? 'bg-[#131416] text-white' : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <ExternalLink className="w-3.5 h-3.5" />
                  <span>1. Redirect Link</span>
                </button>

                <button
                  type="button"
                  onClick={() => {
                    setDestMode('direct_connect');
                    commit({ destMode: 'direct_connect' });
                  }}
                  className={`py-1.5 px-3 rounded-[6px] font-medium flex items-center justify-center gap-1.5 transition-colors ${
                    destMode === 'direct_connect' ? 'bg-[#131416] text-white' : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <MessageCircle className="w-3.5 h-3.5" />
                  <span>2. WhatsApp / Call</span>
                </button>
              </div>
            </div>

            {destMode === 'redirect' ? (
              <div className="p-3.5 rounded-[8px] border border-[#e8eaec] bg-[#f4f6f8] space-y-2">
                <label className="block text-xs font-medium text-[#131416] mb-1">
                  Landing Page Redirect URL
                </label>
                <input
                  type="url"
                  value={destUrl}
                  onChange={(e) => {
                    setDestUrl(e.target.value);
                    commit({ destUrl: e.target.value });
                  }}
                  placeholder="https://apexhealth.org/booking"
                  className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] bg-white font-mono text-xs text-[#131416]"
                />
                <span className="text-[11px] text-[#5d5f60] block">
                  Supports dynamic tokens: <code className="text-[#1b6c80] font-mono">{"{{formatted_quote}}"}</code>
                </span>
              </div>
            ) : (
              /* Direct Connect WhatsApp / Call Settings */
              <div className="p-3.5 rounded-[8px] border border-[#d0e4e8] bg-[#ebf4f6]/40 space-y-3">
                <span className="text-xs font-semibold text-[#131416] block">Direct Connect</span>

                <div className="grid grid-cols-3 p-1 rounded-[6px] bg-[#f4f6f8] border border-[#e8eaec] text-xs">
                  <button
                    type="button"
                    onClick={() => {
                      setQcActionType('both');
                      commit({ qcActionType: 'both' });
                    }}
                    className={`py-1 rounded-[4px] font-medium transition-colors ${qcActionType === 'both' ? 'bg-[#131416] text-white' : 'text-[#5d5f60]'}`}
                  >
                    Both
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setQcActionType('whatsapp');
                      commit({ qcActionType: 'whatsapp' });
                    }}
                    className={`py-1 rounded-[4px] font-medium transition-colors ${qcActionType === 'whatsapp' ? 'bg-[#131416] text-white' : 'text-[#5d5f60]'}`}
                  >
                    WhatsApp
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setQcActionType('call');
                      commit({ qcActionType: 'call' });
                    }}
                    className={`py-1 rounded-[4px] font-medium transition-colors ${qcActionType === 'call' ? 'bg-[#131416] text-white' : 'text-[#5d5f60]'}`}
                  >
                    Call
                  </button>
                </div>

                {(qcActionType === 'both' || qcActionType === 'whatsapp') && (
                  <div className="space-y-2 pt-2 border-t border-[#e8eaec]">
                    <label className="block text-xs font-medium text-[#131416]">WhatsApp Phone (with Country Code)</label>
                    <input
                      type="tel"
                      value={qcWhatsApp}
                      onChange={(e) => {
                        setQcWhatsApp(e.target.value);
                        commit({ qcWhatsApp: e.target.value });
                      }}
                      placeholder="+15552345678"
                      className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs font-mono text-[#131416]"
                    />

                    <label className="block text-xs font-medium text-[#131416] mt-2">Pre-filled Chat Message</label>
                    <textarea
                      rows={2}
                      value={qcWhatsAppMsg}
                      onChange={(e) => {
                        setQcWhatsAppMsg(e.target.value);
                        commit({ qcWhatsAppMsg: e.target.value });
                      }}
                      className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
                    />

                    <div className="flex flex-wrap gap-1">
                      <button
                        type="button"
                        onClick={() => insertWhatsAppTag('{{formatted_quote}}')}
                        className="px-2 py-0.5 rounded-[4px] bg-[#ebedee] text-[#131416] text-[11px] font-mono"
                      >
                        + {"{{formatted_quote}}"}
                      </button>
                      <button
                        type="button"
                        onClick={() => insertWhatsAppTag('{{funnel_name}}')}
                        className="px-2 py-0.5 rounded-[4px] bg-[#ebedee] text-[#131416] text-[11px] font-mono"
                      >
                        + {"{{funnel_name}}"}
                      </button>
                    </div>
                  </div>
                )}

                {(qcActionType === 'both' || qcActionType === 'call') && (
                  <div className="space-y-2 pt-2 border-t border-[#e8eaec]">
                    <label className="block text-xs font-medium text-[#131416]">Call Phone Number</label>
                    <input
                      type="tel"
                      value={qcPhone}
                      onChange={(e) => {
                        setQcPhone(e.target.value);
                        commit({ qcPhone: e.target.value });
                      }}
                      placeholder="+1 (555) 234-5678"
                      className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs font-mono text-[#131416]"
                    />
                  </div>
                )}
              </div>
            )}

            {/* Price Quote / Calculator Formula Engine */}
            <div className="p-3.5 rounded-[8px] border border-[#e8eaec] bg-[#f4f6f8] space-y-3">
              <label className="flex items-center justify-between cursor-pointer">
                <span className="font-semibold text-xs text-[#131416] flex items-center gap-1.5">
                  <Calculator className="w-3.5 h-3.5 text-[#1b6c80]" />
                  <span>Calculator & Quote Engine</span>
                </span>
                <input
                  type="checkbox"
                  checked={calcEnabled}
                  onChange={(e) => {
                    setCalcEnabled(e.target.checked);
                    commit({ calcEnabled: e.target.checked });
                  }}
                  className="w-4 h-4 rounded text-[#1b6c80] accent-[#1b6c80]"
                />
              </label>

              {calcEnabled && (
                <div className="space-y-3 pt-2 border-t border-[#e8eaec]">
                  <div>
                    <label className="block text-xs font-medium text-[#131416] mb-1">
                      Formula Expression
                    </label>
                    <textarea
                      rows={2}
                      value={formulaExpression}
                      onChange={(e) => {
                        setFormulaExpression(e.target.value);
                        commit({ formulaExpression: e.target.value });
                      }}
                      placeholder="e.g. (@Q1 * 1200) + @Q2"
                      className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] font-mono text-xs font-medium text-[#131416]"
                    />
                  </div>

                  {/* Clickable Variable Pills */}
                  <div>
                    <span className="block text-[11px] font-medium text-[#5d5f60] mb-1">Insert variable:</span>
                    <div className="flex flex-wrap gap-1.5">
                      {questionNodes.map((qn) => (
                        <button
                          key={qn.id}
                          type="button"
                          onClick={() => insertFormulaToken(`@${qn.data.variable_key || 'Q1'}`)}
                          className="px-2 py-0.5 rounded-[4px] bg-white border border-[#d0e4e8] text-[#1b6c80] font-mono text-[11px] hover:bg-[#ebf4f6]"
                        >
                          @{qn.data.variable_key || 'Q1'} ({qn.data.title || 'Q'})
                        </button>
                      ))}
                    </div>

                    {/* Math Operator Chips */}
                    <div className="flex items-center gap-1 mt-2">
                      {['+', '-', '*', '/', '(', ')'].map((op) => (
                        <button
                          key={op}
                          type="button"
                          onClick={() => insertFormulaToken(op)}
                          className="w-6 h-6 rounded-[4px] bg-[#ebedee] text-[#131416] font-mono text-xs flex items-center justify-center hover:bg-[#cdced1]"
                        >
                          {op}
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Prefix, Suffix, Label */}
                  <div className="grid grid-cols-2 gap-2 text-xs">
                    <div>
                      <label className="block text-[11px] text-[#5d5f60] mb-0.5">Currency Prefix</label>
                      <input
                        type="text"
                        value={resultPrefix}
                        onChange={(e) => {
                          setResultPrefix(e.target.value);
                          commit({ resultPrefix: e.target.value });
                        }}
                        className="w-full px-2 py-1 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] text-[#5d5f60] mb-0.5">Unit Suffix</label>
                      <input
                        type="text"
                        value={resultSuffix}
                        onChange={(e) => {
                          setResultSuffix(e.target.value);
                          commit({ resultSuffix: e.target.value });
                        }}
                        placeholder="/mo, Total"
                        className="w-full px-2 py-1 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}

        {/* ========================================================
            VIDEO GATE NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'video_gate' && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Video URL (YouTube or Vimeo) *
              </label>
              <input
                type="url"
                value={videoUrl}
                onChange={(e) => {
                  setVideoUrl(e.target.value);
                  commit({ videoUrl: e.target.value });
                }}
                placeholder="https://www.youtube.com/watch?v=..."
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Gate Pause Time (Seconds): {gateTimestamp}s
              </label>
              <input
                type="range"
                min={5}
                max={300}
                step={5}
                value={gateTimestamp}
                onChange={(e) => {
                  const val = Number(e.target.value);
                  setGateTimestamp(val);
                  commit({ gateTimestamp: val });
                }}
                className="w-full accent-[#1b6c80]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Gate Turnstile Headline
              </label>
              <input
                type="text"
                value={gateHeadline}
                onChange={(e) => {
                  setGateHeadline(e.target.value);
                  commit({ gateHeadline: e.target.value });
                }}
                className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
              />
            </div>

            <div className="pt-2">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={allowSkipGate}
                  onChange={(e) => {
                    setAllowSkipGate(e.target.checked);
                    commit({ allowSkipGate: e.target.checked });
                  }}
                  className="w-4 h-4 rounded text-[#1b6c80] accent-[#1b6c80]"
                />
                <span className="text-xs font-medium text-[#131416]">Allow visitor to skip gate</span>
              </label>
            </div>
          </div>
        )}

        {/* ========================================================
            LOADER NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'loader' && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Progress Headline *
              </label>
              <input
                type="text"
                value={loaderHeadline}
                onChange={(e) => {
                  setLoaderHeadline(e.target.value);
                  commit({ loaderHeadline: e.target.value, title: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Secondary Subtext
              </label>
              <input
                type="text"
                value={loaderSubtext}
                onChange={(e) => {
                  setLoaderSubtext(e.target.value);
                  commit({ loaderSubtext: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Auto-Advance Duration: {loaderDuration}s
              </label>
              <input
                type="range"
                min={1}
                max={10}
                step={0.5}
                value={loaderDuration}
                onChange={(e) => {
                  const val = Number(e.target.value);
                  setLoaderDuration(val);
                  commit({ loaderDuration: val });
                }}
                className="w-full accent-[#1b6c80]"
              />
              <span className="text-[11px] text-[#5d5f60] block mt-1">
                Visitor will see progress bar for {loaderDuration} seconds before auto-advancing.
              </span>
            </div>
          </div>
        )}

        {/* ========================================================
            BOOKING NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'booking' && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Booking Headline *
              </label>
              <input
                type="text"
                value={bookingHeadline}
                onChange={(e) => {
                  setBookingHeadline(e.target.value);
                  commit({ bookingHeadline: e.target.value, title: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Calendar Provider
              </label>
              <select
                value={bookingProvider}
                onChange={(e) => {
                  setBookingProvider(e.target.value as any);
                  commit({ bookingProvider: e.target.value });
                }}
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-medium bg-white text-[#131416]"
              >
                <option value="calendly">Calendly (calendly.com/...)</option>
                <option value="cal_com">Cal.com (cal.com/...)</option>
                <option value="custom">Custom Embed Link</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Booking Link / Embed URL *
              </label>
              <input
                type="url"
                value={bookingUrl}
                onChange={(e) => {
                  setBookingUrl(e.target.value);
                  commit({ bookingUrl: e.target.value });
                }}
                placeholder="https://calendly.com/practice/30min"
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Embed Style
              </label>
              <div className="grid grid-cols-2 gap-2 text-xs">
                <button
                  type="button"
                  onClick={() => {
                    setBookingEmbedMode('inline');
                    commit({ bookingEmbedMode: 'inline' });
                  }}
                  className={`p-2 rounded-[6px] border text-center font-medium transition-colors ${
                    bookingEmbedMode === 'inline'
                      ? 'border-[#1b6c80] bg-[#ebf4f6] text-[#1b6c80]'
                      : 'border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8]'
                  }`}
                >
                  Inline Calendar
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setBookingEmbedMode('popup');
                    commit({ bookingEmbedMode: 'popup' });
                  }}
                  className={`p-2 rounded-[6px] border text-center font-medium transition-colors ${
                    bookingEmbedMode === 'popup'
                      ? 'border-[#1b6c80] bg-[#ebf4f6] text-[#1b6c80]'
                      : 'border-[#e8eaec] text-[#5d5f60] hover:bg-[#f4f6f8]'
                  }`}
                >
                  Popup Modal
                </button>
              </div>
            </div>

            <div className="pt-2">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={bookingPrefill}
                  onChange={(e) => {
                    setBookingPrefill(e.target.checked);
                    commit({ bookingPrefill: e.target.checked });
                  }}
                  className="w-4 h-4 rounded text-[#1b6c80] accent-[#1b6c80]"
                />
                <span className="text-xs font-medium text-[#131416]">
                  Auto-fill Name & Email from previous form step
                </span>
              </label>
            </div>
          </div>
        )}
      </div>

      {/* Drawer Delete Footer */}
      <div className="p-3.5 border-t border-[#f0f1f2] bg-[#f4f6f8] flex items-center justify-between">
        <button
          type="button"
          onClick={() => onDelete(node.id)}
          className="px-3 py-1.5 rounded-[6px] text-[#c4242a] hover:bg-[#ffedea] text-xs font-medium flex items-center gap-1.5 transition-colors"
        >
          <Trash2 className="w-3.5 h-3.5" />
          <span>Delete Node</span>
        </button>
      </div>
    </aside>
  );
};

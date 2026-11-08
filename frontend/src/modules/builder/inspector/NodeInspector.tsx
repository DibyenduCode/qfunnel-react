import React, { useState, useEffect, useRef } from 'react';
import { useParams } from 'react-router-dom';
import { api, resolveMediaUrl } from '../../../api/client';
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
  Sparkles,
  Image as ImageIcon,
  Upload,
  Loader2
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
  const [formFields, setFormFields] = useState<any[]>(
    data.video_gate?.form_fields ||
    data.config?.video_gate?.form_fields ||
    data.config?.form_fields ||
    data.form?.fields ||
    data.fields || [
      { id: 1, label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Enter your name', is_required: 1 },
      { id: 2, label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'Enter your email', is_required: 1 },
    ]
  );

  // Destination Node
  const [destName, setDestName] = useState(data.destination?.name || data.title || 'Outcome Page');
  const [destMode, setDestMode] = useState<'redirect' | 'direct_connect'>(
    data.destination_mode || (data.quick_connect?.enabled ? 'direct_connect' : 'redirect')
  );
  const [destUrl, setDestUrl] = useState(data.destination?.url || '');
  const [destMediaType, setDestMediaType] = useState<'none' | 'image' | 'video'>(
    data.media?.media_type || data.config?.media?.media_type || 'none'
  );
  const [destImageUrl, setDestImageUrl] = useState(
    data.media?.image_url || data.config?.media?.image_url || ''
  );
  const [destVideoUrl, setDestVideoUrl] = useState(
    data.media?.video_url || data.config?.media?.video_url || ''
  );

  const { id: routeFunnelId } = useParams<{ id: string }>();
  const funnelId = routeFunnelId || data.funnel_id;
  const imageFileInputRef = useRef<HTMLInputElement | null>(null);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [imageUploadError, setImageUploadError] = useState<string | null>(null);

  const handleImageFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      setImageUploadError('File is too large. Max size is 10MB.');
      return;
    }

    try {
      setUploadingImage(true);
      setImageUploadError(null);

      const formData = new FormData();
      formData.append('media_file', file);

      const endpoint = funnelId ? `/api/funnels/${funnelId}/upload-media` : '/api/media/upload';
      const res = await api.post(endpoint, formData);

      const uploadedUrl = res.url || res.relative_url || '';
      if (uploadedUrl) {
        setDestImageUrl(uploadedUrl);
        commit({ destImageUrl: uploadedUrl });
      }
    } catch (err: any) {
      setImageUploadError(err.message || 'Failed to upload image.');
    } finally {
      setUploadingImage(false);
      if (imageFileInputRef.current) {
        imageFileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveImage = () => {
    setDestImageUrl('');
    setImageUploadError(null);
    commit({ destImageUrl: '' });
    if (imageFileInputRef.current) {
      imageFileInputRef.current.value = '';
    }
  };
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
  const [gateTimestamp, setGateTimestamp] = useState<number>(
    Number(data.video_gate?.gate_timestamp_seconds ?? data.config?.gate_timestamp_seconds ?? 45)
  );
  const [gateHeadline, setGateHeadline] = useState(data.config?.gate_headline || 'Watch presentation to continue');
  const [allowSkipGate, setAllowSkipGate] = useState(Boolean(data.config?.allow_skip_gate));
  const [onVideoEnd, setOnVideoEnd] = useState(data.config?.on_video_end || 'auto_advance');

  // Video Gate Node (Interactive Video) Mode & CTA Controls
  const [vgPlaybackMode, setVgPlaybackMode] = useState<'standard' | 'gated'>(
    data.video_gate?.playback_mode || data.config?.video_gate?.playback_mode || (data.config?.playback_mode ?? 'gated')
  );
  const [vgCtaReveal, setVgCtaReveal] = useState<'always' | 'delayed' | 'on_end'>(
    data.video_gate?.cta_reveal || data.config?.video_gate?.cta_reveal || data.config?.cta_reveal || 'always'
  );
  const [vgCtaDelay, setVgCtaDelay] = useState<number>(
    Number(data.video_gate?.cta_delay_seconds ?? data.config?.video_gate?.cta_delay_seconds ?? data.config?.cta_delay_seconds ?? 15)
  );
  const [vgCtaButtonText, setVgCtaButtonText] = useState(
    data.video_gate?.cta_button_text || data.config?.video_gate?.cta_button_text || data.config?.cta_button_text || 'Continue to Next Step'
  );

  // Content Slide Node
  const [contentHeadline, setContentHeadline] = useState(data.content?.headline || data.title || 'Key Insight & Overview');
  const [contentBody, setContentBody] = useState(data.content?.body_text || data.content?.subtext || '');
  const [contentImageUrl, setContentImageUrl] = useState(data.content?.image_url || '');
  const [contentButtonText, setContentButtonText] = useState(data.content?.button_text || 'Continue to Next Step');
  const contentFileInputRef = useRef<HTMLInputElement | null>(null);
  const [uploadingContentImg, setUploadingContentImg] = useState(false);
  const [contentUploadError, setContentUploadError] = useState<string | null>(null);

  const handleContentImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      setContentUploadError('File is too large. Max size is 10MB.');
      return;
    }

    try {
      setUploadingContentImg(true);
      setContentUploadError(null);

      const formData = new FormData();
      formData.append('media_file', file);

      const endpoint = funnelId ? `/api/funnels/${funnelId}/upload-media` : '/api/media/upload';
      const res = await api.post(endpoint, formData);

      const uploadedUrl = res.url || res.relative_url || '';
      if (uploadedUrl) {
        setContentImageUrl(uploadedUrl);
        commit({ contentImageUrl: uploadedUrl });
      }
    } catch (err: any) {
      setContentUploadError(err.message || 'Failed to upload image.');
    } finally {
      setUploadingContentImg(false);
      if (contentFileInputRef.current) {
        contentFileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveContentImage = () => {
    setContentImageUrl('');
    setContentUploadError(null);
    commit({ contentImageUrl: '' });
    if (contentFileInputRef.current) {
      contentFileInputRef.current.value = '';
    }
  };

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

  // Condition Node
  const [condType, setCondType] = useState(data.condition?.condition_type || 'score');
  const [condOperator, setCondOperator] = useState(data.condition?.operator || '>=');
  const [condValue, setCondValue] = useState(data.condition?.value ?? '80');
  const [condVarKey, setCondVarKey] = useState(data.condition?.variable_key || 'Q1');
  const [condTargetNodeId, setCondTargetNodeId] = useState(data.condition?.target_node_id || '');
  const [condMatchLabel, setCondMatchLabel] = useState(data.condition?.match_label || 'Qualified Lead');
  const [condDefaultLabel, setCondDefaultLabel] = useState(data.condition?.default_label || 'Standard Route');

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
    setFormFields(
      data.video_gate?.form_fields ||
      data.config?.video_gate?.form_fields ||
      data.config?.form_fields ||
      data.form?.fields ||
      data.fields || [
        { id: 1, label: 'Full Name', field_key: 'name', field_type: 'text', placeholder: 'Enter your name', is_required: 1 },
        { id: 2, label: 'Email Address', field_key: 'email', field_type: 'email', placeholder: 'Enter your email', is_required: 1 },
      ]
    );

    setDestName(data.destination?.name || data.title || 'Outcome Page');
    setDestMode(data.destination_mode || (data.quick_connect?.enabled ? 'direct_connect' : 'redirect'));
    setDestUrl(data.destination?.url || '');
    setDestMediaType(data.media?.media_type || data.config?.media?.media_type || 'none');
    setDestImageUrl(data.media?.image_url || data.config?.media?.image_url || '');
    setDestVideoUrl(data.media?.video_url || data.config?.media?.video_url || '');
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

    setVideoUrl(data.video_gate?.video_url || data.config?.video_gate?.video_url || data.config?.video_url || '');
    setGateTimestamp(Number(
      data.video_gate?.gate_timestamp_seconds ??
      data.config?.video_gate?.gate_timestamp_seconds ??
      data.config?.gate_timestamp_seconds ??
      45
    ));
    setGateHeadline(
      data.video_gate?.gate_headline ||
      data.config?.video_gate?.gate_headline ||
      data.config?.gate_headline ||
      'Watch presentation to continue'
    );
    setAllowSkipGate(Boolean(data.video_gate?.allow_skip_gate ?? data.config?.video_gate?.allow_skip_gate ?? data.config?.allow_skip_gate));
    setOnVideoEnd(
      data.video_gate?.on_video_end ||
      data.config?.video_gate?.on_video_end ||
      data.config?.on_video_end ||
      'auto_advance'
    );
    setVgPlaybackMode(
      data.video_gate?.playback_mode || data.config?.video_gate?.playback_mode || (data.config?.playback_mode ?? 'gated')
    );
    setVgCtaReveal(
      data.video_gate?.cta_reveal || data.config?.video_gate?.cta_reveal || data.config?.cta_reveal || 'always'
    );
    setVgCtaDelay(
      Number(data.video_gate?.cta_delay_seconds ?? data.config?.video_gate?.cta_delay_seconds ?? data.config?.cta_delay_seconds ?? 15)
    );
    setVgCtaButtonText(
      data.video_gate?.cta_button_text || data.config?.video_gate?.cta_button_text || data.config?.cta_button_text || 'Continue to Next Step'
    );

    setContentHeadline(data.content?.headline || data.title || 'Key Insight & Overview');
    setContentBody(data.content?.body_text || data.content?.subtext || '');
    setContentImageUrl(data.content?.image_url || '');
    setContentButtonText(data.content?.button_text || 'Continue to Next Step');

    setLoaderHeadline(data.loader?.headline || data.title || 'Analyzing your responses...');
    setLoaderSubtext(data.loader?.subtext || 'Matching the best options based on your answers...');
    setLoaderDuration(Number(data.loader?.duration_seconds ?? 2.5));

    setBookingHeadline(data.booking?.headline || data.title || 'Schedule Consultation');
    setBookingProvider(data.booking?.provider || 'calendly');
    setBookingUrl(data.booking?.booking_url || '');
    setBookingEmbedMode(data.booking?.embed_mode || 'inline');
    setBookingPrefill(Boolean(data.booking?.prefill_contact ?? true));

    setCondType(data.condition?.condition_type || data.config?.condition_type || 'score');
    setCondOperator(data.condition?.operator || data.config?.operator || '>=');
    setCondValue(data.condition?.value ?? data.config?.value ?? '80');
    setCondVarKey(data.condition?.variable_key || data.config?.variable_key || 'Q1');
    setCondTargetNodeId(data.condition?.target_node_id || data.config?.target_node_id || '');
    setCondMatchLabel(data.condition?.match_label || data.config?.match_label || 'Qualified Lead');
    setCondDefaultLabel(data.condition?.default_label || data.config?.default_label || 'Standard Route');
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
      media: {
        ...(data.media || {}),
        media_type: overrides.destMediaType ?? destMediaType,
        image_url: overrides.destImageUrl ?? destImageUrl,
        video_url: overrides.destVideoUrl ?? destVideoUrl,
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
      content: {
        ...(data.content || {}),
        headline: overrides.contentHeadline ?? contentHeadline,
        body_text: overrides.contentBody ?? contentBody,
        image_url: overrides.contentImageUrl ?? contentImageUrl,
        button_text: overrides.contentButtonText ?? contentButtonText,
      },
      video_gate: {
        ...(data.video_gate || {}),
        video_url: overrides.videoUrl ?? videoUrl,
        playback_mode: overrides.vgPlaybackMode ?? vgPlaybackMode,
        cta_reveal: overrides.vgCtaReveal ?? vgCtaReveal,
        cta_delay_seconds: Number(overrides.vgCtaDelay ?? vgCtaDelay),
        cta_button_text: overrides.vgCtaButtonText ?? vgCtaButtonText,
        gate_enabled: (overrides.vgPlaybackMode ?? vgPlaybackMode) === 'gated',
        gate_timestamp_seconds: Number(overrides.gateTimestamp ?? gateTimestamp),
        gate_headline: overrides.gateHeadline ?? gateHeadline,
        allow_skip_gate: overrides.allowSkipGate ?? allowSkipGate,
        on_video_end: overrides.onVideoEnd ?? onVideoEnd,
        form_fields: overrides.formFields ?? formFields,
      },
      config: {
        ...(data.config || {}),
        content: {
          ...(data.content || {}),
          headline: overrides.contentHeadline ?? contentHeadline,
          body_text: overrides.contentBody ?? contentBody,
          image_url: overrides.contentImageUrl ?? contentImageUrl,
          button_text: overrides.contentButtonText ?? contentButtonText,
        },
        video_url: overrides.videoUrl ?? videoUrl,
        playback_mode: overrides.vgPlaybackMode ?? vgPlaybackMode,
        cta_reveal: overrides.vgCtaReveal ?? vgCtaReveal,
        cta_delay_seconds: Number(overrides.vgCtaDelay ?? vgCtaDelay),
        cta_button_text: overrides.vgCtaButtonText ?? vgCtaButtonText,
        gate_enabled: (overrides.vgPlaybackMode ?? vgPlaybackMode) === 'gated',
        gate_timestamp_seconds: Number(overrides.gateTimestamp ?? gateTimestamp),
        gate_headline: overrides.gateHeadline ?? gateHeadline,
        allow_skip_gate: overrides.allowSkipGate ?? allowSkipGate,
        on_video_end: overrides.onVideoEnd ?? onVideoEnd,
        form_fields: overrides.formFields ?? formFields,
        video_gate: {
          ...(data.video_gate || {}),
          ...(data.config?.video_gate || {}),
          video_url: overrides.videoUrl ?? videoUrl,
          playback_mode: overrides.vgPlaybackMode ?? vgPlaybackMode,
          cta_reveal: overrides.vgCtaReveal ?? vgCtaReveal,
          cta_delay_seconds: Number(overrides.vgCtaDelay ?? vgCtaDelay),
          cta_button_text: overrides.vgCtaButtonText ?? vgCtaButtonText,
          gate_enabled: (overrides.vgPlaybackMode ?? vgPlaybackMode) === 'gated',
          gate_timestamp_seconds: Number(overrides.gateTimestamp ?? gateTimestamp),
          gate_headline: overrides.gateHeadline ?? gateHeadline,
          allow_skip_gate: overrides.allowSkipGate ?? allowSkipGate,
          on_video_end: overrides.onVideoEnd ?? onVideoEnd,
          form_fields: overrides.formFields ?? formFields,
        },
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
      condition: {
        ...(data.condition || {}),
        condition_type: overrides.condType ?? condType,
        operator: overrides.condOperator ?? condOperator,
        value: overrides.condValue ?? condValue,
        variable_key: overrides.condVarKey ?? condVarKey,
        target_node_id: overrides.condTargetNodeId ?? condTargetNodeId,
        match_label: overrides.condMatchLabel ?? condMatchLabel,
        default_label: overrides.condDefaultLabel ?? condDefaultLabel,
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
    <aside className="w-[380px] min-w-[380px] max-w-[380px] shrink-0 bg-white border border-[#e8eaec] rounded-[16px] shadow-xs flex flex-col h-full z-20 overflow-hidden animate-in fade-in slide-in-from-right-2 duration-150">
      {/* Drawer Header */}
      <div className="p-3.5 border-b border-[#f0f1f2] flex items-center justify-between bg-white shrink-0">
        <div className="flex items-center gap-2 min-w-0">
          <span className="text-xs font-semibold text-[#131416] capitalize truncate">
            {nodeType.replace('_', ' ')} Settings
          </span>
          <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-[#ebedee] text-[#131416] font-medium shrink-0">
            #{node.id}
          </span>
        </div>
        <button
          type="button"
          onClick={onClose}
          className="p-1 rounded-[6px] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f4f6f8] transition-colors shrink-0 cursor-pointer"
          title="Close Settings (Esc)"
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

            {/* Outcome Media Display (Image / Video / Standard) */}
            <div className="p-3.5 rounded-[8px] border border-[#e8eaec] bg-[#f4f6f8] space-y-3">
              <span className="text-xs font-semibold text-[#131416] block">
                Outcome Media Display
              </span>

              {/* Segmented Buttons for Mode */}
              <div className="grid grid-cols-3 p-1 rounded-[6px] bg-white border border-[#e8eaec] text-xs gap-1">
                <button
                  type="button"
                  onClick={() => {
                    setDestMediaType('none');
                    commit({ destMediaType: 'none' });
                  }}
                  className={`py-1.5 px-2 rounded-[4px] font-medium transition-colors text-center ${
                    destMediaType === 'none'
                      ? 'bg-[#131416] text-white shadow-xs'
                      : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  Standard (Icon)
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setDestMediaType('image');
                    commit({ destMediaType: 'image' });
                  }}
                  className={`py-1.5 px-2 rounded-[4px] font-medium transition-colors flex items-center justify-center gap-1 ${
                    destMediaType === 'image'
                      ? 'bg-[#131416] text-white shadow-xs'
                      : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <ImageIcon className="w-3.5 h-3.5" />
                  <span>Image</span>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setDestMediaType('video');
                    commit({ destMediaType: 'video' });
                  }}
                  className={`py-1.5 px-2 rounded-[4px] font-medium transition-colors flex items-center justify-center gap-1 ${
                    destMediaType === 'video'
                      ? 'bg-[#131416] text-white shadow-xs'
                      : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <Video className="w-3.5 h-3.5" />
                  <span>Video</span>
                </button>
              </div>

              {destMediaType === 'none' && (
                <p className="text-[11px] text-[#5d5f60]">
                  Displays the standard green checkmark icon alongside your outcome heading and quote.
                </p>
              )}

              {destMediaType === 'image' && (
                <div className="space-y-2.5 pt-1 border-t border-[#e8eaec]">
                  <div className="flex items-center justify-between">
                    <label className="block text-xs font-medium text-[#131416]">
                      Outcome Image *
                    </label>
                    {destImageUrl && (
                      <button
                        type="button"
                        onClick={handleRemoveImage}
                        className="text-[11px] text-rose-600 hover:text-rose-700 font-medium inline-flex items-center gap-1 cursor-pointer"
                      >
                        <Trash2 className="w-3 h-3" />
                        Remove
                      </button>
                    )}
                  </div>

                  {/* Hidden File Input */}
                  <input
                    ref={imageFileInputRef}
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                    className="hidden"
                    onChange={handleImageFileUpload}
                    disabled={uploadingImage}
                  />

                  {/* Upload Action Button */}
                  <div>
                    <button
                      type="button"
                      onClick={() => imageFileInputRef.current?.click()}
                      disabled={uploadingImage}
                      className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-[6px] border border-dashed border-[#1b6c80]/50 bg-[#1b6c80]/5 hover:bg-[#1b6c80]/10 text-xs font-medium text-[#1b6c80] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                      {uploadingImage ? (
                        <>
                          <Loader2 className="w-3.5 h-3.5 animate-spin text-[#1b6c80]" />
                          <span>Uploading image...</span>
                        </>
                      ) : (
                        <>
                          <Upload className="w-3.5 h-3.5 text-[#1b6c80]" />
                          <span>{destImageUrl ? 'Upload Different Image' : 'Upload Image File'}</span>
                        </>
                      )}
                    </button>
                  </div>

                  {imageUploadError && (
                    <div className="p-2 rounded-[6px] bg-red-50 border border-red-200 text-[11px] text-red-600">
                      {imageUploadError}
                    </div>
                  )}

                  {/* URL Input */}
                  <div>
                    <label className="block text-[11px] text-[#5d5f60] mb-1">
                      Or enter image URL:
                    </label>
                    <input
                      type="url"
                      value={destImageUrl}
                      onChange={(e) => {
                        setDestImageUrl(e.target.value);
                        commit({ destImageUrl: e.target.value });
                      }}
                      placeholder="https://images.unsplash.com/... or /uploads/..."
                      className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono text-[#131416] focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80] outline-none"
                    />
                  </div>

                  {/* Image Preview */}
                  {destImageUrl && (
                    <div className="mt-2 rounded-lg overflow-hidden border border-[#e8eaec] max-h-40 bg-white relative group">
                      <img
                        src={resolveMediaUrl(destImageUrl)}
                        alt="Preview"
                        className="w-full h-32 object-cover"
                        onError={(e) => {
                          (e.target as HTMLElement).style.display = 'none';
                        }}
                      />
                      <div className="absolute top-1.5 right-1.5 flex gap-1 bg-black/60 backdrop-blur-xs p-1 rounded-md">
                        <button
                          type="button"
                          onClick={() => imageFileInputRef.current?.click()}
                          title="Change image file"
                          className="p-1 rounded text-white hover:bg-white/20 transition cursor-pointer"
                        >
                          <Upload className="w-3 h-3" />
                        </button>
                        <button
                          type="button"
                          onClick={handleRemoveImage}
                          title="Remove image"
                          className="p-1 rounded text-rose-300 hover:text-white hover:bg-rose-600/60 transition cursor-pointer"
                        >
                          <Trash2 className="w-3 h-3" />
                        </button>
                      </div>
                    </div>
                  )}

                  <p className="text-[10px] text-[#5d5f60]">
                    Supported formats: PNG, JPG, WebP, SVG, GIF (up to 10MB). Displayed prominently at the top of the visitor outcome screen.
                  </p>
                </div>
              )}

              {destMediaType === 'video' && (
                <div className="space-y-2 pt-1 border-t border-[#e8eaec]">
                  <label className="block text-xs font-medium text-[#131416]">
                    Video URL (YouTube or Vimeo) *
                  </label>
                  <input
                    type="url"
                    value={destVideoUrl}
                    onChange={(e) => {
                      setDestVideoUrl(e.target.value);
                      commit({ destVideoUrl: e.target.value });
                    }}
                    placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono text-[#131416]"
                  />
                  <p className="text-[10px] text-[#5d5f60]">
                    Embedded responsive player to greet leads or review quote details on completion.
                  </p>
                </div>
              )}
            </div>

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
            CONTENT SLIDE NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'content' && (
          <div className="space-y-4">
            {/* Headline */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Headline / Title *
              </label>
              <input
                type="text"
                value={contentHeadline}
                onChange={(e) => {
                  setContentHeadline(e.target.value);
                  commit({ contentHeadline: e.target.value, title: e.target.value });
                }}
                placeholder="e.g. Did you know? 80% of organizations see higher ROI"
                className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80] outline-none"
              />
            </div>

            {/* Subtext / Body Copy */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Body Copy / Story Text
              </label>
              <textarea
                rows={3}
                value={contentBody}
                onChange={(e) => {
                  setContentBody(e.target.value);
                  commit({ contentBody: e.target.value });
                }}
                placeholder="Add supporting data, customer transformation details, or context before the next question..."
                className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416] focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80] outline-none"
              />
            </div>

            {/* Image / Graphic Media */}
            <div className="p-3 rounded-[8px] border border-[#e8eaec] bg-[#f9fbfc] space-y-2.5">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-xs text-[#131416] flex items-center gap-1.5">
                  <ImageIcon className="w-3.5 h-3.5 text-[#1b6c80]" />
                  <span>Story Image / Infographic</span>
                </span>
                {contentImageUrl && (
                  <button
                    type="button"
                    onClick={handleRemoveContentImage}
                    className="text-[11px] text-rose-600 hover:text-rose-700 font-medium inline-flex items-center gap-1 cursor-pointer"
                  >
                    <Trash2 className="w-3 h-3" />
                    Remove
                  </button>
                )}
              </div>

              {/* Hidden File Input */}
              <input
                ref={contentFileInputRef}
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                className="hidden"
                onChange={handleContentImageUpload}
                disabled={uploadingContentImg}
              />

              {/* Upload Button */}
              <button
                type="button"
                onClick={() => contentFileInputRef.current?.click()}
                disabled={uploadingContentImg}
                className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-[6px] border border-dashed border-[#1b6c80]/50 bg-[#1b6c80]/5 hover:bg-[#1b6c80]/10 text-xs font-medium text-[#1b6c80] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {uploadingContentImg ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin text-[#1b6c80]" />
                    <span>Uploading image...</span>
                  </>
                ) : (
                  <>
                    <Upload className="w-3.5 h-3.5 text-[#1b6c80]" />
                    <span>{contentImageUrl ? 'Upload Different Image' : 'Upload Image File'}</span>
                  </>
                )}
              </button>

              {contentUploadError && (
                <div className="p-2 rounded-[6px] bg-red-50 border border-red-200 text-[11px] text-red-600">
                  {contentUploadError}
                </div>
              )}

              {/* URL Input */}
              <div>
                <label className="block text-[11px] text-[#5d5f60] mb-1">
                  Or enter Image URL:
                </label>
                <input
                  type="url"
                  value={contentImageUrl}
                  onChange={(e) => {
                    setContentImageUrl(e.target.value);
                    commit({ contentImageUrl: e.target.value });
                  }}
                  placeholder="https://images.unsplash.com/... or /uploads/..."
                  className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono text-[#131416] focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80] outline-none"
                />
              </div>

              {/* Live Preview */}
              {contentImageUrl && (
                <div className="rounded-lg overflow-hidden border border-[#e8eaec] max-h-40 bg-white relative group">
                  <img
                    src={resolveMediaUrl(contentImageUrl)}
                    alt="Content Preview"
                    className="w-full h-32 object-cover"
                    onError={(e) => {
                      (e.target as HTMLElement).style.display = 'none';
                    }}
                  />
                  <div className="absolute top-1.5 right-1.5 flex gap-1 bg-black/60 backdrop-blur-xs p-1 rounded-md">
                    <button
                      type="button"
                      onClick={() => contentFileInputRef.current?.click()}
                      title="Change image file"
                      className="p-1 rounded text-white hover:bg-white/20 transition cursor-pointer"
                    >
                      <Upload className="w-3 h-3" />
                    </button>
                    <button
                      type="button"
                      onClick={handleRemoveContentImage}
                      title="Remove image"
                      className="p-1 rounded text-rose-300 hover:text-white hover:bg-rose-600/60 transition cursor-pointer"
                    >
                      <Trash2 className="w-3 h-3" />
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Action Button Label */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Advance Button Text *
              </label>
              <input
                type="text"
                value={contentButtonText}
                onChange={(e) => {
                  setContentButtonText(e.target.value);
                  commit({ contentButtonText: e.target.value });
                }}
                placeholder="Continue to Next Step"
                className="w-full px-3 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
              />
              <p className="text-[10px] text-[#5d5f60] mt-1">
                Visitors click this button to proceed to the next connected step in your funnel.
              </p>
            </div>
          </div>
        )}

        {/* ========================================================
            VIDEO GATE NODE INSPECTOR
        ======================================================== */}
        {nodeType === 'video_gate' && (
          <div className="space-y-4">
            {/* Playback Mode Selector: Standard vs Gated */}
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1.5">
                Playback & Lead Capture Mode
              </label>
              <div className="grid grid-cols-2 gap-1 p-1 bg-[#f4f6f8] rounded-[8px] border border-[#e8eaec]">
                <button
                  type="button"
                  onClick={() => {
                    setVgPlaybackMode('standard');
                    commit({ vgPlaybackMode: 'standard' });
                  }}
                  className={`py-1.5 px-2 rounded-[6px] text-xs font-medium transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                    vgPlaybackMode === 'standard'
                      ? 'bg-white text-[#131416] shadow-2xs font-semibold'
                      : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <Play className="w-3.5 h-3.5 text-emerald-600" />
                  <span>Standard Video</span>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setVgPlaybackMode('gated');
                    commit({ vgPlaybackMode: 'gated' });
                  }}
                  className={`py-1.5 px-2 rounded-[6px] text-xs font-medium transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                    vgPlaybackMode === 'gated'
                      ? 'bg-white text-[#131416] shadow-2xs font-semibold'
                      : 'text-[#5d5f60] hover:text-[#131416]'
                  }`}
                >
                  <Lock className="w-3.5 h-3.5 text-[#c41c29]" />
                  <span>Gated Lead Form</span>
                </button>
              </div>
            </div>

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
                placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
                className="w-full px-3 py-2 rounded-[6px] border border-[#e8eaec] text-xs font-mono text-[#131416]"
              />
            </div>

            {/* Standard Mode CTA Reveal Controls */}
            {vgPlaybackMode === 'standard' && (
              <div className="space-y-3 pt-2 border-t border-[#f0f1f2]">
                <div className="p-3 rounded-[8px] bg-emerald-50/60 border border-emerald-100 text-xs text-emerald-950 space-y-1">
                  <span className="font-semibold block">Standard Presentation Mode</span>
                  <p className="text-[11px] text-emerald-800 leading-relaxed">
                    The video plays freely without interruption. Visitors watch the presentation and click the action button to advance to the next step.
                  </p>
                </div>

                <div>
                  <label className="block text-xs font-medium text-[#131416] mb-1">
                    Advance Button Visibility *
                  </label>
                  <select
                    value={vgCtaReveal}
                    onChange={(e: any) => {
                      setVgCtaReveal(e.target.value);
                      commit({ vgCtaReveal: e.target.value });
                    }}
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                  >
                    <option value="always">Visible Immediately</option>
                    <option value="delayed">Delayed (Show after seconds)</option>
                    <option value="on_end">Show When Video Finishes</option>
                  </select>
                </div>

                {vgCtaReveal === 'delayed' && (
                  <div className="p-3 bg-[#f8fafc] border border-[#e2e8f0] rounded-[8px] space-y-2.5">
                    <div className="flex items-center justify-between">
                      <label className="block text-xs font-semibold text-[#131416]">
                        Show Button After Video Plays *
                      </label>
                      <span className="text-[11px] font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                        {String(Math.floor(vgCtaDelay / 60)).padStart(2, '0')}:
                        {String(Math.floor(vgCtaDelay % 60)).padStart(2, '0')}
                        {' '}({vgCtaDelay}s)
                      </span>
                    </div>

                    {/* Dual Inputs: Minutes & Seconds */}
                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="block text-[11px] text-[#5d5f60] mb-0.5">Minutes</label>
                        <div className="relative">
                          <input
                            type="number"
                            min={0}
                            max={120}
                            value={Math.floor(vgCtaDelay / 60)}
                            onChange={(e) => {
                              const m = Math.max(0, parseInt(e.target.value) || 0);
                              const s = Math.floor(vgCtaDelay % 60);
                              const total = (m * 60) + s;
                              setVgCtaDelay(total);
                              commit({ vgCtaDelay: total });
                            }}
                            className="w-full px-2.5 py-1.5 pr-8 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono font-medium text-[#131416]"
                          />
                          <span className="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-[#5d5f60] font-sans">
                            min
                          </span>
                        </div>
                      </div>

                      <div>
                        <label className="block text-[11px] text-[#5d5f60] mb-0.5">Seconds</label>
                        <div className="relative">
                          <input
                            type="number"
                            min={0}
                            max={59}
                            value={Math.floor(vgCtaDelay % 60)}
                            onChange={(e) => {
                              const s = Math.min(59, Math.max(0, parseInt(e.target.value) || 0));
                              const m = Math.floor(vgCtaDelay / 60);
                              const total = (m * 60) + s;
                              setVgCtaDelay(total);
                              commit({ vgCtaDelay: total });
                            }}
                            className="w-full px-2.5 py-1.5 pr-8 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono font-medium text-[#131416]"
                          />
                          <span className="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-[#5d5f60] font-sans">
                            sec
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Interactive Slider */}
                    <div>
                      <input
                        type="range"
                        min={1}
                        max={Math.max(600, vgCtaDelay > 600 ? vgCtaDelay + 300 : 600)}
                        step={1}
                        value={vgCtaDelay}
                        onChange={(e) => {
                          const val = Number(e.target.value);
                          setVgCtaDelay(val);
                          commit({ vgCtaDelay: val });
                        }}
                        className="w-full accent-indigo-600 cursor-pointer h-1.5 bg-slate-200 rounded-lg"
                      />
                    </div>

                    {/* Quick Presets */}
                    <div className="flex items-center gap-1.5 flex-wrap pt-0.5">
                      <span className="text-[10px] text-[#5d5f60]">Presets:</span>
                      <button
                        type="button"
                        onClick={() => {
                          setVgCtaDelay(30);
                          commit({ vgCtaDelay: 30 });
                        }}
                        className="px-1.5 py-0.5 rounded text-[10px] bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-mono"
                      >
                        00:30
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setVgCtaDelay(60);
                          commit({ vgCtaDelay: 60 });
                        }}
                        className="px-1.5 py-0.5 rounded text-[10px] bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-mono"
                      >
                        01:00
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setVgCtaDelay(120);
                          commit({ vgCtaDelay: 120 });
                        }}
                        className="px-1.5 py-0.5 rounded text-[10px] bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-mono"
                      >
                        02:00
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setVgCtaDelay(285);
                          commit({ vgCtaDelay: 285 });
                        }}
                        className="px-1.5 py-0.5 rounded text-[10px] bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 font-mono font-bold"
                        title="4 minutes 45 seconds"
                      >
                        04:45 (4m 45s)
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setVgCtaDelay(300);
                          commit({ vgCtaDelay: 300 });
                        }}
                        className="px-1.5 py-0.5 rounded text-[10px] bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-mono"
                      >
                        05:00
                      </button>
                    </div>

                    <p className="text-[11px] text-[#5d5f60] leading-tight">
                      Button stays hidden while video plays and arrives at exactly{' '}
                      <span className="font-semibold text-slate-800">
                        {Math.floor(vgCtaDelay / 60)}m {Math.floor(vgCtaDelay % 60)}s
                      </span>{' '}
                      ({vgCtaDelay}s).
                    </p>
                  </div>
                )}

                <div>
                  <label className="block text-xs font-medium text-[#131416] mb-1">
                    Advance Button Text
                  </label>
                  <input
                    type="text"
                    value={vgCtaButtonText}
                    onChange={(e) => {
                      setVgCtaButtonText(e.target.value);
                      commit({ vgCtaButtonText: e.target.value });
                    }}
                    placeholder="Continue to Next Step"
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] text-xs text-[#131416]"
                  />
                </div>
              </div>
            )}

            {/* Gated Mode Controls */}
            {vgPlaybackMode === 'gated' && (
              <>
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <label className="block text-xs font-medium text-[#131416]">
                      Gate Pause Time
                    </label>
                    <div className="flex items-center gap-1.5">
                      <span className="text-[11px] font-mono font-medium text-[#131416] bg-[#f4f6f8] px-1.5 py-0.5 rounded border border-[#e8eaec]">
                        {String(Math.floor(gateTimestamp / 60)).padStart(2, '0')}:{String(Math.floor(gateTimestamp % 60)).padStart(2, '0')}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={3600}
                        value={gateTimestamp}
                        onChange={(e) => {
                          const val = Math.max(1, Number(e.target.value) || 0);
                          setGateTimestamp(val);
                          commit({ gateTimestamp: val });
                        }}
                        className="w-16 px-2 py-0.5 rounded border border-[#e8eaec] text-xs font-mono text-right text-[#131416]"
                      />
                      <span className="text-xs text-[#5d5f60]">sec</span>
                    </div>
                  </div>
                  <input
                    type="range"
                    min={1}
                    max={300}
                    step={1}
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

                <div className="pt-1">
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

            {/* Turnstile Pop-up Form Fields Manager */}
            <div className="space-y-3 pt-3 border-t border-[#f0f1f2]">
              <div className="flex items-center justify-between">
                <div>
                  <span className="font-semibold text-xs text-[#131416] block">
                    Pop-up Form Fields ({formFields.length})
                  </span>
                  <span className="text-[11px] text-[#5d5f60]">
                    Customize fields displayed in the turnstile modal
                  </span>
                </div>
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
                <span className="text-[11px] text-[#5d5f60]">Add:</span>
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
          </>
        )}
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

        {nodeType === 'condition' && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-[#131416] mb-1">
                Router Title
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => {
                  setTitle(e.target.value);
                  commit({ title: e.target.value });
                }}
                placeholder="Conditional Router"
                className="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#131416]"
              />
            </div>

            {/* Decision Rule Configuration */}
            <div className="p-3.5 bg-[#f4f6f8] border border-[#e8eaec] rounded-[8px] space-y-3">
              <span className="text-xs font-semibold text-[#131416] block">
                Decision Rule
              </span>

              <div>
                <label className="block text-[11px] font-medium text-[#5d5f60] mb-1">
                  Evaluate On
                </label>
                <select
                  value={condType}
                  onChange={(e) => {
                    const newType = e.target.value;
                    setCondType(newType);
                    commit({ condType: newType });
                  }}
                  className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-medium text-[#131416]"
                >
                  <option value="score">Total Quiz Score (Sum of all question points)</option>
                  <option value="slider">Specific Slider Value (e.g. Budget range)</option>
                  <option value="variable">Specific Variable Key (e.g. @Q1 or @BUDGET)</option>
                </select>
              </div>

              {/* Slider Node Picker */}
              {condType === 'slider' && (
                <div>
                  <label className="block text-[11px] font-medium text-[#5d5f60] mb-1">
                    Select Slider Question
                  </label>
                  <select
                    value={condTargetNodeId}
                    onChange={(e) => {
                      setCondTargetNodeId(e.target.value);
                      commit({ condTargetNodeId: e.target.value });
                    }}
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs text-[#131416]"
                  >
                    <option value="">-- Choose Slider Node --</option>
                    {allNodes
                      .filter((n) => n.id !== node.id && (n.type === 'question' || n.data?.node_type === 'question'))
                      .map((n) => (
                        <option key={n.id} value={n.id}>
                          {n.data?.title || `Question #${n.id}`}
                        </option>
                      ))}
                  </select>
                </div>
              )}

              {/* Variable Key */}
              {condType === 'variable' && (
                <div>
                  <label className="block text-[11px] font-medium text-[#5d5f60] mb-1">
                    Variable Key
                  </label>
                  <input
                    type="text"
                    value={condVarKey}
                    onChange={(e) => {
                      setCondVarKey(e.target.value);
                      commit({ condVarKey: e.target.value });
                    }}
                    placeholder="Q1 or BUDGET"
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono text-[#131416]"
                  />
                  <p className="text-[10px] text-[#5d5f60] mt-0.5">
                    Matches variable key set on questions (e.g. Q1, Q2)
                  </p>
                </div>
              )}

              {/* Operator and Threshold */}
              <div className="grid grid-cols-12 gap-2">
                <div className="col-span-5">
                  <label className="block text-[11px] font-medium text-[#5d5f60] mb-1">
                    Operator
                  </label>
                  <select
                    value={condOperator}
                    onChange={(e) => {
                      setCondOperator(e.target.value);
                      commit({ condOperator: e.target.value });
                    }}
                    className="w-full px-2 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono font-medium text-[#131416]"
                  >
                    <option value=">=">&gt;= (Greater or equal)</option>
                    <option value=">">&gt; (Greater than)</option>
                    <option value="<=">&lt;= (Less or equal)</option>
                    <option value="<">&lt; (Less than)</option>
                    <option value="==">== (Equals)</option>
                    <option value="!=">!= (Not equals)</option>
                  </select>
                </div>

                <div className="col-span-7">
                  <label className="block text-[11px] font-medium text-[#5d5f60] mb-1">
                    Threshold Value
                  </label>
                  <input
                    type="text"
                    value={condValue}
                    onChange={(e) => {
                      setCondValue(e.target.value);
                      commit({ condValue: e.target.value });
                    }}
                    placeholder="e.g. 80 or 50000"
                    className="w-full px-2.5 py-1.5 rounded-[6px] border border-[#e8eaec] bg-white text-xs font-mono font-medium text-[#131416]"
                  />
                </div>
              </div>
            </div>

            {/* Branch Port Descriptions */}
            <div className="p-3 bg-white border border-[#e8eaec] rounded-[8px] space-y-2.5">
              <span className="text-xs font-semibold text-[#131416] block">
                Outgoing Wire Handles
              </span>

              {/* Match Port */}
              <div className="p-2 rounded-[6px] bg-[#f0fdf4] border border-[#bbf7d0] space-y-1">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-emerald-800 flex items-center gap-1">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
                    MATCH (True)
                  </span>
                  <span className="text-[10px] text-emerald-600 font-mono">Port: match</span>
                </div>
                <input
                  type="text"
                  value={condMatchLabel}
                  onChange={(e) => {
                    setCondMatchLabel(e.target.value);
                    commit({ condMatchLabel: e.target.value });
                  }}
                  placeholder="Match Label"
                  className="w-full px-2 py-1 rounded border border-[#bbf7d0] bg-white text-xs text-[#131416]"
                />
                <p className="text-[10px] text-emerald-700">
                  Connect this handle to the step for qualified leads who satisfy the condition.
                </p>
              </div>

              {/* Default Port */}
              <div className="p-2 rounded-[6px] bg-[#f8fafc] border border-[#e2e8f0] space-y-1">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-800 flex items-center gap-1">
                    <span className="w-2 h-2 rounded-full bg-slate-400 inline-block" />
                    DEFAULT (Else)
                  </span>
                  <span className="text-[10px] text-slate-500 font-mono">Port: default</span>
                </div>
                <input
                  type="text"
                  value={condDefaultLabel}
                  onChange={(e) => {
                    setCondDefaultLabel(e.target.value);
                    commit({ condDefaultLabel: e.target.value });
                  }}
                  placeholder="Default Label"
                  className="w-full px-2 py-1 rounded border border-[#cbd5e1] bg-white text-xs text-[#131416]"
                />
                <p className="text-[10px] text-slate-600">
                  Connect this handle to the fallback step for visitors who do not meet the condition.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Drawer Delete Footer */}
      <div className="p-3 border-t border-[#f0f1f2] bg-[#f9fbfc] flex items-center justify-between shrink-0">
        <button
          type="button"
          onClick={() => onDelete(node.id)}
          className="px-3 py-1.5 rounded-[6px] text-[#c41c29] hover:bg-[#ffedea] text-xs font-medium flex items-center gap-1.5 transition-colors cursor-pointer"
        >
          <Trash2 className="w-3.5 h-3.5" />
          <span>Delete Step</span>
        </button>
        <span className="text-[10px] text-[#5c6064]">
          Auto-saves on change
        </span>
      </div>
    </aside>
  );
};

import React, { useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { api, resolveMediaUrl } from '../../api/client';
import {
  ArrowLeft,
  ArrowRight,
  Check,
  Sliders,
  MessageCircle,
  PhoneCall,
  ExternalLink,
  ShieldCheck,
  Play,
  RotateCcw,
  Sparkles,
  Calendar,
  Loader2,
  Lock,
  Star
} from 'lucide-react';
import { VideoGateStep } from './VideoGateStep';

export const QuizRunnerPage: React.FC = () => {
  const { slug } = useParams<{ slug: string }>();
  const [searchParams] = useSearchParams();
  const isEmbedded = searchParams.get('embedded') === '1';

  const [manifest, setManifest] = useState<any>(null);
  const [sessionToken, setSessionToken] = useState<string | null>(null);
  const [currentStep, setCurrentStep] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [stepLoading, setStepLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Form State
  const [formValues, setFormValues] = useState<Record<string, string>>({});
  // Selected Choice State
  const [selectedOptionId, setSelectedOptionId] = useState<string | null>(null);
  // Slider Value
  const [sliderVal, setSliderVal] = useState<number>(50);
  // Loader Progress
  const [loaderProgress, setLoaderProgress] = useState<number>(0);
  const [logoFailed, setLogoFailed] = useState(false);

  useEffect(() => {
    initQuiz();
  }, [slug]);

  const initQuiz = async () => {
    try {
      setLoading(true);
      setErrorMsg(null);

      // 1. Fetch manifest (optional company branding / settings)
      try {
        const mf = await api.get(`/api/quiz/${slug}/manifest`);
        setManifest(mf);
      } catch (e) {
        console.warn('Manifest load error:', e);
      }

      // 2. Initialize quiz session
      const sess = await api.post(`/api/quiz/${slug}/init`);
      if (!sess || !sess.session_token) {
        throw new Error('Unable to initialize quiz session.');
      }
      setSessionToken(sess.session_token);

      // Check if pre-quiz form is required
      if (sess.has_form && sess.form_fields && sess.form_fields.length > 0) {
        setCurrentStep({
          step_type: 'form',
          is_pre_quiz: true,
          title: 'Before we begin...',
          heading: 'Before we begin...',
          subheading: 'Please enter your contact details to begin the assessment.',
          button_text: 'Start Assessment',
          fields: sess.form_fields,
          can_go_back: false,
        });
      } else {
        // Fetch the active starting step from QuizEngineService
        const stepRes = await api.get('/api/quiz/step', { session_token: sess.session_token });
        processStepResponse(stepRes);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'This quiz is currently unavailable or in draft.');
    } finally {
      setLoading(false);
    }
  };

  const processStepResponse = (res: any) => {
    if (!res) return;

    if (res.status === 'completed') {
      if (settings.fb_pixel_id && typeof (window as any).fbq === 'function') {
        (window as any).fbq('track', 'Lead');
      }
      if (settings.google_tag_id && typeof (window as any).gtag === 'function') {
        (window as any).gtag('event', 'generate_lead');
      }
      setCurrentStep({
        step_type: 'destination',
        result: res.result || {},
      });
    } else if (res.status === 'content_step' || res.step?.node_type === 'content') {
      setCurrentStep({
        step_type: 'content',
        ...(res.step || {}),
        content: res.step?.content || res.step?.config?.content || {},
        can_go_back: Boolean(res.step?.can_go_back),
      });
    } else if (res.status === 'video_gate_step') {
      setCurrentStep({
        step_type: 'video_gate',
        ...(res.step || {}),
        can_go_back: Boolean(res.step?.can_go_back),
      });
    } else if (res.status === 'loader_step' || res.step?.node_type === 'loader') {
      setCurrentStep({
        step_type: 'loader',
        ...(res.step || {}),
        headline: res.step?.headline || res.step?.title || 'Analyzing your responses...',
        subtext: res.step?.subtext || 'Matching optimal solutions based on your answers...',
        duration_seconds: Number(res.step?.duration_seconds || 2.5),
        can_go_back: false,
      });
    } else if (res.status === 'booking_step' || res.step?.node_type === 'booking') {
      setCurrentStep({
        step_type: 'booking',
        ...(res.step || {}),
        headline: res.step?.headline || res.step?.title || 'Schedule Consultation',
        provider: res.step?.provider || 'calendly',
        booking_url: res.step?.booking_url || '',
        embed_mode: res.step?.embed_mode || 'inline',
        can_go_back: Boolean(res.step?.can_go_back),
      });
    } else if (res.status === 'form_step') {
      setCurrentStep({
        step_type: 'form',
        ...(res.step || {}),
        title: res.step?.heading || res.step?.title || 'Lead Capture Form',
        heading: res.step?.heading || res.step?.title || 'Lead Capture Form',
        subheading: res.step?.subheading || res.step?.description || '',
        fields: res.step?.form_fields || [],
        button_text: res.step?.button_text || 'Submit Details',
        can_go_back: Boolean(res.step?.can_go_back),
      });
    } else {
      // Regular in-progress step (Question choice or slider)
      const st = res.step || {};
      setCurrentStep({
        step_type: 'question',
        ...st,
        can_go_back: Boolean(st.can_go_back),
      });
      if (st.input_type === 'slider') {
        const defaultVal = Number(st.current_slider_value ?? st.slider_config?.default_value ?? 50);
        setSliderVal(defaultVal);
      }
    }
    setSelectedOptionId(null);
  };

  const handleSelectOption = async (optionId: string, label: string) => {
    if (!sessionToken || !currentStep || stepLoading) return;
    try {
      setSelectedOptionId(optionId);
      setStepLoading(true);

      // Pacing pause: allows the user to clearly see their selection checked before advancing ("Check and go")
      const minDelay = new Promise((resolve) => setTimeout(resolve, 420));
      const [res] = await Promise.all([
        api.post('/api/quiz/answer', {
          session_token: sessionToken,
          node_id: currentStep.node_id,
          answer: { option_id: optionId },
        }),
        minDelay,
      ]);

      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to submit answer.');
    } finally {
      setStepLoading(false);
    }
  };

  const handleSliderSubmit = async () => {
    if (!sessionToken || !currentStep || stepLoading) return;
    try {
      setStepLoading(true);
      const minDelay = new Promise((resolve) => setTimeout(resolve, 300));
      const [res] = await Promise.all([
        api.post('/api/quiz/answer', {
          session_token: sessionToken,
          node_id: currentStep.node_id,
          answer: { slider_value: sliderVal },
        }),
        minDelay,
      ]);

      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to submit slider value.');
    } finally {
      setStepLoading(false);
    }
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!sessionToken || !currentStep) return;

    try {
      setStepLoading(true);
      let res;
      if (currentStep.node_type === 'form') {
        res = await api.post('/api/quiz/answer', {
          session_token: sessionToken,
          node_id: currentStep.node_id,
          answer: { form_data: formValues },
        });
      } else {
        res = await api.post('/api/quiz/form', {
          session_token: sessionToken,
          form_data: formValues,
        });
      }
      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Please complete all required fields.');
    } finally {
      setStepLoading(false);
    }
  };

  useEffect(() => {
    if (currentStep?.step_type !== 'loader') return;
    setLoaderProgress(0);
    const durationMs = (currentStep.duration_seconds || 2.5) * 1000;
    const intervalTime = 50;
    const stepIncrement = (100 / (durationMs / intervalTime));

    const timer = setInterval(() => {
      setLoaderProgress((prev) => {
        const next = prev + stepIncrement;
        if (next >= 100) {
          clearInterval(timer);
          return 100;
        }
        return next;
      });
    }, intervalTime);

    return () => clearInterval(timer);
  }, [currentStep?.step_type, currentStep?.node_id, currentStep?.duration_seconds]);

  useEffect(() => {
    if (currentStep?.step_type === 'loader' && loaderProgress >= 100 && !stepLoading) {
      handleLoaderAdvance();
    }
  }, [loaderProgress, currentStep?.step_type]);

  // Typeform-style Keyboard Navigation (Press A, B, C... to select option)
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes((e.target as HTMLElement)?.tagName)) {
        return;
      }
      if (currentStep?.step_type === 'question' && Array.isArray(currentStep.options) && !stepLoading) {
        const key = e.key.toUpperCase();
        if (key.length === 1 && key >= 'A' && key <= 'Z') {
          const charCode = key.charCodeAt(0) - 65;
          if (charCode >= 0 && charCode < currentStep.options.length) {
            const opt = currentStep.options[charCode];
            const optId = String(opt.id || opt.option_key || charCode);
            handleSelectOption(optId, opt.label);
          }
        }
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [currentStep, stepLoading, sessionToken]);

  const handleLoaderAdvance = async () => {
    if (!sessionToken || !currentStep || stepLoading) return;
    try {
      setStepLoading(true);
      const res = await api.post('/api/quiz/answer', {
        session_token: sessionToken,
        node_id: currentStep.node_id,
        answer: { auto_advance: true },
      });
      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to complete step.');
    } finally {
      setStepLoading(false);
    }
  };

  const handleBookingAdvance = async () => {
    if (!sessionToken || !currentStep || stepLoading) return;
    try {
      setStepLoading(true);
      const res = await api.post('/api/quiz/answer', {
        session_token: sessionToken,
        node_id: currentStep.node_id,
        answer: { booked: true },
      });
      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to advance booking step.');
    } finally {
      setStepLoading(false);
    }
  };

  const handleContentAdvance = async () => {
    if (!sessionToken || !currentStep || stepLoading) return;
    try {
      setStepLoading(true);
      const res = await api.post('/api/quiz/answer', {
        session_token: sessionToken,
        node_id: currentStep.node_id,
        answer: { continue: true },
      });
      processStepResponse(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to advance content slide.');
    } finally {
      setStepLoading(false);
    }
  };

  const renderLandingPageVideo = (url: string) => {
    if (!url) return null;
    const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
    if (ytMatch) {
      return (
        <iframe
          src={`https://www.youtube.com/embed/${ytMatch[1]}?autoplay=0&controls=1&rel=0`}
          className="w-full h-full"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
        />
      );
    }
    const vimMatch = url.match(/(?:vimeo\.com\/(?:video\/)?|player\.vimeo\.com\/video\/)([0-9]+)/i);
    if (vimMatch) {
      return (
        <iframe
          src={`https://player.vimeo.com/video/${vimMatch[1]}?autoplay=0&controls=1`}
          className="w-full h-full"
          allow="autoplay; fullscreen; picture-in-picture"
          allowFullScreen
        />
      );
    }
    return (
      <video
        src={resolveMediaUrl(url)}
        controls
        className="w-full h-full object-cover"
        playsInline
      />
    );
  };

  const handleBack = async () => {
    if (!sessionToken) return;
    try {
      setStepLoading(true);
      const res = await api.post('/api/quiz/back', { session_token: sessionToken });
      processStepResponse(res);
    } catch (err: any) {
      console.warn('Back navigation error:', err);
    } finally {
      setStepLoading(false);
    }
  };

  const settings = {
    ...(manifest?.settings || {}),
    ...(currentStep?.settings || {}),
  };
  const showProgressBar =
    settings.show_progress_bar !== false &&
    settings.show_progress_bar !== 0 &&
    settings.show_progress_bar !== '0' &&
    settings.show_progress_bar !== 'false';
  const accent = settings.accent_color || '#1d5fd6';
  const isDark = settings.theme === 'dark';
  const logoPosition = settings.logo_position || 'left';
  const logoHeight = Math.max(24, Math.min(90, Number(settings.logo_height) || 36));
  const cardRadius = settings.card_radius || 'rounded-2xl';
  const isWhiteLabel = Boolean(settings.white_label);
  const companyName = settings.company_name || manifest?.name || 'Apex Health & Wellness';
  const footerText = settings.footer_text || `© ${new Date().getFullYear()} ${companyName}. All rights reserved.`;
  const rawTrustBadge = settings.trust_badge_text || '256-Bit SSL Encrypted • 100% Privacy Guaranteed';
  const trustBadgeText = rawTrustBadge.replace(/^[\s\uD83D\uDD12\uD83D\uDEE1]+/, '').trim(); /* taste-skill-ignore */
  const privacyUrl = settings.privacy_policy_url;
  const termsUrl = settings.terms_url;

  // Marketer CRO & Styling
  const buttonRadius = settings.button_radius || 'rounded-xl';
  const buttonStyle = settings.button_style || 'solid';
  const ctaButtonText = settings.cta_button_text || '';
  const fontFamily = settings.font_family || 'sans';
  const backgroundStyle = settings.background_style || 'default';
  const cardElevation = settings.card_elevation || 'soft';
  const socialProofBadge = settings.social_proof_badge || '';

  const fontClass = fontFamily === 'serif' ? 'font-serif' : 'font-sans';
  const bgClass = isDark || backgroundStyle === 'dark'
    ? 'bg-slate-950 text-slate-100'
    : backgroundStyle === 'warm'
    ? 'bg-[#faf8f5] text-[#131416]'
    : backgroundStyle === 'dots'
    ? 'bg-[#f8fafc] text-[#131416]'
    : 'bg-[#f9fbfc] text-[#131416]';

  const elevationClass = isDark
    ? 'border border-slate-800/80 shadow-[0_25px_60px_rgba(0,0,0,0.5)] bg-slate-900/90 backdrop-blur-xl text-slate-100'
    : cardElevation === 'flat'
    ? 'border border-slate-200/80 bg-white/95 backdrop-blur-xl text-[#131416]'
    : cardElevation === 'floating'
    ? 'border border-slate-200/80 shadow-[0_30px_70px_rgba(0,0,0,0.08),0_4px_12px_rgba(0,0,0,0.03)] bg-white/95 backdrop-blur-xl text-[#131416]'
    : 'border border-slate-200/80 shadow-[0_20px_50px_rgba(0,0,0,0.05),0_1px_3px_rgba(0,0,0,0.04)] bg-white/95 backdrop-blur-xl text-[#131416]';

  const btnStyleClass = buttonStyle === 'outline'
    ? `bg-transparent border-2 border-[var(--brand-color)] !text-[var(--brand-color)] hover:bg-[var(--brand-color)] hover:!text-white ${buttonRadius}`
    : `bg-[var(--brand-color)] text-white hover:opacity-95 ${buttonRadius}`;

  useEffect(() => {
    if (settings.meta_title) {
      document.title = settings.meta_title;
    } else if (manifest?.name) {
      document.title = `${manifest.name} | ${companyName}`;
    }

    if (settings.meta_description) {
      let metaDesc = document.querySelector('meta[name="description"]');
      if (!metaDesc) {
        metaDesc = document.createElement('meta');
        metaDesc.setAttribute('name', 'description');
        document.head.appendChild(metaDesc);
      }
      metaDesc.setAttribute('content', settings.meta_description);
    }

    if (settings.fb_pixel_id && typeof (window as any).fbq === 'function') {
      (window as any).fbq('track', 'PageView');
    }
    if (settings.google_tag_id && typeof (window as any).gtag === 'function') {
      (window as any).gtag('event', 'page_view');
    }
  }, [manifest?.name, companyName, settings.meta_title, settings.meta_description, settings.fb_pixel_id, settings.google_tag_id]);

  if (loading) {
    return (
      <div className={`min-h-screen flex flex-col justify-between ${bgClass} p-4 sm:p-6 lg:p-10`}>
        <header className="max-w-xl mx-auto w-full flex items-center justify-between pb-6">
          <div className="h-6 w-32 rounded-lg skeleton-shimmer" />
          <div className="h-5 w-12 rounded-lg skeleton-shimmer" />
        </header>

        <main className="max-w-xl mx-auto w-full my-auto">
          <div className={`${cardRadius} ${isDark ? 'bg-slate-900 border border-slate-800 shadow-2xl' : 'bg-white border border-[#e8eaec] shadow-xs'} p-6 sm:p-10 space-y-6`}>
            <div className="space-y-2">
              <div className="h-6 w-3/4 rounded-lg skeleton-shimmer" />
              <div className="h-4 w-1/2 rounded-lg skeleton-shimmer" />
            </div>

            <div className="space-y-3 pt-2">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className={`h-14 rounded-xl border ${isDark ? 'border-slate-800 bg-slate-800/40' : 'border-[#e8eaec]'} p-4 flex items-center justify-between`}>
                  <div className="flex items-center gap-3 flex-1">
                    <div className="w-6 h-6 rounded-lg skeleton-shimmer shrink-0" />
                    <div className="h-4 w-2/3 rounded-lg skeleton-shimmer" />
                  </div>
                  <div className="w-5 h-5 rounded-full skeleton-shimmer shrink-0" />
                </div>
              ))}
            </div>
          </div>
        </main>

        <footer className="max-w-xl mx-auto w-full pt-6 flex items-center justify-center gap-2">
          <div className="h-4 w-40 rounded-lg skeleton-shimmer" />
        </footer>
      </div>
    );
  }

  if (errorMsg && !currentStep) {
    return (
      <div className={`min-h-screen flex items-center justify-center ${bgClass} p-4`}>
        <div className={`${isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8eaec]'} border rounded-2xl p-8 max-w-md w-full text-center shadow-lg`}>
          <h2 className={`text-base font-bold ${isDark ? 'text-white' : 'text-[#131416]'}`}>Quiz Unavailable</h2>
          <p className={`text-xs mt-2 leading-relaxed ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>{errorMsg}</p>
        </div>
      </div>
    );
  }

  return (
    <div
      className={`min-h-screen relative overflow-hidden flex flex-col justify-between ${fontClass} ${bgClass} ${isEmbedded ? '!p-2' : 'p-4 sm:p-6 lg:p-10'}`}
      style={{ '--brand-color': accent } as any}
    >
      {/* Ambient Background Aura Lights for Depth */}
      <div className="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none">
        {isDark ? (
          <>
            <div
              style={{
                background: `radial-gradient(circle 650px at 50% 25%, ${accent}28, transparent 75%)` /* taste-skill-ignore */
              }}
              className="absolute inset-0 opacity-80"
            />
            <div className="absolute -top-48 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-[#1d5fd6]/10 rounded-full blur-[160px]" />
            <div className="absolute bottom-10 right-1/4 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-[140px]" />
          </>
        ) : (
          <>
            <div
              style={{
                background: `radial-gradient(circle 750px at 50% 20%, ${accent}14, transparent 70%)` /* taste-skill-ignore */
              }}
              className="absolute inset-0 opacity-90"
            />
            <div className="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-blue-100/50 rounded-full blur-[130px]" />
            <div className="absolute bottom-10 left-1/4 w-[450px] h-[450px] bg-[#eef4ff]/40 rounded-full blur-[110px]" />
          </>
        )}
      </div>
      {/* Brand Header */}
      {!isEmbedded && (
        <>
          {logoPosition === 'center' ? (
            <header className="max-w-xl mx-auto w-full pb-6 space-y-3">
              <div className="flex flex-col items-center justify-center text-center">
                {settings.logo_url && !logoFailed ? (
                  <img
                    src={resolveMediaUrl(settings.logo_url)}
                    alt={companyName}
                    style={{ height: `${logoHeight}px` }}
                    className="max-w-[220px] object-contain"
                    onError={() => setLogoFailed(true)}
                  />
                ) : (
                  <span className={`font-bold text-sm tracking-tight ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                    {companyName}
                  </span>
                )}
              </div>
              <div className="flex items-center justify-between">
                {currentStep?.can_go_back ? (
                  <button
                    onClick={handleBack}
                    disabled={stepLoading}
                    className={`text-xs font-semibold flex items-center gap-1 transition disabled:opacity-50 ${
                      isDark ? 'text-slate-400 hover:text-white' : 'text-[#5c6066] hover:text-[#131416]'
                    }`}
                  >
                    {stepLoading ? <Loader2 className="w-3.5 h-3.5 animate-spin text-[var(--brand-color)]" /> : <ArrowLeft className="w-3.5 h-3.5" />}
                    <span>Back</span>
                  </button>
                ) : <span />}
                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold ${
                  isDark ? 'bg-slate-800 text-slate-300 border border-slate-700/80' : 'bg-[#eef4ff] text-[#1d5fd6]'
                }`}>
                  <ShieldCheck className={`w-3 h-3 ${isDark ? 'text-emerald-400' : 'text-[#1d5fd6]'}`} />
                  <span>Verified Secure</span>
                </span>
              </div>
            </header>
          ) : logoPosition === 'right' ? (
            <header className="max-w-xl mx-auto w-full flex items-center justify-between pb-6">
              <div>
                {currentStep?.can_go_back && (
                  <button
                    onClick={handleBack}
                    disabled={stepLoading}
                    className={`text-xs font-semibold flex items-center gap-1 transition disabled:opacity-50 ${
                      isDark ? 'text-slate-400 hover:text-white' : 'text-[#5c6066] hover:text-[#131416]'
                    }`}
                  >
                    {stepLoading ? <Loader2 className="w-3.5 h-3.5 animate-spin text-[var(--brand-color)]" /> : <ArrowLeft className="w-3.5 h-3.5" />}
                    <span>Back</span>
                  </button>
                )}
              </div>
              <div className="flex items-center gap-2.5">
                {settings.logo_url && !logoFailed ? (
                  <img
                    src={resolveMediaUrl(settings.logo_url)}
                    alt={companyName}
                    style={{ height: `${logoHeight}px` }}
                    className="max-w-[220px] object-contain"
                    onError={() => setLogoFailed(true)}
                  />
                ) : (
                  <span className={`font-bold text-sm tracking-tight ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                    {companyName}
                  </span>
                )}
              </div>
            </header>
          ) : (
            <header className="max-w-xl mx-auto w-full flex items-center justify-between pb-6">
              <div className="flex items-center gap-2.5">
                {settings.logo_url && !logoFailed ? (
                  <img
                    src={resolveMediaUrl(settings.logo_url)}
                    alt={companyName}
                    style={{ height: `${logoHeight}px` }}
                    className="max-w-[220px] object-contain"
                    onError={() => setLogoFailed(true)}
                  />
                ) : (
                  <span className={`font-bold text-sm tracking-tight ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                    {companyName}
                  </span>
                )}
              </div>

              {currentStep?.can_go_back && (
                <button
                  onClick={handleBack}
                  disabled={stepLoading}
                  className={`text-xs font-semibold flex items-center gap-1 transition disabled:opacity-50 ${
                    isDark ? 'text-slate-400 hover:text-white' : 'text-[#5c6066] hover:text-[#131416]'
                  }`}
                >
                  {stepLoading ? <Loader2 className="w-3.5 h-3.5 animate-spin text-[var(--brand-color)]" /> : <ArrowLeft className="w-3.5 h-3.5" />}
                  <span>Back</span>
                </button>
              )}
            </header>
          )}
        </>
      )}

      {/* Main Card Container */}
      <main className="max-w-xl mx-auto w-full my-auto">
        <div
          className={`${cardRadius} ${elevationClass} p-6 sm:p-10 relative overflow-hidden transition-colors`}
        >
          {socialProofBadge && (
            <div className="flex justify-center pb-3">
              <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${
                isDark
                  ? 'bg-amber-950/40 text-amber-300 border-amber-800/60'
                  : 'bg-amber-50 text-amber-900 border-amber-200'
              }`}>
                <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                <span>{socialProofBadge}</span>
              </div>
            </div>
          )}

          {stepLoading && (
            <div className={`absolute top-0 left-0 right-0 h-1 overflow-hidden ${isDark ? 'bg-slate-800' : 'bg-[#dcf1ff]'}`}>
              <div className="w-full h-full bg-[var(--brand-color)] animate-top-bar" />
            </div>
          )}

          {/* STEP PROGRESS BAR & TRACKER (ON by default, can be toggled OFF in Funnel Settings) */}
          {showProgressBar && currentStep?.step_type !== 'destination' && (
            <div className="mb-6 space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className={`font-semibold tracking-wide flex items-center gap-1.5 ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  <span>Step {currentStep?.step_number || 1}</span>
                  {currentStep?.total_steps ? (
                    <span className="opacity-50 font-normal">of {currentStep.total_steps}</span>
                  ) : null}
                </span>
                <span className={`font-mono text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${
                  isDark
                    ? 'bg-slate-800/80 text-slate-300 border-slate-700/60'
                    : 'bg-slate-100/90 text-slate-700 border-slate-200/80'
                }`}>
                  {Math.min(100, Math.max(10, Math.round(((currentStep?.step_number || 1) / (currentStep?.total_steps || 3)) * 100)))}% Completed
                </span>
              </div>
              <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div
                  style={{
                    width: `${Math.min(100, Math.max(10, Math.round(((currentStep?.step_number || 1) / (currentStep?.total_steps || 3)) * 100)))}%`,
                    backgroundColor: accent,
                  }}
                  className="h-full rounded-full transition-all duration-500 ease-out shadow-xs"
                />
              </div>
            </div>
          )}
          {/* STEP 1: LEAD CAPTURE FORM (Pre-quiz or Form Node) */}
          {currentStep?.step_type === 'form' && (
            <form onSubmit={handleFormSubmit} className="space-y-4">
              <div className="text-center pb-2">
                <h2 className={`text-lg font-bold ${isDark ? 'text-white' : 'text-[#0F172A]'}`}>
                  {currentStep.heading || currentStep.title || 'Before we begin...'}
                </h2>
                {currentStep.subheading && (
                  <p className={`text-xs mt-1 ${isDark ? 'text-slate-400' : 'text-[#687080]'}`}>
                    {currentStep.subheading}
                  </p>
                )}
              </div>

              {(currentStep.fields || [
                { id: 1, field_key: 'name', label: 'Full Name', field_type: 'text', is_required: 1 },
                { id: 2, field_key: 'email', label: 'Email Address', field_type: 'email', is_required: 1 },
              ]).map((field: any, idx: number) => {
                const key = field.field_key || field.name || `field_${idx}`;
                return (
                  <div key={key}>
                    <label className={`block text-xs font-semibold mb-1 ${isDark ? 'text-slate-200' : 'text-[#171A21]'}`}>
                      {field.label || field.name}{' '}
                      {field.is_required ? <span className="text-rose-500">*</span> : null}
                    </label>
                    <input
                      type={field.field_type || field.type || 'text'}
                      required={Boolean(field.is_required)}
                      placeholder={field.placeholder || ''}
                      value={formValues[key] || ''}
                      onChange={(e) => setFormValues({ ...formValues, [key]: e.target.value })}
                      className={`w-full px-3 py-2.5 rounded-xl text-xs transition-colors focus:outline-none ${
                        isDark
                          ? 'bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:border-[var(--brand-color)] focus:ring-1 focus:ring-[var(--brand-color)]'
                          : 'bg-white border border-[#e8eaec] text-[#131416] placeholder-slate-400 focus:border-[var(--brand-color)]'
                      }`}
                    />
                  </div>
                );
              })}

              <button
                type="submit"
                disabled={stepLoading}
                className={`w-full py-3 ${btnStyleClass} text-xs font-bold transition mt-4 disabled:opacity-50 flex items-center justify-center gap-2`}
              >
                {stepLoading ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                    <span>Submitting...</span>
                  </>
                ) : (
                  <span>{currentStep.button_text || ctaButtonText || 'Continue'}</span>
                )}
              </button>
            </form>
          )}

          {/* STEP: VIDEO GATE PRESENTATION */}
          {currentStep?.step_type === 'video_gate' && (
            <VideoGateStep
              step={currentStep}
              sessionToken={sessionToken || ''}
              isDark={isDark}
              accentColor={accent}
              buttonRadius={buttonRadius}
              onAdvance={processStepResponse}
              onError={(msg) => setErrorMsg(msg)}
            />
          )}

          {/* STEP: LANDING PAGE / BRIDGE PAGE / INTERSTITIAL STORY */}
          {currentStep?.step_type === 'content' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Optional Eyebrow Badge */}
              {currentStep.content?.badge_text && (
                <div className="flex justify-start">
                  <span
                    style={{ borderColor: `${accent}40`, color: accent, backgroundColor: `${accent}10` }}
                    className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border shadow-xs"
                  >
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>{currentStep.content.badge_text}</span>
                  </span>
                </div>
              )}

              {/* Headline & Subheadline */}
              <div className="space-y-2">
                <h2 className={`text-2xl sm:text-3xl font-extrabold tracking-tight leading-snug ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                  {currentStep.content?.headline || currentStep.title || 'Key Information'}
                </h2>
                {currentStep.content?.subheadline && (
                  <p className={`text-sm sm:text-base leading-relaxed ${isDark ? 'text-slate-300' : 'text-[#5d5f60]'}`}>
                    {currentStep.content.subheadline}
                  </p>
                )}
              </div>

              {/* Hero Media: Image or Video */}
              {currentStep.content?.media_type !== 'none' && (
                <>
                  {currentStep.content?.media_type === 'video' && currentStep.content?.video_url ? (
                    <div className="w-full rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-800 bg-black shadow-md aspect-video select-none">
                      {renderLandingPageVideo(currentStep.content.video_url)}
                    </div>
                  ) : currentStep.content?.image_url ? (
                    <div className="w-full max-h-[360px] rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shadow-sm flex items-center justify-center">
                      <img
                        src={resolveMediaUrl(currentStep.content.image_url)}
                        alt={currentStep.content?.headline || 'Landing media'}
                        className="w-full h-full object-cover max-h-[360px]"
                        onError={(e) => {
                          (e.target as HTMLElement).style.display = 'none';
                        }}
                      />
                    </div>
                  ) : null}
                </>
              )}

              {/* Key Benefits Checklist */}
              {currentStep.content?.show_benefits &&
                Array.isArray(currentStep.content?.benefits) &&
                currentStep.content.benefits.filter(Boolean).length > 0 && (
                  <div
                    className={`p-4 sm:p-5 rounded-2xl border ${
                      isDark ? 'bg-slate-800/60 border-slate-700/60' : 'bg-slate-50/80 border-slate-200/80'
                    }`}
                  >
                    <ul className="space-y-2.5">
                      {currentStep.content.benefits.filter(Boolean).map((benefit: string, idx: number) => (
                        <li key={idx} className="flex items-start gap-2.5">
                          <div
                            style={{ backgroundColor: `${accent}15`, color: accent }}
                            className="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                          >
                            <Check className="w-3.5 h-3.5 stroke-[3]" />
                          </div>
                          <span
                            className={`text-sm sm:text-base font-medium leading-normal ${
                              isDark ? 'text-slate-200' : 'text-slate-800'
                            }`}
                          >
                            {benefit}
                          </span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

              {/* Long Copy / Body Text */}
              {currentStep.content?.body_text && (
                <div
                  className={`text-sm sm:text-base leading-relaxed whitespace-pre-line ${
                    isDark ? 'text-slate-300' : 'text-[#5d5f60]'
                  }`}
                >
                  {currentStep.content.body_text}
                </div>
              )}

              {/* Social Proof Star Strip */}
              {currentStep.content?.show_social_proof && currentStep.content?.social_proof_text && (
                <div className="flex items-center gap-2 pt-1 text-sm">
                  <span className="flex items-center gap-0.5 text-amber-500">
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                  </span>
                  <span className={`font-medium ${isDark ? 'text-slate-300' : 'text-slate-700'}`}>
                    {currentStep.content.social_proof_text}
                  </span>
                </div>
              )}

              {/* Primary Call to Action Button & Subtext */}
              <div className="pt-2 space-y-2 text-center">
                <button
                  type="button"
                  onClick={handleContentAdvance}
                  disabled={stepLoading}
                  style={{ backgroundColor: accent }}
                  className={`w-full py-3.5 px-6 text-white font-semibold text-sm shadow-md hover:brightness-110 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer ${buttonRadius}`}
                >
                  {stepLoading ? (
                    <Loader2 className="w-5 h-5 animate-spin" />
                  ) : (
                    <>
                      <span>{currentStep.content?.button_text || 'Continue to Next Step'}</span>
                      <ArrowRight className="w-4 h-4 stroke-[2.5]" />
                    </>
                  )}
                </button>

                {currentStep.content?.button_subtext && (
                  <p className={`text-xs flex items-center justify-center gap-1.5 ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                    <span>{currentStep.content.button_subtext}</span>
                  </p>
                )}
              </div>
            </div>
          )}

          {/* STEP 2: QUESTION STEP (CHOICE OR SLIDER) */}
          {currentStep?.step_type === 'question' && (
            <div className="space-y-6">
              <div>
                <h2 className={`text-xl sm:text-2xl font-bold tracking-tight leading-snug ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                  {currentStep.question_text || currentStep.title || 'What is your current preference?'}
                </h2>
                {currentStep.help_text && (
                  <p className={`text-xs sm:text-sm mt-1.5 leading-relaxed ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                    {currentStep.help_text}
                  </p>
                )}
              </div>

              {/* A: SLIDER MODE */}
              {currentStep.input_type === 'slider' ? (
                <div className="space-y-6 pt-2">
                  <div className={`p-6 rounded-2xl text-center space-y-4 border transition-colors ${
                    isDark
                      ? 'bg-slate-800/60 border-slate-700/80 shadow-inner'
                      : 'bg-[#f9fbfc] border-[#e8eaec]'
                  }`}>
                    <div className="inline-block px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
                      <span className="text-3xl sm:text-4xl font-extrabold text-[var(--brand-color)] tracking-tight">
                        {currentStep.slider_config?.unit_prefix || ''}
                        {sliderVal.toLocaleString()} {currentStep.slider_config?.unit_label || ''}
                      </span>
                    </div>

                    <input
                      type="range"
                      min={currentStep.slider_config?.min_value ?? 1}
                      max={currentStep.slider_config?.max_value ?? 100}
                      step={currentStep.slider_config?.step_value ?? 1}
                      value={sliderVal}
                      onChange={(e) => setSliderVal(Number(e.target.value))}
                      className={`w-full h-2 rounded-lg appearance-none cursor-pointer accent-[var(--brand-color)] ${
                        isDark ? 'bg-slate-700' : 'bg-slate-200'
                      }`}
                    />

                    <div className={`flex justify-between text-[11px] font-mono ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                      <span>Min: {currentStep.slider_config?.min_value ?? 1}</span>
                      <span>Max: {currentStep.slider_config?.max_value ?? 100}</span>
                    </div>
                  </div>

                  <button
                    onClick={handleSliderSubmit}
                    disabled={stepLoading}
                    className={`w-full py-3.5 ${btnStyleClass} text-sm font-bold transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 shadow-md hover:brightness-105 active:scale-[0.99]`}
                  >
                    {stepLoading ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin text-white" />
                        <span>Saving...</span>
                      </>
                    ) : (
                      <>
                        <span>{ctaButtonText || 'Continue'}</span>
                        <ArrowRight className="w-4 h-4 stroke-[2.5]" />
                      </>
                    )}
                  </button>
                </div>
              ) : (
                /* B: MULTIPLE OR SINGLE CHOICE OPTIONS */
                <div className="space-y-3">
                  {(currentStep.options || []).map((opt: any, idx: number) => {
                    const optId = String(opt.id || opt.option_key || idx);
                    const isSelected = selectedOptionId === optId;
                    const letter = String.fromCharCode(65 + (idx % 26));

                    return (
                      <button
                        key={optId}
                        type="button"
                        onClick={() => handleSelectOption(optId, opt.label)}
                        disabled={stepLoading}
                        className={`w-full p-4 rounded-xl border text-left transition-all duration-150 flex items-center justify-between group cursor-pointer active:scale-[0.99] ${
                          isSelected
                            ? (isDark
                                ? 'border-[var(--brand-color)] bg-[var(--brand-color)]/20 ring-2 ring-[var(--brand-color)]/50 shadow-md translate-y-[-1px]'
                                : 'border-[var(--brand-color)] bg-[var(--brand-color)]/10 ring-2 ring-[var(--brand-color)]/30 shadow-md translate-y-[-1px]')
                            : (isDark
                                ? 'border-slate-800 bg-slate-800/40 hover:border-slate-700 hover:bg-slate-800/80 hover:-translate-y-0.5 hover:shadow-md'
                                : 'border-[#e8eaec] bg-white/70 hover:border-[var(--brand-color)]/40 hover:bg-[#f9fbfc] hover:-translate-y-0.5 hover:shadow-md')
                        }`}
                      >
                        <div className="flex items-center gap-3.5 min-w-0">
                          {/* Letter Badge */}
                          <span className={`w-7 h-7 rounded-lg border text-xs font-bold flex items-center justify-center shrink-0 transition-colors shadow-2xs ${
                            isSelected
                              ? 'bg-[var(--brand-color)] text-white border-[var(--brand-color)]'
                              : (isDark
                                  ? 'bg-slate-800 border-slate-700 text-slate-300 group-hover:border-slate-600'
                                  : 'bg-[#f0f2f5] border-[#e8eaec] text-[#131416] group-hover:border-[var(--brand-color)]/30')
                          }`}>
                            {letter}
                          </span>
                          <span className={`text-sm font-semibold transition-colors truncate ${
                            isSelected
                              ? (isDark ? 'text-white' : 'text-[var(--brand-color)]')
                              : (isDark ? 'text-slate-200 group-hover:text-white' : 'text-[#131416] group-hover:text-[var(--brand-color)]')
                          }`}>
                            {opt.label}
                          </span>
                        </div>

                        {/* Radio Check Indicator with Keyboard Hint */}
                        <div className="flex items-center gap-2.5 shrink-0">
                          <span className="hidden sm:inline-block text-[10px] font-mono text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                            Press {letter}
                          </span>
                          <div
                            className={`w-5 h-5 rounded-full border flex items-center justify-center transition-all ${
                              isSelected
                                ? 'border-[var(--brand-color)] bg-[var(--brand-color)] text-white scale-110 shadow-xs'
                                : (isDark ? 'border-slate-700 bg-slate-800/60' : 'border-[#d0d3d8] bg-white')
                            }`}
                          >
                            {isSelected && (
                              <Check className="w-3.5 h-3.5 stroke-[3] text-white" />
                            )}
                          </div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {/* STEP: LOADER INTERSTITIAL */}
          {currentStep?.step_type === 'loader' && (
            <div className="py-8 text-center space-y-6">
              <div className="relative w-20 h-20 mx-auto flex items-center justify-center">
                <div className={`absolute inset-0 rounded-full border-4 ${isDark ? 'border-amber-950/60' : 'border-amber-100'}`} />
                <div
                  className="absolute inset-0 rounded-full border-4 border-amber-500 border-t-transparent animate-spin"
                  style={{ animationDuration: '1s' }}
                />
                <Sparkles className="w-8 h-8 text-amber-500" />
              </div>

              <div className="space-y-2 max-w-sm mx-auto">
                <h2 className={`text-xl font-bold ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                  {currentStep.headline || 'Analyzing your responses...'}
                </h2>
                <p className={`text-xs leading-relaxed ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                  {currentStep.subtext || 'Matching optimal solutions based on your answers...'}
                </p>
              </div>

              {/* Progress bar */}
              <div className="max-w-xs mx-auto space-y-2">
                <div className={`w-full rounded-full h-2.5 overflow-hidden border ${isDark ? 'bg-slate-800 border-slate-700' : 'bg-[#f0f1f2] border-[#e8eaec]'}`}>
                  <div
                    className="bg-amber-500 h-full rounded-full transition-[width] duration-75 ease-out"
                    style={{ width: `${Math.min(100, Math.round(loaderProgress))}%` }}
                  />
                </div>
                <div className={`flex justify-between items-center text-[11px] font-mono ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                  <span>Calculating next step...</span>
                  <span className="font-bold text-amber-500 tabular-nums">{Math.min(100, Math.round(loaderProgress))}%</span>
                </div>
              </div>
            </div>
          )}

          {/* STEP: CALENDAR BOOKING */}
          {currentStep?.step_type === 'booking' && (
            <div className="space-y-6">
              <div className="text-center space-y-2">
                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center mx-auto border ${
                  isDark ? 'bg-slate-800 text-cyan-400 border-slate-700' : 'bg-[#ebf4f6] text-[#1b6c80] border-[#d0e4e8]'
                }`}>
                  <Calendar className="w-6 h-6" />
                </div>
                <h2 className={`text-xl font-bold ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                  {currentStep.headline || 'Schedule Consultation'}
                </h2>
                <p className={`text-xs ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                  Select an available date and time with our specialist.
                </p>
              </div>

              {/* Booking Container: Inline Iframe or Popup Button */}
              {currentStep.booking_url ? (
                currentStep.embed_mode === 'popup' ? (
                  <div className={`p-6 rounded-2xl text-center space-y-4 border ${
                    isDark ? 'bg-slate-800/50 border-slate-700' : 'bg-[#ebf4f6]/50 border-[#d0e4e8]'
                  }`}>
                    <p className={`text-xs font-medium ${isDark ? 'text-slate-200' : 'text-[#131416]'}`}>
                      Click below to open our interactive booking calendar in a new window.
                    </p>
                    <a
                      href={currentStep.booking_url}
                      target="_blank"
                      rel="noreferrer"
                      className={`inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white text-xs font-bold transition ${
                        isDark ? 'bg-slate-800 hover:bg-slate-700 border border-slate-700' : 'bg-[#1d1f20] hover:bg-[#2c3033]'
                      }`}
                    >
                      <Calendar className="w-4 h-4" />
                      <span>Open Booking Calendar</span>
                      <ExternalLink className="w-3.5 h-3.5" />
                    </a>
                  </div>
                ) : (
                  <div className={`rounded-2xl border overflow-hidden ${isDark ? 'border-slate-800 bg-slate-900' : 'border-[#e8eaec] bg-[#f9fbfc]'}`}>
                    <iframe
                      src={currentStep.booking_url}
                      width="100%"
                      height="500"
                      frameBorder="0"
                      title="Schedule Appointment"
                      className="w-full rounded-2xl"
                    />
                  </div>
                )
              ) : (
                <div className={`p-8 text-center rounded-2xl text-xs border border-dashed ${
                  isDark ? 'bg-slate-800/40 border-slate-700 text-slate-400' : 'bg-[#f9fbfc] border-[#e8eaec] text-[#5c6066]'
                }`}>
                  No booking URL configured for this calendar step.
                </div>
              )}

              <button
                type="button"
                onClick={handleBookingAdvance}
                disabled={stepLoading}
                className={`w-full py-3.5 rounded-xl text-white text-xs font-bold transition flex items-center justify-center gap-2 disabled:opacity-50 ${
                  isDark ? 'bg-slate-800 hover:bg-slate-700 border border-slate-700' : 'bg-[#1d1f20] hover:bg-[#2c3033]'
                }`}
              >
                <span>{stepLoading ? 'Advancing...' : 'I Have Booked / Continue'}</span>
                <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          )}

          {/* STEP 3: DESTINATION / OUTCOME RESULT */}
          {currentStep?.step_type === 'destination' && (
            <div className="text-center space-y-5">
              {/* Media Display: Video, Image, or Default Check Icon */}
              {currentStep.result?.media?.media_type === 'video' && currentStep.result.media.video_id ? (
                <div className="relative rounded-2xl overflow-hidden bg-black shadow-xl border border-black/20 aspect-video select-none my-3 max-w-lg mx-auto">
                  <iframe
                    src={
                      currentStep.result.media.provider === 'vimeo'
                        ? `https://player.vimeo.com/video/${currentStep.result.media.video_id}?autoplay=0&controls=1`
                        : `https://www.youtube.com/embed/${currentStep.result.media.video_id}?autoplay=0&controls=1&rel=0`
                    }
                    title={currentStep.result?.destination_name || 'Outcome Presentation'}
                    className="w-full h-full border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                  />
                </div>
              ) : currentStep.result?.media?.media_type === 'image' && currentStep.result.media.image_url ? (
                <div className="relative rounded-2xl overflow-hidden shadow-md border border-slate-200 dark:border-slate-800 max-w-md mx-auto my-3 bg-slate-100 dark:bg-slate-900">
                  <img
                    src={resolveMediaUrl(currentStep.result.media.image_url)}
                    alt={currentStep.result?.destination_name || 'Outcome Media'}
                    className="w-full h-auto max-h-[320px] object-cover object-center"
                    onError={(e) => {
                      (e.target as HTMLElement).style.display = 'none';
                    }}
                  />
                </div>
              ) : (
                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center mx-auto border ${
                  isDark ? 'bg-emerald-950/70 text-emerald-400 border-emerald-800/80' : 'bg-[#dcfae5] text-[#0f5132] border-[#bbf7d0]'
                }`}>
                  <Check className="w-6 h-6 stroke-[2.5]" />
                </div>
              )}

              <div>
                <h2 className={`text-xl font-bold ${isDark ? 'text-white' : 'text-[#131416]'}`}>
                  {currentStep.result?.destination_name ||
                    currentStep.result?.success_heading ||
                    'Thank You! Assessment Completed'}
                </h2>

                {currentStep.result?.formatted_quote && (
                  <div className={`mt-4 p-5 rounded-2xl inline-block max-w-sm w-full border ${
                    isDark
                      ? 'bg-slate-800/80 border-slate-700/80 text-white'
                      : 'bg-[#dcf1ff] border-[#b9e6fe] shadow-xs'
                  }`}>
                    <span className={`text-xs font-semibold block ${isDark ? 'text-slate-300' : 'text-[#0a3a80]'}`}>
                      Your Customized Quote
                    </span>
                    <span className={`text-3xl font-extrabold block mt-1 font-mono tabular-nums ${isDark ? 'text-[var(--brand-color)]' : 'text-[#0a3a80]'}`}>
                      {currentStep.result.formatted_quote}
                    </span>
                  </div>
                )}

                <p className={`text-xs mt-3 leading-relaxed max-w-sm mx-auto ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
                  {currentStep.result?.success_message ||
                    currentStep.result?.description ||
                    'Your answers have been securely recorded. Our team will follow up promptly.'}
                </p>
              </div>

              {/* Direct Connect WhatsApp & Call Buttons */}
              {currentStep.result?.quick_connect && (
                <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                  {currentStep.result.quick_connect.whatsapp_url && (
                    <a
                      href={currentStep.result.quick_connect.whatsapp_url}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition"
                    >
                      <MessageCircle className="w-4 h-4" />
                      <span>
                        {currentStep.result.quick_connect.whatsapp_button_text || 'Chat on WhatsApp'}
                      </span>
                    </a>
                  )}

                  {currentStep.result.quick_connect.call_url && (
                    <a
                      href={currentStep.result.quick_connect.call_url}
                      className={`inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white text-xs font-bold transition ${
                        isDark ? 'bg-cyan-700 hover:bg-cyan-600' : 'bg-[#1b6c80] hover:bg-[#155363]'
                      }`}
                    >
                      <PhoneCall className="w-4 h-4" />
                      <span>
                        {currentStep.result.quick_connect.call_button_text || 'Call Specialist'}
                      </span>
                    </a>
                  )}
                </div>
              )}

              {/* External Destination Redirect */}
              {currentStep.result?.destination_url && (
                <div className="pt-2">
                  <a
                    href={currentStep.result.destination_url}
                    className={`inline-flex items-center gap-1.5 text-xs font-semibold hover:underline ${
                      isDark ? 'text-cyan-400 hover:text-cyan-300' : 'text-[#1b6c80]'
                    }`}
                  >
                    <span>Proceed to External Destination</span>
                    <ExternalLink className="w-3.5 h-3.5" />
                  </a>
                </div>
              )}
            </div>
          )}
        </div>
      </main>

      {/* 100% White-Labeled Marketing & Trust Footer */}
      {!isEmbedded && (
        <footer className="max-w-xl mx-auto w-full text-center pt-8 pb-4 space-y-2">
          {trustBadgeText && (
            <div className={`inline-flex items-center gap-1.5 text-[11px] font-medium ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-500" />
              <span>{trustBadgeText}</span>
            </div>
          )}

          <div className={`flex flex-wrap items-center justify-center gap-2.5 text-[11px] font-medium pt-1 ${isDark ? 'text-slate-400' : 'text-[#8b919a]'}`}>
            {privacyUrl && (
              <>
                <a
                  href={privacyUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`${isDark ? 'hover:text-slate-200' : 'hover:text-[#131416]'} hover:underline transition`}
                >
                  Privacy Policy
                </a>
                <span>•</span>
              </>
            )}
            {termsUrl && (
              <>
                <a
                  href={termsUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`${isDark ? 'hover:text-slate-200' : 'hover:text-[#131416]'} hover:underline transition`}
                >
                  Terms of Service
                </a>
                <span>•</span>
              </>
            )}
            <span>{footerText}</span>
          </div>

          {!isWhiteLabel && (
            <div className={`text-[10px] pt-1 ${isDark ? 'text-slate-500' : 'text-[#8b919a]'}`}>
              Powered by <span className={`font-semibold ${isDark ? 'text-slate-400' : 'text-[#5c6066]'}`}>LeadCollector.in</span>
            </div>
          )}
        </footer>
      )}
    </div>
  );
};

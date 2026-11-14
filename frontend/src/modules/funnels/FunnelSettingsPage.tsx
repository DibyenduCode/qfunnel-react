import React, { useEffect, useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api, resolveMediaUrl } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import {
  ArrowLeft,
  Save,
  Code,
  Copy,
  Check,
  Eye,
  Loader2,
  ShieldCheck,
  Lock,
  Sparkles,
  Sliders,
  Upload,
  X,
  MousePointerClick,
  Activity,
  Layers,
  Share2
} from 'lucide-react';

export const FunnelSettingsPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { isPlatformAdmin } = useAuthStore();

  const [funnel, setFunnel] = useState<any>(null);
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [accentColor, setAccentColor] = useState('#5B5BD6');
  const [theme, setTheme] = useState<'light' | 'dark'>('light');
  const [companyName, setCompanyName] = useState('');
  const [logoUrl, setLogoUrl] = useState('');
  const [logoPosition, setLogoPosition] = useState<'left' | 'center' | 'right'>('left');
  const [logoHeight, setLogoHeight] = useState<number>(36);
  const [cardRadius, setCardRadius] = useState<string>('rounded-2xl');
  const [trustBadgeText, setTrustBadgeText] = useState('');
  const [footerText, setFooterText] = useState('');
  const [privacyUrl, setPrivacyUrl] = useState('');
  const [termsUrl, setTermsUrl] = useState('');
  const [whiteLabel, setWhiteLabel] = useState(false);
  const [showProgressBar, setShowProgressBar] = useState(true);
  const [planFeatures, setPlanFeatures] = useState<any>({});

  // Marketer Conversion & Styling States
  const [buttonRadius, setButtonRadius] = useState<string>('rounded-xl');
  const [buttonStyle, setButtonStyle] = useState<'solid' | 'glow' | 'outline'>('solid');
  const [ctaButtonText, setCtaButtonText] = useState('');
  const [fontFamily, setFontFamily] = useState<'sans' | 'serif' | 'geometric' | 'system'>('sans');
  const [backgroundStyle, setBackgroundStyle] = useState<'default' | 'dots' | 'warm' | 'dark'>('default');
  const [cardElevation, setCardElevation] = useState<'flat' | 'soft' | 'floating'>('soft');
  const [socialProofBadge, setSocialProofBadge] = useState('');
  const [fbPixelId, setFbPixelId] = useState('');
  const [googleTagId, setGoogleTagId] = useState('');
  const [tiktokPixelId, setTiktokPixelId] = useState('');
  const [metaTitle, setMetaTitle] = useState('');
  const [metaDescription, setMetaDescription] = useState('');

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [copiedMode, setCopiedMode] = useState<string | null>(null);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  const handleLogoFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setUploadError('File is too large. Max size is 5MB.');
      return;
    }

    try {
      setUploadingLogo(true);
      setUploadError(null);
      const formData = new FormData();
      formData.append('logo_file', file);

      const res = await api.post(`/api/funnels/${id}/upload-logo`, formData);
      if (res && res.logo_url) {
        setLogoUrl(res.logo_url);
        setMessage({ type: 'success', text: 'Logo uploaded and updated successfully.' });
      }
    } catch (err: any) {
      setUploadError(err.message || 'Failed to upload logo.');
    } finally {
      setUploadingLogo(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveLogo = () => {
    setLogoUrl('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  useEffect(() => {
    loadSettings();
  }, [id]);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/api/funnels/${id}`);
      setFunnel(res.funnel);
      setName(res.funnel.name);
      setSlug(res.funnel.slug);
      setDescription(res.funnel.description || '');

      const s = res.settings || {};
      const pf = res.plan_features || {};
      setPlanFeatures(pf);

      setAccentColor(s.accent_color || '#1b6c80');
      setTheme(s.theme || 'light');
      setCompanyName(s.company_name || '');
      setLogoUrl(s.logo_url || '');
      setLogoPosition(s.logo_position || 'left');
      setLogoHeight(Number(s.logo_height || 36));
      setCardRadius(s.card_radius || 'rounded-2xl');
      setTrustBadgeText(s.trust_badge_text || '256-Bit SSL Encrypted • 100% Privacy Guaranteed');
      setFooterText(s.footer_text || '');
      setPrivacyUrl(s.privacy_policy_url || '');
      setTermsUrl(s.terms_url || '');
      setWhiteLabel(Boolean(s.white_label));
      setShowProgressBar(s.show_progress_bar !== false);
      setButtonRadius(s.button_radius || 'rounded-xl');
      setButtonStyle(s.button_style || 'solid');
      setCtaButtonText(s.cta_button_text || '');
      setFontFamily(s.font_family || 'sans');
      setBackgroundStyle(s.background_style || 'default');
      setCardElevation(s.card_elevation || 'soft');
      setSocialProofBadge(s.social_proof_badge || '');
      setFbPixelId(s.fb_pixel_id || '');
      setGoogleTagId(s.google_tag_id || '');
      setTiktokPixelId(s.tiktok_pixel_id || '');
      setMetaTitle(s.meta_title || '');
      setMetaDescription(s.meta_description || '');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const hasWhiteLabel = Boolean(planFeatures?.white_label || isPlatformAdmin);
  const hasTrackingPixels = Boolean(planFeatures?.custom_tracking_pixels || isPlatformAdmin);
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);
  const [upgradeFeature, setUpgradeFeature] = useState({ name: '', desc: '', plan: 'Pro' });

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      await api.post(`/api/funnels/${id}/settings`, {
        name,
        slug,
        description,
        accent_color: accentColor,
        theme,
        company_name: companyName,
        logo_url: logoUrl,
        logo_position: logoPosition,
        logo_height: logoHeight,
        card_radius: cardRadius,
        trust_badge_text: trustBadgeText,
        footer_text: footerText,
        privacy_policy_url: privacyUrl,
        terms_url: termsUrl,
        white_label: hasWhiteLabel ? whiteLabel : false,
        show_progress_bar: showProgressBar,
        button_radius: buttonRadius,
        button_style: buttonStyle,
        cta_button_text: ctaButtonText,
        font_family: fontFamily,
        background_style: backgroundStyle,
        card_elevation: cardElevation,
        social_proof_badge: socialProofBadge,
        fb_pixel_id: fbPixelId,
        google_tag_id: googleTagId,
        tiktok_pixel_id: tiktokPixelId,
        meta_title: metaTitle,
        meta_description: metaDescription,
      });
      setMessage({ type: 'success', text: 'Settings saved successfully.' });
    } catch (err: any) {
      setMessage({ type: 'error', text: err.message || 'Failed to save settings.' });
    } finally {
      setSaving(false);
    }
  };

  const copyEmbedCode = (mode: 'inline' | 'popup' | 'floating') => {
    const host = window.location.origin;
    let snippet = '';

    if (mode === 'inline') {
      snippet = `<!-- LeadCollector Inline Embed -->\n<div data-qfunnel-embed="${slug}" data-qfunnel-host="${host}" data-qfunnel-mode="inline" data-qfunnel-height="720px"></div>\n<script src="${host}/assets/js/embed.js" async></script>`;
    } else if (mode === 'popup') {
      snippet = `<!-- LeadCollector Modal Button -->\n<button data-qfunnel-embed="${slug}" data-qfunnel-host="${host}" data-qfunnel-mode="popup">Start Assessment</button>\n<script src="${host}/assets/js/embed.js" async></script>`;
    } else {
      snippet = `<!-- LeadCollector Floating Trigger Button -->\n<div data-qfunnel-embed="${slug}" data-qfunnel-host="${host}" data-qfunnel-mode="floating"></div>\n<script src="${host}/assets/js/embed.js" async></script>`;
    }

    navigator.clipboard.writeText(snippet);
    setCopiedMode(mode);
    setTimeout(() => setCopiedMode(null), 2000);
  };

  if (loading) {
    return (
      <div className="space-y-6 max-w-4xl">
        <div className="flex items-center gap-2">
          <div className="h-4 w-28 rounded-md skeleton-shimmer" />
        </div>
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 sm:p-8 space-y-6">
          <div className="space-y-2">
            <div className="h-6 w-44 rounded-md skeleton-shimmer" />
            <div className="h-3 w-64 rounded-md skeleton-shimmer" />
          </div>
          <div className="space-y-4 pt-2">
            <div className="h-10 w-full rounded-xl skeleton-shimmer" />
            <div className="h-10 w-full rounded-xl skeleton-shimmer" />
            <div className="h-20 w-full rounded-xl skeleton-shimmer" />
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <Link to="/admin/funnels" className="inline-flex items-center gap-1.5 text-xs text-[#5c6066] hover:text-[#131416] transition">
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Funnels</span>
        </Link>

        <div className="flex items-center gap-2">
          {funnel?.status === 'published' && (
            <a
              href={`/quiz/${slug}`}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#e8eaec] text-xs font-semibold text-[#131416] hover:bg-[#f9fbfc] transition"
            >
              <Eye className="w-3.5 h-3.5 text-[#5c6066]" />
              <span>Preview Live Quiz</span>
            </a>
          )}
          <Link
            to={`/admin/funnels/${id}/builder`}
            className="px-3.5 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
          >
            Open Visual Builder
          </Link>
        </div>
      </div>

      {message && (
        <div
          className={`p-3 rounded-xl text-xs ${
            message.type === 'success'
              ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
              : 'bg-rose-50 border border-rose-200 text-rose-700'
          }`}
        >
          {message.text}
        </div>
      )}

      <form onSubmit={handleSave} className="space-y-6">
        {/* General Details */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
          <h2 className="text-sm font-bold text-[#131416]">General Information</h2>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Funnel Name</label>
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Public Slug</label>
              <div className="flex rounded-xl border border-[#e8eaec] overflow-hidden focus-within:border-[#1b6c80]">
                <span className="px-3 py-2 bg-[#f9fbfc] text-[11px] text-[#5c6066] border-r border-[#e8eaec]">
                  /quiz/
                </span>
                <input
                  type="text"
                  required
                  value={slug}
                  onChange={(e) => setSlug(e.target.value)}
                  className="w-full px-3 py-2 text-xs text-[#131416] focus:outline-none"
                />
              </div>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#131416] mb-1">Description</label>
            <textarea
              rows={2}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
            />
          </div>
        </div>

        {/* Branding & Design */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-bold text-[#131416]">Branding & Quiz Presentation</h2>
            <span className="text-xs text-[#5d5f60]">Custom styling & corner curvature</span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#171A21] mb-1">Brand Accent Color</label>
              <div className="flex items-center gap-2">
                <input
                  type="color"
                  value={accentColor}
                  onChange={(e) => setAccentColor(e.target.value)}
                  className="w-9 h-9 rounded-lg border border-[#E4E7EC] p-0.5 cursor-pointer"
                />
                <input
                  type="text"
                  value={accentColor}
                  onChange={(e) => setAccentColor(e.target.value)}
                  className="w-full px-3 py-1.5 rounded-xl border border-[#E4E7EC] text-xs font-mono"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#171A21] mb-1">Theme</label>
              <select
                value={theme}
                onChange={(e: any) => setTheme(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:outline-none focus:border-[#5B5BD6]"
              >
                <option value="light">Light Theme</option>
                <option value="dark">Dark Theme</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Card Corner Radius</label>
              <select
                value={cardRadius}
                onChange={(e) => setCardRadius(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="rounded-none">Square (Sharp Corners)</option>
                <option value="rounded-lg">Subtle Rounded (8px)</option>
                <option value="rounded-xl">Medium Rounded (12px)</option>
                <option value="rounded-2xl">Modern Curvature (16px - Recommended)</option>
                <option value="rounded-3xl">Pill Soft (24px)</option>
              </select>
            </div>
          </div>

          {/* Typography, Background & Elevation */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Typography Preset</label>
              <select
                value={fontFamily}
                onChange={(e: any) => setFontFamily(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="sans">Modern Tech & SaaS (Inter / Plus Jakarta)</option>
                <option value="serif">Editorial & High-Ticket (Merriweather / Serif)</option>
                <option value="geometric">Startup Modern (Geometric)</option>
                <option value="system">Clean Native (System UI)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Background Atmosphere</label>
              <select
                value={backgroundStyle}
                onChange={(e: any) => setBackgroundStyle(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="default">Clean Studio (Minimal Off-White)</option>
                <option value="dots">Modern Dot Grid Canvas</option>
                <option value="warm">Warm Neutral Cream (High-Ticket)</option>
                <option value="dark">Midnight Dark Slate</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Card Container Elevation</label>
              <select
                value={cardElevation}
                onChange={(e: any) => setCardElevation(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="flat">Flat Minimal (Clean 1px Border)</option>
                <option value="soft">Soft Diffused (Standard SaaS)</option>
                <option value="floating">Deep Floating (High-Converting Depth)</option>
              </select>
            </div>
          </div>

          {/* Logo & Position */}
          <div className="pt-2 border-t border-[#f0f1f2] space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Company / Brand Name</label>
                <input
                  type="text"
                  value={companyName}
                  onChange={(e) => setCompanyName(e.target.value)}
                  placeholder="e.g. Apex Health & Wellness"
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Brand Logo</label>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                  onChange={handleLogoFileChange}
                  className="hidden"
                />

                {logoUrl ? (
                  <div className="flex items-center gap-2 p-1.5 rounded-xl border border-[#e8eaec] bg-[#f9fbfc]">
                    <div className="h-8 w-16 bg-white rounded-lg border border-[#e8eaec] flex items-center justify-center p-1 overflow-hidden shrink-0">
                      <img
                        src={resolveMediaUrl(logoUrl)}
                        alt="Logo Preview"
                        className="max-h-full max-w-full object-contain"
                        onError={(e) => {
                          (e.target as HTMLElement).style.display = 'none';
                        }}
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <span className="block text-[11px] font-semibold text-[#131416] truncate">
                        Logo active
                      </span>
                    </div>
                    <div className="flex items-center gap-1 shrink-0">
                      <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        disabled={uploadingLogo}
                        className="px-2 py-1 text-[10px] font-semibold text-[#1d5fd6] bg-[#eef4ff] hover:bg-[#dbe7ff] rounded-lg transition disabled:opacity-50"
                      >
                        {uploadingLogo ? <Loader2 className="w-3 h-3 animate-spin inline mr-1" /> : <Upload className="w-3 h-3 inline mr-1" />}
                        Replace
                      </button>
                      <button
                        type="button"
                        onClick={handleRemoveLogo}
                        disabled={uploadingLogo}
                        className="p-1 text-[#5d5f60] hover:text-[#b91c1c] hover:bg-rose-50 rounded-lg transition"
                        title="Remove logo"
                      >
                        <X className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                ) : (
                  <div>
                    <button
                      type="button"
                      onClick={() => fileInputRef.current?.click()}
                      disabled={uploadingLogo}
                      className="w-full h-[38px] flex items-center justify-center gap-1.5 px-3 rounded-xl border border-dashed border-[#cbd5e1] hover:border-[#1d5fd6] hover:bg-[#f8fafc] text-xs font-semibold text-[#1d5fd6] transition disabled:opacity-50"
                    >
                      {uploadingLogo ? (
                        <>
                          <Loader2 className="w-3.5 h-3.5 animate-spin" />
                          <span>Uploading...</span>
                        </>
                      ) : (
                        <>
                          <Upload className="w-3.5 h-3.5" />
                          <span>Upload Logo File</span>
                        </>
                      )}
                    </button>
                  </div>
                )}
                {uploadError && (
                  <p className="text-[10px] text-[#b91c1c] mt-1 font-medium">{uploadError}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Logo Placement</label>
                <select
                  value={logoPosition}
                  onChange={(e: any) => setLogoPosition(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
                >
                  <option value="left">Left Aligned (Standard)</option>
                  <option value="center">Center Aligned</option>
                  <option value="right">Right Aligned</option>
                </select>
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="text-xs font-semibold text-[#131416]">Logo Max Height</label>
                <span className="text-xs font-mono text-[#5d5f60]">{logoHeight}px</span>
              </div>
              <input
                type="range"
                min={24}
                max={90}
                value={logoHeight}
                onChange={(e) => setLogoHeight(Number(e.target.value))}
                className="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#1d5fd6]"
              />
            </div>
          </div>
        </div>

        {/* CTA & Button Optimization (CRO) */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-2">
                <MousePointerClick className="w-4 h-4 text-[#1d5fd6]" />
                <h2 className="text-sm font-bold text-[#131416]">Call-To-Action (CTA) & Button Optimization</h2>
              </div>
              <p className="text-xs text-[#5d5f60] mt-0.5">Maximize click-through and form completions with high-converting button styles</p>
            </div>
            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eef4ff] text-[#1d5fd6]">
              Conversion Optimization
            </span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Button Corner Radius</label>
              <select
                value={buttonRadius}
                onChange={(e) => setButtonRadius(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="rounded-md">Subtle Sharp (6px)</option>
                <option value="rounded-xl">Standard Modern (12px - Recommended)</option>
                <option value="rounded-2xl">Soft Curvature (16px)</option>
                <option value="rounded-full">Full Pill (High Click-Through)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Button Visual Style</label>
              <select
                value={buttonStyle}
                onChange={(e: any) => setButtonStyle(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1d5fd6]"
              >
                <option value="solid">High-Contrast Solid</option>
                <option value="glow">Soft Radiant Glow</option>
                <option value="outline">Clean Modern Outline</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Default CTA Button Text</label>
              <input
                type="text"
                value={ctaButtonText}
                onChange={(e) => setCtaButtonText(e.target.value)}
                placeholder="e.g. Continue → or Get Instant Quote →"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>
          </div>
        </div>

        {/* Social Proof & Urgency */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-amber-500" />
                <h2 className="text-sm font-bold text-[#131416]">Social Proof, Urgency & Trust Badges</h2>
              </div>
              <p className="text-xs text-[#5d5f60] mt-0.5">Build immediate credibility and FOMO to boost lead completion rates</p>
            </div>
            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#fef3c7] text-[#b45309]">
              Social Proof
            </span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">
                Live Social Proof / Urgency Pill
              </label>
              <input
                type="text"
                value={socialProofBadge}
                onChange={(e) => setSocialProofBadge(e.target.value)}
                placeholder="e.g. Rated 4.9/5 by 2,400+ clients or 18 people completed this today"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
              <span className="text-[11px] text-[#5d5f60] mt-1 block">
                Rendered as a prominent social badge at the top of the assessment card.
              </span>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">
                Security & Trust Seal Text
              </label>
              <input
                type="text"
                value={trustBadgeText}
                onChange={(e) => setTrustBadgeText(e.target.value)}
                placeholder="e.g. 256-Bit SSL Encrypted • 100% Privacy Guaranteed"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
              <span className="text-[11px] text-[#5d5f60] mt-1 block">
                Displayed with a verified shield seal beneath the funnel container.
              </span>
            </div>
          </div>
        </div>

        {/* Marketing Tracking & Ad Attribution Pixels */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-2">
                <Activity className="w-4 h-4 text-[#1d5fd6]" />
                <h2 className="text-sm font-bold text-[#131416]">Ad Pixels & Conversion Tracking</h2>
                {!hasTrackingPixels && (
                  <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                    PRO
                  </span>
                )}
              </div>
              <p className="text-xs text-[#5d5f60] mt-0.5">Track PageView and Lead conversion events automatically across your paid ad campaigns</p>
            </div>
            {!hasTrackingPixels ? (
              <button
                type="button"
                onClick={() => {
                  setUpgradeFeature({
                    name: 'Ad Tracking Pixels (Meta, Google, TikTok)',
                    desc: 'Automatically inject Meta Pixel, Google Analytics 4, and TikTok Pixel IDs to track quiz starts and lead conversion events on ad platforms.',
                    plan: 'Pro',
                  });
                  setUpgradeModalOpen(true);
                }}
                className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#1d5fd6] text-white hover:bg-[#1853bf] transition shadow-xs"
              >
                Upgrade to Pro
              </button>
            ) : (
              <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#dcfae5] text-[#2f7a45]">
                Ad ROI
              </span>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Meta (Facebook) Pixel ID</label>
              <input
                type="text"
                value={fbPixelId}
                onChange={(e) => setFbPixelId(e.target.value)}
                placeholder="e.g. 123456789012345"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
              <span className="text-[11px] text-[#5d5f60] mt-1 block">
                Automatically fires PageView and Lead events.
              </span>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Google Tag / GA4 ID</label>
              <input
                type="text"
                value={googleTagId}
                onChange={(e) => setGoogleTagId(e.target.value)}
                placeholder="e.g. G-XXXXXXXXXX or AW-XXXXXXXXXX"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
              <span className="text-[11px] text-[#5d5f60] mt-1 block">
                Tracks pageviews & generate_lead conversion goal.
              </span>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">TikTok Pixel ID</label>
              <input
                type="text"
                value={tiktokPixelId}
                onChange={(e) => setTiktokPixelId(e.target.value)}
                placeholder="e.g. CXXXXXXXXXXXXXXX"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
              <span className="text-[11px] text-[#5d5f60] mt-1 block">
                Fires PageView and SubmitForm for TikTok Ads.
              </span>
            </div>
          </div>
        </div>

        {/* SEO & Social Share Preview */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-2">
                <Share2 className="w-4 h-4 text-[#1d5fd6]" />
                <h2 className="text-sm font-bold text-[#131416]">SEO & Social Share Optimization</h2>
              </div>
              <p className="text-xs text-[#5d5f60] mt-0.5">Control how your funnel appears when shared on WhatsApp, Facebook, LinkedIn, and search engines</p>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Meta Title</label>
              <input
                type="text"
                value={metaTitle}
                onChange={(e) => setMetaTitle(e.target.value)}
                placeholder="e.g. Free 60-Second Solar Savings & ROI Calculator"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Meta Description</label>
              <input
                type="text"
                value={metaDescription}
                onChange={(e) => setMetaDescription(e.target.value)}
                placeholder="e.g. Calculate your exact solar savings and get an instant custom quote in under 2 minutes."
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>
          </div>
        </div>

        {/* 100% White-Labeling & Legal Controls */}
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-5">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-sm font-bold text-[#131416]">100% White-Labeling & Legal Footers</h2>
              <p className="text-xs text-[#5d5f60] mt-0.5">Custom copyright notice, legal terms, and watermark removal</p>
            </div>
            {hasWhiteLabel ? (
              <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-[#dcfae5] text-[#2f7a45] uppercase tracking-wider">
                Unlocked
              </span>
            ) : (
              <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-[#fef3c7] text-[#b45309] uppercase tracking-wider">
                Pro Plan Required
              </span>
            )}
          </div>

          {/* White-Label Toggle Box */}
          <div
            className={`p-4 rounded-xl border transition ${
              hasWhiteLabel
                ? 'border-[#bbf7d0] bg-[#f0fdf4]'
                : 'border-[#fed7aa] bg-[#fffbeb]'
            }`}
          >
            <label className={`flex items-start gap-3 ${hasWhiteLabel ? 'cursor-pointer' : 'opacity-80'}`}>
              <input
                type="checkbox"
                disabled={!hasWhiteLabel}
                checked={hasWhiteLabel && whiteLabel}
                onChange={(e) => setWhiteLabel(e.target.checked)}
                className="w-4 h-4 mt-0.5 rounded text-[#2f7a45] focus:ring-[#2f7a45]"
              />
              <div className="flex-1 text-xs">
                <div className="font-bold text-[#131416]">
                  Enable 100% White-Labeling
                </div>
                <p className="text-[#5d5f60] mt-0.5 leading-relaxed">
                  Completely removes all "Powered by LeadCollector" watermarks, badges, and platform links from public quiz and calculator funnels.
                </p>
                {!hasWhiteLabel && (
                  <p className="text-[#92400e] font-semibold mt-1.5 flex items-center gap-1.5">
                    <span>White-labeling is a Pro Plan feature.</span>
                    <button
                      type="button"
                      onClick={() => {
                        setUpgradeFeature({
                          name: '100% Complete White-Labeling',
                          desc: 'Completely remove all "Powered by LeadCollector" watermarks, platform badges, and footer links from your public quizzes.',
                          plan: 'Pro',
                        });
                        setUpgradeModalOpen(true);
                      }}
                      className="text-[#1d5fd6] underline hover:text-[#1853bf] cursor-pointer"
                    >
                      Upgrade workspace plan &rarr;
                    </button>
                  </p>
                )}
              </div>
            </label>
          </div>

          {/* Custom Footer & Legal URLs */}
          <div className="space-y-4 pt-2">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">
                Custom Footer Copyright / Notice
              </label>
              <input
                type="text"
                value={footerText}
                onChange={(e) => setFooterText(e.target.value)}
                placeholder="e.g. © 2026 Apex Health Clinic. All rights reserved."
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">
                Custom Social Proof / Trust Badge
              </label>
              <input
                type="text"
                value={trustBadgeText}
                onChange={(e) => setTrustBadgeText(e.target.value)}
                placeholder="e.g. 256-Bit SSL Encrypted • 100% Privacy Guaranteed"
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Privacy Policy URL</label>
                <input
                  type="url"
                  value={privacyUrl}
                  onChange={(e) => setPrivacyUrl(e.target.value)}
                  placeholder="https://apexhealth.org/privacy"
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Terms of Service URL</label>
                <input
                  type="url"
                  value={termsUrl}
                  onChange={(e) => setTermsUrl(e.target.value)}
                  placeholder="https://apexhealth.org/terms"
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1d5fd6]"
                />
              </div>
            </div>

            {/* Step Progress Bar Setting Card */}
            <label className="p-4 rounded-xl border border-[#e8eaec] bg-[#f9fbfc] flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-slate-50 transition">
              <div className="space-y-0.5">
                <span className="text-xs font-semibold text-[#131416] block">
                  Display Step Progress Bar & Counter
                </span>
                <p className="text-[11px] text-[#5d5f60] leading-relaxed">
                  Shows the top progress bar and step tracker (e.g. Step 1 of 5 • 20% Completed) in the public quiz runner. Enabled by default for higher completion rates.
                </p>
              </div>
              <div className="relative inline-flex items-center shrink-0">
                <input
                  type="checkbox"
                  checked={showProgressBar}
                  onChange={(e) => setShowProgressBar(e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1d5fd6]"></div>
              </div>
            </label>
          </div>
        </div>

        <div className="flex items-center justify-end">
          <button
            type="submit"
            disabled={saving}
            className="px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition disabled:opacity-50 flex items-center gap-2 shadow-xs"
          >
            {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
            <span>{saving ? 'Saving...' : 'Save Settings'}</span>
          </button>
        </div>
      </form>

      {/* Embed Code Section */}
      <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
        <div className="flex items-center gap-2">
          <Code className="w-5 h-5 text-[#1b6c80]" />
          <h2 className="text-sm font-bold text-[#131416]">Embed on Any Website</h2>
        </div>
        <p className="text-xs text-[#5c6066]">
          Embed this funnel on WordPress, Webflow, Shopify, Squarespace, or custom HTML.
        </p>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="p-4 rounded-xl border border-[#e8eaec] bg-[#f9fbfc] flex flex-col justify-between">
            <div>
              <h4 className="text-xs font-bold text-[#131416]">Inline iFrame</h4>
              <p className="text-[11px] text-[#5c6066] mt-1">Embeds directly inside your page content.</p>
            </div>
            <button
              type="button"
              onClick={() => copyEmbedCode('inline')}
              className="mt-3 w-full py-1.5 px-3 rounded-lg border border-[#e8eaec] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f0f1f2] flex items-center justify-center gap-1.5"
            >
              {copiedMode === 'inline' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copiedMode === 'inline' ? 'Copied!' : 'Copy Code'}</span>
            </button>
          </div>

          <div className="p-4 rounded-xl border border-[#e8eaec] bg-[#f9fbfc] flex flex-col justify-between">
            <div>
              <h4 className="text-xs font-bold text-[#131416]">Popup Modal</h4>
              <p className="text-[11px] text-[#5c6066] mt-1">Triggers in an overlay modal when clicking a button.</p>
            </div>
            <button
              type="button"
              onClick={() => copyEmbedCode('popup')}
              className="mt-3 w-full py-1.5 px-3 rounded-lg border border-[#e8eaec] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f0f1f2] flex items-center justify-center gap-1.5"
            >
              {copiedMode === 'popup' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copiedMode === 'popup' ? 'Copied!' : 'Copy Code'}</span>
            </button>
          </div>

          <div className="p-4 rounded-xl border border-[#e8eaec] bg-[#f9fbfc] flex flex-col justify-between">
            <div>
              <h4 className="text-xs font-bold text-[#131416]">Floating Trigger Widget</h4>
              <p className="text-[11px] text-[#5c6066] mt-1">Fixed floating bubble at the bottom corner of the site.</p>
            </div>
            <button
              type="button"
              onClick={() => copyEmbedCode('floating')}
              className="mt-3 w-full py-1.5 px-3 rounded-lg border border-[#e8eaec] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f0f1f2] flex items-center justify-center gap-1.5"
            >
              {copiedMode === 'floating' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copiedMode === 'floating' ? 'Copied!' : 'Copy Code'}</span>
            </button>
          </div>
        </div>
      </div>

      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName={upgradeFeature.name}
        description={upgradeFeature.desc}
        requiredPlan={upgradeFeature.plan}
      />
    </div>
  );
};


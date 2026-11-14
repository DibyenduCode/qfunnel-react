import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { SmtpSettings, EmailTemplate, EmailLog } from '../../types';
import { TablePagination } from '../../components/TablePagination';
import {
  Mail,
  Server,
  FileCode,
  History,
  Save,
  Send,
  RotateCcw,
  CheckCircle2,
  AlertTriangle,
  X,
  Eye,
  EyeOff,
  Search,
  ExternalLink,
  Code2,
  ShieldCheck,
  Tag,
  Loader2,
  AlertCircle
} from 'lucide-react';

export const SuperadminEmailSettings: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'smtp' | 'templates' | 'logs'>('smtp');
  const [loading, setLoading] = useState(true);
  const [savingSmtp, setSavingSmtp] = useState(false);
  const [testingSmtp, setTestingSmtp] = useState(false);
  const [savingTemplate, setSavingTemplate] = useState(false);
  const [sendingPreview, setSendingPreview] = useState(false);

  const [msg, setMsg] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  // SMTP Settings State
  const [smtp, setSmtp] = useState<SmtpSettings>({
    enabled: false,
    host: 'smtp.gmail.com',
    port: 587,
    encryption: 'tls',
    username: '',
    password: '',
    has_password: false,
    from_email: 'no-reply@leadcollector.in',
    from_name: 'LeadCollector Platform',
    reply_to: 'support@leadcollector.in',
  });
  const [showPassword, setShowPassword] = useState(false);
  const [testRecipient, setTestRecipient] = useState('');
  const [testFeedback, setTestFeedback] = useState<{ success: boolean; message: string } | null>(null);

  // Email Templates State
  const [templates, setTemplates] = useState<EmailTemplate[]>([]);
  const [selectedTemplateKey, setSelectedTemplateKey] = useState<string>('email_verification_otp');
  const [currentTemplate, setCurrentTemplate] = useState<EmailTemplate | null>(null);
  const [templatePreviewTab, setTemplatePreviewTab] = useState<'source' | 'preview'>('preview');
  const [previewRecipient, setPreviewRecipient] = useState('');

  // Logs State
  const [logs, setLogs] = useState<EmailLog[]>([]);
  const [logSearch, setLogSearch] = useState('');
  const [logsPage, setLogsPage] = useState(1);
  const [logsPageSize, setLogsPageSize] = useState(15);
  const [selectedLogForDetails, setSelectedLogForDetails] = useState<EmailLog | null>(null);

  // Stats
  const [stats, setStats] = useState({
    total_sent: 0,
    total_simulated: 0,
    total_failed: 0,
    active_templates: 0,
  });

  useEffect(() => {
    loadEmailData();
  }, []);

  const loadEmailData = async () => {
    try {
      setLoading(true);
      setErrorMsg('');
      const data = await api.get('/api/platform/email-settings');
      if (data.smtp) {
        setSmtp(data.smtp);
      }
      if (data.templates && Array.isArray(data.templates)) {
        setTemplates(data.templates);
        const match = data.templates.find((t: EmailTemplate) => t.template_key === selectedTemplateKey) || data.templates[0];
        if (match) {
          setSelectedTemplateKey(match.template_key);
          setCurrentTemplate({ ...match });
        }
      }
      if (data.logs) {
        setLogs(data.logs);
      }
      if (data.stats) {
        setStats(data.stats);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load email configuration');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectTemplate = (tpl: EmailTemplate) => {
    setSelectedTemplateKey(tpl.template_key);
    setCurrentTemplate({ ...tpl });
    setTestFeedback(null);
  };

  const handleSaveSmtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setSavingSmtp(true);
    setMsg('');
    setErrorMsg('');
    setTestFeedback(null);

    try {
      await api.post('/api/platform/email-settings/smtp', smtp);
      setMsg('Global SMTP settings saved successfully.');
      await loadEmailData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to save SMTP settings');
    } finally {
      setSavingSmtp(false);
    }
  };

  const handleTestSmtp = async () => {
    if (!testRecipient) {
      alert('Please enter a recipient email address to send the test message.');
      return;
    }
    setTestingSmtp(true);
    setTestFeedback(null);
    try {
      const res = await api.post('/api/platform/email-settings/test-smtp', { email: testRecipient });
      setTestFeedback({ success: true, message: res.message || 'Test message sent successfully.' });
      loadEmailData();
    } catch (err: any) {
      setTestFeedback({ success: false, message: err.message || 'SMTP connection test failed.' });
    } finally {
      setTestingSmtp(false);
    }
  };

  const handleSaveTemplate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!currentTemplate) return;
    setSavingTemplate(true);
    setMsg('');
    setErrorMsg('');

    try {
      await api.post(`/api/platform/email-settings/templates/${currentTemplate.template_key}`, currentTemplate);
      setMsg(`Template '${currentTemplate.name}' saved.`);
      await loadEmailData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to save template');
    } finally {
      setSavingTemplate(false);
    }
  };

  const handleResetTemplate = async () => {
    if (!currentTemplate) return;
    if (!confirm(`Are you sure you want to reset '${currentTemplate.name}' to its default subject and HTML content?`)) {
      return;
    }
    try {
      setSavingTemplate(true);
      await api.post(`/api/platform/email-settings/templates/${currentTemplate.template_key}/reset`, {});
      setMsg(`Template '${currentTemplate.name}' restored to defaults.`);
      await loadEmailData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to reset template');
    } finally {
      setSavingTemplate(false);
    }
  };

  const handleSendPreviewEmail = async () => {
    if (!currentTemplate) return;
    if (!previewRecipient) {
      alert('Please enter an email address to receive the preview.');
      return;
    }
    setSendingPreview(true);
    try {
      const res = await api.post(`/api/platform/email-settings/templates/${currentTemplate.template_key}/preview-test`, {
        email: previewRecipient,
      });
      alert(res.message || 'Preview email sent!');
      loadEmailData();
    } catch (err: any) {
      alert(err.message || 'Failed to dispatch preview email');
    } finally {
      setSendingPreview(false);
    }
  };

  const insertTagIntoTemplate = (tag: string) => {
    if (!currentTemplate) return;
    const tagText = `{{${tag}}}`;
    setCurrentTemplate({
      ...currentTemplate,
      body_html: currentTemplate.body_html + tagText,
    });
  };

  // Sample data for live HTML preview
  const sampleTags: Record<string, string> = {
    app_name: 'LeadCollector',
    app_url: 'https://leadcollector.in',
    dashboard_url: 'https://leadcollector.in/dashboard',
    login_url: 'https://leadcollector.in/login',
    support_email: 'support@leadcollector.in',
    current_year: '2026',
    user_name: 'Sarah Connor',
    user_email: 'sarah.connor@apexstudio.io',
    otp_code: '492817',
    otp_expiry_minutes: '15',
    tenant_name: 'Apex Growth Lab',
    workspace_slug: 'apex-growth',
    invoice_number: 'INV-2026-0042',
    plan_name: 'Pro Unlimited',
    amount: '4,999.00',
    currency_symbol: '₹',
    payment_method: 'UPI / NetBanking',
    billing_interval: 'monthly',
    paid_at: '2026-11-14 13:40',
    due_date: '2026-12-14',
    suspension_reason: 'Overdue payment grace period expired',
  };

  const renderLivePreviewHtml = () => {
    if (!currentTemplate) return '';
    let rendered = currentTemplate.body_html;
    Object.entries(sampleTags).forEach(([key, val]) => {
      rendered = rendered.split(`{{${key}}}`).join(val);
      rendered = rendered.split(`{{ ${key} }}`).join(val);
    });
    return rendered;
  };

  // Filter and paginate logs
  const filteredLogs = logs.filter((l) => {
    const q = logSearch.toLowerCase();
    return (
      l.recipient_email.toLowerCase().includes(q) ||
      (l.template_key && l.template_key.toLowerCase().includes(q)) ||
      l.subject.toLowerCase().includes(q) ||
      l.status.toLowerCase().includes(q)
    );
  });

  const paginatedLogs = filteredLogs.slice(
    (logsPage - 1) * logsPageSize,
    logsPage * logsPageSize
  );

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#fef3c7] text-[#b45309]">
              <Mail className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Email & Global SMTP Configuration</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Global outbound SMTP server settings, transactional email templates, OTP verification, and delivery outbox audit.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <span className="px-3 py-1 rounded-full bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416]">
            {stats.total_sent} Sent • {stats.total_simulated} Simulated • {stats.total_failed} Failed
          </span>
        </div>
      </div>

      {msg && (
        <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 shrink-0" />
            <span>{msg}</span>
          </div>
          <button type="button" onClick={() => setMsg('')} className="text-[#2f7a45] hover:opacity-75">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {errorMsg && (
        <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{errorMsg}</span>
          </div>
          <button type="button" onClick={() => setErrorMsg('')} className="text-[#991b1b] hover:opacity-75">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Navigation Tabs */}
      <div className="border-b border-[#e2e5e9] flex gap-2 sm:gap-6 text-xs font-semibold overflow-x-auto">
        <button
          type="button"
          onClick={() => setActiveTab('smtp')}
          className={`pb-3 border-b-2 transition whitespace-nowrap flex items-center gap-1.5 ${
            activeTab === 'smtp'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          <Server className="w-3.5 h-3.5" />
          <span>Global SMTP Server</span>
          {smtp.enabled ? (
            <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-[#dcfae5] text-[#2f7a45] font-bold">Active</span>
          ) : (
            <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-[#f4f6f8] text-[#5d5f60]">Simulated</span>
          )}
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('templates')}
          className={`pb-3 border-b-2 transition whitespace-nowrap flex items-center gap-1.5 ${
            activeTab === 'templates'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          <FileCode className="w-3.5 h-3.5" />
          <span>Email Templates ({templates.length})</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('logs')}
          className={`pb-3 border-b-2 transition whitespace-nowrap flex items-center gap-1.5 ${
            activeTab === 'logs'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          <History className="w-3.5 h-3.5" />
          <span>Outbox Delivery Logs ({logs.length})</span>
        </button>
      </div>

      {/* ======================================================== */}
      {/* TAB 1: GLOBAL SMTP SETTINGS */}
      {/* ======================================================== */}
      {activeTab === 'smtp' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main SMTP Form */}
          <div className="lg:col-span-2 bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-6">
            <div className="flex items-center justify-between pb-4 border-b border-[#edf0f3]">
              <div>
                <h2 className="text-sm font-bold text-[#131416]">SMTP Server Credentials</h2>
                <p className="text-xs text-[#5d5f60]">
                  Configure your production mail gateway (Google Workspace, Mailgun, SendGrid, Amazon SES, or custom relay).
                </p>
              </div>

              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={smtp.enabled}
                  onChange={(e) => setSmtp({ ...smtp, enabled: e.target.checked })}
                  className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
                />
                <span className="text-xs font-semibold text-[#131416]">
                  {smtp.enabled ? 'Live SMTP Active' : 'Simulation Mode'}
                </span>
              </label>
            </div>

            {!smtp.enabled && (
              <div className="p-3.5 rounded-xl bg-[#eff6ff] border border-[#bfdbfe] text-xs text-[#1d5fd6] flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 shrink-0 text-[#1d5fd6]" />
                <span>
                  <strong>Simulation Mode Active:</strong> When disabled, emails are not sent over the wire but are cleanly recorded in the Outbox Logs ledger. In local development, OTPs are also shown directly in the UI.
                </span>
              </div>
            )}

            <form onSubmit={handleSaveSmtp} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="sm:col-span-2">
                  <label className="block text-xs font-bold text-[#131416] mb-1">SMTP Host</label>
                  <input
                    type="text"
                    required={smtp.enabled}
                    placeholder="e.g. smtp.gmail.com or smtp.sendgrid.net"
                    value={smtp.host}
                    onChange={(e) => setSmtp({ ...smtp, host: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Port</label>
                  <input
                    type="number"
                    required
                    value={smtp.port}
                    onChange={(e) => setSmtp({ ...smtp, port: Number(e.target.value) })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">Encryption Protocol</label>
                  <select
                    value={smtp.encryption}
                    onChange={(e) => setSmtp({ ...smtp, encryption: e.target.value as any })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  >
                    <option value="tls">TLS (STARTTLS - Port 587)</option>
                    <option value="ssl">SSL (SMTPS - Port 465)</option>
                    <option value="none">None (Plain - Port 25)</option>
                  </select>
                </div>

                <div className="sm:col-span-2">
                  <label className="block text-xs font-bold text-[#131416] mb-1">SMTP Username</label>
                  <input
                    type="text"
                    placeholder="e.g. apikey or user@company.com"
                    value={smtp.username}
                    onChange={(e) => setSmtp({ ...smtp, username: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">
                  SMTP Password / API Key
                  {smtp.has_password && !smtp.password && (
                    <span className="ml-2 font-normal text-[11px] text-[#2f7a45]">
                      (Saved in database)
                    </span>
                  )}
                </label>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    placeholder={smtp.has_password ? '•••••••••••••••• (Leave blank to keep current)' : 'Enter SMTP password or API token'}
                    value={smtp.password || ''}
                    onChange={(e) => setSmtp({ ...smtp, password: e.target.value })}
                    className="w-full pl-3 pr-10 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-[#5d5f60] hover:text-[#131416]"
                  >
                    {showPassword ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                  </button>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-[#edf0f3]">
                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">From Sender Email</label>
                  <input
                    type="email"
                    required
                    placeholder="no-reply@yourdomain.com"
                    value={smtp.from_email}
                    onChange={(e) => setSmtp({ ...smtp, from_email: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-[#131416] mb-1">From Sender Name</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. LeadCollector Cloud Notifications"
                    value={smtp.from_name}
                    onChange={(e) => setSmtp({ ...smtp, from_name: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Reply-To Address</label>
                <input
                  type="email"
                  placeholder="support@yourdomain.com"
                  value={smtp.reply_to}
                  onChange={(e) => setSmtp({ ...smtp, reply_to: e.target.value })}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <div className="pt-4 flex items-center justify-end">
                <button
                  type="submit"
                  disabled={savingSmtp}
                  className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] transition shadow-xs disabled:opacity-50"
                >
                  {savingSmtp ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                  <span>Save SMTP Configuration</span>
                </button>
              </div>
            </form>
          </div>

          {/* Test Dispatch Sidepanel */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-5 h-fit">
            <div>
              <h2 className="text-sm font-bold text-[#131416]">Test SMTP Outbound</h2>
              <p className="text-xs text-[#5d5f60] mt-0.5">
                Dispatch an immediate test email to verify live handshake, authentication, and port connectivity.
              </p>
            </div>

            <div className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-[#131416] mb-1">Test Recipient Address</label>
                <input
                  type="email"
                  placeholder="admin@yourcompany.com"
                  value={testRecipient}
                  onChange={(e) => setTestRecipient(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                />
              </div>

              <button
                type="button"
                onClick={handleTestSmtp}
                disabled={testingSmtp || !testRecipient}
                className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs font-semibold text-white bg-[#131416] hover:bg-[#25272a] transition disabled:opacity-50"
              >
                {testingSmtp ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Send className="w-3.5 h-3.5" />}
                <span>Send Diagnostic Test Email</span>
              </button>

              {testFeedback && (
                <div
                  className={`p-3 rounded-xl border text-xs space-y-1 ${
                    testFeedback.success
                      ? 'bg-[#dcfae5] border-[#bbf7d0] text-[#166534]'
                      : 'bg-[#fef2f2] border-[#fecaca] text-[#991b1b]'
                  }`}
                >
                  <p className="font-bold flex items-center gap-1.5">
                    {testFeedback.success ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
                    <span>{testFeedback.success ? 'Success' : 'Connection Error'}</span>
                  </p>
                  <p className="text-[11px] leading-relaxed break-words">{testFeedback.message}</p>
                </div>
              )}
            </div>

            <div className="pt-4 border-t border-[#edf0f3] space-y-2 text-xs text-[#5d5f60]">
              <h3 className="font-bold text-[#131416] text-xs">Common Mail Server Ports:</h3>
              <ul className="space-y-1 text-[11px]">
                <li>• <strong>587 (TLS):</strong> Recommended for SendGrid, Gmail, Mailgun</li>
                <li>• <strong>465 (SSL):</strong> Encrypted direct socket connection</li>
                <li>• <strong>25 (Plain):</strong> Standard unencrypted relay port</li>
              </ul>
            </div>
          </div>
        </div>
      )}

      {/* ======================================================== */}
      {/* TAB 2: EMAIL TEMPLATES CMS */}
      {/* ======================================================== */}
      {activeTab === 'templates' && (
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          {/* Template Selector List */}
          <div className="lg:col-span-1 space-y-2">
            <h2 className="text-xs font-bold text-[#5d5f60] px-2">
              System Templates
            </h2>
            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-2 shadow-xs space-y-1">
              {templates.map((tpl) => {
                const isSelected = tpl.template_key === selectedTemplateKey;
                return (
                  <button
                    key={tpl.template_key}
                    type="button"
                    onClick={() => handleSelectTemplate(tpl)}
                    className={`w-full text-left p-3 rounded-xl transition ${
                      isSelected
                        ? 'bg-[#1d5fd6] text-white shadow-xs'
                        : 'hover:bg-[#f8fafc] text-[#131416]'
                    }`}
                  >
                    <div className="font-bold text-xs">{tpl.name}</div>
                    <div className={`text-[11px] truncate mt-0.5 ${isSelected ? 'text-blue-100' : 'text-[#5d5f60]'}`}>
                      {tpl.subject}
                    </div>
                  </button>
                );
              })}
            </div>

            {/* Available Tags Helper */}
            {currentTemplate && currentTemplate.available_tags && (
              <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2.5">
                <div className="flex items-center gap-1.5 text-xs font-bold text-[#131416]">
                  <Tag className="w-3.5 h-3.5 text-[#1d5fd6]" />
                  <span>Available Placeholders</span>
                </div>
                <p className="text-[11px] text-[#5d5f60]">
                  Click any tag to append it into your template body:
                </p>
                <div className="flex flex-wrap gap-1.5">
                  {currentTemplate.available_tags.map((tag) => (
                    <button
                      key={tag}
                      type="button"
                      onClick={() => insertTagIntoTemplate(tag)}
                      title={`Insert {{${tag}}}`}
                      className="px-2 py-0.5 rounded-md border border-[#e2e5e9] bg-[#f8fafc] text-[10px] font-mono font-medium text-[#131416] hover:bg-[#eef4ff] hover:border-[#1d5fd6] transition"
                    >
                      {`{{${tag}}}`}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Template Editor & Preview Panel */}
          <div className="lg:col-span-3 bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-5">
            {currentTemplate ? (
              <form onSubmit={handleSaveTemplate} className="space-y-4">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#edf0f3] gap-2">
                  <div>
                    <h2 className="text-sm font-bold text-[#131416]">{currentTemplate.name}</h2>
                    <p className="text-xs text-[#5d5f60]">{currentTemplate.description}</p>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={handleResetTemplate}
                      disabled={savingTemplate}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc] transition"
                    >
                      <RotateCcw className="w-3.5 h-3.5" />
                      <span>Reset Default</span>
                    </button>

                    <button
                      type="submit"
                      disabled={savingTemplate}
                      className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154cb3] transition shadow-xs disabled:opacity-50"
                    >
                      {savingTemplate ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                      <span>Save Template</span>
                    </button>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-[#131416] mb-1">Subject Line</label>
                    <input
                      type="text"
                      required
                      value={currentTemplate.subject}
                      onChange={(e) => setCurrentTemplate({ ...currentTemplate, subject: e.target.value })}
                      className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-[#131416] mb-1">Inbox Preheader Text</label>
                    <input
                      type="text"
                      value={currentTemplate.preheader || ''}
                      onChange={(e) => setCurrentTemplate({ ...currentTemplate, preheader: e.target.value })}
                      placeholder="Preview snippet shown next to subject line"
                      className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                    />
                  </div>
                </div>

                {/* Editor vs Live Preview Switcher */}
                <div className="flex items-center justify-between pt-2">
                  <div className="inline-flex p-1 rounded-xl bg-[#f4f6f8] border border-[#e2e5e9]">
                    <button
                      type="button"
                      onClick={() => setTemplatePreviewTab('preview')}
                      className={`px-3 py-1 text-xs font-semibold rounded-lg transition ${
                        templatePreviewTab === 'preview'
                          ? 'bg-white text-[#131416] shadow-xs'
                          : 'text-[#5d5f60] hover:text-[#131416]'
                      }`}
                    >
                      Live Visual Preview
                    </button>
                    <button
                      type="button"
                      onClick={() => setTemplatePreviewTab('source')}
                      className={`px-3 py-1 text-xs font-semibold rounded-lg transition ${
                        templatePreviewTab === 'source'
                          ? 'bg-white text-[#131416] shadow-xs'
                          : 'text-[#5d5f60] hover:text-[#131416]'
                      }`}
                    >
                      HTML Template Source
                    </button>
                  </div>

                  {/* Send Preview to Email */}
                  <div className="flex items-center gap-2">
                    <input
                      type="email"
                      placeholder="send.preview@to.com"
                      value={previewRecipient}
                      onChange={(e) => setPreviewRecipient(e.target.value)}
                      className="w-48 px-2.5 py-1 text-xs rounded-lg border border-[#e2e5e9] bg-[#f8fafc] text-[#131416]"
                    />
                    <button
                      type="button"
                      onClick={handleSendPreviewEmail}
                      disabled={sendingPreview || !previewRecipient}
                      className="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-[#1d5fd6] bg-[#eef4ff] hover:bg-[#dbe7ff] rounded-lg transition disabled:opacity-50"
                    >
                      {sendingPreview ? <Loader2 className="w-3 h-3 animate-spin" /> : <Send className="w-3 h-3" />}
                      <span>Send Preview</span>
                    </button>
                  </div>
                </div>

                {/* Display Editor or Live Preview */}
                {templatePreviewTab === 'source' ? (
                  <div>
                    <textarea
                      rows={14}
                      value={currentTemplate.body_html}
                      onChange={(e) => setCurrentTemplate({ ...currentTemplate, body_html: e.target.value })}
                      className="w-full p-3 font-mono text-xs rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-[#131416] focus:bg-white focus:border-[#1d5fd6] outline-hidden transition"
                    />
                  </div>
                ) : (
                  <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] flex justify-center">
                    <div className="w-full max-w-[560px] bg-white rounded-2xl border border-[#e2e5e9] shadow-xs overflow-hidden">
                      {/* Fake Email Header */}
                      <div className="px-6 py-4 bg-white border-b border-[#edf0f3]">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-xs text-[#1d5fd6] tracking-tight">QuizFunnel</span>
                          <span className="text-[10px] text-[#5d5f60]">Platform Notification</span>
                        </div>
                      </div>
                      {/* Body */}
                      <div
                        className="p-6 text-xs text-[#334155] leading-relaxed"
                        dangerouslySetInnerHTML={{ __html: renderLivePreviewHtml() }}
                      />
                      {/* Fake Email Footer */}
                      <div className="px-6 py-3.5 bg-[#f8fafc] border-t border-[#edf0f3] text-center text-[10px] text-[#64748b]">
                        Sent securely by <strong>QuizFunnel</strong> • Interactive Funnels
                      </div>
                    </div>
                  </div>
                )}
              </form>
            ) : (
              <div className="p-12 text-center text-[#5d5f60]">
                Select a template from the left to edit and preview.
              </div>
            )}
          </div>
        </div>
      )}

      {/* ======================================================== */}
      {/* TAB 3: OUTBOX LOGS LEDGER */}
      {/* ======================================================== */}
      {activeTab === 'logs' && (
        <div className="space-y-4">
          {/* Search bar */}
          <div className="flex items-center justify-between gap-3">
            <div className="relative flex-1 max-w-md">
              <Search className="w-4 h-4 text-[#5d5f60] absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search outbox by recipient, template, or subject..."
                value={logSearch}
                onChange={(e) => {
                  setLogSearch(e.target.value);
                  setLogsPage(1);
                }}
                className="w-full pl-9 pr-3.5 py-2 rounded-xl border border-[#e2e5e9] bg-white text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>
            <span className="text-xs font-semibold text-[#5d5f60]">
              Showing {filteredLogs.length} delivery records
            </span>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden shadow-xs">
            {filteredLogs.length === 0 ? (
              <div className="p-12 text-center text-[#5d5f60] space-y-2">
                <History className="w-8 h-8 mx-auto opacity-50" />
                <p className="text-xs">No email records matching your search.</p>
              </div>
            ) : (
              <>
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse text-xs text-[#5d5f60]">
                    <thead>
                      <tr className="border-b border-[#e2e5e9] bg-[#f8fafc] text-[11px] font-bold text-[#131416]">
                        <th className="py-3 px-4">Recipient</th>
                        <th className="py-3 px-4">Template / Reason</th>
                        <th className="py-3 px-4">Subject</th>
                        <th className="py-3 px-4">Delivery Status</th>
                        <th className="py-3 px-4 text-right">Timestamp</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#edf0f3]">
                      {paginatedLogs.map((log) => (
                        <tr key={log.id} className="hover:bg-[#f8fafc] transition">
                          <td className="py-3 px-4 font-semibold text-[#131416]">
                            {log.recipient_email}
                            {log.recipient_name && (
                              <div className="text-[11px] font-normal text-[#5d5f60]">{log.recipient_name}</div>
                            )}
                          </td>

                          <td className="py-3 px-4 text-[11px]">
                            <span className="font-mono text-[#1d5fd6]">
                              {log.template_name || log.template_key || 'Manual Raw Email'}
                            </span>
                          </td>

                          <td className="py-3 px-4 text-[#131416] font-medium max-w-xs truncate">
                            {log.subject}
                          </td>

                          <td className="py-3 px-4">
                            <span
                              className={`px-2 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1 ${
                                log.status === 'sent'
                                  ? 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                                  : log.status === 'simulated'
                                  ? 'bg-[#eff6ff] text-[#1d5fd6] border border-[#bfdbfe]'
                                  : 'bg-[#fef2f2] text-[#991b1b] border border-[#fecaca]'
                              }`}
                            >
                              {log.status === 'sent' && <CheckCircle2 className="w-3 h-3" />}
                              {log.status === 'simulated' && <ShieldCheck className="w-3 h-3" />}
                              {log.status === 'failed' && <AlertCircle className="w-3 h-3" />}
                              <span className="capitalize">{log.status}</span>
                            </span>
                            {log.error_message && (
                              <button
                                type="button"
                                onClick={() => setSelectedLogForDetails(log)}
                                className="ml-2 text-[10px] text-[#991b1b] underline"
                              >
                                View Error
                              </button>
                            )}
                          </td>

                          <td className="py-3 px-4 text-right text-[11px] text-[#5d5f60]">
                            {log.sent_at ? new Date(log.sent_at).toLocaleString() : '-'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <TablePagination
                  currentPage={logsPage}
                  totalItems={filteredLogs.length}
                  pageSize={logsPageSize}
                  onPageChange={setLogsPage}
                  onPageSizeChange={setLogsPageSize}
                  pageSizeOptions={[15, 30, 50]}
                  itemLabel="outbox records"
                />
              </>
            )}
          </div>
        </div>
      )}

      {/* Error Details Modal */}
      {selectedLogForDetails && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-md w-full p-6 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <h3 className="text-sm font-bold text-[#991b1b] flex items-center gap-1.5">
                <AlertCircle className="w-4 h-4" />
                <span>SMTP Delivery Error Diagnostic</span>
              </h3>
              <button type="button" onClick={() => setSelectedLogForDetails(null)} className="text-[#5d5f60] hover:text-[#131416]">
                <X className="w-4 h-4" />
              </button>
            </div>
            <div className="space-y-2 text-xs">
              <p>
                <strong>Recipient:</strong> {selectedLogForDetails.recipient_email}
              </p>
              <p>
                <strong>Subject:</strong> {selectedLogForDetails.subject}
              </p>
              <div className="p-3 bg-[#fef2f2] border border-[#fecaca] rounded-xl font-mono text-[11px] text-[#991b1b] break-words">
                {selectedLogForDetails.error_message}
              </div>
            </div>
            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={() => setSelectedLogForDetails(null)}
                className="px-4 py-2 rounded-xl border border-[#e2e5e9] text-xs font-semibold hover:bg-[#f8fafc]"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

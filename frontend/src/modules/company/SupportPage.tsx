import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  HelpCircle,
  MessageCircle,
  Mail,
  Phone,
  PhoneCall,
  FileQuestion,
  Cpu,
  Calculator,
  Webhook,
  CreditCard,
  CheckCircle,
  AlertCircle,
  ArrowRight,
  Clock,
  Sparkles,
  Send
} from 'lucide-react';
import { SeoHelmet } from '../../components/SeoHelmet';
import { api } from '../../api/client';

interface SupportChannels {
  email: string;
  phone: string;
  whatsapp: string;
  hours: string;
  description: string;
}

export const SupportPage: React.FC = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    category: 'builder',
    subject: '',
    message: ''
  });

  const [channels, setChannels] = useState<SupportChannels>({
    email: 'support@leadcollector.in',
    phone: '+1 (800) 555-0199',
    whatsapp: '+1 (800) 555-0199',
    hours: 'Monday – Friday, 8:00 AM – 8:00 PM EST',
    description: 'Direct account manager support for Agency Scale plans'
  });

  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [ticketNum, setTicketNum] = useState('');
  const [errorMsg, setErrorMsg] = useState('');

  useEffect(() => {
    let isMounted = true;
    api.get('/api/public/support-channels')
      .then((res: any) => {
        if (isMounted && res?.channels) {
          setChannels(res.channels);
        }
      })
      .catch(() => {});

    return () => { isMounted = false; };
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name.trim() || !formData.email.trim() || !formData.message.trim()) {
      setErrorMsg('Please fill in all required fields.');
      return;
    }

    setSubmitting(true);
    setErrorMsg('');

    try {
      const res = await api.post('/api/public/support-tickets', formData);
      if (res?.ticket) {
        setTicketNum(res.ticket.ticket_num || '');
        setSubmitted(true);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to submit support request. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="py-12 sm:py-20 px-4 sm:px-8 max-w-5xl mx-auto space-y-16">
      <SeoHelmet
        title="Help Center & Support — LeadCollector.in"
        description="Get assistance setting up branching paths, quote calculation rules, or connecting webhook endpoints."
      />

      {/* 1. Header */}
      <div className="text-center space-y-4 max-w-3xl mx-auto">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#dcf1ff] border border-[#b9e6fe] text-xs font-semibold text-[#0a3a80]">
          <HelpCircle className="w-3.5 h-3.5 text-[#1d5fd6]" />
          <span>LeadCollector Help Center & Support</span>
        </div>
        <h1 className="text-3xl sm:text-5xl font-bold tracking-tight text-[#131416]">
          We are here to help your funnels <span className="text-[#1d5fd6]">convert</span>
        </h1>
        <p className="text-sm sm:text-base text-[#5d5f60] leading-relaxed">
          Need assistance setting up branching paths, configuring quote calculation rules, or connecting WhatsApp endpoints? Our technical support engineers are ready to assist.
        </p>
      </div>

      {/* 2. Quick Help Topic Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition shadow-xs">
          <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
            <Cpu className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Visual Builder</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Learn how to link question nodes, set conditional branch gates, and trigger modal previews.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition shadow-xs">
          <div className="w-9 h-9 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
            <Calculator className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Quote Engine</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Construct arithmetic equations with base rates, multipliers, and dynamic currency symbols.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition shadow-xs">
          <div className="w-9 h-9 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
            <Webhook className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Webhooks & Leads</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Dispatch real-time JSON payloads to Zapier, Make, HubSpot, or custom external endpoints.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition shadow-xs">
          <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
            <CreditCard className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Billing & Plans</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Manage your workspace subscription tier, invoices, team seats, and account credentials.
          </p>
        </div>
      </div>

      {/* 3. Main Form & Support Channel Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        {/* Support Ticket Form (2 Cols) */}
        <div className="lg:col-span-2 bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 space-y-6 shadow-xs">
          <div className="space-y-1">
            <h2 className="text-lg font-bold text-[#131416]">Submit a Support Request</h2>
            <p className="text-xs text-[#5d5f60]">
              Our technical team typically responds within 2 hours during active business hours.
            </p>
          </div>

          {errorMsg && (
            <div className="p-3.5 rounded-xl bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}

          {submitted ? (
            <div className="p-8 rounded-2xl bg-[#dcfae5]/30 border border-[#bbf7d0] space-y-4 text-center">
              <div className="w-12 h-12 rounded-full bg-[#2f7a45] text-white flex items-center justify-center mx-auto shadow-xs">
                <CheckCircle className="w-7 h-7" />
              </div>
              <div className="space-y-1">
                <h3 className="text-lg font-bold text-[#131416]">Ticket #{ticketNum} Received</h3>
                <p className="text-xs text-[#5d5f60] max-w-md mx-auto leading-relaxed">
                  Thank you, <span className="font-semibold text-[#131416]">{formData.name}</span>. We have logged your request regarding <span className="font-semibold text-[#131416]">{formData.subject || 'your inquiry'}</span>. Our technical engineers are reviewing your details.
                </p>
              </div>

              {formData.phone && (
                <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-[#bbf7d0] text-xs font-mono text-[#2f7a45]">
                  <Phone className="w-3.5 h-3.5" />
                  <span>Call-back Phone: {formData.phone}</span>
                </div>
              )}

              <div>
                <button
                  type="button"
                  onClick={() => {
                    setSubmitted(false);
                    setTicketNum('');
                    setFormData({ name: '', email: '', phone: '', category: 'builder', subject: '', message: '' });
                  }}
                  className="mt-2 px-5 py-2 rounded-xl bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416] hover:bg-[#f4f6f8] transition shadow-xs"
                >
                  Send Another Message
                </button>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416]">Full Name *</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. Alex Morgan"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416]">Work Email *</label>
                  <input
                    type="email"
                    required
                    placeholder="alex@company.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416] flex items-center justify-between">
                    <span>Phone Number</span>
                    <span className="text-[10px] text-[#8c9096] font-normal">Optional / WhatsApp Call</span>
                  </label>
                  <input
                    type="tel"
                    placeholder="e.g. +1 (555) 019-2834"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition font-mono"
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416]">Category *</label>
                  <select
                    value={formData.category}
                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                  >
                    <option value="builder">Visual Builder & Nodes</option>
                    <option value="calculator">Quote Calculator Engine</option>
                    <option value="webhooks">Webhooks & Integrations</option>
                    <option value="billing">Billing & Subscriptions</option>
                    <option value="enterprise">Enterprise Custom Deployment</option>
                  </select>
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-[#131416]">Subject *</label>
                <input
                  type="text"
                  required
                  placeholder="Brief description of the inquiry"
                  value={formData.subject}
                  onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                  className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                />
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-[#131416]">How can we help? *</label>
                <textarea
                  rows={4}
                  required
                  placeholder="Describe your issue or question in detail. Include funnel URLs if applicable..."
                  value={formData.message}
                  onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                  className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition resize-none"
                />
              </div>

              <button
                type="submit"
                disabled={submitting}
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs disabled:opacity-60"
              >
                <Send className="w-3.5 h-3.5" />
                <span>{submitting ? 'Submitting...' : 'Submit Support Ticket'}</span>
              </button>
            </form>
          )}
        </div>

        {/* Sidebar Direct Channels (Synchronized Live with Super Admin Settings) */}
        <div className="space-y-6">
          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
            <h3 className="font-bold text-sm text-[#131416]">Direct Channels</h3>

            <div className="space-y-3.5 text-xs">
              {/* Direct Email */}
              <div className="flex items-start gap-3">
                <div className="p-2 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] shrink-0">
                  <Mail className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-semibold block text-[#131416]">Email Support</span>
                  <a
                    href={`mailto:${channels.email}`}
                    className="font-mono text-[#1d5fd6] hover:underline text-[11px]"
                  >
                    {channels.email}
                  </a>
                </div>
              </div>

              {/* Direct Phone Number */}
              {channels.phone && (
                <div className="flex items-start gap-3">
                  <div className="p-2 rounded-lg bg-[#dcfae5] text-[#2f7a45] shrink-0">
                    <Phone className="w-4 h-4" />
                  </div>
                  <div>
                    <span className="font-semibold block text-[#131416]">Direct Telephone</span>
                    <a
                      href={`tel:${channels.phone}`}
                      className="font-mono text-[#2f7a45] font-bold hover:underline text-[11px]"
                    >
                      {channels.phone}
                    </a>
                  </div>
                </div>
              )}

              {/* Direct WhatsApp Line */}
              {channels.whatsapp && (
                <div className="flex items-start gap-3">
                  <div className="p-2 rounded-lg bg-[#dcfce7] text-[#16a34a] shrink-0">
                    <MessageCircle className="w-4 h-4" />
                  </div>
                  <div>
                    <span className="font-semibold block text-[#131416]">WhatsApp Enterprise</span>
                    <a
                      href={`https://wa.me/${channels.whatsapp.replace(/[^0-9]/g, '')}`}
                      target="_blank"
                      rel="noreferrer"
                      className="text-[#16a34a] font-mono hover:underline text-[11px] block"
                    >
                      {channels.whatsapp}
                    </a>
                    {channels.description && (
                      <span className="text-[#8c9096] text-[10px] block mt-0.5">
                        {channels.description}
                      </span>
                    )}
                  </div>
                </div>
              )}

              {/* Operating Hours */}
              {channels.hours && (
                <div className="flex items-start gap-3">
                  <div className="p-2 rounded-lg bg-[#fef3c7] text-[#b45309] shrink-0">
                    <Clock className="w-4 h-4" />
                  </div>
                  <div>
                    <span className="font-semibold block text-[#131416]">Hours of Operation</span>
                    <span className="text-[#5d5f60] text-[11px] block">{channels.hours}</span>
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-[#dcf1ff]/30 border border-[#b9e6fe] space-y-3 shadow-xs">
            <div className="flex items-center gap-2 text-xs font-bold text-[#0a3a80]">
              <span className="w-2.5 h-2.5 rounded-full bg-[#2f7a45]" />
              <span>System Status: 99.98% Uptime</span>
            </div>
            <p className="text-xs text-[#0a3a80]/80 leading-relaxed">
              All visual canvas rendering servers, calculation engines, and webhook dispatch queues are operating normally.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  HelpCircle,
  MessageCircle,
  Mail,
  FileQuestion,
  Cpu,
  Calculator,
  Webhook,
  CreditCard,
  CheckCircle,
  ArrowRight,
  Clock,
  Sparkles,
  Send
} from 'lucide-react';

export const SupportPage: React.FC = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    category: 'builder',
    subject: '',
    message: ''
  });
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.message) return;
    setSubmitted(true);
  };

  return (
    <div className="py-12 sm:py-20 px-4 sm:px-8 max-w-5xl mx-auto space-y-16">
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
        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition">
          <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
            <Cpu className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Visual Builder</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Learn how to link question nodes, set conditional branch gates, and trigger modal previews.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition">
          <div className="w-9 h-9 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
            <Calculator className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Quote Engine</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Construct arithmetic equations with base rates, multipliers, and dynamic currency symbols.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition">
          <div className="w-9 h-9 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
            <Webhook className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-sm text-[#131416]">Webhooks & Leads</h3>
          <p className="text-xs text-[#5d5f60] leading-relaxed">
            Dispatch real-time JSON payloads to Zapier, Make, HubSpot, or custom external endpoints.
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-white border border-[#e2e5e9] space-y-3 hover:border-[#1d5fd6]/50 transition">
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
        <div className="lg:col-span-2 bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 space-y-6">
          <div className="space-y-1">
            <h2 className="text-lg font-bold text-[#131416]">Submit a Support Request</h2>
            <p className="text-xs text-[#5d5f60]">
              Our technical team typically responds within 2 hours during active business hours.
            </p>
          </div>

          {submitted ? (
            <div className="p-6 rounded-xl bg-[#dcfae5]/30 border border-[#bbf7d0] space-y-3 text-center">
              <div className="w-10 h-10 rounded-full bg-[#2f7a45] text-white flex items-center justify-center mx-auto">
                <CheckCircle className="w-6 h-6" />
              </div>
              <h3 className="text-base font-bold text-[#131416]">Ticket #QF-{Math.floor(1000 + Math.random() * 9000)} Received</h3>
              <p className="text-xs text-[#5d5f60] max-w-md mx-auto">
                Thank you, {formData.name}. We have logged your request regarding <span className="font-semibold text-[#131416]">{formData.subject || 'your question'}</span>. A confirmation has been sent to <span className="font-mono text-[#1d5fd6]">{formData.email}</span>.
              </p>
              <button
                type="button"
                onClick={() => {
                  setSubmitted(false);
                  setFormData({ name: '', email: '', category: 'builder', subject: '', message: '' });
                }}
                className="mt-2 px-4 py-2 rounded-lg bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416] hover:bg-[#f4f6f8] transition"
              >
                Send Another Message
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416]">Full Name</label>
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
                  <label className="text-xs font-semibold text-[#131416]">Work Email</label>
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
                  <label className="text-xs font-semibold text-[#131416]">Category</label>
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

                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-[#131416]">Subject</label>
                  <input
                    type="text"
                    required
                    placeholder="Brief description of the inquiry"
                    value={formData.subject}
                    onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-[#131416]">How can we help?</label>
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
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
              >
                <Send className="w-3.5 h-3.5" />
                <span>Submit Support Ticket</span>
              </button>
            </form>
          )}
        </div>

        {/* Sidebar Info & Status (1 Col) */}
        <div className="space-y-6">
          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4">
            <h3 className="font-bold text-sm text-[#131416]">Direct Channels</h3>

            <div className="space-y-3 text-xs">
              <div className="flex items-start gap-3">
                <div className="p-2 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
                  <Mail className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-semibold block text-[#131416]">Email Support</span>
                  <span className="font-mono text-[#5d5f60] text-[11px]">support@leadcollector.in</span>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-2 rounded-lg bg-[#dcfae5] text-[#2f7a45]">
                  <MessageCircle className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-semibold block text-[#131416]">WhatsApp Enterprise</span>
                  <span className="text-[#5d5f60] text-[11px]">Direct account manager support for Agency Scale plans</span>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-2 rounded-lg bg-[#fef3c7] text-[#b45309]">
                  <Clock className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-semibold block text-[#131416]">Hours of Operation</span>
                  <span className="text-[#5d5f60] text-[11px]">Monday – Friday, 8:00 AM – 8:00 PM EST</span>
                </div>
              </div>
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-[#dcf1ff]/30 border border-[#b9e6fe] space-y-3">
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

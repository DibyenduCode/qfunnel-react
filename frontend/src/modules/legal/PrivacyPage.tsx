import React from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck, Lock, Eye, FileText, ArrowLeft } from 'lucide-react';

export const PrivacyPage: React.FC = () => {
  return (
    <div className="py-12 sm:py-16 px-4 sm:px-8 max-w-4xl mx-auto space-y-8">
      {/* Header */}
      <div className="space-y-3">
        <Link to="/" className="inline-flex items-center gap-1.5 text-xs text-[#5d5f60] hover:text-[#1d5fd6] transition">
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Back to Home</span>
        </Link>

        <div className="flex items-center gap-2">
          <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
            <ShieldCheck className="w-5 h-5" />
          </span>
          <h1 className="text-2xl sm:text-3xl font-bold text-[#131416]">Privacy Policy</h1>
        </div>
        <p className="text-xs text-[#5d5f60]">
          Last revised: October 30, 2026 • Effective Date: November 1, 2026
        </p>
      </div>

      {/* Content Sheet */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-10 space-y-8 text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">1. Overview & Commitment</h2>
          <p>
            At LeadCollector ("we", "our", or "us", accessible via leadcollector.in), we prioritize the privacy and confidentiality of our customers, website visitors, and end-users who interact with interactive quizzes and calculators created through our software platform. This Privacy Policy details how we collect, store, isolate, and safeguard your data.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">2. Information We Collect</h2>
          <div className="space-y-2">
            <h3 className="font-semibold text-[#131416]">A. Workspace Customer Account Data</h3>
            <p>
              When you register for a LeadCollector account, we collect your name, business email address, company name, workspace subdomains, and encrypted password credentials.
            </p>
            <h3 className="font-semibold text-[#131416] pt-1">B. End-User Lead Submission Data</h3>
            <p>
              When visitors complete an interactive branching quiz, estimate calculator, or lead form hosted via our runner, we collect the responses, answers, calculated quotes, timestamps, and optional contact details (e.g. name, email, phone) provided by the visitor.
            </p>
          </div>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">3. Multi-Tenant Data Isolation & Security</h2>
          <p>
            LeadCollector is architected with strict multi-tenancy boundaries. Customer workspace data and lead submissions are strictly isolated by unique tenant identifiers. One workspace tenant cannot access, query, or view data belonging to another workspace.
          </p>
          <div className="p-4 rounded-xl bg-[#dcf1ff]/30 border border-[#b9e6fe] text-xs text-[#0a3a80] space-y-1">
            <span className="font-bold block">Encryption Standards</span>
            <p>
              All data transmitted between your visitors and LeadCollector is protected via TLS 1.3 / 256-Bit SSL encryption. Sensitive authentication secrets and webhook tokens are hashed using industry-standard algorithms.
            </p>
          </div>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">4. How We Use Information</h2>
          <ul className="list-disc pl-5 space-y-1">
            <li>To operate, maintain, and render visual branching funnels and calculations.</li>
            <li>To deliver lead notifications to your connected integrations (Zapier, Webhooks, WhatsApp).</li>
            <li>To calculate conversion metrics, drop-off rates, and funnel analytics.</li>
            <li>We do not sell, rent, or monetize your customer lead data to third parties or advertising networks.</li>
          </ul>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">5. GDPR & Data Subject Rights</h2>
          <p>
            Under GDPR, CCPA, and applicable global privacy laws, you and your visitors have the right to access, rectify, export, or permanently delete personal data. You can delete lead submissions or workspaces directly from the LeadCollector dashboard or by emailing <span className="font-mono text-[#1d5fd6]">privacy@leadcollector.in</span>.
          </p>
        </section>

        <section className="space-y-3 pt-4 border-t border-[#edf0f3]">
          <h2 className="text-base font-bold text-[#131416]">6. Contact Our Privacy Officer</h2>
          <p>
            If you have questions regarding this Privacy Policy or our security practices, contact us at:
          </p>
          <div className="font-mono text-xs bg-[#f4f6f8] p-3 rounded-lg border border-[#e2e5e9]">
            LeadCollector Data Protection Officer<br />
            Email: privacy@leadcollector.in<br />
            Domain: leadcollector.in
          </div>
        </section>
      </div>
    </div>
  );
};


import React from 'react';
import { Link } from 'react-router-dom';
import { FileText, ArrowLeft, ShieldCheck, Scale, CheckCircle } from 'lucide-react';

export const TermsPage: React.FC = () => {
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
            <Scale className="w-5 h-5" />
          </span>
          <h1 className="text-2xl sm:text-3xl font-bold text-[#131416]">Terms of Service</h1>
        </div>
        <p className="text-xs text-[#5d5f60]">
          Last revised: October 30, 2026 • Effective Date: November 1, 2026
        </p>
      </div>

      {/* Content Sheet */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-10 space-y-8 text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">1. Acceptance of Terms</h2>
          <p>
            By registering for an account, accessing, or using LeadCollector ("the Service", provided by LeadCollector Technologies Pvt Ltd / LeadCollector.in), you agree to be bound by these Terms of Service. If you are entering into this agreement on behalf of a company, organization, or other legal entity, you represent that you possess the authority to bind such entity to these terms.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">2. Account Registration & Tenant Security</h2>
          <p>
            To use LeadCollector, you must provide valid credentials during account creation. You are solely responsible for safeguarding the credentials associated with your workspace and all actions taken under your account. You must notify LeadCollector immediately if you discover any unauthorized use or security compromise.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">3. Acceptable Use Policy</h2>
          <p>
            You agree not to misuse the platform. Specifically, you agree that you will not use LeadCollector to:
          </p>
          <ul className="list-disc pl-5 space-y-1">
            <li>Collect personal credit card numbers, national identification numbers, or HIPAA-restricted patient health data directly into unsecured form input fields without approved compliance add-ons.</li>
            <li>Conduct phishing campaigns, fraudulent deceptive sweepstakes, or distribute malicious code.</li>
            <li>Exceed rate limits or attempt to reverse-engineer the underlying API infrastructure and tenant isolation models.</li>
            <li>Send unsolicited commercial bulk emails (SPAM) via webhook triggers or calendar integrations.</li>
          </ul>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">4. Subscription Billing, Upgrades & Cancellations</h2>
          <p>
            Paid subscription plans (Starter, Growth, Agency Scale) are billed in advance on a recurring monthly or annual basis via Stripe, PayPal, or supported regional payment processors.
          </p>
          <div className="p-4 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] text-xs space-y-2">
            <div className="flex items-center gap-2 text-[#131416] font-semibold">
              <CheckCircle className="w-4 h-4 text-[#2f7a45]" />
              <span>Plan Invoicing & Proration</span>
            </div>
            <p>
              Upgrades take effect immediately with prorated billing for the remainder of the active billing cycle. Downgrades or cancellations take effect at the end of the current paid billing cycle. No refunds are issued for partial billing periods.
            </p>
          </div>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">5. Data Ownership & Customer Intellectual Property</h2>
          <p>
            You retain all ownership rights, title, and interest in and to all question structures, funnel configurations, branding assets, formulas, and submission data you create or collect using LeadCollector. LeadCollector retains all rights, title, and interest in the software platform, runtime engine, visual canvas components, and documentation.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">6. Service Level Agreement & Availability</h2>
          <p>
            We strive to maintain a 99.9% uptime record for the public quiz runner runtime. Scheduled maintenance windows are communicated in advance. We provide real-time status reporting on our public status page.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">7. Limitation of Liability</h2>
          <p>
            To the maximum extent permitted by applicable law, in no event shall LeadCollector, its affiliates, directors, or employees be liable for any indirect, incidental, special, consequential, or punitive damages, or loss of profits or revenues arising out of or related to your use of the Service.
          </p>
        </section>

        <section className="space-y-3 pt-4 border-t border-[#edf0f3]">
          <h2 className="text-base font-bold text-[#131416]">8. Governing Law & Contact</h2>
          <p>
            These Terms shall be governed by and construed in accordance with the laws of Delaware, United States, without regard to conflict of law principles.
          </p>
          <p>
            For questions regarding these Terms of Service, contact our legal team at{' '}
            <span className="font-mono text-[#1d5fd6]">legal@leadcollector.in</span>.
          </p>
        </section>
      </div>
    </div>
  );
};

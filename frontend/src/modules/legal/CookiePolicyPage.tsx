import React from 'react';
import { Link } from 'react-router-dom';
import { Cookie, ArrowLeft, ShieldCheck, CheckCircle, Info } from 'lucide-react';

export const CookiePolicyPage: React.FC = () => {
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
            <Cookie className="w-5 h-5" />
          </span>
          <h1 className="text-2xl sm:text-3xl font-bold text-[#131416]">Cookie Policy</h1>
        </div>
        <p className="text-xs text-[#5d5f60]">
          Last revised: October 30, 2026 • Effective Date: November 1, 2026
        </p>
      </div>

      {/* Content Sheet */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-10 space-y-8 text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">1. What Are Cookies?</h2>
          <p>
            Cookies are small data files stored on your device (computer, tablet, or smartphone) when you visit websites or interact with web-based applications. They are widely used to ensure websites function properly, retain user state across multiple steps, and deliver customized user experiences.
          </p>
        </section>

        <section className="space-y-4">
          <h2 className="text-base font-bold text-[#131416]">2. The Cookies We Use & Why</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] space-y-2">
              <div className="flex items-center gap-2 font-bold text-[#131416]">
                <ShieldCheck className="w-4 h-4 text-[#1d5fd6]" />
                <span>Essential & Security Cookies</span>
              </div>
              <p className="text-xs text-[#5d5f60]">
                Strictly required to maintain secure administrator sessions, protect against Cross-Site Request Forgery (CSRF), and verify authenticated tenant boundaries. These cannot be disabled.
              </p>
            </div>

            <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] space-y-2">
              <div className="flex items-center gap-2 font-bold text-[#131416]">
                <CheckCircle className="w-4 h-4 text-[#2f7a45]" />
                <span>Quiz Runner State Cookies</span>
              </div>
              <p className="text-xs text-[#5d5f60]">
                Stores session identifiers for active quiz respondents, allowing users to navigate between branching questions and retain partial quote calculation without losing their progress.
              </p>
            </div>

            <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] space-y-2">
              <div className="flex items-center gap-2 font-bold text-[#131416]">
                <Info className="w-4 h-4 text-[#b45309]" />
                <span>Analytics & Performance</span>
              </div>
              <p className="text-xs text-[#5d5f60]">
                Collects aggregated, anonymous telemetry regarding completion rates, question drop-off times, and device viewports to help funnel authors optimize their conversion performance.
              </p>
            </div>

            <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] space-y-2">
              <div className="flex items-center gap-2 font-bold text-[#131416]">
                <ShieldCheck className="w-4 h-4 text-[#1d5fd6]" />
                <span>Payment Processing Cookies</span>
              </div>
              <p className="text-xs text-[#5d5f60]">
                When upgrading subscription plans, payment processors (such as Stripe) set fraud-prevention and session identification cookies to safely execute transactions.
              </p>
            </div>
          </div>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-[#131416]">3. Managing & Disabling Cookies</h2>
          <p>
            Most web browsers allow you to manage or delete cookies via your browser settings. You can set your browser to notify you when you receive a cookie, or prevent cookies from being saved altogether.
          </p>
          <div className="p-4 rounded-xl bg-[#dcf1ff]/40 border border-[#b9e6fe] text-xs text-[#0a3a80] space-y-1">
            <span className="font-bold block">Important Note</span>
            <p>
              If you configure your browser to block essential cookies, you will not be able to log in to the QFunnel workspace, and respondents might encounter errors during multi-step quiz calculations.
            </p>
          </div>
        </section>

        <section className="space-y-3 pt-4 border-t border-[#edf0f3]">
          <h2 className="text-base font-bold text-[#131416]">4. Questions & Policy Updates</h2>
          <p>
            We may update this Cookie Policy periodically to reflect technological changes, new platform features, or legal compliance requirements.
          </p>
          <p>
            For inquiries regarding our use of cookies, reach us at{' '}
            <span className="font-mono text-[#1d5fd6]">privacy@qfunnel.local</span>.
          </p>
        </section>
      </div>
    </div>
  );
};

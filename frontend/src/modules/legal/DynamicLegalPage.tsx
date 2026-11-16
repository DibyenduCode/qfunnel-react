import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Scale, Clock, AlertCircle, ShieldCheck } from 'lucide-react';
import { SeoHelmet } from '../../components/SeoHelmet';
import { api } from '../../api/client';

interface LegalPageData {
  id: number;
  slug: string;
  title: string;
  meta_description: string;
  content: string;
  is_published: number;
  created_at: string;
  updated_at: string;
}

interface DynamicLegalPageProps {
  defaultSlug?: string;
}

const FALLBACK_PAGES: Record<string, Partial<LegalPageData>> = {
  privacy: {
    id: 1,
    slug: 'privacy',
    title: 'Privacy Policy',
    meta_description: 'Learn how LeadCollector collects, stores, and safeguards your data and user assessment submissions.',
    updated_at: '2026-11-01',
    content: `<h2>1. Overview & Commitment</h2>
<p>At LeadCollector ("we", "our", or "us", accessible via leadcollector.in), we prioritize the privacy and confidentiality of our customers, website visitors, and end-users who interact with interactive quizzes and calculators created through our software platform. This Privacy Policy details how we collect, store, isolate, and safeguard your data.</p>

<h2>2. Information We Collect</h2>
<h3>A. Workspace Customer Account Data</h3>
<p>When you register for a LeadCollector account, we collect your name, business email address, company name, workspace subdomains, and encrypted password credentials.</p>
<h3>B. End-User Lead Submission Data</h3>
<p>When visitors complete an interactive branching quiz, estimate calculator, or lead form hosted via our runner, we collect the responses, answers, calculated quotes, timestamps, and optional contact details provided by the visitor.</p>

<h2>3. Multi-Tenant Data Isolation & Security</h2>
<p>LeadCollector is architected with strict multi-tenancy boundaries. Customer workspace data and lead submissions are strictly isolated by unique tenant identifiers. One workspace tenant cannot access, query, or view data belonging to another workspace.</p>
<p><strong>Encryption Standards:</strong> All data transmitted between your visitors and LeadCollector is protected via TLS 1.3 / 256-Bit SSL encryption. Sensitive authentication secrets and webhook tokens are hashed using industry-standard algorithms.</p>

<h2>4. How We Use Information</h2>
<ul>
  <li>To operate, maintain, and render visual branching funnels and calculations.</li>
  <li>To deliver lead notifications to your connected integrations (Zapier, Webhooks, WhatsApp).</li>
  <li>To calculate conversion metrics, drop-off rates, and funnel analytics.</li>
  <li>We do not sell, rent, or monetize your customer lead data to third parties or advertising networks.</li>
</ul>

<h2>5. GDPR & Data Subject Rights</h2>
<p>Under GDPR, CCPA, and applicable global privacy laws, you and your visitors have the right to access, rectify, export, or permanently delete personal data. You can delete lead submissions or workspaces directly from the LeadCollector dashboard or by emailing privacy@leadcollector.in.</p>

<h2>6. Contact Our Privacy Officer</h2>
<p>If you have questions regarding this Privacy Policy or our security practices, contact us at:</p>
<p>LeadCollector Data Protection Officer<br>Email: privacy@leadcollector.in<br>Domain: leadcollector.in</p>`,
  },
  terms: {
    id: 2,
    slug: 'terms',
    title: 'Terms of Service',
    meta_description: 'Review the terms and conditions governing the use of the LeadCollector quiz funnels platform.',
    updated_at: '2026-11-01',
    content: `<h2>1. Acceptance of Terms</h2>
<p>By registering for an account, accessing, or using LeadCollector ("the Service", provided by LeadCollector Technologies Pvt Ltd / LeadCollector.in), you agree to be bound by these Terms of Service. If you are entering into this agreement on behalf of a company, organization, or other legal entity, you represent that you possess the authority to bind such entity to these terms.</p>

<h2>2. Account Registration & Tenant Security</h2>
<p>To use LeadCollector, you must provide valid credentials during account creation. You are solely responsible for safeguarding the credentials associated with your workspace and all actions taken under your account. You must notify LeadCollector immediately if you discover any unauthorized use or security compromise.</p>

<h2>3. Acceptable Use Policy</h2>
<p>You agree not to misuse the platform. Specifically, you agree that you will not use LeadCollector to:</p>
<ul>
  <li>Collect personal credit card numbers, national identification numbers, or HIPAA-restricted patient health data directly into unsecured form input fields without approved compliance add-ons.</li>
  <li>Conduct phishing campaigns, fraudulent deceptive sweepstakes, or distribute malicious code.</li>
  <li>Exceed rate limits or attempt to reverse-engineer the underlying API infrastructure and tenant isolation models.</li>
  <li>Send unsolicited commercial bulk emails (SPAM) via webhook triggers or calendar integrations.</li>
</ul>

<h2>4. Subscription Billing, Upgrades & Cancellations</h2>
<p>Paid subscription plans (Starter, Growth, Agency Scale) are billed in advance on a recurring monthly or annual basis via Stripe, Cashfree, PayPal, or supported regional payment processors. Upgrades take effect immediately with prorated billing. Downgrades or cancellations take effect at the end of the current paid billing cycle.</p>

<h2>5. Data Ownership & Customer Intellectual Property</h2>
<p>You retain all ownership rights, title, and interest in and to all question structures, funnel configurations, branding assets, formulas, and submission data you create or collect using LeadCollector. LeadCollector retains all rights, title, and interest in the software platform, runtime engine, visual canvas components, and documentation.</p>

<h2>6. Service Level Agreement & Availability</h2>
<p>We strive to maintain a 99.9% uptime record for the public quiz runner runtime. Scheduled maintenance windows are communicated in advance via our operational status channels.</p>

<h2>7. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law, in no event shall LeadCollector, its affiliates, directors, or employees be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or related to your use of the Service.</p>

<h2>8. Governing Law & Contact</h2>
<p>For questions regarding these Terms of Service, contact our legal team at legal@leadcollector.in.</p>`,
  },
  'cookie-policy': {
    id: 3,
    slug: 'cookie-policy',
    title: 'Cookie Policy',
    meta_description: 'Understand how LeadCollector uses cookies, session identifiers, and tracking technologies.',
    updated_at: '2026-11-01',
    content: `<h2>1. What Are Cookies?</h2>
<p>Cookies are small data files stored on your device (computer, tablet, or smartphone) when you visit websites or interact with web-based applications. They are widely used to ensure websites function properly, retain user state across multiple steps, and deliver customized user experiences.</p>

<h2>2. The Cookies We Use & Why</h2>
<ul>
  <li><strong>Essential & Security Cookies:</strong> Strictly required to maintain secure administrator sessions, protect against Cross-Site Request Forgery (CSRF), and verify authenticated tenant boundaries. These cannot be disabled.</li>
  <li><strong>Quiz Runner State Cookies:</strong> Stores session identifiers for active quiz respondents, allowing users to navigate between branching questions and retain partial quote calculation without losing their progress.</li>
  <li><strong>Performance & Analytics Cookies:</strong> Helps us understand how visitors interact with the marketing homepage and funnels to optimize page speed and conversion paths.</li>
</ul>

<h2>3. Managing Cookie Preferences</h2>
<p>You can configure your browser settings to reject or delete cookies. However, please note that disabling essential cookies may impact the proper functionality of the LeadCollector quiz builder and authenticated dashboards.</p>`,
  },
  'refund-policy': {
    id: 4,
    slug: 'refund-policy',
    title: 'Refund & Cancellation Policy',
    meta_description: 'Review our clear cancellation terms, subscription downgrade rules, and refund policy.',
    updated_at: '2026-11-01',
    content: `<h2>1. Subscription Billing & Auto-Renewal</h2>
<p>LeadCollector subscription plans are billed on a recurring monthly or annual cycle. You may cancel your subscription at any time directly from your Workspace Billing dashboard.</p>

<h2>2. 14-Day Money Back Guarantee for Annual Plans</h2>
<p>We provide a 14-day money-back guarantee for all newly purchased annual subscription plans. If you are not completely satisfied with LeadCollector within 14 days of your initial annual upgrade, contact support for a full refund.</p>

<h2>3. Monthly Subscriptions & Cancellations</h2>
<p>Monthly subscriptions can be canceled at any time prior to the next renewal date. When canceled, your plan will remain active until the end of the paid billing period, and no further charges will occur. Partial month refunds are not issued.</p>

<h2>4. How to Request a Refund</h2>
<p>To submit a cancellation or refund inquiry, email billing@leadcollector.in with your workspace name and account email. Our finance team processes eligible refund requests within 3 to 5 business days.</p>`,
  },
};

export const DynamicLegalPage: React.FC<DynamicLegalPageProps> = ({ defaultSlug }) => {
  const { slug } = useParams<{ slug: string }>();
  const rawSlug = defaultSlug || slug || '';
  const activeSlug = rawSlug === 'cookies' ? 'cookie-policy' : rawSlug;

  const [page, setPage] = useState<LegalPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!activeSlug) {
      setError('Document slug not specified');
      setLoading(false);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setError('');

    api.get(`/api/public/legal-pages/${activeSlug}`)
      .then((res: any) => {
        if (!isMounted) return;
        if (res?.page) {
          setPage(res.page);
        } else if (FALLBACK_PAGES[activeSlug]) {
          setPage(FALLBACK_PAGES[activeSlug] as LegalPageData);
        } else {
          setError('Document not found');
        }
      })
      .catch((err: any) => {
        if (!isMounted) return;
        if (FALLBACK_PAGES[activeSlug]) {
          setPage(FALLBACK_PAGES[activeSlug] as LegalPageData);
        } else {
          setError(err.message || 'Unable to load legal document.');
        }
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [activeSlug]);

  if (loading) {
    return (
      <div className="py-12 sm:py-16 px-4 sm:px-8 max-w-4xl mx-auto space-y-8 animate-pulse">
        <div className="space-y-3">
          <div className="h-4 w-24 bg-gray-200 rounded" />
          <div className="h-8 w-64 bg-gray-200 rounded-lg" />
          <div className="h-4 w-48 bg-gray-100 rounded" />
        </div>
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-10 space-y-6">
          <div className="h-6 w-1/3 bg-gray-200 rounded" />
          <div className="space-y-2">
            <div className="h-4 w-full bg-gray-100 rounded" />
            <div className="h-4 w-5/6 bg-gray-100 rounded" />
            <div className="h-4 w-4/6 bg-gray-100 rounded" />
          </div>
          <div className="h-6 w-1/4 bg-gray-200 rounded" />
          <div className="space-y-2">
            <div className="h-4 w-full bg-gray-100 rounded" />
            <div className="h-4 w-3/4 bg-gray-100 rounded" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !page) {
    return (
      <div className="py-16 px-4 sm:px-8 max-w-2xl mx-auto text-center space-y-6">
        <div className="w-14 h-14 mx-auto rounded-2xl bg-[#fee2e2] text-[#991b1b] flex items-center justify-center">
          <AlertCircle className="w-7 h-7" />
        </div>
        <div className="space-y-2">
          <h1 className="text-2xl font-bold text-[#131416]">Document Not Found</h1>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            The requested legal policy or company document is currently unavailable or has not been published yet.
          </p>
        </div>
        <div>
          <Link
            to="/"
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Return to Homepage</span>
          </Link>
        </div>
      </div>
    );
  }

  const isHtml = /<[a-z][\s\S]*>/i.test(page.content);

  return (
    <div className="py-12 sm:py-16 px-4 sm:px-8 max-w-4xl mx-auto space-y-8">
      <SeoHelmet
        title={`${page.title} — LeadCollector.in`}
        description={page.meta_description || `Official ${page.title} for LeadCollector platform.`}
      />

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
          <h1 className="text-2xl sm:text-3xl font-bold text-[#131416]">{page.title}</h1>
        </div>
        <p className="text-xs text-[#5d5f60] flex items-center gap-1.5">
          <Clock className="w-3.5 h-3.5" />
          <span>
            Last updated: {page.updated_at ? new Date(page.updated_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : 'Recently revised'}
          </span>
        </p>
      </div>

      {/* Content Sheet */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-10 space-y-6 text-xs sm:text-sm text-[#5d5f60] leading-relaxed shadow-xs">
        {isHtml ? (
          <div
            className="legal-content prose prose-slate max-w-none space-y-5 text-xs sm:text-sm text-[#5d5f60] leading-relaxed [&_h2]:text-base [&_h2]:font-bold [&_h2]:text-[#131416] [&_h2]:mt-6 [&_h2]:mb-2 [&_h3]:text-sm [&_h3]:font-semibold [&_h3]:text-[#131416] [&_h3]:mt-4 [&_h3]:mb-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3 [&_strong]:text-[#131416] [&_a]:text-[#1d5fd6] [&_a]:underline"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        ) : (
          <div className="whitespace-pre-line space-y-4">
            {page.content}
          </div>
        )}

        <div className="pt-6 border-t border-[#edf0f3] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-[#5d5f60]">
          <div>
            Need legal or compliance clarifications? Contact our compliance officer at{' '}
            <a href="mailto:privacy@leadcollector.in" className="text-[#1d5fd6] underline font-medium">
              privacy@leadcollector.in
            </a>
          </div>
          <div className="text-[11px] font-mono text-[#8c9096]">
            Document ID: #{page.id} • /{page.slug}
          </div>
        </div>
      </div>
    </div>
  );
};

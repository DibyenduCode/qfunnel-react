import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { SeoHelmet } from '../../components/SeoHelmet';
import { api } from '../../api/client';
import {
  GitFork,
  Calculator,
  MessageCircle,
  PhoneCall,
  Calendar,
  CheckCircle,
  Check,
  ArrowRight,
  Sparkles,
  Zap,
  BarChart3,
  Layers,
  ShieldCheck,
  Sliders,
  ExternalLink,
  ChevronRight,
  ChevronDown,
  Globe,
  Clock,
  Play,
  Users,
  Award,
  Star,
  Activity,
  Workflow,
  XCircle,
  Building,
  Stethoscope,
  Hammer,
  TrendingUp,
  DollarSign,
  Lock,
  Tag,
  Crown
} from 'lucide-react';

export const HomePage: React.FC = () => {
  const { user } = useAuthStore();
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'annual'>('monthly');

  // Dynamic Plans from Database (Synchronized with Admin Visibility & Lifetime Deal)
  const [publicPlans, setPublicPlans] = useState<any[]>([]);
  const [loadingPlans, setLoadingPlans] = useState(true);
  const [selectedCycleTab, setSelectedCycleTab] = useState<'all' | 'monthly' | 'lifetime'>('all');

  useEffect(() => {
    let active = true;
    api.get('/api/public/plans')
      .then((res: any) => {
        if (active && res?.plans && Array.isArray(res.plans)) {
          setPublicPlans(res.plans);
        }
      })
      .catch((err) => {
        console.warn('Could not load public plans:', err);
      })
      .finally(() => {
        if (active) setLoadingPlans(false);
      });
    return () => {
      active = false;
    };
  }, []);

  const fallbackPlans = [
    {
      id: 3,
      name: 'Free',
      slug: 'free',
      description: 'Essential single-funnel plan for testing the platform',
      price: '0.00',
      billing_interval: 'monthly',
      limits_json: '{"max_funnels":1,"max_submissions_per_month":100,"max_team_members":1}',
      features_json: '{"white_label":false,"calculator_mode":false,"webhooks":false,"csv_export":false,"api_access":false,"funnel_cloning":false}',
      is_active: 1,
      is_visible: 1
    },
    {
      id: 1,
      name: 'Starter',
      slug: 'starter',
      description: 'Ideal for emerging creators and growing lead funnels',
      price: '749.00',
      billing_interval: 'monthly',
      limits_json: '{"max_funnels":5,"max_submissions_per_month":1000,"max_team_members":3}',
      features_json: '{"white_label":false,"calculator_mode":false,"webhooks":false,"csv_export":true,"api_access":false,"funnel_cloning":true}',
      is_active: 1,
      is_visible: 1
    },
    {
      id: 4,
      name: 'Standard',
      slug: 'standard',
      description: 'Robust multi-funnel plan for scaling marketing operations',
      price: '999.00',
      billing_interval: 'monthly',
      limits_json: '{"max_funnels":10,"max_submissions_per_month":5000,"max_team_members":10}',
      features_json: '{"white_label":false,"calculator_mode":true,"webhooks":true,"csv_export":true,"api_access":false,"funnel_cloning":true}',
      is_active: 1,
      is_visible: 1
    },
    {
      id: 2,
      name: 'Pro',
      slug: 'pro',
      description: 'Unlimited funnels and submissions for high-velocity teams',
      price: '1499.00',
      billing_interval: 'monthly',
      limits_json: '{"max_funnels":-1,"max_submissions_per_month":-1,"max_team_members":-1}',
      features_json: '{"white_label":true,"calculator_mode":true,"webhooks":true,"csv_export":true,"api_access":true,"funnel_cloning":true}',
      is_active: 1,
      is_visible: 1
    },
    {
      id: 11,
      name: 'Lifetime Pro Deal',
      slug: 'lifetime-pro-deal',
      description: 'One-time payment lifetime access to all Pro capabilities',
      price: '4999.00',
      billing_interval: 'lifetime',
      limits_json: '{"max_funnels":-1,"max_submissions_per_month":-1,"max_team_members":10}',
      features_json: '{"white_label":true,"calculator_mode":true,"webhooks":true,"csv_export":true,"api_access":true,"funnel_cloning":true}',
      is_active: 1,
      is_visible: 1
    }
  ];

  // Interactive Live Calculator Playground State
  const [demoTier, setDemoTier] = useState<'standard' | 'premium' | 'enterprise'>('premium');
  const [demoScope, setDemoScope] = useState<number>(4);
  const [includeAddon, setIncludeAddon] = useState<boolean>(true);

  // Dynamic Calculation
  const baseRate = demoTier === 'standard' ? 450 : demoTier === 'premium' ? 850 : 1600;
  const calculatedQuote = baseRate + (demoScope * 180) + (includeAddon ? 320 : 0);

  // FAQ Accordion State
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  const toggleFaq = (index: number) => {
    setOpenFaq(openFaq === index ? null : index);
  };

  const faqs = [
    {
      q: 'How does FilterFunnel increase lead conversion rates compared to traditional forms?',
      a: 'Traditional 10-field web forms create cognitive fatigue and suffer from a 97% bounce rate. FilterFunnel breaks the qualification process into single-question micro-steps with personalized branching. Prospective buyers receive instant value—such as a tailored price estimate or diagnostic score—before submitting their details, driving 3x to 5x higher completions.'
    },
    {
      q: 'Can I embed funnels into my existing WordPress, Webflow, or Shopify website?',
      a: 'Yes. Every funnel can be published via a single-line JavaScript embed or responsive iframe that blends natively into any CMS or custom web stack. You can also host funnels as standalone high-speed landing pages with custom subdomains.'
    },
    {
      q: 'How does the real-time Quote Formula engine work?',
      a: 'You can define arithmetic formulas directly inside the builder using question input variables, base fees, and category multipliers. As the respondent selects options, the calculated estimate updates in real time and is stored with the lead payload.'
    },
    {
      q: 'Can leads be automatically sent to WhatsApp and our CRM?',
      a: 'Yes. FilterFunnel features native 1-click WhatsApp deep routing, prefilling the customer message with their calculated quote and answers. Furthermore, real-time webhooks dispatch structured JSON payloads to Zapier, Make, HubSpot, or any custom API endpoint.'
    },
    {
      q: 'Do I need programming knowledge to build complex branching logic?',
      a: 'None. The visual React Flow canvas enables you to connect question nodes, define conditional rules (e.g., if Budget > ₹50,000 jump to VIP consultation), and preview visitor experiences in real time without writing any code.'
    }
  ];

  return (
    <div className="space-y-24 sm:space-y-32 pb-20">
      <SeoHelmet
        title="FilterFunnel.in — Visual Interactive Quiz Funnels & Lead Generation Platform"
        description="Replace static web forms with interactive visual quiz funnels, calculator flows, and multi-step lead capture. Automate lead qualification with WhatsApp and email OTP."
      />
      {/* 1. Hero Section */}
      <section className="pt-12 sm:pt-20 px-4 sm:px-8 max-w-6xl mx-auto text-center space-y-8">
        {/* Eyebrow Badge */}
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#dcf1ff] border border-[#b9e6fe] text-xs font-semibold text-[#0a3a80] shadow-2xs">
          <Sparkles className="w-3.5 h-3.5 text-[#1d5fd6]" />
          <span>Interactive Quiz & Quote Calculator Platform</span>
        </div>

        {/* Main Headline */}
        <div className="space-y-4 max-w-4xl mx-auto">
          <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-[#131416] leading-tight sm:leading-tight">
            Turn Cold Visitors Into Qualified Buyers with{' '}
            <span className="text-[#1d5fd6]">Interactive Visual Funnels</span>
          </h1>
          <p className="text-base sm:text-lg text-[#5d5f60] max-w-2xl mx-auto leading-relaxed">
            Replace low-converting static forms with guided visual branching pathways, real-time price calculators, and direct handoffs to WhatsApp and calendar bookings.
          </p>
        </div>

        {/* Hero CTAs */}
        <div className="flex flex-wrap items-center justify-center gap-3.5 pt-2">
          <Link
            to={user ? '/admin/dashboard' : '/register'}
            className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#1d5fd6] text-white text-sm font-semibold hover:bg-[#1853bf] transition shadow-xs"
          >
            <span>{user ? 'Open Your Workspace' : 'Start Free 14-Day Trial'}</span>
            <ArrowRight className="w-4 h-4" />
          </Link>
          <a
            href="#calculator"
            className="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-[#e2e5e9] bg-white text-sm font-semibold text-[#131416] hover:bg-[#f4f6f8] transition"
          >
            <span>Test Live Calculator</span>
            <ChevronRight className="w-4 h-4 text-[#5d5f60]" />
          </a>
        </div>

        {/* Live Social Proof Metrics */}
        <div className="pt-6 flex flex-wrap items-center justify-center gap-6 sm:gap-12 text-xs text-[#5d5f60]">
          <div className="flex items-center gap-2">
            <div className="flex -space-x-1">
              {[1, 2, 3, 4, 5].map((i) => (
                <Star key={i} className="w-3.5 h-3.5 fill-[#b45309] text-[#b45309]" />
              ))}
            </div>
            <span className="font-semibold text-[#131416]">4.9/5</span>
            <span>(2,400+ reviews)</span>
          </div>

          <div className="flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-[#2f7a45]" />
            <span className="font-semibold text-[#131416]">1,200,000+</span>
            <span>leads captured</span>
          </div>

          <div className="flex items-center gap-2">
            <ShieldCheck className="w-4 h-4 text-[#1d5fd6]" />
            <span>GDPR & Multi-Tenant Verified</span>
          </div>
        </div>

        {/* Visual Mockup Preview */}
        <div className="pt-6 max-w-5xl mx-auto">
          <div className="p-3 sm:p-4 rounded-3xl bg-white border border-[#e2e5e9] shadow-xs">
            <div className="rounded-2xl border border-[#edf0f3] bg-[#f8fafc] overflow-hidden">
              {/* Fake Window Toolbar */}
              <div className="px-4 py-2.5 bg-white border-b border-[#e2e5e9] flex items-center justify-between text-xs text-[#5d5f60]">
                <div className="flex items-center gap-2">
                  <div className="flex items-center gap-1.5">
                    <span className="w-2.5 h-2.5 rounded-full bg-[#cbd5e1]" />
                    <span className="w-2.5 h-2.5 rounded-full bg-[#94a3b8]" />
                    <span className="w-2.5 h-2.5 rounded-full bg-[#64748b]" />
                  </div>
                  <span className="text-[11px] font-mono pl-2 text-[#5d5f60]">app.filterfunnel.in/funnel/quote-demo</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#dcfae5] text-[#2f7a45] text-[10px] font-semibold">
                    Live Runner
                  </span>
                </div>
              </div>

              {/* Mockup Canvas / Runner Graphic */}
              <div className="p-6 sm:p-10 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div className="p-4 rounded-xl bg-white border border-[#e2e5e9] space-y-2 text-left">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold text-[#1d5fd6]">Node 01: Choice</span>
                    <span className="w-2 h-2 rounded-full bg-[#1d5fd6]" />
                  </div>
                  <h4 className="text-xs font-bold text-[#131416]">Project Category</h4>
                  <div className="space-y-1.5 pt-1">
                    <div className="p-2 rounded-lg bg-[#dcf1ff] border border-[#b9e6fe] text-[11px] font-semibold text-[#0a3a80] flex items-center justify-between">
                      <span>Full Custom Redesign</span>
                      <Check className="w-3.5 h-3.5 text-[#1d5fd6]" />
                    </div>
                    <div className="p-2 rounded-lg bg-[#f8fafc] border border-[#e2e5e9] text-[11px] text-[#5d5f60]">
                      Growth Acceleration
                    </div>
                  </div>
                </div>

                <div className="p-4 rounded-xl bg-white border border-[#e2e5e9] space-y-2 text-left">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold text-[#2f7a45]">Node 02: Formula</span>
                    <Calculator className="w-3.5 h-3.5 text-[#2f7a45]" />
                  </div>
                  <h4 className="text-xs font-bold text-[#131416]">Real-Time Estimate</h4>
                  <div className="p-3 rounded-lg bg-[#dcfae5]/30 border border-[#bbf7d0] space-y-1">
                    <span className="text-[10px] text-[#2f7a45] font-semibold block">Calculated Value</span>
                    <div className="text-xl font-extrabold text-[#131416]">₹2,450.00</div>
                  </div>
                </div>

                <div className="p-4 rounded-xl bg-white border border-[#e2e5e9] space-y-2 text-left">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold text-[#b45309]">Node 03: Handoff</span>
                    <MessageCircle className="w-3.5 h-3.5 text-[#b45309]" />
                  </div>
                  <h4 className="text-xs font-bold text-[#131416]">Instant Connection</h4>
                  <div className="space-y-1.5 pt-1">
                    <div className="p-2 rounded-lg bg-[#dcfae5] border border-[#bbf7d0] text-[11px] font-semibold text-[#2f7a45] flex items-center gap-1.5">
                      <MessageCircle className="w-3.5 h-3.5" />
                      <span>WhatsApp Direct Booking</span>
                    </div>
                    <div className="p-2 rounded-lg bg-[#f8fafc] border border-[#e2e5e9] text-[11px] text-[#5d5f60] flex items-center gap-1.5">
                      <Calendar className="w-3.5 h-3.5" />
                      <span>Sync to Google Calendar</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. Before vs After Comparison Matrix */}
      <section id="features" className="scroll-mt-20 px-4 sm:px-8 max-w-6xl mx-auto space-y-12">
        <div className="text-center space-y-3 max-w-3xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">Conversion Breakdown</span>
          <h2 className="text-2xl sm:text-4xl font-bold tracking-tight text-[#131416]">
            Why Modern Revenue Teams Are Abandoning Static Web Forms
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            Compare the friction of traditional contact forms against the engagement of FilterFunnel interactive paths.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
          {/* Traditional Forms (Negative) */}
          <div className="p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9] space-y-6">
            <div className="flex items-center justify-between border-b border-[#e2e5e9] pb-4">
              <div>
                <h3 className="font-bold text-base text-[#131416]">Old Way: Static Web Forms</h3>
                <p className="text-xs text-[#5d5f60]">Boring, transactional, high bounce rates</p>
              </div>
              <span className="px-2.5 py-1 rounded bg-[#fef2f2] text-[#991b1b] text-xs font-bold">
                ~2.8% Conversion
              </span>
            </div>

            <ul className="space-y-4 text-xs sm:text-sm text-[#5d5f60]">
              <li className="flex items-start gap-3">
                <XCircle className="w-4 h-4 text-[#991b1b] shrink-0 mt-0.5" />
                <span>Asks for high-friction contact info (phone, email) before offering any value.</span>
              </li>
              <li className="flex items-start gap-3">
                <XCircle className="w-4 h-4 text-[#991b1b] shrink-0 mt-0.5" />
                <span>Rigid linear structure with zero branching logic or intelligent qualification.</span>
              </li>
              <li className="flex items-start gap-3">
                <XCircle className="w-4 h-4 text-[#991b1b] shrink-0 mt-0.5" />
                <span>Prospects are forced to wait 24-48 hours for a manual sales quote email.</span>
              </li>
              <li className="flex items-start gap-3">
                <XCircle className="w-4 h-4 text-[#991b1b] shrink-0 mt-0.5" />
                <span>No instant mobile messaging handoff; leads quickly turn cold.</span>
              </li>
            </ul>
          </div>

          {/* FilterFunnel Interactive (Positive) */}
          <div className="p-6 sm:p-8 rounded-2xl bg-[#dcf1ff]/30 border border-[#b9e6fe] space-y-6">
            <div className="flex items-center justify-between border-b border-[#b9e6fe] pb-4">
              <div>
                <h3 className="font-bold text-base text-[#0a3a80]">FilterFunnel Interactive Quizzes</h3>
                <p className="text-xs text-[#0a3a80]/80">Engaging, transparent, high-intent conversions</p>
              </div>
              <span className="px-2.5 py-1 rounded bg-[#dcfae5] text-[#2f7a45] text-xs font-bold">
                18% – 35% Conversion
              </span>
            </div>

            <ul className="space-y-4 text-xs sm:text-sm text-[#0a3a80]">
              <li className="flex items-start gap-3">
                <CheckCircle className="w-4 h-4 text-[#1d5fd6] shrink-0 mt-0.5" />
                <span>Delivers immediate transparency with real-time calculated quotes and recommendations.</span>
              </li>
              <li className="flex items-start gap-3">
                <CheckCircle className="w-4 h-4 text-[#1d5fd6] shrink-0 mt-0.5" />
                <span>Intelligent conditional branching adapts the questions to match the buyer's budget and scope.</span>
              </li>
              <li className="flex items-start gap-3">
                <CheckCircle className="w-4 h-4 text-[#1d5fd6] shrink-0 mt-0.5" />
                <span>Connects hot buyers directly to WhatsApp or calendar bookings in under 60 seconds.</span>
              </li>
              <li className="flex items-start gap-3">
                <CheckCircle className="w-4 h-4 text-[#1d5fd6] shrink-0 mt-0.5" />
                <span>Multi-tenant data isolation, webhook automation, and in-depth drop-off analytics.</span>
              </li>
            </ul>
          </div>
        </div>
      </section>

      {/* 3. Three-Step Interactive Visual Engine */}
      <section id="how-it-works" className="scroll-mt-20 px-4 sm:px-8 max-w-6xl mx-auto space-y-12">
        <div className="text-center space-y-3 max-w-3xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">How It Works</span>
          <h2 className="text-2xl sm:text-4xl font-bold tracking-tight text-[#131416]">
            Build, Calculate, and Convert in 3 Simple Steps
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            From visual canvas node wiring to live webhook dispatch, deploy production funnels without code.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 space-y-4 relative">
            <span className="text-3xl font-extrabold text-[#dcf1ff] absolute top-5 right-6 select-none">
              01
            </span>
            <div className="w-10 h-10 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <Workflow className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-base text-[#131416]">1. Map Visual Paths</h3>
            <p className="text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
              Drag choice nodes, text prompts, image selectors, and conditional branch gates on an infinite canvas. Connect ports with smooth bezier lines.
            </p>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 space-y-4 relative">
            <span className="text-3xl font-extrabold text-[#dcf1ff] absolute top-5 right-6 select-none">
              02
            </span>
            <div className="w-10 h-10 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
              <Calculator className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-base text-[#131416]">2. Configure Dynamic Formulas</h3>
            <p className="text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
              Define live arithmetic logic. Assign dollar multipliers to answers, tally score diagnostics, and present a live estimate card before the lead gate.
            </p>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 space-y-4 relative">
            <span className="text-3xl font-extrabold text-[#dcf1ff] absolute top-5 right-6 select-none">
              03
            </span>
            <div className="w-10 h-10 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
              <Zap className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-base text-[#131416]">3. Route Qualified Buyers</h3>
            <p className="text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
              Direct high-ticket respondents to your WhatsApp chat with prefilled quotes, open Calendly slots, or trigger webhooks directly into your CRM.
            </p>
          </div>
        </div>
      </section>

      {/* 4. Live Interactive Quote Playground */}
      <section id="calculator" className="scroll-mt-20 px-4 sm:px-8 max-w-5xl mx-auto space-y-8">
        <div className="text-center space-y-3 max-w-2xl mx-auto">
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#dcfae5] border border-[#bbf7d0] text-xs font-semibold text-[#166534]">
            <Calculator className="w-3.5 h-3.5 text-[#2f7a45]" />
            <span>Interactive Live Playground</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#131416]">
            Experience the Real-Time Calculation Engine
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            Adjust the sliders below to see how FilterFunnel recalculates dynamic estimates instantly without server latency.
          </p>
        </div>

        <div className="bg-white border border-[#e2e5e9] rounded-3xl p-6 sm:p-10 shadow-xs grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
          {/* Controls Column */}
          <div className="space-y-6">
            <div className="space-y-2">
              <label className="text-xs font-bold text-[#131416]">
                Select Service Tier
              </label>
              <div className="grid grid-cols-3 gap-2">
                {(['standard', 'premium', 'enterprise'] as const).map((tier) => (
                  <button
                    key={tier}
                    type="button"
                    onClick={() => setDemoTier(tier)}
                    className={`py-2 px-3 rounded-xl text-xs font-semibold capitalize border transition ${
                      demoTier === tier
                        ? 'bg-[#1d5fd6] text-white border-[#1d5fd6]'
                        : 'bg-[#f8fafc] text-[#5d5f60] border-[#e2e5e9] hover:bg-[#edf0f3]'
                    }`}
                  >
                    {tier}
                  </button>
                ))}
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="font-bold text-[#131416]">Scope / Complexity Units</span>
                <span className="font-mono font-bold text-[#1d5fd6]">{demoScope} Units</span>
              </div>
              <input
                type="range"
                min="1"
                max="10"
                value={demoScope}
                onChange={(e) => setDemoScope(Number(e.target.value))}
                className="w-full accent-[#1d5fd6] cursor-pointer"
              />
              <div className="flex justify-between text-[10px] text-[#5d5f60]">
                <span>1 (Basic)</span>
                <span>5 (Standard)</span>
                <span>10 (Full Scale)</span>
              </div>
            </div>

            <div className="pt-2">
              <label className="flex items-center gap-3 p-3 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] cursor-pointer hover:bg-white transition">
                <input
                  type="checkbox"
                  checked={includeAddon}
                  onChange={(e) => setIncludeAddon(e.target.checked)}
                  className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0 w-4 h-4"
                />
                <div className="text-xs">
                  <span className="font-semibold text-[#131416] block">Include Priority Onboarding & SLA</span>
                  <span className="text-[#5d5f60] text-[11px]">Adds +₹3,200 flat rate to calculation</span>
                </div>
              </label>
            </div>
          </div>

          {/* Live Dynamic Output Card */}
          <div className="p-6 sm:p-8 rounded-2xl bg-[#dcf1ff]/30 border border-[#b9e6fe] space-y-6 text-center">
            <span className="text-xs font-bold text-[#0a3a80]">
              Real-Time Dynamic Output
            </span>

            <div className="space-y-1">
              <div className="text-3xl sm:text-5xl font-extrabold text-[#131416] tracking-tight">
                ₹{calculatedQuote.toLocaleString()}
              </div>
              <p className="text-xs text-[#0a3a80]/80">
                Calculated on the client runtime in &lt;5 milliseconds
              </p>
            </div>

            <div className="space-y-2 pt-2 border-t border-[#b9e6fe]/60 text-xs text-left text-[#0a3a80]">
              <div className="flex justify-between">
                <span>Base Tier Fee:</span>
                <span className="font-mono font-bold">₹{baseRate}</span>
              </div>
              <div className="flex justify-between">
                <span>Scope Multiplier ({demoScope} x ₹180):</span>
                <span className="font-mono font-bold">₹{demoScope * 180}</span>
              </div>
              {includeAddon && (
                <div className="flex justify-between">
                  <span>Priority SLA Addon:</span>
                  <span className="font-mono font-bold">₹3,200</span>
                </div>
              )}
            </div>

            <Link
              to="/register"
              className="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
            >
              <span>Build This Formula In FilterFunnel</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </section>

      {/* 5. Industry Solutions & Use Cases */}
      <section id="use-cases" className="scroll-mt-20 px-4 sm:px-8 max-w-6xl mx-auto space-y-12">
        <div className="text-center space-y-3 max-w-3xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">Tailored Solutions</span>
          <h2 className="text-2xl sm:text-4xl font-bold tracking-tight text-[#131416]">
            High-Converting Funnels for Every High-Ticket Industry
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            Pre-built formulas, question sequences, and WhatsApp handoffs ready to deploy in minutes.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 hover:border-[#1d5fd6]/50 transition">
            <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <Stethoscope className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Clinics & Aesthetics</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Patient eligibility screening, dental procedure pricing, and instant booking into practice management calendars.
            </p>
            <div className="pt-2 text-xs font-semibold text-[#1d5fd6]">
              Avg +318% Consultation Rate
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 hover:border-[#1d5fd6]/50 transition">
            <div className="w-9 h-9 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
              <Building className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Digital Agencies</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Interactive client scoping, web design retainer estimators, and instant lead handoff directly to sales Slack channels.
            </p>
            <div className="pt-2 text-xs font-semibold text-[#2f7a45]">
              Avg ₹8.4L Retainers Captured
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 hover:border-[#1d5fd6]/50 transition">
            <div className="w-9 h-9 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
              <Hammer className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Home Services & Solar</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Square-footage roofing, kitchen remodeling, and solar panel savings calculators that qualify homeowner budgets upfront.
            </p>
            <div className="pt-2 text-xs font-semibold text-[#b45309]">
              -64% Cost Per Qualified Lead
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 hover:border-[#3730a3]/50 transition">
            <div className="w-9 h-9 rounded-xl bg-[#e0e7ff] text-[#3730a3] flex items-center justify-center font-bold">
              <TrendingUp className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Financial Advisory</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Retirement readiness diagnostic tests, mortgage loan qualification funnels, and wealth management lead routing.
            </p>
            <div className="pt-2 text-xs font-semibold text-[#3730a3]">
              4.2x Appointment Booking Lift
            </div>
          </div>
        </div>
      </section>

      {/* 6. Customer Stories / Social Proof */}
      <section id="testimonials" className="scroll-mt-20 px-4 sm:px-8 max-w-6xl mx-auto space-y-12">
        <div className="text-center space-y-3 max-w-3xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">Verified Customer Proof</span>
          <h2 className="text-2xl sm:text-4xl font-bold tracking-tight text-[#131416]">
            Loved by Fast-Growing Revenue Teams
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            See what founders, agency directors, and clinic managers achieve after switching to FilterFunnel.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 flex flex-col justify-between">
            <div className="space-y-3">
              <div className="flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((i) => (
                  <Star key={i} className="w-3.5 h-3.5 fill-[#b45309] text-[#b45309]" />
                ))}
              </div>
              <p className="text-xs sm:text-sm text-[#131416] leading-relaxed">
                "Our cosmetic dentistry clinic replaced a traditional appointment form with an interactive smile quiz. Patients love seeing their preliminary treatment tier instantly. We saw a 318% increase in booked consultations in month one."
              </p>
            </div>
            <div className="pt-4 border-t border-[#edf0f3] flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold text-xs">
                SC
              </div>
              <div>
                <span className="font-bold text-xs block text-[#131416]">Dr. Sarah Chen</span>
                <span className="text-[11px] text-[#5d5f60]">Founder, Apex Aesthetics Clinic</span>
              </div>
            </div>
          </div>

          <div className="p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 flex flex-col justify-between">
            <div className="space-y-3">
              <div className="flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((i) => (
                  <Star key={i} className="w-3.5 h-3.5 fill-[#b45309] text-[#b45309]" />
                ))}
              </div>
              <p className="text-xs sm:text-sm text-[#131416] leading-relaxed">
                "We build custom funnels for our B2B clients. The visual node canvas is responsive and fluid, and being able to route directly into WhatsApp with the pre-calculated quote closed ₹8,40,000 in new retainers within our first 30 days."
              </p>
            </div>
            <div className="pt-4 border-t border-[#edf0f3] flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold text-xs">
                MV
              </div>
              <div>
                <span className="font-bold text-xs block text-[#131416]">Marcus Vance</span>
                <span className="text-[11px] text-[#5d5f60]">Managing Director, Vance Media</span>
              </div>
            </div>
          </div>

          <div className="p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 flex flex-col justify-between">
            <div className="space-y-3">
              <div className="flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((i) => (
                  <Star key={i} className="w-3.5 h-3.5 fill-[#b45309] text-[#b45309]" />
                ))}
              </div>
              <p className="text-xs sm:text-sm text-[#131416] leading-relaxed">
                "Our home remodeling leads were costing over ₹1,100 each on Google Ads because people bounced off our 15-field static form. With FilterFunnel, our cost-per-qualified lead dropped to ₹390. The formula engine is unbeatable."
              </p>
            </div>
            <div className="pt-4 border-t border-[#edf0f3] flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold text-xs">
                ER
              </div>
              <div>
                <span className="font-bold text-xs block text-[#131416]">Elena Rostova</span>
                <span className="text-[11px] text-[#5d5f60]">VP Growth, Renova Home Solutions</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 7. Transparent SaaS Pricing Matrix (Dynamic Database Plans & Lifetime Deals) */}
      <section id="pricing" className="scroll-mt-20 px-4 sm:px-8 max-w-7xl mx-auto space-y-12">
        <div className="text-center space-y-4 max-w-2xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">Transparent Plans</span>
          <h2 className="text-2xl sm:text-4xl font-bold tracking-tight text-[#131416]">
            Simple Pricing That Scales With Your Revenue
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            Every plan includes visual node editing, dynamic quote formulas, and webhook endpoints.
          </p>

          {/* Cycle / Plan Filter Tabs */}
          <div className="pt-2 flex flex-wrap items-center justify-center gap-2">
            <button
              type="button"
              onClick={() => setSelectedCycleTab('all')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer ${
                selectedCycleTab === 'all'
                  ? 'bg-[#131416] text-white shadow-xs'
                  : 'bg-[#f4f6f8] text-[#5d5f60] hover:text-[#131416] hover:bg-[#e8eaec]'
              }`}
            >
              All Plans ({((publicPlans.length > 0 ? publicPlans : fallbackPlans)).length})
            </button>
            <button
              type="button"
              onClick={() => setSelectedCycleTab('monthly')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer ${
                selectedCycleTab === 'monthly'
                  ? 'bg-[#1d5fd6] text-white shadow-xs'
                  : 'bg-[#f4f6f8] text-[#5d5f60] hover:text-[#131416] hover:bg-[#e8eaec]'
              }`}
            >
              Monthly Subscriptions
            </button>
            <button
              type="button"
              onClick={() => setSelectedCycleTab('lifetime')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 ${
                selectedCycleTab === 'lifetime'
                  ? 'bg-[#d97706] text-white shadow-xs'
                  : 'bg-[#fef3c7] text-[#92400e] border border-[#fde68a] hover:bg-[#fde68a]'
              }`}
            >
              <Crown className="w-3.5 h-3.5" />
              <span>Lifetime Deals</span>
            </button>
          </div>
        </div>

        {/* Dynamic Pricing Cards Grid */}
        {loadingPlans ? (
          <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-5 items-stretch">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-4 animate-pulse">
                <div className="h-4 w-24 bg-[#e2e5e9] rounded" />
                <div className="h-8 w-32 bg-[#e2e5e9] rounded" />
                <div className="space-y-2 pt-4">
                  <div className="h-3 w-full bg-[#e2e5e9] rounded" />
                  <div className="h-3 w-3/4 bg-[#e2e5e9] rounded" />
                  <div className="h-3 w-5/6 bg-[#e2e5e9] rounded" />
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className={`grid grid-cols-1 ${
            (publicPlans.length > 0 ? publicPlans : fallbackPlans).filter((p) => {
              if (selectedCycleTab === 'all') return true;
              if (selectedCycleTab === 'lifetime') return p.billing_interval === 'lifetime';
              if (selectedCycleTab === 'monthly') return p.billing_interval === 'monthly' || p.billing_interval === 'yearly';
              return true;
            }).length >= 4
              ? 'md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5'
              : (publicPlans.length > 0 ? publicPlans : fallbackPlans).filter((p) => {
                  if (selectedCycleTab === 'all') return true;
                  if (selectedCycleTab === 'lifetime') return p.billing_interval === 'lifetime';
                  if (selectedCycleTab === 'monthly') return p.billing_interval === 'monthly' || p.billing_interval === 'yearly';
                  return true;
                }).length === 3
              ? 'md:grid-cols-3'
              : 'md:grid-cols-2'
          } gap-5 items-stretch`}>
            {(publicPlans.length > 0 ? publicPlans : fallbackPlans)
              .filter((p) => {
                if (selectedCycleTab === 'all') return true;
                if (selectedCycleTab === 'lifetime') return p.billing_interval === 'lifetime';
                if (selectedCycleTab === 'monthly') return p.billing_interval === 'monthly' || p.billing_interval === 'yearly';
                return true;
              })
              .map((p) => {
                const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
                const feats = typeof p.features_json === 'string' ? JSON.parse(p.features_json || '{}') : (p.features_json || {});
                const isLifetime = p.billing_interval === 'lifetime';
                const isFree = Number(p.price) === 0;
                const isPro = p.slug === 'pro';

                const premiumFeatures = [
                  { key: 'white_label', label: '100% White-Label (No Badge)' },
                  { key: 'calculator_mode', label: 'Formula & Quote Engine' },
                  { key: 'webhooks', label: 'HTTP Webhooks & Zapier' },
                  { key: 'csv_export', label: '1-Click CSV Lead Export' },
                  { key: 'api_access', label: 'Developer REST API Keys' },
                  { key: 'funnel_cloning', label: '1-Click Funnel Clone' },
                ];

                return (
                  <div
                    key={p.id}
                    className={`rounded-2xl p-6 flex flex-col justify-between transition-all duration-200 relative ${
                      isLifetime
                        ? 'bg-gradient-to-b from-[#fffbeb] to-white border-2 border-[#f59e0b] shadow-xs'
                        : isPro
                        ? 'bg-[#dcf1ff]/25 border-2 border-[#1d5fd6] shadow-xs'
                        : 'bg-white border border-[#e2e5e9] hover:border-[#1d5fd6]/40 hover:shadow-xs'
                    }`}
                  >
                    {/* Top Badges */}
                    {isLifetime && (
                      <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#d97706] text-white text-[10px] font-bold tracking-wide flex items-center gap-1 shadow-xs whitespace-nowrap">
                        <Crown className="w-3 h-3" />
                        <span>Lifetime Deal</span>
                      </div>
                    )}
                    {isPro && !isLifetime && (
                      <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#1d5fd6] text-white text-[10px] font-bold tracking-wide shadow-xs whitespace-nowrap">
                        Most Popular
                      </div>
                    )}
                    {isFree && (
                      <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#dcfae5] text-[#166534] border border-[#bbf7d0] text-[10px] font-bold tracking-wide shadow-xs whitespace-nowrap">
                        Free Forever
                      </div>
                    )}

                    <div className="space-y-4">
                      <div className="pt-1">
                        <h3 className={`text-base font-bold ${
                          isLifetime ? 'text-[#92400e]' : isPro ? 'text-[#0a3a80]' : 'text-[#131416]'
                        }`}>
                          {p.name}
                        </h3>
                        <p className="text-xs text-[#5d5f60] mt-1 min-h-[32px] line-clamp-2">
                          {p.description}
                        </p>
                      </div>

                      <div className="space-y-1">
                        <div className="text-3xl font-extrabold text-[#131416] tracking-tight">
                          ₹{Number(p.price).toLocaleString('en-IN')}
                        </div>
                        <span className="text-xs text-[#5d5f60] block">
                          {isFree
                            ? 'forever free, no card needed'
                            : isLifetime
                            ? 'one-time payment · lifetime access'
                            : 'per month, billed monthly'}
                        </span>
                      </div>

                      {/* Limits and Feature Highlights */}
                      <ul className="space-y-2 text-xs text-[#131416] pt-4 border-t border-[#edf0f3]">
                        <li className="flex items-center gap-2">
                          <Check className={`w-3.5 h-3.5 shrink-0 ${isLifetime ? 'text-[#d97706]' : isPro ? 'text-[#1d5fd6]' : 'text-[#2f7a45]'}`} />
                          <span>{Number(limits.max_funnels || 0) <= 0 ? 'Unlimited Active Funnels' : `${limits.max_funnels} Active Funnel${Number(limits.max_funnels) > 1 ? 's' : ''}`}</span>
                        </li>
                        <li className="flex items-center gap-2">
                          <Check className={`w-3.5 h-3.5 shrink-0 ${isLifetime ? 'text-[#d97706]' : isPro ? 'text-[#1d5fd6]' : 'text-[#2f7a45]'}`} />
                          <span>{Number(limits.max_submissions_per_month || 0) <= 0 ? 'Unlimited Leads / mo' : `${Number(limits.max_submissions_per_month).toLocaleString()} Leads / mo`}</span>
                        </li>
                        <li className="flex items-center gap-2">
                          <Check className={`w-3.5 h-3.5 shrink-0 ${isLifetime ? 'text-[#d97706]' : isPro ? 'text-[#1d5fd6]' : 'text-[#2f7a45]'}`} />
                          <span>{Number(limits.max_team_members || 0) <= 0 ? 'Unlimited Team Seats' : `${limits.max_team_members} Team Member${Number(limits.max_team_members) > 1 ? 's' : ''}`}</span>
                        </li>

                        {premiumFeatures.map(({ key, label }) => {
                          const enabled = Boolean(feats[key]) || isPro || (isLifetime && ['white_label', 'calculator_mode', 'csv_export', 'funnel_cloning'].includes(key));
                          if (!enabled) return null;
                          return (
                            <li key={key} className="flex items-center gap-2">
                              <Check className={`w-3.5 h-3.5 shrink-0 ${isLifetime ? 'text-[#d97706]' : 'text-[#1d5fd6]'}`} />
                              <span className="font-medium">{label}</span>
                            </li>
                          );
                        })}
                      </ul>
                    </div>

                    {/* Call-to-Action Link */}
                    <div className="pt-6">
                      {isFree ? (
                        <Link
                          to={user ? "/admin/dashboard" : "/register"}
                          className="w-full py-2.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] text-center hover:bg-[#f4f6f8] transition block shadow-xs"
                        >
                          {user ? "Go to Dashboard" : "Get Started Free"}
                        </Link>
                      ) : isLifetime ? (
                        <Link
                          to={user ? "/admin/billing" : "/register"}
                          className="w-full py-2.5 rounded-xl bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-semibold text-center transition shadow-xs flex items-center justify-center gap-1.5"
                        >
                          <span>Get Lifetime Access</span>
                          <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                      ) : isPro ? (
                        <Link
                          to={user ? "/admin/billing" : "/register"}
                          className="w-full py-2.5 rounded-xl bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-semibold text-center transition shadow-xs flex items-center justify-center gap-1.5"
                        >
                          <span>Start 14-Day Free Trial</span>
                          <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                      ) : (
                        <Link
                          to={user ? "/admin/billing" : "/register"}
                          className="w-full py-2.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] text-center hover:bg-[#f4f6f8] transition block shadow-xs"
                        >
                          {user ? `Upgrade to ${p.name}` : `Choose ${p.name}`}
                        </Link>
                      )}
                    </div>
                  </div>
                );
              })}
          </div>
        )}
      </section>

      {/* 8. Interactive FAQ Accordion */}
      <section id="faq" className="scroll-mt-20 px-4 sm:px-8 max-w-4xl mx-auto space-y-8">
        <div className="text-center space-y-3 max-w-2xl mx-auto">
          <span className="text-xs font-bold text-[#1d5fd6]">Got Questions?</span>
          <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#131416]">
            Frequently Asked Questions
          </h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">
            Everything you need to know about visual funnels, formula calculation, and team setup.
          </p>
        </div>

        <div className="space-y-3">
          {faqs.map((faq, index) => {
            const isOpen = openFaq === index;
            return (
              <div
                key={index}
                className="bg-white border border-[#e2e5e9] rounded-2xl overflow-hidden transition"
              >
                <button
                  type="button"
                  onClick={() => toggleFaq(index)}
                  className="w-full px-6 py-4 flex items-center justify-between text-left gap-4 hover:bg-[#f8fafc] transition"
                >
                  <span className="font-bold text-xs sm:text-sm text-[#131416]">
                    {faq.q}
                  </span>
                  <ChevronDown
                    className={`w-4 h-4 text-[#5d5f60] shrink-0 transition-transform ${
                      isOpen ? 'rotate-180 text-[#1d5fd6]' : ''
                    }`}
                  />
                </button>
                {isOpen && (
                  <div className="px-6 pb-5 pt-1 text-xs sm:text-sm text-[#5d5f60] leading-relaxed border-t border-[#edf0f3]">
                    {faq.a}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </section>

      {/* 9. Final High-Conversion CTA Banner */}
      <section className="px-4 sm:px-8 max-w-5xl mx-auto">
        <div className="p-8 sm:p-14 rounded-3xl bg-[#dcf1ff]/40 border border-[#b9e6fe] text-center space-y-6">
          <div className="space-y-2 max-w-2xl mx-auto">
            <h2 className="text-2xl sm:text-4xl font-extrabold text-[#131416] tracking-tight">
              Ready to Triple Your Lead Conversion Rates?
            </h2>
            <p className="text-xs sm:text-sm text-[#0a3a80] leading-relaxed">
              Build your first interactive quiz or real-time quote calculator in under 5 minutes. No coding required, no credit card needed.
            </p>
          </div>

          <div className="flex flex-wrap items-center justify-center gap-3">
            <Link
              to={user ? '/admin/dashboard' : '/register'}
              className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#1d5fd6] text-white text-xs sm:text-sm font-semibold hover:bg-[#1853bf] transition shadow-xs"
            >
              <span>{user ? 'Open Workspace' : 'Claim Free 14-Day Trial'}</span>
              <ArrowRight className="w-4 h-4" />
            </Link>
            <Link
              to="/support"
              className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white border border-[#b9e6fe] text-xs sm:text-sm font-semibold text-[#0a3a80] hover:bg-[#dcf1ff]/60 transition"
            >
              <span>Talk to Our Team</span>
            </Link>
          </div>

          <div className="pt-2 text-[11px] text-[#5d5f60] flex items-center justify-center gap-4">
            <span>Free 14-day trial</span>
            <span>•</span>
            <span>Instant setup</span>
            <span>•</span>
            <span>Cancel anytime</span>
          </div>
        </div>
      </section>
    </div>
  );
};
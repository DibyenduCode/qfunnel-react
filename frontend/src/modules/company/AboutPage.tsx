import React from 'react';
import { Link } from 'react-router-dom';
import {
  GitFork,
  ArrowRight,
  ShieldCheck,
  CheckCircle,
  Users,
  Award,
  Globe,
  Zap,
  Cpu,
  BarChart2,
  HeartHandshake
} from 'lucide-react';

export const AboutPage: React.FC = () => {
  return (
    <div className="py-12 sm:py-20 px-4 sm:px-8 max-w-5xl mx-auto space-y-16">
      {/* 1. Hero */}
      <div className="text-center space-y-4 max-w-3xl mx-auto">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#dcf1ff] border border-[#b9e6fe] text-xs font-semibold text-[#0a3a80]">
          <HeartHandshake className="w-3.5 h-3.5 text-[#1d5fd6]" />
          <span>About LeadCollector</span>
        </div>
        <h1 className="text-3xl sm:text-5xl font-bold tracking-tight text-[#131416]">
          Transforming static forms into <span className="text-[#1d5fd6]">intelligent conversations</span>
        </h1>
        <p className="text-sm sm:text-base text-[#5d5f60] leading-relaxed">
          We founded LeadCollector with a simple conviction: traditional 10-field web forms are broken. Buyers demand immediate value, transparency, and personal recommendations before handing over their contact information.
        </p>
      </div>

      {/* 2. Key Metrics Bar */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9]">
        <div className="space-y-1 text-center md:text-left">
          <div className="text-2xl sm:text-3xl font-extrabold text-[#131416]">1.2M+</div>
          <div className="text-xs text-[#5d5f60]">Leads Captured</div>
        </div>
        <div className="space-y-1 text-center md:text-left">
          <div className="text-2xl sm:text-3xl font-extrabold text-[#1d5fd6]">3.8x</div>
          <div className="text-xs text-[#5d5f60]">Average Conversion Lift</div>
        </div>
        <div className="space-y-1 text-center md:text-left">
          <div className="text-2xl sm:text-3xl font-extrabold text-[#2f7a45]">2,400+</div>
          <div className="text-xs text-[#5d5f60]">Active Workspaces</div>
        </div>
        <div className="space-y-1 text-center md:text-left">
          <div className="text-2xl sm:text-3xl font-extrabold text-[#131416]">42</div>
          <div className="text-xs text-[#5d5f60]">Countries Served</div>
        </div>
      </div>

      {/* 3. The Problem & The Solution */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
        <div className="p-6 sm:p-8 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 flex flex-col justify-between">
          <div className="space-y-3">
            <span className="text-xs font-bold text-[#b45309]">The Problem</span>
            <h2 className="text-xl font-bold text-[#131416]">Static forms bounce 97% of prospective buyers</h2>
            <p className="text-xs sm:text-sm text-[#5d5f60] leading-relaxed">
              When high-intent prospects arrive on your landing pages, presenting them with a wall of empty input boxes feels demanding and transactional. Visitors leave without knowing if your service fits their budget, timeline, or exact requirements.
            </p>
          </div>
          <div className="p-4 rounded-xl bg-[#fef3c7]/40 border border-[#fde68a] text-xs text-[#92400e]">
            High cost-per-click ad traffic is wasted because forms ask for everything while delivering zero upfront value.
          </div>
        </div>

        <div className="p-6 sm:p-8 rounded-2xl bg-[#dcf1ff]/30 border border-[#b9e6fe] space-y-4 flex flex-col justify-between">
          <div className="space-y-3">
            <span className="text-xs font-bold text-[#1d5fd6]">The LeadCollector Solution</span>
            <h2 className="text-xl font-bold text-[#0a3a80]">Interactive diagnostic journeys with instant value</h2>
            <p className="text-xs sm:text-sm text-[#0a3a80]/80 leading-relaxed">
              LeadCollector replaces rigid forms with guided visual question paths. Each answer recalculates dynamic project quotes, qualifies visitor readiness, and routes hot buyers straight to WhatsApp or scheduled consultations.
            </p>
          </div>
          <div className="p-4 rounded-xl bg-white border border-[#b9e6fe] text-xs text-[#0a3a80]">
            Visitors willingly share details because they receive a transparent instant quote, tailored roadmap, and direct expert access.
          </div>
        </div>
      </div>

      {/* 4. Core Architecture Values */}
      <div className="space-y-6">
        <div className="text-center space-y-2">
          <h2 className="text-xl sm:text-2xl font-bold text-[#131416]">Our Architectural Pillars</h2>
          <p className="text-xs sm:text-sm text-[#5d5f60]">Engineered from the ground up for conversion speed and tenant isolation.</p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-3">
            <div className="w-9 h-9 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <Cpu className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Visual Node Canvas</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Built on interactive React Flow graphs with zero-code branching, arithmetic formulas, score tallies, and live step previewing.
            </p>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-3">
            <div className="w-9 h-9 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Strict Multi-Tenancy</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Complete data isolation across tenants with encrypted API credentials, granular role-based team management, and GDPR compliance.
            </p>
          </div>

          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 space-y-3">
            <div className="w-9 h-9 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
              <Zap className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-sm text-[#131416]">Immediate Sales Handoff</h3>
            <p className="text-xs text-[#5d5f60] leading-relaxed">
              Convert prospects within 60 seconds with 1-click WhatsApp deep links, Calendly appointments, and automated webhook web hooks.
            </p>
          </div>
        </div>
      </div>

      {/* 5. CTA Card */}
      <div className="p-8 sm:p-12 rounded-3xl bg-white border border-[#e2e5e9] text-center space-y-6">
        <h2 className="text-2xl sm:text-3xl font-bold text-[#131416]">
          Ready to scale your lead generation?
        </h2>
        <p className="text-xs sm:text-sm text-[#5d5f60] max-w-xl mx-auto">
          Join thousands of agencies, clinics, and service businesses already building interactive funnels with LeadCollector.
        </p>
        <div className="flex flex-wrap items-center justify-center gap-3">
          <Link
            to="/register"
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
          >
            <span>Start Free Trial</span>
            <ArrowRight className="w-4 h-4" />
          </Link>
          <Link
            to="/support"
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416] hover:bg-[#f4f6f8] transition"
          >
            <span>Contact Support</span>
          </Link>
        </div>
      </div>
    </div>
  );
};
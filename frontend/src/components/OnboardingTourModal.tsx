import React, { useEffect, useState, useMemo } from 'react';
import { useTourStore, TourStep } from '../store/tourStore';
import {
  Sparkles,
  ArrowRight,
  ArrowLeft,
  X,
  Play,
  FileText,
  GitBranch,
  HelpCircle,
  Trophy,
  CheckCircle2,
  MousePointer2,
  Layers,
  ChevronRight,
  Zap,
  Globe,
  Sliders,
  Video,
  ShieldCheck
} from 'lucide-react';

const TOUR_STEPS: TourStep[] = [
  {
    id: 'step-1-create',
    stepNumber: 1,
    totalSteps: 6,
    badge: 'Step 1 of 6 • Getting Started',
    title: 'Create Your First Funnel',
    bengaliTitle: '১. প্রথম ফানেল তৈরি করুন',
    description: 'Funnels are the high-converting engines that turn visitors into qualified leads. Click the primary "Create Funnel" button to get started.',
    bengaliDescription: 'নতুন কাস্টমার জার্নি শুরু করতে প্রথমে ওপরের বা ড্যাশবোর্ডের "+ Create Funnel" বাটনে ক্লিক করুন।',
    targetSelector: '[data-tour="create-funnel-btn"]',
    position: 'bottom',
    diagramType: 'create',
    actionPrompt: 'Click "Next Step" to see how to name and configure your funnel.',
  },
  {
    id: 'step-2-details',
    stepNumber: 2,
    totalSteps: 6,
    badge: 'Step 2 of 6 • Funnel Setup',
    title: 'Name Your Funnel & Choose Mode',
    bengaliTitle: '২. ফানেলের নাম ও মোড নির্বাচন',
    description: 'Give your funnel a memorable title, custom URL slug, and select between Standard Quiz or Dynamic Calculator & Instant Quote mode.',
    bengaliDescription: 'ফানেলের একটি সুন্দর নাম ও URL স্লাগ দিন, এবং আপনার প্রয়োজনমতো কুইজ বা ক্যালকুলেটর মোড বেছে নিন।',
    targetSelector: '[data-tour="funnel-name-input"]',
    position: 'center',
    diagramType: 'details',
    actionPrompt: 'Next, let us explore the drag-and-drop Visual Canvas.',
  },
  {
    id: 'step-3-nodes',
    stepNumber: 3,
    totalSteps: 6,
    badge: 'Step 3 of 6 • Node Library Overview',
    title: 'Visual Canvas & Node Library',
    bengaliTitle: '৩. ভিজ্যুয়াল ক্যানভাস ও নোড লাইব্রেরি পরিচিতি',
    description: 'Inside the builder, the left sidebar holds your Node Library with 4 building blocks: Inputs, Media, Logic Routers, and Outcomes.',
    bengaliDescription: 'ক্যানভাসের বাম পাশে রয়েছে নোড লাইব্রেরি—ইনপুট ফর্ম, মিডিয়া, লজিক ডিসিশন এবং আউটকাম পেজ।',
    targetSelector: '[data-tour="node-library-panel"]',
    position: 'right',
    diagramType: 'nodes_overview',
    actionPrompt: 'Let us connect a hands-on basic workflow with 3 simple steps.',
  },
  {
    id: 'step-4-start-form',
    stepNumber: 4,
    totalSteps: 6,
    badge: 'Step 4 of 6 • Hands-on Workflow',
    title: 'Start Step ➔ 2-Field Lead Capture Form',
    bengaliTitle: '৪. এন্ট্রি পয়েন্ট থেকে ২-ফিল্ড লিড ক্যাপচার ফর্ম',
    description: 'Every funnel begins at the Start Step. Connect a wire to a Lead Capture Form node with 2 essential fields: Full Name & Work Email.',
    bengaliDescription: 'প্রতিটি ফানেল শুরু হয় Start Step দিয়ে। তার সাথে যুক্ত করুন একটি লিড ফর্ম যার মধ্যে ২টি ফিল্ড থাকবে: নাম ও ইমেইল।',
    position: 'center',
    diagramType: 'start_to_form',
    actionPrompt: 'Now let us add intelligent branching logic with a Decision Node.',
  },
  {
    id: 'step-5-decision-quiz',
    stepNumber: 5,
    totalSteps: 6,
    badge: 'Step 5 of 6 • Smart Branching',
    title: 'Form ➔ Decision Node ➔ Quiz Branch',
    bengaliTitle: '৫. ফর্ম থেকে ডিসিশন নোড এবং কুইজ ব্রাঞ্চিং',
    description: 'Connect the form into a Decision Node (Conditional Router). Route qualified prospects to an Interactive Quiz Node to qualify their budget.',
    bengaliDescription: 'ফর্ম থেকে একটি Decision Node-এ কানেক্ট করুন। যোগ্য ভিজিটরদের একটি কুইজ নোডে নিয়ে গিয়ে তাদের বাজেট ও চাহিদা যাচাই করুন।',
    position: 'center',
    diagramType: 'form_to_decision_quiz',
    actionPrompt: 'Finally, let us set the outcome and publish the funnel live!',
  },
  {
    id: 'step-6-publish',
    stepNumber: 6,
    totalSteps: 6,
    badge: 'Step 6 of 6 • Final Destination & Launch',
    title: 'Outcome Node & 1-Click Publishing',
    bengaliTitle: '৬. আউটকাম পেজ ও ফানেল পাবলিশ',
    description: 'Route quiz answers to a Thank You or Instant Quote Node. When your flowchart is ready, click Publish to get your live embed link!',
    bengaliDescription: 'কুইজ শেষে ফলাফল পেজ যুক্ত করুন এবং ওপরের Publish বাটনে ক্লিক করে আপনার ফানেলটি লাইভ করে শেয়ার করুন!',
    targetSelector: '[data-tour="publish-btn"]',
    position: 'bottom',
    diagramType: 'publish_launch',
    actionPrompt: 'You are ready to create high-converting funnels! Click "Finish & Start Building".',
  },
];

export const OnboardingTourModal: React.FC = () => {
  const { isOpen, currentStep, totalSteps, nextStep, prevStep, goToStep, skipTour } = useTourStore();
  const [highlightRect, setHighlightRect] = useState<DOMRect | null>(null);

  const step = useMemo(() => TOUR_STEPS[currentStep] || TOUR_STEPS[0], [currentStep]);

  // Keyboard navigation listener (Esc to close, Arrow keys for step navigation)
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        skipTour();
      } else if (e.key === 'ArrowRight') {
        nextStep();
      } else if (e.key === 'ArrowLeft') {
        prevStep();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, nextStep, prevStep, skipTour]);

  // Track target element rect if present on current screen
  useEffect(() => {
    if (!isOpen || !step.targetSelector) {
      setHighlightRect(null);
      return;
    }

    const updateRect = () => {
      const el = document.querySelector(step.targetSelector!);
      if (el) {
        const rect = el.getBoundingClientRect();
        setHighlightRect(rect);
      } else {
        setHighlightRect(null);
      }
    };

    updateRect();
    window.addEventListener('resize', updateRect);
    window.addEventListener('scroll', updateRect, true);
    return () => {
      window.removeEventListener('resize', updateRect);
      window.removeEventListener('scroll', updateRect, true);
    };
  }, [isOpen, step.targetSelector, currentStep]);

  if (!isOpen) return null;

  const progressPercent = Math.round(((currentStep + 1) / totalSteps) * 100);

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center pointer-events-auto select-none font-sans">
      {/* Dark Translucent Backdrop with Cutout Spotlight */}
      <div 
        className="fixed inset-0 bg-slate-950/75 backdrop-blur-[2px] transition-all duration-300"
        onClick={skipTour}
      />

      {/* Spotlight Ring around target element if found on screen */}
      {highlightRect && (
        <div
          className="fixed pointer-events-none transition-all duration-300 ease-out z-[101]"
          style={{
            top: `${highlightRect.top - 6}px`,
            left: `${highlightRect.left - 6}px`,
            width: `${highlightRect.width + 12}px`,
            height: `${highlightRect.height + 12}px`,
          }}
        >
          {/* Glowing Ring & Pulse Beacon */}
          <div className="w-full h-full rounded-xl border-2 border-[#38bdf8] shadow-[0_0_24px_rgba(56,189,248,0.7)] animate-pulse" />
          <div className="absolute -top-3 -right-3 w-6 h-6 bg-[#0284c7] rounded-full flex items-center justify-center shadow-lg border-2 border-white animate-bounce">
            <MousePointer2 className="w-3.5 h-3.5 text-white stroke-[2.5]" />
          </div>
        </div>
      )}

      {/* Main Tour Floating Card */}
      <div 
        className="relative z-[102] w-full max-w-[540px] mx-4 bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col text-slate-900 transition-all duration-200 animate-in fade-in zoom-in-95"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Top Progress Line */}
        <div className="w-full h-1.5 bg-slate-100 overflow-hidden">
          <div 
            className="h-full bg-gradient-to-r from-[#1d5fd6] via-[#38bdf8] to-[#2f7a45] transition-all duration-300 ease-out"
            style={{ width: `${progressPercent}%` }}
          />
        </div>

        {/* Card Header */}
        <div className="px-6 pt-5 pb-3 flex items-start justify-between border-b border-slate-100 bg-slate-50/70">
          <div className="space-y-1">
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#dcf1ff] text-[#1d5fd6] border border-[#b9e6fe]">
              <Sparkles className="w-3 h-3 text-[#1d5fd6]" />
              <span>{step.badge}</span>
            </div>
            <h3 className="text-base font-bold text-slate-900 leading-tight">
              {step.title}
            </h3>
            <p className="text-xs font-semibold text-[#1d5fd6]">
              {step.bengaliTitle}
            </p>
          </div>

          <button
            onClick={skipTour}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors"
            title="Skip Interactive Tutorial (Esc)"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Card Body & Hands-On Animated Visualizer */}
        <div className="px-6 py-4 space-y-4">
          <p className="text-xs text-slate-600 leading-relaxed">
            {step.description}
          </p>
          <div className="p-2.5 rounded-lg bg-blue-50/60 border border-blue-100 text-[11px] text-[#1e40af] flex items-center gap-2">
            <span className="font-bold text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-200/70 text-[#1e3a8a]">বাংলা গাইড</span>
            <span>{step.bengaliDescription}</span>
          </div>

          {/* Interactive Visualizer Canvas / Animated Diagram */}
          <div className="w-full h-[180px] bg-slate-950 rounded-xl border border-slate-800 p-3.5 flex flex-col justify-center relative overflow-hidden shadow-inner">
            {/* Background Grid Accent */}
            <div 
              className="absolute inset-0 opacity-15 pointer-events-none"
              style={{
                backgroundImage: 'radial-gradient(circle, #94a3b8 1px, transparent 1px)',
                backgroundSize: '16px 16px',
              }}
            />

            {/* STEP 1 DIAGRAM: Create Funnel Callout */}
            {step.diagramType === 'create' && (
              <div className="relative z-10 flex flex-col items-center justify-center space-y-3">
                <div className="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-3 shadow-md">
                  <div className="w-8 h-8 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold text-xs">
                    QF
                  </div>
                  <div className="text-left">
                    <p className="text-xs font-bold text-white">FilterFunnel Dashboard</p>
                    <p className="text-[10px] text-slate-400">0 of 5 funnels used</p>
                  </div>
                  <div className="ml-4 px-3 py-1.5 rounded-lg bg-[#1d5fd6] text-white text-xs font-bold flex items-center gap-1.5 shadow-[0_0_15px_rgba(29,95,214,0.7)] animate-pulse">
                    <span>+ Create Funnel</span>
                    <MousePointer2 className="w-3.5 h-3.5 text-white animate-bounce" />
                  </div>
                </div>
                <div className="flex items-center gap-2 text-[11px] text-sky-400 font-medium">
                  <span className="w-2 h-2 rounded-full bg-sky-400 animate-ping" />
                  <span>Click "+ Create Funnel" at top-right to initiate a new workflow</span>
                </div>
              </div>
            )}

            {/* STEP 2 DIAGRAM: Funnel Details & Mode Picker */}
            {step.diagramType === 'details' && (
              <div className="relative z-10 flex flex-col items-center justify-center space-y-2.5">
                <div className="w-full max-w-[380px] p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-left space-y-2">
                  <div className="flex items-center justify-between text-[11px]">
                    <span className="text-slate-400 font-medium">Funnel Name:</span>
                    <span className="text-sky-300 font-bold">B2B Qualified Lead Generator</span>
                  </div>
                  <div className="flex items-center gap-2 pt-1">
                    <div className="flex-1 p-1.5 rounded-lg bg-indigo-950/80 border border-indigo-500/50 text-[10px] text-indigo-300 flex items-center justify-center gap-1.5">
                      <HelpCircle className="w-3 h-3 text-indigo-400" />
                      <span className="font-semibold">Quiz Mode</span>
                    </div>
                    <div className="flex-1 p-1.5 rounded-lg bg-slate-800 border border-slate-700 text-[10px] text-slate-300 flex items-center justify-center gap-1.5">
                      <Sliders className="w-3 h-3 text-emerald-400" />
                      <span className="font-semibold">Calculator Mode</span>
                    </div>
                  </div>
                </div>
                <p className="text-[10px] text-slate-400">
                  Select <strong className="text-white">Blank Canvas</strong> to build from scratch or start with a template.
                </p>
              </div>
            )}

            {/* STEP 3 DIAGRAM: Node Library 4 Categories */}
            {step.diagramType === 'nodes_overview' && (
              <div className="relative z-10 grid grid-cols-2 gap-2 w-full max-w-[420px]">
                <div className="p-2 rounded-lg bg-slate-900/90 border border-indigo-900/60 flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-md bg-[#e0e7ff] text-[#3730a3] flex items-center justify-center shrink-0">
                    <FileText className="w-3.5 h-3.5" />
                  </div>
                  <div className="text-left min-w-0">
                    <p className="text-[11px] font-bold text-white truncate">1. Inputs</p>
                    <p className="text-[9px] text-slate-400 truncate">Forms, Sliders, Choices</p>
                  </div>
                </div>

                <div className="p-2 rounded-lg bg-slate-900/90 border border-rose-900/60 flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-md bg-[#ffedea] text-[#c41c29] flex items-center justify-center shrink-0">
                    <Video className="w-3.5 h-3.5" />
                  </div>
                  <div className="text-left min-w-0">
                    <p className="text-[11px] font-bold text-white truncate">2. Media</p>
                    <p className="text-[9px] text-slate-400 truncate">Video Gate, Stories</p>
                  </div>
                </div>

                <div className="p-2 rounded-lg bg-slate-900/90 border border-amber-900/60 flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-md bg-[#ffedd5] text-[#c2410c] flex items-center justify-center shrink-0">
                    <GitBranch className="w-3.5 h-3.5" />
                  </div>
                  <div className="text-left min-w-0">
                    <p className="text-[11px] font-bold text-white truncate">3. Logic</p>
                    <p className="text-[9px] text-slate-400 truncate">Start, Decision Routers</p>
                  </div>
                </div>

                <div className="p-2 rounded-lg bg-slate-900/90 border border-emerald-900/60 flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-md bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center shrink-0">
                    <Trophy className="w-3.5 h-3.5" />
                  </div>
                  <div className="text-left min-w-0">
                    <p className="text-[11px] font-bold text-white truncate">4. Outcomes</p>
                    <p className="text-[9px] text-slate-400 truncate">Thank You, Quotes</p>
                  </div>
                </div>
              </div>
            )}

            {/* STEP 4 DIAGRAM: Start Node -> 2-Field Lead Capture Form */}
            {step.diagramType === 'start_to_form' && (
              <div className="relative z-10 flex items-center justify-center gap-3 w-full">
                {/* Start Step Node */}
                <div className="p-2.5 rounded-xl bg-slate-900 border-2 border-emerald-500/70 shadow-lg text-left w-[140px] shrink-0">
                  <div className="flex items-center gap-1.5 pb-1">
                    <div className="w-5 h-5 rounded-md bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                      <Play className="w-3 h-3 fill-current" />
                    </div>
                    <span className="text-[11px] font-bold text-emerald-400">Start Step</span>
                  </div>
                  <p className="text-[9px] text-slate-400">Visitor entry point</p>
                </div>

                {/* Animated Connecting Flow Wire */}
                <div className="relative flex items-center justify-center w-14 shrink-0">
                  <div className="w-full h-0.5 bg-gradient-to-r from-emerald-500 to-indigo-500" />
                  <span className="absolute w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_8px_#38bdf8] animate-[ping_1.5s_infinite]" />
                  <ArrowRight className="absolute right-0 w-3.5 h-3.5 text-indigo-400" />
                </div>

                {/* 2-Field Lead Capture Form Node */}
                <div className="p-2.5 rounded-xl bg-slate-900 border-2 border-indigo-500/80 shadow-lg text-left w-[180px] shrink-0 space-y-1.5">
                  <div className="flex items-center gap-1.5">
                    <div className="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                      <FileText className="w-3 h-3" />
                    </div>
                    <span className="text-[11px] font-bold text-indigo-300">Lead Capture (2 Fields)</span>
                  </div>
                  <div className="space-y-1 pt-0.5">
                    <div className="px-2 py-0.5 rounded bg-slate-800 text-[9px] text-slate-300 flex items-center justify-between">
                      <span>👤 Full Name</span>
                      <span className="text-indigo-400 font-bold">*</span>
                    </div>
                    <div className="px-2 py-0.5 rounded bg-slate-800 text-[9px] text-slate-300 flex items-center justify-between">
                      <span>✉️ Work Email</span>
                      <span className="text-indigo-400 font-bold">*</span>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* STEP 5 DIAGRAM: Form -> Decision Node -> Quiz Question */}
            {step.diagramType === 'form_to_decision_quiz' && (
              <div className="relative z-10 flex items-center justify-center gap-2 w-full scale-95">
                {/* Form Node */}
                <div className="p-2 rounded-lg bg-slate-900 border border-indigo-500/70 text-left w-[105px] shrink-0">
                  <span className="text-[10px] font-bold text-indigo-400 block truncate">📋 Form Node</span>
                  <span className="text-[8px] text-slate-400 block">Name + Email</span>
                </div>

                {/* Wire 1 */}
                <div className="w-6 h-0.5 bg-indigo-500 relative flex items-center justify-center shrink-0">
                  <ArrowRight className="w-3 h-3 text-indigo-400 -mr-1" />
                </div>

                {/* Decision Node (Router) */}
                <div className="p-2 rounded-lg bg-slate-900 border-2 border-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.3)] text-left w-[120px] shrink-0">
                  <div className="flex items-center gap-1">
                    <GitBranch className="w-3 h-3 text-amber-400" />
                    <span className="text-[10px] font-bold text-amber-400">Decision Node</span>
                  </div>
                  <span className="text-[8px] text-amber-300/80 block">Rule: Qualified Lead?</span>
                </div>

                {/* Wire 2 */}
                <div className="w-6 h-0.5 bg-amber-500 relative flex items-center justify-center shrink-0">
                  <ArrowRight className="w-3 h-3 text-amber-400 -mr-1" />
                </div>

                {/* Multiple Choice Quiz Node */}
                <div className="p-2 rounded-lg bg-slate-900 border border-sky-500 text-left w-[125px] shrink-0 space-y-0.5">
                  <div className="flex items-center gap-1">
                    <HelpCircle className="w-3 h-3 text-sky-400" />
                    <span className="text-[10px] font-bold text-sky-300">Quiz Question</span>
                  </div>
                  <p className="text-[8px] text-slate-300 truncate font-medium">"What is your budget?"</p>
                  <p className="text-[7px] text-slate-400">Multiple choice branches</p>
                </div>
              </div>
            )}

            {/* STEP 6 DIAGRAM: Finished Funnel & Publish Button */}
            {step.diagramType === 'publish_launch' && (
              <div className="relative z-10 flex flex-col items-center justify-center space-y-3">
                <div className="flex items-center gap-2 text-xs font-semibold text-slate-300">
                  <span className="px-2 py-0.5 rounded bg-emerald-900/60 text-emerald-300 text-[10px] font-bold border border-emerald-700/50">
                    Start
                  </span>
                  <span className="text-slate-500">➔</span>
                  <span className="px-2 py-0.5 rounded bg-indigo-900/60 text-indigo-300 text-[10px] font-bold border border-indigo-700/50">
                    Form
                  </span>
                  <span className="text-slate-500">➔</span>
                  <span className="px-2 py-0.5 rounded bg-amber-900/60 text-amber-300 text-[10px] font-bold border border-amber-700/50">
                    Decision
                  </span>
                  <span className="text-slate-500">➔</span>
                  <span className="px-2 py-0.5 rounded bg-sky-900/60 text-sky-300 text-[10px] font-bold border border-sky-700/50">
                    Quiz
                  </span>
                  <span className="text-slate-500">➔</span>
                  <span className="px-2 py-0.5 rounded bg-purple-900/60 text-purple-300 text-[10px] font-bold border border-purple-700/50">
                    Outcome
                  </span>
                </div>

                <div className="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex items-center gap-3 shadow-[0_0_25px_rgba(37,99,235,0.6)]">
                  <CheckCircle2 className="w-5 h-5 text-emerald-300 shrink-0" />
                  <div className="text-left">
                    <p className="text-xs font-bold leading-tight">One-Click Publishing Ready</p>
                    <p className="text-[10px] text-blue-100">Click Publish at toolbar top to go live</p>
                  </div>
                  <div className="px-2.5 py-1 rounded bg-white text-blue-600 font-extrabold text-[11px] ml-2">
                    Publish 🚀
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Card Footer Navigation Controls */}
        <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
          {/* Step dots */}
          <div className="flex items-center gap-1.5">
            {TOUR_STEPS.map((s, idx) => (
              <button
                key={s.id}
                onClick={() => goToStep(idx)}
                className={`transition-all duration-200 rounded-full ${
                  idx === currentStep 
                    ? 'w-6 h-2 bg-[#1d5fd6]' 
                    : idx < currentStep 
                    ? 'w-2 h-2 bg-emerald-500' 
                    : 'w-2 h-2 bg-slate-300 hover:bg-slate-400'
                }`}
                title={`Jump to step ${idx + 1}: ${s.title}`}
              />
            ))}
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={skipTour}
              className="px-3 h-8 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/50 rounded-lg transition-colors"
            >
              Skip Tutorial
            </button>

            {currentStep > 0 && (
              <button
                onClick={prevStep}
                className="inline-flex items-center gap-1 px-3 h-8 text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg transition-colors shadow-xs"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                <span>Back</span>
              </button>
            )}

            <button
              onClick={nextStep}
              className="inline-flex items-center gap-1.5 px-4 h-8 text-xs font-bold text-white bg-[#1d5fd6] hover:bg-[#1853bf] rounded-lg transition-colors shadow-xs"
            >
              <span>{currentStep === totalSteps - 1 ? 'Finish & Build 🎉' : 'Next Step'}</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        {/* Keyboard Helper Sub-footer */}
        <div className="px-6 py-1.5 bg-slate-100 text-[10px] text-slate-500 flex items-center justify-between border-t border-slate-200/60">
          <span>Tip: You can use your keyboard <strong>Esc</strong> to close or <strong>➔</strong> to advance</span>
          <span className="font-semibold text-slate-400">FilterFunnel Onboarding Tour</span>
        </div>
      </div>
    </div>
  );
};

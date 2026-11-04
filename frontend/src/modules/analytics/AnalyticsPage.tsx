import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ChartOptions
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { Filter, Eye, FileText, Play, CheckCircle2, TrendingUp, Lock, Sparkles } from 'lucide-react';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

export const AnalyticsPage: React.FC = () => {
  const { features, isPlatformAdmin } = useAuthStore();
  const hasDropoff = Boolean(features?.step_dropoff_analytics || isPlatformAdmin);
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);

  const [funnels, setFunnels] = useState<any[]>([]);
  const [selectedFunnelId, setSelectedFunnelId] = useState<string>('');
  const [analytics, setAnalytics] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  // Question Choice Explorer state
  const [selectedQuestionId, setSelectedQuestionId] = useState<string>('');

  useEffect(() => {
    loadAnalytics();
  }, [selectedFunnelId]);

  const loadAnalytics = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/analytics', {
        funnel_id: selectedFunnelId || undefined,
      });
      setFunnels(res.funnels || []);
      if (!selectedFunnelId && res.selected_funnel_id) {
        setSelectedFunnelId(String(res.selected_funnel_id));
      }
      setAnalytics(res.analytics);

      if (res.analytics?.questions && res.analytics.questions.length > 0) {
        setSelectedQuestionId(String(res.analytics.questions[0].id));
      }
    } catch (err) {
      console.error('Failed to load analytics:', err);
    } finally {
      setLoading(false);
    }
  };

  const views = Number(analytics?.views || 0);
  const formStarts = Number(analytics?.form_starts || 0);
  const quizStarts = Number(analytics?.quiz_starts || 0);
  const completions = Number(analytics?.completions || 0);
  const completionRate = analytics?.completion_rate || '0';
  const leadCaptureRate = analytics?.lead_capture_rate || '0';

  // -------------------------------------------------------------
  // Full-Width Velocity Line Chart (Tension: 0, 5 faint hairlines)
  // -------------------------------------------------------------
  const trendLabels = analytics?.activity_trend?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  const trendStarts = analytics?.activity_trend?.starts || [0, 0, 0, 0, 0, 0, 0];
  const trendCompletions = analytics?.activity_trend?.completions || [0, 0, 0, 0, 0, 0, 0];

  const trendData = {
    labels: trendLabels,
    datasets: [
      {
        label: 'Quiz starts',
        data: trendStarts,
        borderColor: '#1d5fd6',
        backgroundColor: '#1d5fd6',
        tension: 0,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: false,
      },
      {
        label: 'Completions',
        data: trendCompletions,
        borderColor: '#2a8798',
        backgroundColor: '#2a8798',
        tension: 0,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: false,
      },
    ],
  };

  const trendOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top' as const,
        align: 'start' as const,
        labels: {
          boxWidth: 8,
          boxHeight: 8,
          usePointStyle: true,
          font: { family: 'IBM Plex Sans', size: 12 },
          color: '#5d5f60',
          padding: 16,
        },
      },
      tooltip: {
        backgroundColor: '#131416',
        padding: 8,
        titleFont: { family: 'IBM Plex Sans', size: 11, weight: 'bold' },
        bodyFont: { family: 'IBM Plex Sans', size: 11 },
        cornerRadius: 4,
      },
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { family: 'IBM Plex Sans', size: 11 }, color: '#5d5f60' },
      },
      y: {
        beginAtZero: true,
        grid: { color: '#f0f1f2' },
        ticks: {
          maxTicksLimit: 5,
          precision: 0,
          font: { family: 'IBM Plex Sans', size: 11 },
          color: '#5d5f60',
        },
      },
    },
  };

  // -------------------------------------------------------------
  // Data for 2x2 Share Tables
  // -------------------------------------------------------------
  // Table 1: Funnel progression steps
  const funnelStages = [
    { label: 'Total views', count: views },
    { label: 'Pre-form capture', count: formStarts },
    { label: 'Quiz starts', count: quizStarts },
    { label: 'Completed outcomes', count: completions },
  ];
  const maxStageCount = Math.max(...funnelStages.map((s) => s.count), 1);

  // Table 2: Question drop-offs
  const questionsList = analytics?.questions || [];
  const maxQuestionCount = Math.max(...questionsList.map((q: any) => Number(q.answers_count || 0)), 1);

  // Table 3: Destination outcomes
  const destinations = analytics?.destinations || [];
  const maxDestCount = Math.max(...destinations.map((d: any) => Number(d.reach_count || 0)), 1);
  const totalDestReaches = destinations.reduce((acc: number, d: any) => acc + Number(d.reach_count || 0), 0);

  // Table 4: Choice distribution for selected question
  const allOptions = analytics?.options || [];
  const activeQuestionOptions = allOptions.filter(
    (o: any) => String(o.question_id) === selectedQuestionId
  );
  const maxOptionCount = Math.max(...activeQuestionOptions.map((o: any) => Number(o.select_count || 0)), 1);
  const totalOptionSelects = activeQuestionOptions.reduce((acc: number, o: any) => acc + Number(o.select_count || 0), 0);

  return (
    <div className="space-y-8">
      {/* Top Header & Funnel Filter Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#f0f1f2]">
        <div>
          <h1 className="text-[22px] font-semibold text-[#131416]">Funnel Analytics</h1>
          <p className="text-sm text-[#5d5f60] mt-0.5">
            Progression rates, question drop-offs, and destination outcomes.
          </p>
        </div>

        <div className="flex items-center gap-2 self-start sm:self-auto">
          <label className="text-xs text-[#5d5f60] flex items-center gap-1.5">
            <Filter className="w-3.5 h-3.5 text-[#5d5f60]" />
            <span>Funnel:</span>
          </label>
          <select
            value={selectedFunnelId}
            onChange={(e) => setSelectedFunnelId(e.target.value)}
            className="h-8 px-2.5 rounded-[6px] border border-[#e8eaec] text-xs font-medium text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80] cursor-pointer"
          >
            {funnels.map((f: any) => (
              <option key={f.id} value={f.id}>
                {f.name} {f.funnel_mode === 'calculator' ? '(Calculator)' : ''}
              </option>
            ))}
          </select>
        </div>
      </div>

      {loading ? (
        <div className="space-y-6">
          {/* KPI Skeletons */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="bg-white border border-[#e2e5e9] rounded-2xl p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="h-3 w-20 rounded-md skeleton-shimmer" />
                  <div className="w-7 h-7 rounded-lg skeleton-shimmer" />
                </div>
                <div className="h-7 w-16 rounded-md skeleton-shimmer" />
                <div className="h-2.5 w-24 rounded-md skeleton-shimmer" />
              </div>
            ))}
          </div>

          {/* Chart Skeleton */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-4">
            <div className="flex items-center justify-between">
              <div className="space-y-1">
                <div className="h-4 w-40 rounded-md skeleton-shimmer" />
                <div className="h-3 w-60 rounded-md skeleton-shimmer" />
              </div>
              <div className="h-4 w-28 rounded-md skeleton-shimmer" />
            </div>
            <div className="h-56 rounded-xl skeleton-shimmer" />
          </div>

          {/* 2x2 Tables Skeleton */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {[1, 2].map((i) => (
              <div key={i} className="bg-white border border-[#e2e5e9] rounded-2xl p-4 space-y-3">
                <div className="h-4 w-32 rounded-md skeleton-shimmer" />
                <div className="space-y-2 pt-1">
                  {[1, 2, 3].map((j) => (
                    <div key={j} className="h-8 rounded-lg skeleton-shimmer" />
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : !analytics ? (
        <div className="py-16 text-center text-xs text-[#5d5f60]">
          No analytics data recorded for this funnel yet.
        </div>
      ) : (
        <>
          {/* Colorful Category KPI Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2 hover:border-[#1d5fd6]/50 transition">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-[#5d5f60]">Total Views</span>
                <div className="w-7 h-7 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
                  <Eye className="w-3.5 h-3.5" />
                </div>
              </div>
              <div className="text-2xl font-extrabold text-[#131416] tracking-tight">{views.toLocaleString()}</div>
              <span className="text-[11px] text-[#5d5f60] block">Top of funnel</span>
            </div>

            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2 hover:border-[#2f7a45]/50 transition">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-[#5d5f60]">Pre-Form Captures</span>
                <div className="w-7 h-7 rounded-lg bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
                  <FileText className="w-3.5 h-3.5" />
                </div>
              </div>
              <div className="text-2xl font-extrabold text-[#131416] tracking-tight">{formStarts.toLocaleString()}</div>
              <span className="text-[11px] text-[#2f7a45] font-semibold block">{leadCaptureRate}% capture rate</span>
            </div>

            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2 hover:border-[#3730a3]/50 transition">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-[#5d5f60]">Quiz Starts</span>
                <div className="w-7 h-7 rounded-lg bg-[#e0e7ff] text-[#3730a3] flex items-center justify-center font-bold">
                  <Play className="w-3.5 h-3.5 fill-current" />
                </div>
              </div>
              <div className="text-2xl font-extrabold text-[#131416] tracking-tight">{quizStarts.toLocaleString()}</div>
              <span className="text-[11px] text-[#5d5f60] block">Initial step reached</span>
            </div>

            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2 hover:border-[#0f766e]/50 transition">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-[#5d5f60]">Completions</span>
                <div className="w-7 h-7 rounded-lg bg-[#ccfbf1] text-[#0f766e] flex items-center justify-center font-bold">
                  <CheckCircle2 className="w-3.5 h-3.5" />
                </div>
              </div>
              <div className="text-2xl font-extrabold text-[#131416] tracking-tight">{completions.toLocaleString()}</div>
              <span className="text-[11px] text-[#5d5f60] block">Terminal outcomes</span>
            </div>

            <div className="bg-white border border-[#e2e5e9] rounded-2xl p-4 shadow-xs space-y-2 hover:border-[#1d5fd6]/50 transition">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-[#5d5f60]">Conversion Rate</span>
                <div className="w-7 h-7 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
                  <TrendingUp className="w-3.5 h-3.5" />
                </div>
              </div>
              <div className="text-2xl font-extrabold text-[#1d5fd6] tracking-tight">{completionRate}%</div>
              <span className="text-[11px] text-[#2f7a45] font-semibold block">Starts to completion</span>
            </div>
          </div>

          {/* Section: Full-Width Activity Trend Chart */}
          <div className="space-y-2 pt-4">
            <div className="flex items-baseline justify-between">
              <div>
                <h2 className="text-base font-medium text-[#131416]">Visitor Velocity</h2>
                <p className="text-sm text-[#5d5f60]">
                  Daily quiz starts and completions over the past 7 days.
                </p>
              </div>
            </div>

            <div className="h-[218px] w-full pt-2">
              <Line data={trendData} options={trendOptions} />
            </div>
          </div>

          {/* 2 x 2 Grid of Small Share Tables with 24px Gutter */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-6">
            {/* Table 1: Funnel Drop-off Stages */}
            <div className="space-y-3 relative">
              <div className="flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-base font-medium text-[#131416]">Funnel Stages</h3>
                    {!hasDropoff && (
                      <span className="px-1.5 py-0.2 rounded text-[9px] bg-[#dcf1ff] text-[#1d5fd6] font-bold">
                        PRO
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-[#5d5f60]">Drop-off progression across core funnel checkpoints.</p>
                </div>
                {!hasDropoff && (
                  <button
                    type="button"
                    onClick={() => setUpgradeModalOpen(true)}
                    className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#1d5fd6] text-white hover:bg-[#1853bf] transition shadow-xs"
                  >
                    Upgrade to View
                  </button>
                )}
              </div>

              <div className="space-y-1">
                <div className="grid grid-cols-12 text-xs text-[#5d5f60] pb-1 px-3">
                  <span className="col-span-6">Stage</span>
                  <span className="col-span-3 text-right">Share</span>
                  <span className="col-span-3 text-right">Visitors</span>
                </div>

                {funnelStages.map((stage, idx) => {
                  const sharePct = views > 0 ? ((stage.count / views) * 100).toFixed(1) : '0';
                  const barWidth = Math.max(4, Math.round((stage.count / maxStageCount) * 100));

                  return (
                    <div
                      key={idx}
                      className="relative h-11 px-3 rounded-[8px] flex items-center overflow-hidden"
                    >
                      {/* Rounded fill data bar */}
                      <div
                        className="absolute inset-y-0 left-0 bg-[#f4f6f8] rounded-[8px]"
                        style={{ width: `${barWidth}%` }}
                      />

                      <div className="relative z-10 grid grid-cols-12 w-full items-center text-xs">
                        <span className="col-span-6 font-medium text-[#131416] truncate">
                          {stage.label}
                        </span>
                        <span className="col-span-3 text-right text-xs text-[#5d5f60] num">
                          {sharePct}%
                        </span>
                        <span className="col-span-3 text-right text-sm text-[#131416] font-medium num">
                          {stage.count.toLocaleString()}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Table 2: Question Step Answers */}
            <div className="space-y-3">
              <div>
                <h3 className="text-base font-medium text-[#131416]">Question Drop-off</h3>
                <p className="text-sm text-[#5d5f60]">Answers logged at each question step in the graph.</p>
              </div>

              <div className="space-y-1">
                <div className="grid grid-cols-12 text-xs text-[#5d5f60] pb-1 px-3">
                  <span className="col-span-6">Question</span>
                  <span className="col-span-3 text-right">Retention</span>
                  <span className="col-span-3 text-right">Answers</span>
                </div>

                {questionsList.length === 0 ? (
                  <div className="h-10 border border-dashed border-[#e8eaec] rounded-[8px] flex items-center justify-center text-xs text-[#5d5f60]">
                    No question nodes configured.
                  </div>
                ) : (
                  questionsList.map((q: any, idx: number) => {
                    const count = Number(q.answers_count || 0);
                    const retentionPct = quizStarts > 0 ? ((count / quizStarts) * 100).toFixed(1) : '0';
                    const barWidth = Math.max(4, Math.round((count / maxQuestionCount) * 100));

                    return (
                      <div
                        key={q.id || idx}
                        className="relative h-11 px-3 rounded-[8px] flex items-center overflow-hidden"
                      >
                        <div
                          className="absolute inset-y-0 left-0 bg-[#dcf1ff]/70 rounded-[8px]"
                          style={{ width: `${barWidth}%` }}
                        />

                        <div className="relative z-10 grid grid-cols-12 w-full items-center text-xs">
                          <span className="col-span-6 font-medium text-[#131416] truncate">
                            {idx + 1}. {q.question_text || q.node_title}
                          </span>
                          <span className="col-span-3 text-right text-xs text-[#5d5f60] num">
                            {retentionPct}%
                          </span>
                          <span className="col-span-3 text-right text-sm text-[#131416] font-medium num">
                            {count.toLocaleString()}
                          </span>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>

            {/* Table 3: Destination Outcomes */}
            <div className="space-y-3">
              <div>
                <h3 className="text-base font-medium text-[#131416]">Destination Outcomes</h3>
                <p className="text-sm text-[#5d5f60]">Distribution of completed visitor endpoints.</p>
              </div>

              <div className="space-y-1">
                <div className="grid grid-cols-12 text-xs text-[#5d5f60] pb-1 px-3">
                  <span className="col-span-6">Outcome</span>
                  <span className="col-span-3 text-right">Share</span>
                  <span className="col-span-3 text-right">Count</span>
                </div>

                {destinations.length === 0 ? (
                  <div className="h-10 border border-dashed border-[#e8eaec] rounded-[8px] flex items-center justify-center text-xs text-[#5d5f60]">
                    No terminal outcomes reached in this range.
                  </div>
                ) : (
                  destinations.map((d: any, idx: number) => {
                    const count = Number(d.reach_count || 0);
                    const sharePct = totalDestReaches > 0 ? ((count / totalDestReaches) * 100).toFixed(1) : '0';
                    const barWidth = Math.max(4, Math.round((count / maxDestCount) * 100));

                    return (
                      <div
                        key={d.id || idx}
                        className="relative h-11 px-3 rounded-[8px] flex items-center overflow-hidden"
                      >
                        <div
                          className="absolute inset-y-0 left-0 bg-[#dcf1ff]/70 rounded-[8px]"
                          style={{ width: `${barWidth}%` }}
                        />

                        <div className="relative z-10 grid grid-cols-12 w-full items-center text-xs">
                          <span className="col-span-6 font-medium text-[#131416] truncate">
                            {d.name || `Outcome ${idx + 1}`}
                          </span>
                          <span className="col-span-3 text-right text-xs text-[#5d5f60] num">
                            {sharePct}%
                          </span>
                          <span className="col-span-3 text-right text-sm text-[#131416] font-medium num">
                            {count.toLocaleString()}
                          </span>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>

            {/* Table 4: Question Choice Breakdown */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-base font-medium text-[#131416]">Choice Distribution</h3>
                  <p className="text-sm text-[#5d5f60]">Option selection breakdown for chosen question.</p>
                </div>
                {questionsList.length > 0 && (
                  <select
                    value={selectedQuestionId}
                    onChange={(e) => setSelectedQuestionId(e.target.value)}
                    className="h-7 px-2 rounded-[4px] border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
                  >
                    {questionsList.map((q: any, i: number) => (
                      <option key={q.id} value={q.id}>
                        {i + 1}. {q.question_text || q.node_title}
                      </option>
                    ))}
                  </select>
                )}
              </div>

              <div className="space-y-1">
                <div className="grid grid-cols-12 text-xs text-[#5d5f60] pb-1 px-3">
                  <span className="col-span-6">Option</span>
                  <span className="col-span-3 text-right">Share</span>
                  <span className="col-span-3 text-right">Selects</span>
                </div>

                {activeQuestionOptions.length === 0 ? (
                  <div className="h-10 border border-dashed border-[#e8eaec] rounded-[8px] flex items-center justify-center text-xs text-[#5d5f60]">
                    No choice options recorded for this step.
                  </div>
                ) : (
                  activeQuestionOptions.map((opt: any, idx: number) => {
                    const count = Number(opt.select_count || 0);
                    const sharePct = totalOptionSelects > 0 ? ((count / totalOptionSelects) * 100).toFixed(1) : '0';
                    const barWidth = Math.max(4, Math.round((count / maxOptionCount) * 100));

                    return (
                      <div
                        key={opt.id || idx}
                        className="relative h-11 px-3 rounded-[8px] flex items-center overflow-hidden"
                      >
                        <div
                          className="absolute inset-y-0 left-0 bg-[#dcf1ff]/70 rounded-[8px]"
                          style={{ width: `${barWidth}%` }}
                        />

                        <div className="relative z-10 grid grid-cols-12 w-full items-center text-xs">
                          <span className="col-span-6 font-medium text-[#131416] truncate">
                            {opt.label || `Choice ${idx + 1}`}
                          </span>
                          <span className="col-span-3 text-right text-xs text-[#5d5f60] num">
                            {sharePct}%
                          </span>
                          <span className="col-span-3 text-right text-sm text-[#131416] font-medium num">
                            {count.toLocaleString()}
                          </span>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          </div>
        </>
      )}
      {/* Upgrade Modal */}
      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName="Step Drop-Off & Bottleneck Analytics"
        description="Visual funnel progression and question abandonment metrics are available on Pro and higher plans. Upgrade to identify exact conversion friction points."
        requiredPlan="Pro"
      />
    </div>
  );
};

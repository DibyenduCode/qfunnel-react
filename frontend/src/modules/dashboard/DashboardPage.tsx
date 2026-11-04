import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../api/client';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  ChartOptions
} from 'chart.js';
import { Line, Doughnut } from 'react-chartjs-2';
import {
  GitFork,
  Users,
  Eye,
  CheckCircle2,
  ArrowRight,
  Plus,
  ExternalLink,
  TrendingUp,
  PieChart
} from 'lucide-react';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

export const DashboardPage: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/dashboard');
      setData(res);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1.5">
            <div className="h-6 w-32 rounded-md skeleton-shimmer" />
            <div className="h-3 w-64 rounded-md skeleton-shimmer" />
          </div>
          <div className="h-8 w-28 rounded-md skeleton-shimmer" />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-3 shadow-xs">
              <div className="flex items-center justify-between">
                <div className="h-3 w-20 rounded-md skeleton-shimmer" />
                <div className="w-8 h-8 rounded-xl skeleton-shimmer" />
              </div>
              <div className="h-8 w-16 rounded-md skeleton-shimmer" />
              <div className="h-4 w-28 rounded-md skeleton-shimmer" />
            </div>
          ))}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-4">
            <div className="h-4 w-40 rounded-md skeleton-shimmer" />
            <div className="h-64 rounded-xl skeleton-shimmer" />
          </div>
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 space-y-4">
            <div className="h-4 w-32 rounded-md skeleton-shimmer" />
            <div className="h-64 rounded-xl skeleton-shimmer" />
          </div>
        </div>
      </div>
    );
  }

  const metrics = data?.metrics || {};
  const usage = data?.usage || {};
  const recentFunnels = data?.recent_funnels || [];

  // Trend Chart Data
  const trendLabels = metrics?.activity_trend?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  const trendStarts = metrics?.activity_trend?.starts || [0, 0, 0, 0, 0, 0, 0];
  const trendCompletions = metrics?.activity_trend?.completions || [0, 0, 0, 0, 0, 0, 0];

  const trendData = {
    labels: trendLabels,
    datasets: [
      {
        label: 'Quiz starts',
        data: trendStarts,
        borderColor: '#1d5fd6',
        backgroundColor: 'rgba(29, 95, 214, 0.1)',
        tension: 0,
        fill: true,
        pointBackgroundColor: '#1d5fd6',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 1.5,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
      {
        label: 'Completions',
        data: trendCompletions,
        borderColor: '#2a8798',
        backgroundColor: 'rgba(42, 135, 152, 0.08)',
        tension: 0,
        fill: true,
        pointBackgroundColor: '#2a8798',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 1.5,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
    ],
  };

  const trendOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top' as const,
        align: 'end' as const,
        labels: { boxWidth: 8, usePointStyle: true, font: { size: 11, family: '"IBM Plex Sans", sans-serif' }, color: '#5d5f60' },
      },
      tooltip: {
        backgroundColor: '#131416',
        padding: 8,
        titleFont: { size: 11, family: '"IBM Plex Sans", sans-serif' },
        bodyFont: { size: 11, family: '"IBM Plex Mono", monospace' },
        cornerRadius: 6,
      },
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { size: 11, family: '"IBM Plex Sans", sans-serif' }, color: '#5d5f60' },
      },
      y: {
        beginAtZero: true,
        grid: { color: '#f0f1f2' },
        ticks: { precision: 0, font: { size: 11, family: '"IBM Plex Mono", monospace' }, color: '#5d5f60' },
      },
    },
  };

  // Pipeline Stages Distribution Data
  const stageDistribution = metrics?.stage_distribution || {};
  const stageLabels = stageDistribution.labels || [];
  const stageDataPoints = stageDistribution.data || [];
  const stageColors = ['#1b6c80', '#2f70bb', '#a86f0c', '#5d5f60', '#cdced1'];

  const stageChartData = {
    labels: stageLabels,
    datasets: [
      {
        data: stageDataPoints,
        backgroundColor: stageColors,
        borderColor: '#ffffff',
        borderWidth: 1.5,
        hoverOffset: 3,
      },
    ],
  };

  const stageChartOptions: ChartOptions<'doughnut'> = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: { boxWidth: 8, usePointStyle: true, font: { size: 11, family: '"IBM Plex Sans", sans-serif' }, color: '#5d5f60' },
      },
      tooltip: {
        backgroundColor: '#131416',
        padding: 8,
        titleFont: { size: 11, family: '"IBM Plex Sans", sans-serif' },
        bodyFont: { size: 11, family: '"IBM Plex Mono", monospace' },
        cornerRadius: 6,
        callbacks: {
          label: (ctx) => {
            const val = ctx.raw as number;
            const total = stageDataPoints.reduce((acc: number, v: number) => acc + v, 0);
            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0';
            return ` ${ctx.label}: ${val} (${pct}%)`;
          },
        },
      },
    },
  };

  return (
    <div className="space-y-6">
      {/* Title Row */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold text-[#131416]">Overview</h1>
          <p className="text-xs text-[#5d5f60] mt-0.5">Real-time funnel completions, leads, and usage quota</p>
        </div>
        <Link
          to="/admin/funnels/create"
          className="inline-flex items-center gap-1.5 px-3 h-8 rounded-md bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] transition self-start sm:self-auto shadow-xs"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>Create Funnel</span>
        </Link>
      </div>

      {/* 4 Category-Coded Metric Cards with Pastel Category Tiles */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1: Active Funnels (Sky Blue) */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 shadow-xs space-y-3 hover:border-[#1d5fd6]/50 transition">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Active Funnels</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <GitFork className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {metrics?.published_funnels ?? metrics?.total_funnels ?? usage?.funnels?.used ?? 0}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#0a3a80] font-medium">
              <span className="px-1.5 py-0.5 rounded bg-[#dcf1ff] border border-[#b9e6fe]">
                {metrics?.total_funnels ?? 0} total · Limit: {usage?.funnels?.is_unlimited || !usage?.funnels?.limit || usage?.funnels?.limit <= 0 ? 'Unlimited' : usage.funnels.limit}
              </span>
            </div>
          </div>
        </div>

        {/* Card 2: Total Leads (Emerald Green) */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 shadow-xs space-y-3 hover:border-[#2f7a45]/50 transition">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Total Leads</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
              <Users className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {Number(metrics?.total_leads ?? metrics?.total_submissions ?? 0).toLocaleString()}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#2f7a45] font-medium">
              <span className="px-1.5 py-0.5 rounded bg-[#dcfae5] border border-[#bbf7d0]">
                {metrics?.qualified_submissions ?? metrics?.total_leads ?? 0} qualified
              </span>
            </div>
          </div>
        </div>

        {/* Card 3: Total Views (Sunset Amber) */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 shadow-xs space-y-3 hover:border-[#b45309]/50 transition">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Total Views</span>
            <div className="w-8 h-8 rounded-xl bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
              <Eye className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#131416] tracking-tight">
              {Number(metrics?.total_views ?? metrics?.total_starts ?? 0).toLocaleString()}
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#92400e] font-medium">
              <span className="px-1.5 py-0.5 rounded bg-[#fef3c7] border border-[#fde68a]">
                {metrics?.total_starts ?? metrics?.total_views ?? 0} visitor sessions
              </span>
            </div>
          </div>
        </div>

        {/* Card 4: Conversion Rate (Cobalt Royal Blue) */}
        <div className="bg-white border border-[#e2e5e9] rounded-2xl p-5 shadow-xs space-y-3 hover:border-[#1d5fd6]/50 transition">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-[#5d5f60]">Completion Rate</span>
            <div className="w-8 h-8 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center font-bold">
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-extrabold text-[#1d5fd6] tracking-tight">
              {metrics?.completion_rate ?? 0}%
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-[#2f7a45] font-medium">
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span>{metrics?.total_completions ?? 0} completions</span>
            </div>
          </div>
        </div>
      </div>

      {/* Visual Analytics Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* 7-Day Performance Velocity Chart (7 cols) */}
        <div className="lg:col-span-7 bg-white border border-[#e8eaec] rounded-lg p-5 space-y-3">
          <div className="flex items-center justify-between pb-3 border-b border-[#f0f1f2]">
            <div>
              <h3 className="text-xs font-medium text-[#131416]">7-day visitor velocity</h3>
              <p className="text-[11px] text-[#5d5f60]">Quiz starts vs completed outcomes</p>
            </div>
            <Link
              to="/admin/analytics"
              className="text-xs font-medium text-[#1d5fd6] hover:underline flex items-center gap-1"
            >
              <span>Full Analytics</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="h-60">
            <Line data={trendData} options={trendOptions} />
          </div>
        </div>

        {/* Pipeline Stage Distribution (5 cols) */}
        <div className="lg:col-span-5 bg-white border border-[#e8eaec] rounded-lg p-5 space-y-3">
          <div className="flex items-center justify-between pb-3 border-b border-[#f0f1f2]">
            <div>
              <h3 className="text-xs font-medium text-[#131416]">Pipeline distribution</h3>
              <p className="text-[11px] text-[#5d5f60]">Leads categorized across stages</p>
            </div>
            <span className="text-[11px] font-medium text-[#5d5f60] bg-[#f4f6f8] px-2 py-0.5 rounded">
              {stageDistribution.total ?? 0} leads
            </span>
          </div>

          {stageDataPoints.length === 0 || stageDataPoints.every((v: number) => v === 0) ? (
            <div className="h-60 flex items-center justify-center text-xs text-[#5d5f60]">
              No leads categorized in CRM pipeline yet.
            </div>
          ) : (
            <div className="h-60 relative flex items-center justify-center">
              <Doughnut data={stageChartData} options={stageChartOptions} />
            </div>
          )}
        </div>
      </div>

      {/* Recent Funnels Section */}
      <div className="bg-white border border-[#e8eaec] rounded-lg overflow-hidden">
        <div className="p-4 sm:p-5 border-b border-[#f0f1f2] flex items-center justify-between">
          <div>
            <h2 className="text-sm font-semibold text-[#131416]">Recent funnels</h2>
            <p className="text-xs text-[#5d5f60]">Jump into the canvas builder or configure routes</p>
          </div>
          <Link to="/admin/funnels" className="text-xs font-medium text-[#1b6c80] hover:underline flex items-center gap-1">
            <span>View all</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {recentFunnels.length === 0 ? (
          <div className="p-10 text-center">
            <GitFork className="w-8 h-8 text-[#5d5f60] mx-auto mb-2.5" />
            <p className="text-xs font-medium text-[#131416]">No funnels created yet</p>
            <p className="text-xs text-[#5d5f60] mt-1">Start by creating your first branching quiz funnel.</p>
            <Link
              to="/admin/funnels/create"
              className="mt-3 inline-flex items-center gap-1.5 px-3 h-8 rounded-md bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] shadow-xs"
            >
              <Plus className="w-3.5 h-3.5" />
              <span>Create Funnel</span>
            </Link>
          </div>
        ) : (
          <div className="divide-y divide-[#f0f1f2]">
            {recentFunnels.map((funnel: any) => (
              <div key={funnel.id} className="p-3.5 sm:px-5 flex items-center justify-between hover:bg-[#f9fbfc] transition">
                <div className="min-w-0 flex-1 pr-4">
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-semibold text-[#131416] truncate">{funnel.name}</span>
                    <span className={`px-2 py-0.2 rounded text-[10px] font-medium ${
                      funnel.status === 'published' ? 'bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]' : 'bg-[#eef1f4] text-[#3a3d40] border border-[#e2e5e9]'
                    }`}>
                      {funnel.status === 'published' ? 'Published' : 'Draft'}
                    </span>
                    {funnel.funnel_mode === 'calculator' && (
                      <span className="px-1.5 py-0.2 rounded text-[10px] bg-[#dcf1ff] text-[#0a3a80] font-medium border border-[#b9e6fe]">
                        Calculator
                      </span>
                    )}
                  </div>
                  <span className="text-[11px] text-[#5d5f60] font-mono mt-0.5 block truncate">
                    /quiz/{funnel.slug}
                  </span>
                </div>

                <div className="flex items-center gap-1.5">
                  {funnel.status === 'published' && (
                    <a
                      href={`/quiz/${funnel.slug}`}
                      target="_blank"
                      rel="noreferrer"
                      className="p-1.5 text-[#5d5f60] hover:text-[#131416] rounded-md hover:bg-[#f4f6f8]"
                      title="Open Public Quiz"
                    >
                      <ExternalLink className="w-3.5 h-3.5" />
                    </a>
                  )}
                  <Link
                    to={`/admin/funnels/${funnel.id}/builder`}
                    className="px-2.5 h-7 inline-flex items-center rounded-md bg-[#1d5fd6] text-white text-xs font-medium hover:bg-[#1853bf] transition shadow-xs"
                  >
                    Builder
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

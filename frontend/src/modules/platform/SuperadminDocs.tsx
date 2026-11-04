import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { Code2, Copy, Check, ExternalLink, Globe, Key, Send, Layers } from 'lucide-react';

export const SuperadminDocs: React.FC = () => {
  const [docsData, setDocsData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'api' | 'webhooks' | 'embed'>('api');
  const [copiedSnippet, setCopiedSnippet] = useState<string | null>(null);

  useEffect(() => {
    loadDocs();
  }, []);

  const loadDocs = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/docs');
      setDocsData(res);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopiedSnippet(id);
    setTimeout(() => setCopiedSnippet(null), 2000);
  };

  const baseUrl = docsData?.base_url || window.location.origin;
  const sampleApiKey = docsData?.sample_api_key || 'qfn_live_demo123456789';
  const sampleFunnelSlug = docsData?.sample_funnel_slug || 'instant-quote-quiz';
  const sampleFunnelId = docsData?.sample_funnel_id || 1;

  if (loading) {
    return (
      <div className="space-y-6 max-w-7xl mx-auto">
        <div className="h-10 w-64 rounded-xl skeleton-shimmer" />
        <div className="h-96 rounded-2xl skeleton-shimmer" />
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#ede9fe] text-[#5b21b6]">
              <Code2 className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Developer & Integration Hub</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Documentation, REST endpoints, outbound webhook payloads, and universal website embedding codes.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <span className="px-3 py-1 rounded-full bg-white border border-[#e2e5e9] text-xs font-semibold text-[#131416]">
            API Engine: v1 Active
          </span>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="border-b border-[#e2e5e9] flex gap-2 sm:gap-6 text-xs font-semibold overflow-x-auto">
        <button
          type="button"
          onClick={() => setActiveTab('api')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'api'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          1. REST API
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('webhooks')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'webhooks'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          2. Webhooks & Automation
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('embed')}
          className={`pb-3 border-b-2 transition whitespace-nowrap ${
            activeTab === 'embed'
              ? 'border-[#1d5fd6] text-[#1d5fd6] font-bold'
              : 'border-transparent text-[#5d5f60] hover:text-[#131416]'
          }`}
        >
          3. Universal Website Embedding
        </button>
      </div>

      {/* TAB 1: REST API */}
      {activeTab === 'api' && (
        <div className="space-y-6">
          {/* Authentication Card */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-sm font-bold text-[#131416]">API Authentication</h2>
                <p className="text-xs text-[#5d5f60]">Include your workspace API key in request headers.</p>
              </div>
              <span className="px-2.5 py-1 rounded-md bg-[#f4f6f8] text-[#131416] text-[11px] font-mono">
                X-API-Key: {sampleApiKey}
              </span>
            </div>

            <div className="p-4 rounded-xl bg-[#1e293b] text-slate-100 text-xs font-mono relative">
              <button
                onClick={() =>
                  copyToClipboard(
                    `curl -X GET "${baseUrl}/api/v1/funnels" \\\n  -H "X-API-Key: ${sampleApiKey}"`,
                    'auth-curl'
                  )
                }
                className="absolute top-3 right-3 p-1 rounded-md bg-slate-700/60 hover:bg-slate-700 text-slate-300"
                title="Copy Curl"
              >
                {copiedSnippet === 'auth-curl' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              </button>
              <pre className="overflow-x-auto">
{`curl -X GET "${baseUrl}/api/v1/funnels" \\
  -H "X-API-Key: ${sampleApiKey}"`}
              </pre>
            </div>
          </div>

          {/* Endpoint 1: List Funnels */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div className="flex items-center gap-2">
              <span className="px-2 py-0.5 rounded text-[11px] font-bold bg-[#dcfae5] text-[#2f7a45]">
                GET
              </span>
              <span className="text-xs font-mono font-bold text-[#131416]">
                /api/v1/funnels
              </span>
            </div>
            <p className="text-xs text-[#5d5f60]">
              Returns a paginated list of all active quiz funnels published in your tenant workspace.
            </p>

            <div className="p-4 rounded-xl bg-[#1e293b] text-slate-100 text-xs font-mono relative">
              <button
                onClick={() =>
                  copyToClipboard(
                    JSON.stringify(
                      {
                        success: true,
                        data: [
                          {
                            id: sampleFunnelId,
                            name: docsData?.sample_funnel_name || 'Instant Quote Quiz',
                            slug: sampleFunnelSlug,
                            status: 'published',
                            total_views: 1240,
                            total_submissions: 340,
                            conversion_rate: '27.4%',
                            created_at: '2026-10-15T09:30:00Z'
                          }
                        ]
                      },
                      null,
                      2
                    ),
                    'funnels-json'
                  )
                }
                className="absolute top-3 right-3 p-1 rounded-md bg-slate-700/60 hover:bg-slate-700 text-slate-300"
                title="Copy Response"
              >
                {copiedSnippet === 'funnels-json' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              </button>
              <pre className="overflow-x-auto text-[11px] leading-relaxed">
{`{
  "success": true,
  "data": [
    {
      "id": ${sampleFunnelId},
      "name": "${docsData?.sample_funnel_name || 'Instant Quote Quiz'}",
      "slug": "${sampleFunnelSlug}",
      "status": "published",
      "total_views": 1240,
      "total_submissions": 340,
      "conversion_rate": "27.4%"
    }
  ]
}`}
              </pre>
            </div>
          </div>

          {/* Endpoint 2: Retrieve Leads & Submissions */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div className="flex items-center gap-2">
              <span className="px-2 py-0.5 rounded text-[11px] font-bold bg-[#dcfae5] text-[#2f7a45]">
                GET
              </span>
              <span className="text-xs font-mono font-bold text-[#131416]">
                /api/v1/funnels/{sampleFunnelId}/submissions
              </span>
            </div>
            <p className="text-xs text-[#5d5f60]">
              Fetches all recorded leads with full question responses, calculate formulas, and visitor device metadata.
            </p>

            <div className="p-4 rounded-xl bg-[#1e293b] text-slate-100 text-xs font-mono relative">
              <pre className="overflow-x-auto text-[11px] leading-relaxed">
{`{
  "success": true,
  "leads": [
    {
      "id": 1420,
      "respondent_name": "Sarah Connor",
      "email": "sarah@cyberdyne.io",
      "phone": "+1 (555) 019-2834",
      "calculated_quote": "₹14,500",
      "answers": {
        "project_type": "Full E-Commerce Stack",
        "budget": "₹10,000 - ₹30,000",
        "timeline": "Immediate (2 Weeks)"
      },
      "created_at": "2026-11-01T14:22:10Z"
    }
  ]
}`}
              </pre>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: Webhooks */}
      {activeTab === 'webhooks' && (
        <div className="space-y-6">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div className="flex items-start justify-between">
              <div>
                <h2 className="text-sm font-bold text-[#131416]">Outbound Webhook Delivery</h2>
                <p className="text-xs text-[#5d5f60]">
                  Real-time HTTP POST notification delivered directly to Zapier, Make, n8n, or your CRM webhook endpoint.
                </p>
              </div>
              <span className="px-2.5 py-1 rounded-md bg-[#dcfae5] text-[#2f7a45] text-[11px] font-bold">
                HTTPS POST
              </span>
            </div>

            <div className="space-y-2 text-xs text-[#5d5f60]">
              <p>
                Each outbound webhook contains an HMAC-SHA256 signature in the <code className="bg-[#f4f6f8] px-1 py-0.5 rounded text-[#131416]">X-QFunnel-Signature</code> header so you can verify authenticity.
              </p>
            </div>

            <div className="p-4 rounded-xl bg-[#1e293b] text-slate-100 text-xs font-mono relative">
              <button
                onClick={() =>
                  copyToClipboard(
                    JSON.stringify(
                      {
                        event: 'submission.completed',
                        funnel_id: sampleFunnelId,
                        funnel_slug: sampleFunnelSlug,
                        session_id: 'sess_99381bda7',
                        timestamp: '2026-11-02T19:35:00Z',
                        lead: {
                          name: 'Alex Johnson',
                          email: 'alex.j@enterprise.com',
                          phone: '+1 415 555 2671'
                        },
                        quote: {
                          raw_value: 12500,
                          formatted: '₹12,500'
                        },
                        responses: [
                          { question_id: 'q1', question: 'Company Size', answer: '50-200 Employees' },
                          { question_id: 'q2', question: 'Primary Goal', answer: 'Scale Pipeline Velocity' }
                        ],
                        tracking: {
                          utm_source: 'linkedin_ads',
                          utm_campaign: 'enterprise_growth'
                        }
                      },
                      null,
                      2
                    ),
                    'webhook-json'
                  )
                }
                className="absolute top-3 right-3 p-1 rounded-md bg-slate-700/60 hover:bg-slate-700 text-slate-300"
                title="Copy Webhook Payload"
              >
                {copiedSnippet === 'webhook-json' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              </button>
              <pre className="overflow-x-auto text-[11px] leading-relaxed">
{`{
  "event": "submission.completed",
  "funnel_id": ${sampleFunnelId},
  "funnel_slug": "${sampleFunnelSlug}",
  "session_id": "sess_99381bda7",
  "timestamp": "2026-11-02T19:35:00Z",
  "lead": {
    "name": "Alex Johnson",
    "email": "alex.j@enterprise.com",
    "phone": "+1 415 555 2671"
  },
  "quote": {
    "raw_value": 12500,
    "formatted": "₹12,500"
  },
  "responses": [
    { "question": "Company Size", "answer": "50-200 Employees" },
    { "question": "Primary Goal", "answer": "Scale Pipeline Velocity" }
  ]
}`}
              </pre>
            </div>
          </div>
        </div>
      )}

      {/* TAB 3: Website Embedding */}
      {activeTab === 'embed' && (
        <div className="space-y-6">
          {/* Method A: Inline Responsive iFrame */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div>
              <h2 className="text-sm font-bold text-[#131416]">A. Inline Responsive iFrame</h2>
              <p className="text-xs text-[#5d5f60]">Embed the quiz directly inside any container on your webpage.</p>
            </div>

            <div className="p-4 rounded-xl bg-[#1e293b] text-slate-100 text-xs font-mono relative">
              <button
                onClick={() =>
                  copyToClipboard(
                    `<iframe\n  src="${baseUrl}/quiz/${sampleFunnelSlug}"\n  width="100%"\n  height="650"\n  frameborder="0"\n  style="border: none; border-radius: 16px; overflow: hidden;"\n  allow="camera; microphone; autoplay"\n></iframe>`,
                    'iframe-snippet'
                  )
                }
                className="absolute top-3 right-3 p-1 rounded-md bg-slate-700/60 hover:bg-slate-700 text-slate-300"
                title="Copy Snippet"
              >
                {copiedSnippet === 'iframe-snippet' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              </button>
              <pre className="overflow-x-auto text-[11px] leading-relaxed">
{`<iframe
  src="${baseUrl}/quiz/${sampleFunnelSlug}"
  width="100%"
  height="650"
  frameborder="0"
  style="border: none; border-radius: 16px; overflow: hidden;"
  allow="camera; microphone; autoplay"
></iframe>`}
              </pre>
            </div>
          </div>

          {/* Method B: Direct Hosted Link */}
          <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
            <div>
              <h2 className="text-sm font-bold text-[#131416]">B. Standalone Hosted Link</h2>
              <p className="text-xs text-[#5d5f60]">Send visitors directly to the distraction-free standalone quiz runner.</p>
            </div>

            <div className="flex items-center gap-3">
              <input
                type="text"
                readOnly
                value={`${baseUrl}/quiz/${sampleFunnelSlug}`}
                className="flex-1 px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs font-mono text-[#131416] bg-[#f8fafc]"
              />
              <button
                type="button"
                onClick={() => copyToClipboard(`${baseUrl}/quiz/${sampleFunnelSlug}`, 'direct-url')}
                className="px-3.5 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition flex items-center gap-1.5"
              >
                {copiedSnippet === 'direct-url' ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                <span>Copy URL</span>
              </button>
              <a
                href={`${baseUrl}/quiz/${sampleFunnelSlug}`}
                target="_blank"
                rel="noreferrer"
                className="p-2 rounded-xl border border-[#e2e5e9] text-[#5d5f60] hover:text-[#131416] hover:bg-[#f8fafc] transition"
                title="Open in new tab"
              >
                <ExternalLink className="w-4 h-4" />
              </a>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { UpgradeModal } from '../../components/UpgradeModal';
import { Webhook, Plus, Trash2, Loader2, Sparkles } from 'lucide-react';

export const WebhooksPage: React.FC = () => {
  const { features, isPlatformAdmin } = useAuthStore();
  const [endpoints, setEndpoints] = useState<any[]>([]);
  const [recentDeliveries, setRecentDeliveries] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [targetUrl, setTargetUrl] = useState('');
  const [eventType, setEventType] = useState('all');
  const [secret, setSecret] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [upgradeModalOpen, setUpgradeModalOpen] = useState(false);

  const canUseWebhooks = Boolean(features?.webhooks || isPlatformAdmin);

  useEffect(() => {
    loadWebhooks();
  }, []);

  const loadWebhooks = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/webhooks');
      setEndpoints(res.endpoints || []);
      setRecentDeliveries(res.recent_deliveries || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.post('/api/webhooks', {
        target_url: targetUrl,
        event_type: eventType,
        secret: secret || undefined,
      });
      setShowCreate(false);
      setTargetUrl('');
      setSecret('');
      loadWebhooks();
    } catch (err: any) {
      alert(err.message || 'Failed to create webhook');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Delete this webhook endpoint?')) return;
    try {
      await api.post(`/api/webhooks/${id}/delete`);
      loadWebhooks();
    } catch (err: any) {
      alert(err.message || 'Failed to delete');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-lg font-bold text-[#131416]">Webhooks & Integrations</h1>
          <p className="text-xs text-[#5c6066] mt-0.5">Stream live leads and funnel events to Zapier, Make, or your custom CRM</p>
        </div>

        <button
          onClick={() => {
            if (!canUseWebhooks) {
              setUpgradeModalOpen(true);
              return;
            }
            setShowCreate(!showCreate);
          }}
          className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition self-start sm:self-auto shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>Add Webhook</span>
          {!canUseWebhooks && (
            <span className="px-1.5 py-0.2 rounded text-[9px] bg-white/20 text-white font-bold">
              PRO
            </span>
          )}
        </button>
      </div>

      {showCreate && (
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-5 space-y-4">
          <h3 className="text-xs font-semibold text-[#131416]">New Webhook Endpoint</h3>
          <form onSubmit={handleCreate} className="space-y-3">
            <div>
              <label className="block text-xs font-semibold text-[#131416] mb-1">Payload Target URL</label>
              <input
                type="url"
                required
                value={targetUrl}
                onChange={(e) => setTargetUrl(e.target.value)}
                placeholder="https://hooks.zapier.com/hooks/catch/..."
                className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Trigger Event</label>
                <select
                  value={eventType}
                  onChange={(e) => setEventType(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
                >
                  <option value="all">All Events</option>
                  <option value="lead.created">lead.created</option>
                  <option value="session.started">session.started</option>
                  <option value="session.completed">session.completed</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Signing Secret (Optional)</label>
                <input
                  type="text"
                  value={secret}
                  onChange={(e) => setSecret(e.target.value)}
                  placeholder="whsec_..."
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
                />
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowCreate(false)}
                className="px-3.5 py-1.5 rounded-xl border border-[#e8eaec] text-xs font-semibold text-[#5c6066] hover:bg-[#f9fbfc]"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={submitting}
                className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] shadow-xs flex items-center gap-1.5 disabled:opacity-50"
              >
                {submitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                <span>{submitting ? 'Saving...' : 'Save Endpoint'}</span>
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Endpoints List */}
      <div className="bg-white border border-[#e8eaec] rounded-2xl overflow-hidden">
        <div className="p-4 border-b border-[#e8eaec]">
          <h2 className="text-xs font-semibold text-[#131416]">Active Webhooks</h2>
        </div>

        {loading ? (
          <div className="p-6 divide-y divide-[#e8eaec]">
            {[1, 2, 3].map((i) => (
              <div key={i} className="py-3 flex items-center justify-between gap-4">
                <div className="space-y-1.5 flex-1">
                  <div className="h-4 w-64 rounded-md skeleton-shimmer" />
                  <div className="h-3 w-40 rounded-md skeleton-shimmer" />
                </div>
                <div className="h-6 w-20 rounded-full skeleton-shimmer" />
                <div className="w-7 h-7 rounded-lg skeleton-shimmer" />
              </div>
            ))}
          </div>
        ) : endpoints.length === 0 ? (
          <div className="p-8 text-center text-xs text-[#5c6066]">No webhook endpoints configured.</div>
        ) : (
          <div className="divide-y divide-[#e8eaec]">
            {endpoints.map((ep) => (
              <div key={ep.id} className="p-4 flex items-center justify-between hover:bg-[#f9fbfc] transition">
                <div className="min-w-0 flex-1 pr-4">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-xs font-semibold text-[#131416] truncate">{ep.target_url}</span>
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]">
                      {ep.event_type}
                    </span>
                  </div>
                  {ep.secret && (
                    <span className="text-[11px] text-[#5c6066] font-mono mt-0.5 block truncate">
                      Secret: {ep.secret.slice(0, 8)}••••••••
                    </span>
                  )}
                </div>

                <button
                  onClick={() => handleDelete(ep.id)}
                  className="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Recent Deliveries */}
      <div className="bg-white border border-[#e8eaec] rounded-2xl overflow-hidden">
        <div className="p-4 border-b border-[#e8eaec]">
          <h2 className="text-xs font-semibold text-[#131416]">Recent Deliveries</h2>
        </div>

        {recentDeliveries.length === 0 ? (
          <div className="p-8 text-center text-xs text-[#5c6066]">No delivery logs recorded yet.</div>
        ) : (
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="border-b border-[#e8eaec] bg-[#f9fbfc] text-[11px] font-semibold text-[#5c6066]">
                <th className="py-2.5 px-4">Event</th>
                <th className="py-2.5 px-4">Status</th>
                <th className="py-2.5 px-4">Time</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#e8eaec]">
              {recentDeliveries.map((d) => (
                <tr key={d.id}>
                  <td className="py-3 px-4 font-semibold text-[#131416]">{d.event_type}</td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                      d.response_code >= 200 && d.response_code < 300
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                    }`}>
                      {d.response_code || 'Err'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-[#5c6066] text-[11px] font-mono tabular-nums">
                    {new Date(d.created_at).toLocaleString()}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <UpgradeModal
        isOpen={upgradeModalOpen}
        onClose={() => setUpgradeModalOpen(false)}
        featureName="Outbound HTTP Webhooks"
        description="Streaming real-time lead payloads and funnel events to Zapier, Make, HubSpot, and custom HTTP endpoints is available on Pro plans. Upgrade to automate your workflow integrations."
        requiredPlan="Pro"
      />
    </div>
  );
};

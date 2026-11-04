import React, { useEffect, useState } from 'react';
import { api, resolveMediaUrl } from '../../api/client';
import { CreditCard, Check, Sparkles, Shield, ArrowUpRight, Loader2, QrCode, Landmark } from 'lucide-react';

const formatInterval = (interval?: string) => {
  switch (interval) {
    case 'quarterly':
      return '3mo';
    case 'semi_annual':
      return '6mo';
    case 'yearly':
      return 'yr';
    case 'monthly':
    default:
      return 'mo';
  }
};

export const BillingPage: React.FC = () => {
  const [billingData, setBillingData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [upgradingPlanId, setUpgradingPlanId] = useState<number | null>(null);
  const [selectedCycle, setSelectedCycle] = useState<string>('all');

  useEffect(() => {
    loadBilling();
  }, []);

  const loadBilling = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/billing');
      setBillingData(res);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleUpgrade = async (planId: number) => {
    try {
      setUpgradingPlanId(planId);
      await api.post('/api/billing/upgrade', { plan_id: planId });
      alert('Subscription plan updated successfully!');
      loadBilling();
    } catch (err: any) {
      alert(err.message || 'Upgrade failed');
    } finally {
      setUpgradingPlanId(null);
    }
  };

  if (loading) {
    return (
      <div className="space-y-8">
        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="space-y-2 flex-1">
            <div className="h-3 w-28 rounded-md skeleton-shimmer" />
            <div className="h-6 w-48 rounded-md skeleton-shimmer" />
            <div className="h-3 w-32 rounded-md skeleton-shimmer" />
          </div>
          <div className="flex-1 max-w-md grid grid-cols-2 gap-4">
            <div className="h-10 rounded-xl skeleton-shimmer" />
            <div className="h-10 rounded-xl skeleton-shimmer" />
          </div>
        </div>

        <div className="space-y-4">
          <div className="space-y-1">
            <div className="h-5 w-36 rounded-md skeleton-shimmer" />
            <div className="h-3 w-64 rounded-md skeleton-shimmer" />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {[1, 2, 3].map((i) => (
              <div key={i} className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
                <div className="h-4 w-24 rounded-md skeleton-shimmer" />
                <div className="h-8 w-20 rounded-md skeleton-shimmer" />
                <div className="h-3 w-full rounded-md skeleton-shimmer" />
                <div className="space-y-2 pt-4 border-t border-[#f0f1f2]">
                  <div className="h-3 w-3/4 rounded-md skeleton-shimmer" />
                  <div className="h-3 w-2/3 rounded-md skeleton-shimmer" />
                  <div className="h-3 w-4/5 rounded-md skeleton-shimmer" />
                </div>
                <div className="h-9 w-full rounded-xl skeleton-shimmer pt-2" />
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  const sub = billingData?.subscription || {};
  const usage = billingData?.usage || {};
  const plans = billingData?.plans || [];
  const currentPlanId = sub.plan_id;

  return (
    <div className="space-y-8">
      {/* Current Plan Overview Card */}
      <div className="bg-white border border-[#e8eaec] rounded-[12px] p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <span className="text-xs font-medium text-[#1b6c80] block mb-1">
            Current Subscription
          </span>
          <h1 className="text-xl font-semibold text-[#131416]">
            {sub.plan_name || 'Free Plan'}
          </h1>
          <p className="text-xs text-[#687080] mt-1">
            Status:{' '}
            <span className="capitalize font-semibold text-emerald-600">
              {sub.status || 'Active'}
            </span>
          </p>
        </div>

        {/* Quota Usage Bars */}
        <div className="flex-1 max-w-md grid grid-cols-2 gap-4">
          <div>
            <div className="flex justify-between text-xs mb-1">
              <span className="text-[#687080]">Funnels</span>
              <span className="font-bold text-[#171A21]">
                {usage.funnels_count || 0} / {usage.max_funnels || '∞'}
              </span>
            </div>
            <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-[#5B5BD6] rounded-full"
                style={{ width: `${Math.min(100, ((usage.funnels_count || 0) / (usage.max_funnels || 1)) * 100)}%` }}
              />
            </div>
          </div>

          <div>
            <div className="flex justify-between text-xs mb-1">
              <span className="text-[#687080]">Monthly Leads</span>
              <span className="font-bold text-[#171A21]">
                {usage.submissions_count || 0} / {usage.max_submissions_per_month || '∞'}
              </span>
            </div>
            <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-emerald-500 rounded-full"
                style={{ width: `${Math.min(100, ((usage.submissions_count || 0) / (usage.max_submissions_per_month || 1)) * 100)}%` }}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Subscription Plans Grid */}
      <div className="space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 className="text-base font-bold text-[#171A21]">Available Plans</h2>
            <p className="text-xs text-[#687080]">Upgrade or switch your tier for higher limits and pro features</p>
          </div>

          <div className="flex items-center gap-1.5 flex-wrap">
            {[
              { id: 'all', label: 'All Cycles' },
              { id: 'monthly', label: 'Monthly' },
              { id: 'quarterly', label: '3 Months' },
              { id: 'semi_annual', label: '6 Months' },
              { id: 'yearly', label: 'Annual (1 Yr)' },
            ].map((tab) => (
              <button
                key={tab.id}
                type="button"
                onClick={() => setSelectedCycle(tab.id)}
                className={`px-3 py-1 rounded-lg text-xs font-semibold transition ${
                  selectedCycle === tab.id
                    ? 'bg-[#1d5fd6] text-white shadow-xs'
                    : 'bg-white border border-[#e8eaec] text-[#5d5f60] hover:bg-[#f8fafc]'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {plans
            .filter((p: any) => selectedCycle === 'all' || (p.billing_interval || 'monthly') === selectedCycle)
            .map((p: any) => {
            const isCurrent = p.id === currentPlanId;
            const limits = typeof p.limits_json === 'string' ? JSON.parse(p.limits_json || '{}') : (p.limits_json || {});
            const feats = typeof p.features_json === 'string' ? JSON.parse(p.features_json || '{}') : (p.features_json || {});
            const isPro = ['pro', 'enterprise'].includes(p.slug);

            const premiumHighlights = [
              { key: 'white_label', label: '100% White-Label (No Branding)' },
              { key: 'calculator_mode', label: 'Quote & Math Formula Engine' },
              { key: 'webhooks', label: 'HTTP Webhooks & Zapier/Make' },
              { key: 'csv_export', label: '1-Click CSV Lead Export' },
              { key: 'api_access', label: 'Developer REST API Keys' },
              { key: 'funnel_cloning', label: '1-Click Funnel Duplication' },
              { key: 'step_dropoff_analytics', label: 'Step Drop-Off Analytics' },
              { key: 'custom_tracking_pixels', label: 'Meta & TikTok Ad Pixels' },
            ];

            return (
              <div
                key={p.id}
                className={`bg-white rounded-[12px] border p-5 flex flex-col justify-between transition-colors ${
                  isCurrent ? 'border-[#1d5fd6] ring-1 ring-[#1d5fd6]' : 'border-[#e8eaec] hover:border-[#1d5fd6]/40 hover:shadow-xs'
                }`}
              >
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <h3 className="text-sm font-semibold text-[#131416]">{p.name}</h3>
                    {isCurrent && (
                      <span className="px-2 py-0.5 rounded-[4px] text-[10px] font-medium bg-[#dcfae5] text-[#04250e] border border-[#bbf7d0]">
                        Active
                      </span>
                    )}
                  </div>

                  <div className="flex items-baseline gap-1 my-3">
                    <span className="text-2xl font-bold text-[#131416]">₹{Number(p.price).toLocaleString('en-IN')}</span>
                    <span className="text-xs text-[#5d5f60]">/{formatInterval(p.billing_interval)}</span>
                  </div>

                  <p className="text-[11px] text-[#5d5f60] mb-4 min-h-[32px]">{p.description}</p>

                  <div className="space-y-2 pt-2 border-t border-[#f0f1f2] text-xs">
                    <div className="flex items-center gap-2 text-[#131416]">
                      <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span>{Number(limits.max_funnels || 0) <= 0 ? 'Unlimited' : limits.max_funnels} Funnels</span>
                    </div>
                    <div className="flex items-center gap-2 text-[#131416]">
                      <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span>{Number(limits.max_submissions_per_month || 0) <= 0 ? 'Unlimited Leads' : `${Number(limits.max_submissions_per_month).toLocaleString()} Leads/mo`}</span>
                    </div>
                    <div className="flex items-center gap-2 text-[#131416]">
                      <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span>{Number(limits.max_team_members || 0) <= 0 ? 'Unlimited Team Seats' : `${limits.max_team_members} Team Member${Number(limits.max_team_members) > 1 ? 's' : ''}`}</span>
                    </div>

                    {premiumHighlights.map(({ key, label }) => {
                      const hasIt = Boolean(feats[key]) || isPro;
                      if (!hasIt) return null;
                      return (
                        <div key={key} className="flex items-center gap-2 text-[#131416]">
                          <Check className="w-3.5 h-3.5 text-[#1d5fd6] shrink-0" />
                          <span className="font-medium text-[#131416]">{label}</span>
                        </div>
                      );
                    })}
                  </div>
                </div>

                <div className="pt-6">
                  {isCurrent ? (
                    <button
                      disabled
                      className="w-full py-2 rounded-[6px] border border-[#e8eaec] bg-[#f4f6f8] text-xs font-medium text-[#5d5f60] cursor-default"
                    >
                      Current Plan
                    </button>
                  ) : (
                    <button
                      onClick={() => handleUpgrade(p.id)}
                      disabled={upgradingPlanId !== null}
                      className="w-full py-2 rounded-xl bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow-xs disabled:opacity-50"
                    >
                      {upgradingPlanId === p.id ? (
                        <>
                          <Loader2 className="w-3.5 h-3.5 animate-spin text-white" />
                          <span>Updating Plan...</span>
                        </>
                      ) : (
                        <span>Switch to {p.name}</span>
                      )}
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Direct & Offline Payment Options (UPI QR / Bank Wire) */}
      {(billingData?.gateways?.manual_custom?.enabled || billingData?.gateways?.bank_transfer?.enabled) && (
        <div className="bg-white border border-[#e8eaec] rounded-[12px] p-6 space-y-5">
          <div>
            <h2 className="text-base font-bold text-[#171A21]">Direct & Offline Payment Options</h2>
            <p className="text-xs text-[#687080]">
              Pay your subscription directly via UPI QR scan, NEFT/IMPS bank transfer, or offline purchase order.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            {/* UPI / QR Code / Manual Card */}
            {billingData?.gateways?.manual_custom?.enabled && (
              <div className="p-5 rounded-xl border border-[#e2e5e9] bg-[#fcfdfe] space-y-4">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-[#eef4ff] text-[#1d5fd6] flex items-center justify-center font-bold shrink-0">
                    <QrCode className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="font-bold text-xs text-[#131416]">
                      {billingData.gateways.manual_custom.title || 'UPI & Offline Payment'}
                    </h3>
                    <p className="text-[11px] text-[#5d5f60]">Scan and pay directly or follow instructions</p>
                  </div>
                </div>

                {billingData.gateways.manual_custom.qr_code_url && (
                  <div className="flex flex-col sm:flex-row items-center gap-4 p-3 bg-white rounded-xl border border-[#e2e5e9]">
                    <div className="w-32 h-32 bg-white rounded-lg border border-[#e2e5e9] p-2 flex items-center justify-center shrink-0">
                      <img
                        src={resolveMediaUrl(billingData.gateways.manual_custom.qr_code_url)}
                        alt="Company Payment QR Code"
                        className="max-w-full max-h-full object-contain"
                      />
                    </div>
                    <div className="space-y-1.5 text-center sm:text-left">
                      <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#eef4ff] text-[#1d5fd6]">
                        Instant Scan & Pay
                      </span>
                      <p className="text-xs font-semibold text-[#131416]">Company Payment QR Code</p>
                      <p className="text-[11px] text-[#5d5f60]">
                        Open Google Pay, PhonePe, Paytm, BHIM, or any banking app to scan this QR code.
                      </p>
                    </div>
                  </div>
                )}

                {billingData.gateways.manual_custom.instructions && (
                  <div className="p-3 rounded-lg bg-[#f8fafc] border border-[#e8eaec] text-xs text-[#131416] whitespace-pre-line leading-relaxed">
                    {billingData.gateways.manual_custom.instructions}
                  </div>
                )}
              </div>
            )}

            {/* Bank Wire / NEFT Card */}
            {billingData?.gateways?.bank_transfer?.enabled && (
              <div className="p-5 rounded-xl border border-[#e2e5e9] bg-[#fcfdfe] space-y-4">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-[#f0fdf4] text-[#15803d] flex items-center justify-center font-bold shrink-0">
                    <Landmark className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="font-bold text-xs text-[#131416]">Bank Wire Transfer (NEFT / IMPS / RTGS)</h3>
                    <p className="text-[11px] text-[#5d5f60]">Direct corporate bank settlement</p>
                  </div>
                </div>

                <div className="p-3 bg-white rounded-xl border border-[#e2e5e9] space-y-2 text-xs">
                  <div className="flex justify-between py-1 border-b border-[#f1f3f5]">
                    <span className="text-[#5d5f60]">Bank Name</span>
                    <span className="font-semibold text-[#131416]">{billingData.gateways.bank_transfer.bank_name}</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-[#f1f3f5]">
                    <span className="text-[#5d5f60]">Account Name</span>
                    <span className="font-semibold text-[#131416]">{billingData.gateways.bank_transfer.account_holder}</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-[#f1f3f5]">
                    <span className="text-[#5d5f60]">Account Number</span>
                    <span className="font-semibold text-[#131416]">{billingData.gateways.bank_transfer.account_number}</span>
                  </div>
                  {billingData.gateways.bank_transfer.routing_code && (
                    <div className="flex justify-between py-1">
                      <span className="text-[#5d5f60]">Routing / IFSC Code</span>
                      <span className="font-semibold text-[#131416]">{billingData.gateways.bank_transfer.routing_code}</span>
                    </div>
                  )}
                </div>

                {billingData.gateways.bank_transfer.instructions && (
                  <div className="p-3 rounded-lg bg-[#f8fafc] border border-[#e8eaec] text-xs text-[#131416] whitespace-pre-line leading-relaxed">
                    {billingData.gateways.bank_transfer.instructions}
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e8eaec] flex items-center gap-3">
            <Sparkles className="w-4 h-4 text-[#1d5fd6] shrink-0" />
            <p className="text-xs text-[#5d5f60]">
              After completing your payment, send your transaction UTR or receipt reference to our billing desk for instant invoice generation and subscription activation.
            </p>
          </div>
        </div>
      )}
    </div>
  );
};


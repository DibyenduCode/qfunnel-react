import React, { useEffect, useState, useRef } from 'react';
import { api, resolveMediaUrl } from '../../api/client';
import { CreditCard, Save, CheckCircle, AlertTriangle, ShieldCheck, Landmark, Banknote, X, QrCode, Upload, Loader2 } from 'lucide-react';

export const SuperadminGateways: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');
  const [warnings, setWarnings] = useState<string[]>([]);
  const [errorMsg, setErrorMsg] = useState('');

  // Stripe State
  const [stripeEnabled, setStripeEnabled] = useState(false);
  const [stripeMode, setStripeMode] = useState('test');
  const [stripePub, setStripePub] = useState('');
  const [stripeSec, setStripeSec] = useState('');
  const [stripeWebhook, setStripeWebhook] = useState('');

  // PayPal State
  const [paypalEnabled, setPaypalEnabled] = useState(false);
  const [paypalMode, setPaypalMode] = useState('sandbox');
  const [paypalClientId, setPaypalClientId] = useState('');
  const [paypalSecret, setPaypalSecret] = useState('');

  // Razorpay State
  const [razorpayEnabled, setRazorpayEnabled] = useState(false);
  const [razorpayKeyId, setRazorpayKeyId] = useState('');
  const [razorpayKeySecret, setRazorpayKeySecret] = useState('');

  // Bank Transfer State
  const [bankEnabled, setBankEnabled] = useState(false);
  const [bankName, setBankName] = useState('');
  const [bankAccountHolder, setBankAccountHolder] = useState('');
  const [bankAccountNumber, setBankAccountNumber] = useState('');
  const [bankRoutingCode, setBankRoutingCode] = useState('');
  const [bankInstructions, setBankInstructions] = useState('');

  // Manual / Custom State
  const [manualEnabled, setManualEnabled] = useState(false);
  const [manualTitle, setManualTitle] = useState('Offline / Cash / Cheque / UPI Payment');
  const [manualInstructions, setManualInstructions] = useState('');
  const [manualQrCodeUrl, setManualQrCodeUrl] = useState('');
  const [uploadingQr, setUploadingQr] = useState(false);
  const [qrUploadError, setQrUploadError] = useState<string | null>(null);
  const qrFileInputRef = useRef<HTMLInputElement | null>(null);

  const handleQrFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setQrUploadError('QR code file size must be under 5MB.');
      return;
    }

    setUploadingQr(true);
    setQrUploadError(null);
    try {
      const formData = new FormData();
      formData.append('qr_file', file);
      const res = await api.post('/api/platform/payment-gateways/upload-qr', formData);
      if (res && res.qr_code_url) {
        setManualQrCodeUrl(res.qr_code_url);
      }
    } catch (err: any) {
      setQrUploadError(err.message || 'Failed to upload QR code image.');
    } finally {
      setUploadingQr(false);
      if (qrFileInputRef.current) {
        qrFileInputRef.current.value = '';
      }
    }
  };

  useEffect(() => {
    loadGateways();
  }, []);

  const loadGateways = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/payment-gateways');
      const gw = res.gateways || {};

      // Stripe
      setStripeEnabled(Boolean(gw.stripe?.enabled));
      setStripeMode(gw.stripe?.mode || 'test');
      setStripePub(gw.stripe?.publishable_key || '');
      setStripeSec(gw.stripe?.secret_key || '');
      setStripeWebhook(gw.stripe?.webhook_secret || '');

      // PayPal
      setPaypalEnabled(Boolean(gw.paypal?.enabled));
      setPaypalMode(gw.paypal?.mode || 'sandbox');
      setPaypalClientId(gw.paypal?.client_id || '');
      setPaypalSecret(gw.paypal?.client_secret || '');

      // Razorpay
      setRazorpayEnabled(Boolean(gw.razorpay?.enabled));
      setRazorpayKeyId(gw.razorpay?.key_id || '');
      setRazorpayKeySecret(gw.razorpay?.key_secret || '');

      // Bank Transfer
      setBankEnabled(Boolean(gw.bank_transfer?.enabled));
      setBankName(gw.bank_transfer?.bank_name || '');
      setBankAccountHolder(gw.bank_transfer?.account_holder || '');
      setBankAccountNumber(gw.bank_transfer?.account_number || '');
      setBankRoutingCode(gw.bank_transfer?.routing_code || '');
      setBankInstructions(gw.bank_transfer?.instructions || '');

      // Manual Custom
      setManualEnabled(Boolean(gw.manual_custom?.enabled));
      setManualTitle(gw.manual_custom?.title || 'Offline / Cash / Cheque / UPI Payment');
      setManualInstructions(gw.manual_custom?.instructions || '');
      setManualQrCodeUrl(gw.manual_custom?.qr_code_url || '');
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load gateway settings');
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMsg('');
    setWarnings([]);
    setErrorMsg('');
    try {
      const res = await api.post('/api/platform/payment-gateways', {
        stripe_enabled: stripeEnabled,
        stripe_mode: stripeMode,
        stripe_publishable_key: stripePub,
        stripe_secret_key: stripeSec,
        stripe_webhook_secret: stripeWebhook,

        paypal_enabled: paypalEnabled,
        paypal_mode: paypalMode,
        paypal_client_id: paypalClientId,
        paypal_client_secret: paypalSecret,

        razorpay_enabled: razorpayEnabled,
        razorpay_key_id: razorpayKeyId,
        razorpay_key_secret: razorpayKeySecret,

        bank_enabled: bankEnabled,
        bank_name: bankName,
        bank_account_holder: bankAccountHolder,
        bank_account_number: bankAccountNumber,
        bank_routing_code: bankRoutingCode,
        bank_instructions: bankInstructions,

        manual_enabled: manualEnabled,
        manual_title: manualTitle,
        manual_instructions: manualInstructions,
        manual_qr_code_url: manualQrCodeUrl,
      });

      setMsg(res.message || 'Payment gateway configuration saved successfully.');
      if (res.warnings && res.warnings.length > 0) {
        setWarnings(res.warnings);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to save gateway configuration');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-4xl space-y-6 mx-auto">
        <div className="h-10 w-64 rounded-xl skeleton-shimmer" />
        <div className="h-96 rounded-2xl skeleton-shimmer" />
      </div>
    );
  }

  return (
    <div className="max-w-4xl space-y-6 mx-auto">
      <div>
        <div className="flex items-center gap-2">
          <span className="p-1.5 rounded-lg bg-[#e0e7ff] text-[#3730a3]">
            <CreditCard className="w-5 h-5" />
          </span>
          <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Payment Gateway Configuration</h1>
        </div>
        <p className="text-xs text-[#5d5f60] mt-1">
          Configure automated billing providers (Stripe, PayPal, Razorpay) and manual offline instructions for customer subscriptions.
        </p>
      </div>

      {msg && (
        <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 shrink-0" />
            <span>{msg}</span>
          </div>
          <button onClick={() => setMsg('')} className="text-xs opacity-70 hover:opacity-100 p-1" title="Dismiss">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {warnings.length > 0 && (
        <div className="p-3.5 rounded-xl bg-[#fffbeb] border border-[#fde68a] text-[#92400e] text-xs space-y-1">
          <span className="font-bold flex items-center gap-1.5">
            <AlertTriangle className="w-4 h-4" />
            <span>Configuration Warnings:</span>
          </span>
          <ul className="list-disc pl-5 space-y-0.5">
            {warnings.map((w, idx) => (
              <li key={idx}>{w}</li>
            ))}
          </ul>
        </div>
      )}

      {errorMsg && (
        <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{errorMsg}</span>
          </div>
          <button onClick={() => setErrorMsg('')} className="text-xs opacity-70 hover:opacity-100 p-1" title="Dismiss">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      <form onSubmit={handleSave} className="space-y-6">
        {/* 1. Stripe */}
        <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
            <div>
              <h3 className="font-bold text-sm text-[#131416]">Stripe Subscriptions & Checkout</h3>
              <p className="text-xs text-[#5d5f60]">Automated credit card subscriptions and webhook synchronizations</p>
            </div>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={stripeEnabled}
                onChange={(e) => setStripeEnabled(e.target.checked)}
                className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
              />
              <span className="text-xs font-semibold text-[#131416]">Enable Stripe</span>
            </label>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Environment Mode</label>
              <select
                value={stripeMode}
                onChange={(e) => setStripeMode(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              >
                <option value="test">Test Mode (Sandbox)</option>
                <option value="live">Live Production</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Publishable Key</label>
              <input
                type="text"
                value={stripePub}
                onChange={(e) => setStripePub(e.target.value)}
                placeholder="pk_test_..."
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Secret Key</label>
              <input
                type="password"
                value={stripeSec}
                onChange={(e) => setStripeSec(e.target.value)}
                placeholder="sk_test_..."
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Webhook Secret (whsec_...)</label>
              <input
                type="password"
                value={stripeWebhook}
                onChange={(e) => setStripeWebhook(e.target.value)}
                placeholder="whsec_..."
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>
          </div>
        </div>

        {/* 2. PayPal */}
        <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
            <div>
              <h3 className="font-bold text-sm text-[#131416]">PayPal Express Checkout</h3>
              <p className="text-xs text-[#5d5f60]">Support international PayPal account and buyer checkout</p>
            </div>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={paypalEnabled}
                onChange={(e) => setPaypalEnabled(e.target.checked)}
                className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
              />
              <span className="text-xs font-semibold text-[#131416]">Enable PayPal</span>
            </label>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Environment</label>
              <select
                value={paypalMode}
                onChange={(e) => setPaypalMode(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              >
                <option value="sandbox">Sandbox Testing</option>
                <option value="live">Live Production</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Client ID</label>
              <input
                type="text"
                value={paypalClientId}
                onChange={(e) => setPaypalClientId(e.target.value)}
                placeholder="PayPal REST Client ID"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Client Secret</label>
              <input
                type="password"
                value={paypalSecret}
                onChange={(e) => setPaypalSecret(e.target.value)}
                placeholder="PayPal Client Secret"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>
          </div>
        </div>

        {/* 3. Razorpay */}
        <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
            <div>
              <h3 className="font-bold text-sm text-[#131416]">Razorpay Payments</h3>
              <p className="text-xs text-[#5d5f60]">Native UPI, NetBanking, Cards, and Indian Rupee (INR) subscriptions</p>
            </div>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={razorpayEnabled}
                onChange={(e) => setRazorpayEnabled(e.target.checked)}
                className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
              />
              <span className="text-xs font-semibold text-[#131416]">Enable Razorpay</span>
            </label>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Key ID</label>
              <input
                type="text"
                value={razorpayKeyId}
                onChange={(e) => setRazorpayKeyId(e.target.value)}
                placeholder="rzp_test_..."
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Key Secret</label>
              <input
                type="password"
                value={razorpayKeySecret}
                onChange={(e) => setRazorpayKeySecret(e.target.value)}
                placeholder="Razorpay Key Secret"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>
          </div>
        </div>

        {/* 4. Bank Wire / Transfer */}
        <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
            <div className="flex items-center gap-2.5">
              <div className="w-7 h-7 rounded-lg bg-[#dcfae5] text-[#2f7a45] flex items-center justify-center font-bold">
                <Landmark className="w-4 h-4" />
              </div>
              <div>
                <h3 className="font-bold text-sm text-[#131416]">Direct Bank Wire / NEFT / RTGS Transfer</h3>
                <p className="text-xs text-[#5d5f60]">Manual bank account instructions presented on billing checkout</p>
              </div>
            </div>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={bankEnabled}
                onChange={(e) => setBankEnabled(e.target.checked)}
                className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
              />
              <span className="text-xs font-semibold text-[#131416]">Enable Bank Wire</span>
            </label>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Bank Name</label>
              <input
                type="text"
                value={bankName}
                onChange={(e) => setBankName(e.target.value)}
                placeholder="e.g. HDFC Bank Ltd / Chase Bank"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Account Holder Name</label>
              <input
                type="text"
                value={bankAccountHolder}
                onChange={(e) => setBankAccountHolder(e.target.value)}
                placeholder="e.g. LeadCollector Technologies Pvt Ltd"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Account Number / IBAN</label>
              <input
                type="text"
                value={bankAccountNumber}
                onChange={(e) => setBankAccountNumber(e.target.value)}
                placeholder="Account number"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Routing / IFSC / SWIFT Code</label>
              <input
                type="text"
                value={bankRoutingCode}
                onChange={(e) => setBankRoutingCode(e.target.value)}
                placeholder="e.g. HDFC0001234 / CHASUS33"
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs font-mono text-[#131416]"
              />
            </div>

            <div className="sm:col-span-2">
              <label className="block text-xs font-bold text-[#131416] mb-1">Remittance Instructions for Customer</label>
              <textarea
                rows={2}
                value={bankInstructions}
                onChange={(e) => setBankInstructions(e.target.value)}
                placeholder="e.g. Please put your workspace slug or registered email in NEFT remarks and email proof to billing@leadcollector.in"
                className="w-full p-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              />
            </div>
          </div>
        </div>

        {/* 5. Manual / Custom / UPI */}
        <div className="p-6 rounded-2xl bg-white border border-[#e2e5e9] space-y-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
            <div className="flex items-center gap-2.5">
              <div className="w-7 h-7 rounded-lg bg-[#fef3c7] text-[#b45309] flex items-center justify-center font-bold">
                <Banknote className="w-4 h-4" />
              </div>
              <div>
                <h3 className="font-bold text-sm text-[#131416]">Manual / Offline / UPI / Cheque Payment</h3>
                <p className="text-xs text-[#5d5f60]">Custom offline payment instructions or company QR code</p>
              </div>
            </div>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={manualEnabled}
                onChange={(e) => setManualEnabled(e.target.checked)}
                className="w-4 h-4 rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
              />
              <span className="text-xs font-semibold text-[#131416]">Enable Offline Option</span>
            </label>
          </div>

          <div className="space-y-3">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Option Display Title</label>
              <input
                type="text"
                value={manualTitle}
                onChange={(e) => setManualTitle(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Instructions / UPI VPA / QR Note</label>
              <textarea
                rows={3}
                value={manualInstructions}
                onChange={(e) => setManualInstructions(e.target.value)}
                placeholder="e.g. Scan QR code or transfer to UPI: company@upi. Share transaction UTR screenshot via support desk for instant plan activation."
                className="w-full p-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
              />
            </div>

            {/* Payment QR Code Option */}
            <div className="pt-3 border-t border-[#edf0f3] space-y-3">
              <div className="flex items-center justify-between">
                <div>
                  <label className="block text-xs font-bold text-[#131416]">Payment QR Code (UPI / QR Barcode)</label>
                  <p className="text-[11px] text-[#5d5f60]">
                    Upload or link your company UPI QR code (Google Pay, PhonePe, Paytm, BHIM) or bank barcode for subscribers to scan and pay directly.
                  </p>
                </div>
                {manualQrCodeUrl && (
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#dcfae5] text-[#065f46] border border-[#a7f3d0]">
                    Active QR
                  </span>
                )}
              </div>

              <input
                ref={qrFileInputRef}
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                onChange={handleQrFileUpload}
                className="hidden"
              />

              {manualQrCodeUrl ? (
                <div className="p-4 rounded-xl border border-[#e2e5e9] bg-[#f9fbfc] flex flex-col sm:flex-row items-center gap-4">
                  <div className="w-28 h-28 bg-white rounded-lg border border-[#e2e5e9] p-1.5 flex items-center justify-center shrink-0">
                    <img
                      src={resolveMediaUrl(manualQrCodeUrl)}
                      alt="Payment QR Code Preview"
                      className="max-w-full max-h-full object-contain"
                      onError={(e) => {
                        (e.target as HTMLElement).style.display = 'none';
                      }}
                    />
                  </div>
                  <div className="flex-1 min-w-0 space-y-2 text-center sm:text-left">
                    <div>
                      <p className="text-xs font-semibold text-[#131416]">Company QR Code Active</p>
                      <p className="text-[11px] text-[#5d5f60] truncate">{manualQrCodeUrl}</p>
                    </div>
                    <div className="flex items-center justify-center sm:justify-start gap-2 pt-1">
                      <button
                        type="button"
                        onClick={() => qrFileInputRef.current?.click()}
                        disabled={uploadingQr}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#1d5fd6] bg-[#eef4ff] hover:bg-[#dbe7ff] rounded-lg transition disabled:opacity-50"
                      >
                        {uploadingQr ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Upload className="w-3.5 h-3.5" />}
                        <span>Replace QR Image</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => setManualQrCodeUrl('')}
                        disabled={uploadingQr}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#b91c1c] bg-[#fef2f2] hover:bg-[#fee2e2] rounded-lg transition disabled:opacity-50"
                      >
                        <X className="w-3.5 h-3.5" />
                        <span>Remove QR</span>
                      </button>
                    </div>
                  </div>
                </div>
              ) : (
                <div className="space-y-2">
                  <button
                    type="button"
                    onClick={() => qrFileInputRef.current?.click()}
                    disabled={uploadingQr}
                    className="w-full py-5 border-2 border-dashed border-[#cbd5e1] hover:border-[#1d5fd6] hover:bg-[#f8fafc] rounded-xl flex flex-col items-center justify-center gap-1.5 transition text-[#5d5f60] hover:text-[#1d5fd6] disabled:opacity-50"
                  >
                    {uploadingQr ? (
                      <>
                        <Loader2 className="w-6 h-6 animate-spin text-[#1d5fd6]" />
                        <span className="text-xs font-semibold">Uploading QR Code Image...</span>
                      </>
                    ) : (
                      <>
                        <QrCode className="w-6 h-6 text-[#1d5fd6]" />
                        <span className="text-xs font-semibold text-[#131416]">Click to Upload Company QR Code</span>
                        <span className="text-[11px] text-[#5d5f60]">Supports PNG, JPG, WebP, SVG (Max 5MB)</span>
                      </>
                    )}
                  </button>

                  <div className="flex items-center gap-2">
                    <span className="text-[11px] text-[#5d5f60] shrink-0 font-medium">Or enter Image URL:</span>
                    <input
                      type="text"
                      placeholder="https://... or /uploads/gateways/qr_code.png"
                      value={manualQrCodeUrl}
                      onChange={(e) => setManualQrCodeUrl(e.target.value)}
                      className="flex-1 px-3 py-1.5 rounded-lg border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416]"
                    />
                  </div>
                </div>
              )}

              {qrUploadError && (
                <p className="text-xs text-[#b91c1c] font-medium">{qrUploadError}</p>
              )}
            </div>
          </div>
        </div>

        <div className="flex justify-end pt-2">
          <button
            type="submit"
            disabled={saving}
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs disabled:opacity-60"
          >
            <Save className="w-4 h-4" />
            <span>{saving ? 'Saving...' : 'Save All Gateway Settings'}</span>
          </button>
        </div>
      </form>
    </div>
  );
};

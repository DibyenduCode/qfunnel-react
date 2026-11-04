import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api } from '../../api/client';
import {
  ArrowLeft,
  Phone,
  MessageCircle,
  CheckCircle2,
  Clock,
  Play,
  Lock,
  Flag,
  Calculator,
  User,
  ExternalLink,
  ChevronRight,
  Layers,
  Sparkles,
  Info,
  Check,
  Video,
  Sliders
} from 'lucide-react';

export const LeadDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadDetail();
  }, [id]);

  const loadDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await api.get(`/api/submissions/${id}`);
      setData(res);
    } catch (err: any) {
      console.error('Failed to load submission detail:', err);
      setError(err.message || 'Submission record not found.');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center gap-3">
          <div className="h-8 w-8 rounded-lg skeleton-shimmer" />
          <div className="space-y-1.5 flex-1">
            <div className="h-5 w-48 rounded-md skeleton-shimmer" />
            <div className="h-3 w-32 rounded-md skeleton-shimmer" />
          </div>
        </div>

        <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-full skeleton-shimmer" />
              <div className="space-y-2">
                <div className="h-5 w-40 rounded-md skeleton-shimmer" />
                <div className="h-3.5 w-60 rounded-md skeleton-shimmer" />
              </div>
            </div>
            <div className="h-7 w-28 rounded-full skeleton-shimmer" />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
            <div className="h-5 w-40 rounded-md skeleton-shimmer" />
            <div className="space-y-3 pt-2">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="h-16 rounded-xl border border-[#f0f1f2] p-4 flex items-center justify-between">
                  <div className="space-y-1.5 flex-1">
                    <div className="h-3.5 w-1/3 rounded-md skeleton-shimmer" />
                    <div className="h-3 w-1/2 rounded-md skeleton-shimmer" />
                  </div>
                  <div className="h-5 w-20 rounded-md skeleton-shimmer" />
                </div>
              ))}
            </div>
          </div>
          <div className="bg-white border border-[#e8eaec] rounded-2xl p-6 space-y-4">
            <div className="h-5 w-32 rounded-md skeleton-shimmer" />
            <div className="space-y-3 pt-2">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-10 rounded-lg skeleton-shimmer" />
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error || !data || !data.submission) {
    return (
      <div className="bg-white border border-[#e8eaec] rounded-[12px] p-8 text-center max-w-lg mx-auto">
        <Info className="w-8 h-8 text-[#c4242a] mx-auto mb-3" />
        <h2 className="text-base font-semibold text-[#131416] mb-1">Submission Not Found</h2>
        <p className="text-xs text-[#5d5f60] mb-4">{error || 'The requested submission record could not be loaded.'}</p>
        <Link
          to="/admin/submissions"
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] bg-[#1d1f20] text-white text-xs font-medium"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Submissions</span>
        </Link>
      </div>
    );
  }

  const { submission } = data;
  const session = submission.session || {};
  const formValues = submission.form_values || [];
  const answers = submission.answers || [];
  const events = submission.events || [];
  const result = submission.result || {};
  const calculatorData = result?.calculator || null;
  const quickConnect = result?.quick_connect || null;

  // Extract visitor phone and name from form values
  let visitorPhone = '';
  let visitorName = '';
  for (const fv of formValues) {
    const fKey = (fv.field_key_snapshot || '').toLowerCase();
    const fType = (fv.field_type_snapshot || '').toLowerCase();
    if (['phone', 'mobile', 'whatsapp', 'tel'].includes(fKey) || fType === 'phone') {
      if (fv.value_text) visitorPhone = fv.value_text;
    }
    if (['name', 'full_name', 'first_name'].includes(fKey) && !visitorName) {
      if (fv.value_text) visitorName = fv.value_text;
    }
  }

  const cleanLeadPhone = visitorPhone.replace(/[^0-9]/g, '');
  const greetingMsg = encodeURIComponent(
    `Hi ${visitorName || 'there'}, thank you for your submission on ${session.funnel_name || 'our funnel'}!`
  );

  const hasQuote =
    Boolean(session.formatted_quote) ||
    session.calculated_value !== null ||
    Boolean(calculatorData);

  return (
    <div className="space-y-6">
      {/* Top Bar Navigation & Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              to="/admin/submissions"
              className="text-xs text-[#5d5f60] hover:text-[#131416] transition-colors"
            >
              Submissions
            </Link>
            <span className="text-xs text-[#cdced1]">/</span>
            <span className="text-xs font-medium text-[#131416]">
              Session #{session.id}
            </span>
          </div>
          <h1 className="text-[22px] font-semibold text-[#131416]">Visitor Journey Inspector</h1>
        </div>

        {/* Quick Outreach & Status Header Actions */}
        <div className="flex flex-wrap items-center gap-2">
          {cleanLeadPhone && (
            <>
              <a
                href={`https://wa.me/${cleanLeadPhone}?text=${greetingMsg}`}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] text-xs font-medium bg-[#dcfae5] hover:bg-[#bbf0cc] text-[#1e7b4f] transition-colors"
              >
                <MessageCircle className="w-3.5 h-3.5" />
                <span>WhatsApp Lead</span>
              </a>

              <a
                href={`tel:${cleanLeadPhone}`}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] text-xs font-medium bg-[#1d1f20] hover:bg-black text-white transition-colors"
              >
                <Phone className="w-3.5 h-3.5" />
                <span>Call Lead</span>
              </a>
            </>
          )}

          {session.status === 'completed' ? (
            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-[6px] text-xs font-medium bg-[#dcfae5] text-[#1e7b4f] border border-[#bbf0cc]">
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span>Completed Journey</span>
            </span>
          ) : session.status === 'form_pending_video_gate' ? (
            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-[6px] text-xs font-medium bg-[#fffbeb] text-[#92400e] border border-[#fef3c7]">
              <Video className="w-3.5 h-3.5" />
              <span>Form Pending (Video Gate)</span>
            </span>
          ) : (
            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-[6px] text-xs font-medium bg-[#ebedee] text-[#131416] capitalize">
              <span>{session.status?.replace(/_/g, ' ') || 'In Progress'}</span>
            </span>
          )}
        </div>
      </div>

      {/* Overview Meta Strip */}
      <div className="bg-[#f4f6f8] border border-[#e8eaec] rounded-[8px] p-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
        <div>
          <span className="text-[#5d5f60] block text-[11px] mb-0.5">Funnel</span>
          <span className="font-medium text-[#131416] block truncate">{session.funnel_name}</span>
          <span className="text-[11px] text-[#5d5f60] block">
            Version {session.version_number || 1}
          </span>
        </div>

        <div>
          <span className="text-[#5d5f60] block text-[11px] mb-0.5">Session Initialized</span>
          <span className="font-medium text-[#131416] block">
            {session.created_at
              ? new Date(session.created_at).toLocaleString()
              : 'None'}
          </span>
        </div>

        <div>
          <span className="text-[#5d5f60] block text-[11px] mb-0.5">Journey Completed</span>
          <span className="font-medium text-[#131416] block">
            {session.completed_at
              ? new Date(session.completed_at).toLocaleString()
              : 'In Progress'}
          </span>
        </div>

        <div>
          <span className="text-[#5d5f60] block text-[11px] mb-0.5">Final Destination</span>
          <span className="font-medium text-[#1b6c80] truncate block">
            {session.destination_name || 'Pending completion'}
          </span>
          {session.destination_url && (
            <a
              href={session.destination_url}
              target="_blank"
              rel="noreferrer"
              className="text-[11px] text-[#5d5f60] hover:underline truncate block"
            >
              {session.destination_url}
            </a>
          )}
        </div>
      </div>

      {/* Main Content: Two Columns */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Column 1: Captured Lead Profile & Answer Trail (7 cols) */}
        <div className="lg:col-span-7 space-y-6">
          {/* Dynamic Quote Result Card */}
          {hasQuote && (
            <div className="bg-white border border-[#e8eaec] rounded-[8px] p-5 space-y-3">
              <div className="flex items-center justify-between pb-2 border-b border-[#f0f1f2]">
                <h3 className="text-xs font-semibold text-[#131416] flex items-center gap-2">
                  <Calculator className="w-3.5 h-3.5 text-[#1b6c80]" />
                  <span>{calculatorData?.result_label || 'Calculated Quote Result'}</span>
                </h3>
                <span className="px-2 py-0.5 rounded-[4px] text-[10px] font-medium bg-[#ebedee] text-[#131416]">
                  Calculated
                </span>
              </div>

              <div className="flex items-baseline justify-between">
                <div>
                  <span className="text-xs text-[#5d5f60] block mb-0.5">
                    Generated Quote
                  </span>
                  <span className="text-2xl font-normal text-[#131416] num">
                    {session.formatted_quote ||
                      (session.calculated_value !== null
                        ? Number(session.calculated_value).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                          })
                        : '—')}
                  </span>
                </div>

                {session.calculated_value !== null && (
                  <div className="text-right">
                    <span className="text-xs text-[#5d5f60] block">
                      Raw Calculated Value
                    </span>
                    <span className="font-mono text-[#131416] text-xs">
                      {session.calculated_value}
                    </span>
                  </div>
                )}
              </div>

              {calculatorData?.formula && (
                <div className="p-2 rounded-[6px] bg-[#f4f6f8] border border-[#e8eaec] text-xs">
                  <span className="text-[11px] text-[#5d5f60] block mb-1">
                    Evaluated Formula
                  </span>
                  <code className="font-mono text-xs text-[#131416] block truncate">
                    {calculatorData.formula}
                  </code>
                </div>
              )}

              {calculatorData?.variables && Object.keys(calculatorData.variables).length > 0 && (
                <div>
                  <span className="text-[11px] text-[#5d5f60] block mb-1">
                    Captured Question Variables
                  </span>
                  <div className="flex flex-wrap gap-1.5">
                    {Object.entries(calculatorData.variables).map(([vKey, vVal]) => (
                      <div
                        key={vKey}
                        className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] border border-[#e8eaec] text-xs font-mono"
                      >
                        <span className="text-[#1b6c80]">@{vKey}</span>
                        <span className="text-[#5d5f60]">=</span>
                        <span className="text-[#131416]">{String(vVal)}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Quick Connect Direct Outreach Card */}
          {quickConnect && quickConnect.enabled && (
            <div className="bg-white border border-[#e8eaec] rounded-[8px] p-5 space-y-3">
              <div className="flex items-center justify-between pb-2 border-b border-[#f0f1f2]">
                <h3 className="text-xs font-semibold text-[#131416] flex items-center gap-2">
                  <Phone className="w-3.5 h-3.5 text-[#1e7b4f]" />
                  <span>Direct Connect Channel</span>
                </h3>
                <span className="px-2 py-0.5 rounded-[4px] text-[10px] font-medium bg-[#dcfae5] text-[#1e7b4f] capitalize">
                  {quickConnect.action_type || 'both'}
                </span>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                {quickConnect.call_url && (
                  <div className="p-2.5 bg-[#f4f6f8] rounded-[6px] border border-[#e8eaec]">
                    <span className="text-[11px] text-[#5d5f60] block mb-0.5">
                      Target Phone Number
                    </span>
                    <div className="flex items-center justify-between">
                      <span className="font-mono text-[#131416]">
                        {quickConnect.phone_number || 'None'}
                      </span>
                      <a
                        href={quickConnect.call_url}
                        className="text-[11px] text-[#1b6c80] hover:underline"
                      >
                        Call link
                      </a>
                    </div>
                  </div>
                )}

                {quickConnect.whatsapp_url && (
                  <div className="p-2.5 bg-[#f4f6f8] rounded-[6px] border border-[#e8eaec]">
                    <span className="text-[11px] text-[#5d5f60] block mb-0.5">
                      Target WhatsApp
                    </span>
                    <div className="flex items-center justify-between">
                      <span className="font-mono text-[#131416]">
                        {quickConnect.whatsapp_number || 'None'}
                      </span>
                      <a
                        href={quickConnect.whatsapp_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-[11px] text-[#1e7b4f] hover:underline"
                      >
                        Chat link
                      </a>
                    </div>
                  </div>
                )}
              </div>

              {quickConnect.message && (
                <div className="p-2 rounded-[6px] bg-[#f4f6f8] border border-[#e8eaec] text-xs">
                  <span className="text-[11px] text-[#5d5f60] block mb-0.5">
                    Pre-filled Message Template
                  </span>
                  <p className="text-xs text-[#131416] whitespace-pre-wrap">
                    {quickConnect.message}
                  </p>
                </div>
              )}
            </div>
          )}

          {/* Pre-Quiz Lead Form Card */}
          <div className="bg-white border border-[#e8eaec] rounded-[8px] p-5">
            <div className="flex items-center justify-between mb-3 pb-2 border-b border-[#f0f1f2]">
              <h3 className="text-xs font-semibold text-[#131416] flex items-center gap-2">
                <User className="w-3.5 h-3.5 text-[#5d5f60]" />
                <span>Pre-Quiz Lead Profile</span>
              </h3>
              <span className="text-[11px] text-[#5d5f60]">
                {formValues.length} fields captured
              </span>
            </div>

            {formValues.length === 0 ? (
              <p className="text-xs text-[#5d5f60]">
                No pre-quiz form data captured for this submission.
              </p>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                {formValues.map((fv: any, idx: number) => (
                  <div
                    key={fv.id || idx}
                    className="p-2.5 bg-[#f4f6f8] rounded-[6px] border border-[#e8eaec]"
                  >
                    <span className="text-[11px] text-[#5d5f60] block mb-0.5">
                      {fv.field_label_snapshot || fv.field_key_snapshot}
                    </span>
                    <span className="font-medium text-[#131416] break-words block">
                      {fv.field_type_snapshot === 'consent'
                        ? fv.value_text === '1'
                          ? 'Consent Acknowledged'
                          : 'Not Granted'
                        : fv.value_text || 'None'}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Sequential Question Answers Path */}
          <div className="bg-white border border-[#e8eaec] rounded-[8px] p-5">
            <div className="flex items-center justify-between mb-3 pb-2 border-b border-[#f0f1f2]">
              <h3 className="text-xs font-semibold text-[#131416] flex items-center gap-2">
                <CheckCircle2 className="w-3.5 h-3.5 text-[#1e7b4f]" />
                <span>Sequential Question Answers Path</span>
              </h3>
              <span className="text-[11px] text-[#5d5f60]">
                {answers.length} steps completed
              </span>
            </div>

            {answers.length === 0 ? (
              <p className="text-xs text-[#5d5f60]">No question answers recorded.</p>
            ) : (
              <div className="space-y-2">
                {answers.map((ans: any, idx: number) => (
                  <div
                    key={ans.id || idx}
                    className="p-3 rounded-[6px] border border-[#e8eaec] bg-white text-xs space-y-1.5"
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className="w-5 h-5 rounded-[4px] bg-[#ebedee] text-[#131416] text-[10px] font-medium flex items-center justify-center">
                          {idx + 1}
                        </span>
                        <h4 className="font-medium text-[#131416]">
                          {ans.question_text_snapshot || 'Question'}
                        </h4>
                      </div>
                      <span className="text-[10px] text-[#5d5f60]">
                        {ans.answered_at
                          ? new Date(ans.answered_at).toLocaleTimeString()
                          : ''}
                      </span>
                    </div>

                    <div className="pl-7">
                      {ans.answer_type === 'slider' ? (
                        <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[4px] bg-[#ebf3fc] text-[#2f70bb] text-xs">
                          <Sliders className="w-3 h-3" />
                          <span>Slider value:</span>
                          <span className="font-mono">{ans.answer_value_text}</span>
                        </div>
                      ) : ans.answer_type === 'video_gate_question' ? (
                        <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[4px] bg-[#ffedea] text-[#c4242a] text-xs">
                          <Video className="w-3 h-3" />
                          <span>Video option:</span>
                          <span>{ans.answer_value_text}</span>
                        </div>
                      ) : (
                        <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[4px] bg-[#f4f6f8] text-[#131416] border border-[#e8eaec] text-xs">
                          <span>Choice:</span>
                          <span className="font-medium">{ans.answer_value_text}</span>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Column 2: Chronological Journey Event Timeline (5 cols) */}
        <div className="lg:col-span-5 space-y-6">
          <div className="bg-white border border-[#e8eaec] rounded-[8px] p-5">
            <h3 className="text-xs font-semibold text-[#131416] mb-3 pb-2 border-b border-[#f0f1f2] flex items-center gap-2">
              <Clock className="w-3.5 h-3.5 text-[#5d5f60]" />
              <span>Journey Event Timeline</span>
            </h3>

            {events.length === 0 ? (
              <p className="text-xs text-[#5d5f60]">No timeline events recorded.</p>
            ) : (
              <div className="relative pl-5 space-y-4 before:content-[''] before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-[1px] before:bg-[#e8eaec]">
                {events.map((evt: any, idx: number) => {
                  const eventType = evt.event_type || '';
                  let bulletBg = 'bg-[#1b6c80]';
                  if (eventType === 'video_started') bulletBg = 'bg-[#2f70bb]';
                  else if (eventType === 'video_gate_reached') bulletBg = 'bg-[#92400e]';
                  else if (eventType === 'video_gate_completed') bulletBg = 'bg-[#1e7b4f]';
                  else if (eventType === 'video_completed') bulletBg = 'bg-[#1b6c80]';

                  let eventLabel = eventType.replace(/_/g, ' ');
                  if (eventType === 'video_started') eventLabel = 'Video playback started';
                  else if (eventType === 'video_gate_reached') eventLabel = 'Video gate paused';
                  else if (eventType === 'video_gate_completed') eventLabel = 'Video gate passed';
                  else if (eventType === 'video_completed') eventLabel = 'Video completed';

                  return (
                    <div key={evt.id || idx} className="relative text-xs">
                      {/* Bullet node */}
                      <div
                        className={`absolute -left-5 top-1 w-2 h-2 rounded-full ${bulletBg}`}
                      />

                      <div>
                        <div className="flex items-center gap-1.5 flex-wrap">
                          <span className="font-medium text-[#131416] capitalize">
                            {eventLabel}
                          </span>
                          <span className="text-[10px] text-[#5d5f60]">
                            {evt.occurred_at
                              ? new Date(evt.occurred_at).toLocaleTimeString()
                              : ''}
                          </span>
                        </div>

                        {evt.node_title && (
                          <p className="text-[11px] text-[#5d5f60] mt-0.5">
                            Node: {evt.node_title}
                          </p>
                        )}

                        {evt.option_label && (
                          <div className="mt-1 text-[11px] text-[#131416] bg-[#f4f6f8] px-2 py-0.5 rounded-[4px] border border-[#e8eaec] inline-block">
                            Selected: {evt.option_label}
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { showConfirm, showAlert } from '../../utils/swal';
import {
  LifeBuoy,
  Mail,
  Phone,
  MessageCircle,
  Clock,
  CheckCircle,
  AlertCircle,
  Search,
  Filter,
  Save,
  Trash2,
  ExternalLink,
  Eye,
  X,
  PhoneCall,
  User,
  Calendar,
  Globe
} from 'lucide-react';

interface SupportTicket {
  id: number;
  ticket_num: string;
  name: string;
  email: string;
  phone: string | null;
  category: string;
  subject: string;
  message: string;
  status: 'open' | 'in_progress' | 'resolved';
  admin_notes: string | null;
  ip_address: string | null;
  created_at: string;
  updated_at: string;
}

interface TicketStats {
  total: number;
  open: number;
  in_progress: number;
  resolved: number;
}

interface SupportChannels {
  email: string;
  phone: string;
  whatsapp: string;
  hours: string;
  description: string;
}

export const SuperadminSupportTickets: React.FC = () => {
  const [tickets, setTickets] = useState<SupportTicket[]>([]);
  const [stats, setStats] = useState<TicketStats>({ total: 0, open: 0, in_progress: 0, resolved: 0 });
  const [channels, setChannels] = useState<SupportChannels>({
    email: '',
    phone: '',
    whatsapp: '',
    hours: '',
    description: '',
  });

  const [loading, setLoading] = useState(true);
  const [savingChannels, setSavingChannels] = useState(false);
  const [channelsMsg, setChannelsMsg] = useState('');
  const [channelsError, setChannelsError] = useState('');

  // Filtering
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState<string>('');

  // Active Ticket Modal
  const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);
  const [adminNotes, setAdminNotes] = useState<string>('');
  const [updatingTicket, setUpdatingTicket] = useState(false);

  useEffect(() => {
    loadData();
  }, [statusFilter]);

  const loadData = async () => {
    try {
      setLoading(true);
      const queryParam = statusFilter !== 'all' ? `?status=${statusFilter}` : '';
      const res = await api.get(`/api/platform/support-tickets${queryParam}`);
      if (res?.tickets) setTickets(res.tickets);
      if (res?.stats) setStats(res.stats);
      if (res?.channels) setChannels(res.channels);
    } catch (err: any) {
      console.error('Failed to load tickets', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSaveChannels = async (e: React.FormEvent) => {
    e.preventDefault();
    setSavingChannels(true);
    setChannelsMsg('');
    setChannelsError('');

    try {
      const res = await api.post('/api/platform/support-channels', channels);
      if (res?.channels) setChannels(res.channels);
      setChannelsMsg('Direct contact channels saved successfully. Public support page and footers updated.');
    } catch (err: any) {
      setChannelsError(err.message || 'Failed to save contact channels.');
    } finally {
      setSavingChannels(false);
    }
  };

  const handleUpdateStatus = async (ticketId: number, newStatus: 'open' | 'in_progress' | 'resolved', notes?: string) => {
    setUpdatingTicket(true);
    try {
      await api.post(`/api/platform/support-tickets/${ticketId}/status`, {
        status: newStatus,
        admin_notes: notes !== undefined ? notes : adminNotes,
      });

      // Update local state
      setTickets(prev => prev.map(t => t.id === ticketId ? { ...t, status: newStatus, admin_notes: notes !== undefined ? notes : adminNotes } : t));
      if (selectedTicket && selectedTicket.id === ticketId) {
        setSelectedTicket(prev => prev ? { ...prev, status: newStatus, admin_notes: notes !== undefined ? notes : adminNotes } : null);
      }

      // Refresh stats
      const res = await api.get('/api/platform/support-tickets');
      if (res?.stats) setStats(res.stats);
    } catch (err: any) {
      await showAlert('Update Failed', err.message || 'Failed to update ticket status', 'error');
    } finally {
      setUpdatingTicket(false);
    }
  };

  const handleDeleteTicket = async (ticket: SupportTicket) => {
    const confirmed = await showConfirm(
      'Delete Support Ticket',
      `Permanently delete ticket #${ticket.ticket_num} from ${ticket.name}?`,
      'Yes, Delete',
      true
    );
    if (!confirmed) return;

    try {
      await api.post(`/api/platform/support-tickets/${ticket.id}/delete`, {});
      setTickets(prev => prev.filter(t => t.id !== ticket.id));
      if (selectedTicket?.id === ticket.id) setSelectedTicket(null);

      // Refresh stats
      const res = await api.get('/api/platform/support-tickets');
      if (res?.stats) setStats(res.stats);
    } catch (err: any) {
      await showAlert('Delete Failed', err.message || 'Failed to delete ticket', 'error');
    }
  };

  const openTicketDetail = (ticket: SupportTicket) => {
    setSelectedTicket(ticket);
    setAdminNotes(ticket.admin_notes || '');
  };

  const filteredTickets = tickets.filter(t => {
    if (!searchTerm.trim()) return true;
    const term = searchTerm.toLowerCase();
    return (
      t.ticket_num.toLowerCase().includes(term) ||
      t.name.toLowerCase().includes(term) ||
      t.email.toLowerCase().includes(term) ||
      (t.phone && t.phone.toLowerCase().includes(term)) ||
      t.subject.toLowerCase().includes(term) ||
      t.message.toLowerCase().includes(term)
    );
  });

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* 1. Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-[#e0f2fe] text-[#0369a1]">
              <LifeBuoy className="w-5 h-5" />
            </span>
            <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Support Tickets & Direct Channels</h1>
          </div>
          <p className="text-xs text-[#5d5f60] mt-1">
            Manage inquiries with full telephone numbers, review tickets, and update direct phone/email channels in real time.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <a
            href="/support"
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] hover:bg-[#f8fafc] transition"
          >
            <ExternalLink className="w-3.5 h-3.5" />
            <span>View Public Support Page</span>
          </a>
        </div>
      </div>

      {/* 2. Direct Channels Editor Card */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#edf0f3] gap-2">
          <div className="flex items-center gap-2">
            <div className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
              <PhoneCall className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-sm font-bold text-[#131416]">Direct Contact Channels Configuration</h2>
              <p className="text-[11px] text-[#5d5f60]">Update the official phone number and email shown to public visitors.</p>
            </div>
          </div>
        </div>

        {channelsMsg && (
          <div className="p-3 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
            <CheckCircle className="w-4 h-4 shrink-0" />
            <span>{channelsMsg}</span>
          </div>
        )}

        {channelsError && (
          <div className="p-3 rounded-xl bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{channelsError}</span>
          </div>
        )}

        <form onSubmit={handleSaveChannels} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="space-y-1">
            <label className="text-xs font-semibold text-[#131416] flex items-center gap-1.5">
              <Mail className="w-3.5 h-3.5 text-[#1d5fd6]" />
              <span>Direct Support Email</span>
            </label>
            <input
              type="email"
              required
              value={channels.email}
              onChange={(e) => setChannels({ ...channels, email: e.target.value })}
              placeholder="e.g. support@filterfunnel.in"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold text-[#131416] flex items-center gap-1.5">
              <Phone className="w-3.5 h-3.5 text-[#2f7a45]" />
              <span>Direct Phone Number</span>
            </label>
            <input
              type="text"
              value={channels.phone}
              onChange={(e) => setChannels({ ...channels, phone: e.target.value })}
              placeholder="e.g. +1 (800) 555-0199 or +91 98765-43210"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition font-mono"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold text-[#131416] flex items-center gap-1.5">
              <MessageCircle className="w-3.5 h-3.5 text-[#25D366]" />
              <span>WhatsApp Direct Line</span>
            </label>
            <input
              type="text"
              value={channels.whatsapp}
              onChange={(e) => setChannels({ ...channels, whatsapp: e.target.value })}
              placeholder="e.g. +18005550199"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition font-mono"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold text-[#131416] flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5 text-[#b45309]" />
              <span>Operating Hours</span>
            </label>
            <input
              type="text"
              value={channels.hours}
              onChange={(e) => setChannels({ ...channels, hours: e.target.value })}
              placeholder="e.g. Mon–Fri, 8:00 AM – 8:00 PM EST"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
            />
          </div>

          <div className="sm:col-span-2 lg:col-span-3 space-y-1">
            <label className="text-xs font-semibold text-[#131416]">Channel Note / Subtext</label>
            <input
              type="text"
              value={channels.description}
              onChange={(e) => setChannels({ ...channels, description: e.target.value })}
              placeholder="e.g. Direct account manager support for Agency Scale plans"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
            />
          </div>

          <div className="sm:col-span-2 lg:col-span-1 flex items-end">
            <button
              type="submit"
              disabled={savingChannels}
              className="w-full h-9 inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60 shadow-xs"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{savingChannels ? 'Saving...' : 'Save Direct Channels'}</span>
            </button>
          </div>
        </form>
      </div>

      {/* 3. KPI Metrics Row */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="p-4 rounded-2xl bg-white border border-[#e2e5e9] space-y-1">
          <span className="text-xs text-[#5d5f60]">Total Tickets</span>
          <div className="text-2xl font-bold text-[#131416]">{stats.total}</div>
        </div>
        <div className="p-4 rounded-2xl bg-white border border-[#e2e5e9] space-y-1">
          <span className="text-xs text-[#5d5f60]">Open / Pending</span>
          <div className="text-2xl font-bold text-[#1d5fd6]">{stats.open}</div>
        </div>
        <div className="p-4 rounded-2xl bg-white border border-[#e2e5e9] space-y-1">
          <span className="text-xs text-[#5d5f60]">In Progress</span>
          <div className="text-2xl font-bold text-[#b45309]">{stats.in_progress}</div>
        </div>
        <div className="p-4 rounded-2xl bg-white border border-[#e2e5e9] space-y-1">
          <span className="text-xs text-[#5d5f60]">Resolved</span>
          <div className="text-2xl font-bold text-[#2f7a45]">{stats.resolved}</div>
        </div>
      </div>

      {/* 4. Tickets Table & Management */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#edf0f3]">
          <div className="flex items-center gap-2">
            <span className="text-sm font-bold text-[#131416]">Support Inquiries</span>
            <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-[#f4f6f8] text-[#5d5f60]">
              {filteredTickets.length}
            </span>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            {/* Status Filter Buttons */}
            <div className="inline-flex items-center bg-[#f4f6f8] p-1 rounded-xl gap-1 text-xs font-semibold">
              <button
                type="button"
                onClick={() => setStatusFilter('all')}
                className={`px-3 py-1 rounded-lg transition ${statusFilter === 'all' ? 'bg-white text-[#1d5fd6] shadow-xs' : 'text-[#5d5f60] hover:text-[#131416]'}`}
              >
                All
              </button>
              <button
                type="button"
                onClick={() => setStatusFilter('open')}
                className={`px-3 py-1 rounded-lg transition ${statusFilter === 'open' ? 'bg-white text-[#1d5fd6] shadow-xs' : 'text-[#5d5f60] hover:text-[#131416]'}`}
              >
                Open ({stats.open})
              </button>
              <button
                type="button"
                onClick={() => setStatusFilter('in_progress')}
                className={`px-3 py-1 rounded-lg transition ${statusFilter === 'in_progress' ? 'bg-white text-[#1d5fd6] shadow-xs' : 'text-[#5d5f60] hover:text-[#131416]'}`}
              >
                In Progress ({stats.in_progress})
              </button>
              <button
                type="button"
                onClick={() => setStatusFilter('resolved')}
                className={`px-3 py-1 rounded-lg transition ${statusFilter === 'resolved' ? 'bg-white text-[#1d5fd6] shadow-xs' : 'text-[#5d5f60] hover:text-[#131416]'}`}
              >
                Resolved ({stats.resolved})
              </button>
            </div>

            {/* Search Input */}
            <div className="relative">
              <Search className="w-3.5 h-3.5 text-[#8c9096] absolute left-3 top-2.5" />
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search ticket, name, phone..."
                className="pl-8 pr-3 py-1.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition w-56"
              />
            </div>
          </div>
        </div>

        {/* Tickets Table */}
        {loading ? (
          <div className="py-12 text-center text-xs text-[#5d5f60]">Loading inquiries...</div>
        ) : filteredTickets.length === 0 ? (
          <div className="py-12 text-center text-[#5d5f60] space-y-2">
            <LifeBuoy className="w-8 h-8 mx-auto opacity-40 text-[#1d5fd6]" />
            <p className="text-xs font-semibold text-[#131416]">No support inquiries found</p>
            <p className="text-[11px]">When visitors submit tickets with phone numbers, they will appear here.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#f8fafc] text-[#5d5f60] font-semibold border-b border-[#e2e5e9]">
                <tr>
                  <th className="py-3 px-3">Ticket</th>
                  <th className="py-3 px-3">Requester</th>
                  <th className="py-3 px-3">Phone Number</th>
                  <th className="py-3 px-3">Category & Subject</th>
                  <th className="py-3 px-3">Status</th>
                  <th className="py-3 px-3">Submitted</th>
                  <th className="py-3 px-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#edf0f3]">
                {filteredTickets.map((t) => (
                  <tr key={t.id} className="hover:bg-[#f8fafc]/70 transition">
                    <td className="py-3 px-3 font-mono font-bold text-[#1d5fd6]">
                      #{t.ticket_num}
                    </td>
                    <td className="py-3 px-3">
                      <div className="font-semibold text-[#131416]">{t.name}</div>
                      <a href={`mailto:${t.email}`} className="text-[11px] text-[#5d5f60] hover:text-[#1d5fd6]">
                        {t.email}
                      </a>
                    </td>
                    <td className="py-3 px-3">
                      {t.phone ? (
                        <div className="flex items-center gap-1.5 font-mono text-[#131416]">
                          <Phone className="w-3 h-3 text-[#2f7a45]" />
                          <a href={`tel:${t.phone}`} className="hover:text-[#1d5fd6] hover:underline">
                            {t.phone}
                          </a>
                        </div>
                      ) : (
                        <span className="text-[#8c9096] text-[11px] italic">Not provided</span>
                      )}
                    </td>
                    <td className="py-3 px-3 max-w-xs">
                      <span className="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#f1f3f5] text-[#5d5f60] uppercase mb-0.5">
                        {t.category}
                      </span>
                      <div className="font-semibold text-[#131416] truncate">{t.subject}</div>
                      <div className="text-[11px] text-[#5d5f60] truncate">{t.message}</div>
                    </td>
                    <td className="py-3 px-3">
                      <span
                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${
                          t.status === 'open'
                            ? 'bg-[#dcf1ff] text-[#0a3a80] border border-[#b9e6fe]'
                            : t.status === 'in_progress'
                            ? 'bg-[#fef3c7] text-[#92400e] border border-[#fde68a]'
                            : 'bg-[#dcfae5] text-[#2f7a45] border border-[#bbf7d0]'
                        }`}
                      >
                        {t.status === 'open' ? 'Open' : t.status === 'in_progress' ? 'In Progress' : 'Resolved'}
                      </span>
                    </td>
                    <td className="py-3 px-3 text-[#5d5f60] text-[11px] whitespace-nowrap">
                      {new Date(t.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                    </td>
                    <td className="py-3 px-3 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          type="button"
                          onClick={() => openTicketDetail(t)}
                          title="View Full Ticket"
                          className="p-1.5 rounded-lg border border-[#e2e5e9] bg-white text-[#5d5f60] hover:text-[#1d5fd6] hover:bg-[#f8fafc] transition"
                        >
                          <Eye className="w-3.5 h-3.5" />
                        </button>

                        <button
                          type="button"
                          onClick={() => handleDeleteTicket(t)}
                          title="Delete Ticket"
                          className="p-1.5 rounded-lg border border-[#e2e5e9] bg-white text-[#5d5f60] hover:text-[#991b1b] hover:bg-[#fee2e2]/30 transition"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* 5. Ticket Detail Modal */}
      {selectedTicket && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white border border-[#e2e5e9] rounded-2xl max-w-2xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div className="flex items-center justify-between pb-3 border-b border-[#edf0f3]">
              <div className="flex items-center gap-2">
                <span className="font-mono font-bold text-sm text-[#1d5fd6]">#{selectedTicket.ticket_num}</span>
                <span
                  className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                    selectedTicket.status === 'open'
                      ? 'bg-[#dcf1ff] text-[#0a3a80]'
                      : selectedTicket.status === 'in_progress'
                      ? 'bg-[#fef3c7] text-[#92400e]'
                      : 'bg-[#dcfae5] text-[#2f7a45]'
                  }`}
                >
                  {selectedTicket.status.toUpperCase()}
                </span>
              </div>
              <button
                type="button"
                onClick={() => setSelectedTicket(null)}
                className="text-[#5d5f60] hover:text-[#131416] p-1 rounded-lg hover:bg-[#f8fafc]"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Requester Contact Header */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-[#f8fafc] border border-[#e2e5e9] text-xs">
              <div>
                <span className="text-[#8c9096] block text-[10px] uppercase font-bold">Requester</span>
                <span className="font-bold text-[#131416]">{selectedTicket.name}</span>
              </div>
              <div>
                <span className="text-[#8c9096] block text-[10px] uppercase font-bold">Email Address</span>
                <a href={`mailto:${selectedTicket.email}`} className="text-[#1d5fd6] font-mono hover:underline">
                  {selectedTicket.email}
                </a>
              </div>
              <div>
                <span className="text-[#8c9096] block text-[10px] uppercase font-bold">Phone Number</span>
                {selectedTicket.phone ? (
                  <div className="flex items-center gap-2 pt-0.5">
                    <a href={`tel:${selectedTicket.phone}`} className="text-[#2f7a45] font-mono font-bold hover:underline">
                      {selectedTicket.phone}
                    </a>
                    <a
                      href={`https://wa.me/${selectedTicket.phone.replace(/[^0-9]/g, '')}`}
                      target="_blank"
                      rel="noreferrer"
                      className="p-1 rounded bg-[#dcfae5] text-[#2f7a45] hover:bg-[#25D366] hover:text-white transition"
                      title="Open WhatsApp chat"
                    >
                      <MessageCircle className="w-3 h-3" />
                    </a>
                  </div>
                ) : (
                  <span className="text-[#8c9096] italic">None</span>
                )}
              </div>
            </div>

            {/* Ticket Subject & Message Content */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="font-bold text-sm text-[#131416]">{selectedTicket.subject}</span>
                <span className="text-[11px] text-[#5d5f60] font-mono">Category: {selectedTicket.category}</span>
              </div>
              <div className="p-4 rounded-xl border border-[#e2e5e9] bg-white text-xs text-[#131416] whitespace-pre-wrap leading-relaxed">
                {selectedTicket.message}
              </div>
              {selectedTicket.ip_address && (
                <div className="text-[10px] text-[#8c9096] font-mono flex items-center gap-1">
                  <Globe className="w-3 h-3" />
                  <span>Sender IP: {selectedTicket.ip_address} • Submitted: {new Date(selectedTicket.created_at).toLocaleString()}</span>
                </div>
              )}
            </div>

            {/* Status Change & Admin Notes */}
            <div className="pt-3 border-t border-[#edf0f3] space-y-3">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-[#131416]">Ticket Status</label>
                <div className="flex items-center gap-1.5">
                  <button
                    type="button"
                    disabled={updatingTicket}
                    onClick={() => handleUpdateStatus(selectedTicket.id, 'open')}
                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition ${
                      selectedTicket.status === 'open'
                        ? 'bg-[#1d5fd6] text-white shadow-xs'
                        : 'bg-[#f4f6f8] text-[#5d5f60] hover:bg-[#e2e5e9]'
                    }`}
                  >
                    Open
                  </button>
                  <button
                    type="button"
                    disabled={updatingTicket}
                    onClick={() => handleUpdateStatus(selectedTicket.id, 'in_progress')}
                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition ${
                      selectedTicket.status === 'in_progress'
                        ? 'bg-[#b45309] text-white shadow-xs'
                        : 'bg-[#f4f6f8] text-[#5d5f60] hover:bg-[#e2e5e9]'
                    }`}
                  >
                    In Progress
                  </button>
                  <button
                    type="button"
                    disabled={updatingTicket}
                    onClick={() => handleUpdateStatus(selectedTicket.id, 'resolved')}
                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition ${
                      selectedTicket.status === 'resolved'
                        ? 'bg-[#2f7a45] text-white shadow-xs'
                        : 'bg-[#f4f6f8] text-[#5d5f60] hover:bg-[#e2e5e9]'
                    }`}
                  >
                    Resolved
                  </button>
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-bold text-[#131416]">Internal Admin Notes</label>
                <textarea
                  rows={3}
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Private notes for support team (not visible to visitor)..."
                  className="w-full p-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] focus:bg-white transition"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setSelectedTicket(null)}
                  className="px-3 py-1.5 rounded-xl border border-[#e2e5e9] text-xs font-semibold text-[#5d5f60] hover:bg-[#f8fafc] transition"
                >
                  Close
                </button>
                <button
                  type="button"
                  disabled={updatingTicket}
                  onClick={() => handleUpdateStatus(selectedTicket.id, selectedTicket.status, adminNotes)}
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60"
                >
                  Save Notes
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

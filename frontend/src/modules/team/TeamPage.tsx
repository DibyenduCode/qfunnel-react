import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { UserPlus, Trash2, Loader2 } from 'lucide-react';

export const TeamPage: React.FC = () => {
  const [members, setMembers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [inviteEmail, setInviteEmail] = useState('');
  const [inviteName, setInviteName] = useState('');
  const [inviteRole, setInviteRole] = useState('editor');
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  useEffect(() => {
    loadTeam();
  }, []);

  const loadTeam = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/team');
      setMembers(res.members || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleInvite = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setErrorMsg('');

    try {
      await api.post('/api/team', {
        email: inviteEmail,
        name: inviteName,
        role: inviteRole,
      });
      setShowInviteModal(false);
      setInviteEmail('');
      setInviteName('');
      loadTeam();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to add member.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleRoleChange = async (membershipId: number, newRole: string) => {
    try {
      await api.post(`/api/team/${membershipId}/update`, { role: newRole });
      loadTeam();
    } catch (err: any) {
      alert(err.message || 'Failed to update role');
    }
  };

  const handleRemove = async (membershipId: number) => {
    if (!confirm('Remove this member from workspace?')) return;
    try {
      await api.post(`/api/team/${membershipId}/remove`);
      loadTeam();
    } catch (err: any) {
      alert(err.message || 'Failed to remove member');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-lg font-bold text-[#131416]">Workspace Team</h1>
          <p className="text-xs text-[#5c6066] mt-0.5">Manage collaborators, editors, and analyst permissions</p>
        </div>

        <button
          onClick={() => setShowInviteModal(true)}
          className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition self-start sm:self-auto shadow-xs"
        >
          <UserPlus className="w-4 h-4" />
          <span>Invite Member</span>
        </button>
      </div>

      {/* Members Table */}
      <div className="bg-white border border-[#e8eaec] rounded-2xl overflow-hidden">
        {loading ? (
          <div className="p-6 divide-y divide-[#e8eaec]">
            {[1, 2, 3].map((i) => (
              <div key={i} className="py-3.5 flex items-center justify-between gap-4">
                <div className="space-y-1.5 flex-1">
                  <div className="h-4 w-36 rounded-md skeleton-shimmer" />
                  <div className="h-3 w-48 rounded-md skeleton-shimmer" />
                </div>
                <div className="h-7 w-24 rounded-lg skeleton-shimmer" />
                <div className="h-5 w-16 rounded-full skeleton-shimmer" />
                <div className="w-7 h-7 rounded-lg skeleton-shimmer" />
              </div>
            ))}
          </div>
        ) : (
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-[#e8eaec] bg-[#f9fbfc] text-[11px] font-semibold text-[#5c6066]">
                <th className="py-3 px-4">User</th>
                <th className="py-3 px-4">Role</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#e8eaec] text-xs">
              {members.map((m) => (
                <tr key={m.membership_id} className="hover:bg-[#f9fbfc] transition">
                  <td className="py-3.5 px-4">
                    <div className="font-semibold text-[#131416]">{m.name}</div>
                    <div className="text-[11px] text-[#5c6066]">{m.email}</div>
                  </td>

                  <td className="py-3.5 px-4">
                    <select
                      value={m.role}
                      onChange={(e) => handleRoleChange(m.membership_id, e.target.value)}
                      disabled={m.role === 'owner'}
                      className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-[#e8eaec] text-[#131416] focus:outline-none disabled:opacity-60"
                    >
                      <option value="owner">Owner</option>
                      <option value="admin">Admin</option>
                      <option value="editor">Editor</option>
                      <option value="analyst">Analyst</option>
                    </select>
                  </td>

                  <td className="py-3.5 px-4">
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#dcfae5] text-[#0f5132] border border-[#bbf7d0]">
                      {m.status}
                    </span>
                  </td>

                  <td className="py-3.5 px-4 text-right">
                    {m.role !== 'owner' && (
                      <button
                        onClick={() => handleRemove(m.membership_id)}
                        className="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                        title="Remove Member"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Invite Modal */}
      {showInviteModal && (
        <div className="fixed inset-0 bg-[#131416]/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 border border-[#e8eaec] space-y-4">
            <h3 className="text-sm font-bold text-[#131416]">Invite Team Member</h3>

            {errorMsg && (
              <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                {errorMsg}
              </div>
            )}

            <form onSubmit={handleInvite} className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Full Name</label>
                <input
                  type="text"
                  required
                  value={inviteName}
                  onChange={(e) => setInviteName(e.target.value)}
                  placeholder="Dr. Sarah Chen"
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Email Address</label>
                <input
                  type="email"
                  required
                  value={inviteEmail}
                  onChange={(e) => setInviteEmail(e.target.value)}
                  placeholder="sarah@apexhealth.org"
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Role Permissions</label>
                <select
                  value={inviteRole}
                  onChange={(e) => setInviteRole(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#1b6c80]"
                >
                  <option value="editor">Editor (Can edit funnels & view leads)</option>
                  <option value="admin">Admin (Can manage team & settings)</option>
                  <option value="analyst">Analyst (Read-only access to analytics)</option>
                </select>
              </div>

              <div className="pt-2 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowInviteModal(false)}
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
                  <span>{submitting ? 'Adding...' : 'Add Member'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import {
  UserPlus,
  Trash2,
  Loader2,
  Lock,
  AlertTriangle,
  Eye,
  EyeOff,
  Sparkles,
  Copy,
  Check,
  CheckCircle2,
  Mail
} from 'lucide-react';
import { ProFeatureTooltip } from '../../components/ProFeatureTooltip';

export const TeamPage: React.FC = () => {
  const { limits, isPlatformAdmin, refreshLimits } = useAuthStore();
  const [members, setMembers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [inviteEmail, setInviteEmail] = useState('');
  const [inviteName, setInviteName] = useState('');
  const [inviteRole, setInviteRole] = useState('editor');
  const [invitePassword, setInvitePassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [createdMemberData, setCreatedMemberData] = useState<any | null>(null);
  const [copied, setCopied] = useState(false);

  const generateRandomPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let pwd = 'Pass-';
    for (let i = 0; i < 6; i++) {
      pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    pwd += '@' + Math.floor(10 + Math.random() * 90);
    setInvitePassword(pwd);
  };

  const isAtSeatLimit = Boolean(
    !isPlatformAdmin &&
    limits &&
    limits.max_team_members > 0 &&
    (limits.current_team_members ?? members.length) >= limits.max_team_members
  );

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
      const res = await api.post('/api/team', {
        email: inviteEmail,
        name: inviteName,
        role: inviteRole,
        password: invitePassword,
      });
      setShowInviteModal(false);
      setCreatedMemberData(res);
      setInviteEmail('');
      setInviteName('');
      setInvitePassword('');
      await refreshLimits();
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
      await refreshLimits();
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

        <ProFeatureTooltip
          isLocked={isAtSeatLimit}
          featureName="Team Member Seats"
          description={`You have reached your workspace seat limit of ${limits?.max_team_members || 3} members. Upgrade to Pro to add up to 10 or unlimited team members.`}
          position="bottom"
        >
          <button
            onClick={() => {
              if (isAtSeatLimit) {
                return;
              }
              setShowInviteModal(true);
            }}
            className={`inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition self-start sm:self-auto shadow-xs cursor-pointer ${
              isAtSeatLimit
                ? 'bg-slate-200 text-slate-500 hover:bg-slate-300'
                : 'bg-[#1d5fd6] text-white hover:bg-[#1853bf]'
            }`}
          >
            <UserPlus className="w-4 h-4" />
            <span>Invite Member</span>
            {isAtSeatLimit && (
              <span className="px-1.5 py-0.2 rounded text-[9px] bg-amber-100 text-amber-800 border border-amber-300 font-bold font-mono">
                PRO
              </span>
            )}
          </button>
        </ProFeatureTooltip>
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
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#004cfd]"
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
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#004cfd]"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#131416] mb-1">Role Permissions</label>
                <select
                  value={inviteRole}
                  onChange={(e) => setInviteRole(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-[#e8eaec] text-xs text-[#131416] bg-white focus:outline-none focus:border-[#004cfd]"
                >
                  <option value="editor">Editor (Can edit funnels & view leads)</option>
                  <option value="admin">Admin (Can manage team & settings)</option>
                  <option value="analyst">Analyst (Read-only access to analytics)</option>
                </select>
              </div>

              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="block text-xs font-semibold text-[#131416]">
                    Temporary Password
                  </label>
                  <button
                    type="button"
                    onClick={generateRandomPassword}
                    className="text-[11px] font-semibold text-[#1d5fd6] hover:underline inline-flex items-center gap-1 cursor-pointer"
                    title="Generate a random secure password"
                  >
                    <Sparkles className="w-3 h-3 text-[#1d5fd6]" />
                    <span>Generate Secure</span>
                  </button>
                </div>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    value={invitePassword}
                    onChange={(e) => setInvitePassword(e.target.value)}
                    placeholder="Enter or generate password (min 8 chars)"
                    className="w-full px-3 py-2 pr-10 rounded-xl border border-[#e8eaec] text-xs font-mono text-[#131416] focus:outline-none focus:border-[#004cfd]"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                    title={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                  </button>
                </div>
                <p className="text-[11px] text-[#5d5f60] mt-1 leading-normal">
                  If left blank, a secure password will be generated automatically. If the user already has an account, their existing password will remain unchanged.
                </p>
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
                  className="px-4 py-1.5 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] shadow-xs flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                >
                  {submitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                  <span>{submitting ? 'Adding...' : 'Add Member'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Credential Success Dialog */}
      {createdMemberData && (
        <div className="fixed inset-0 bg-[#131416]/50 z-50 flex items-center justify-center p-4 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 border border-[#e8eaec] space-y-4 shadow-xl">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                <CheckCircle2 className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-sm font-bold text-[#131416]">
                  {createdMemberData.is_new_user ? 'Team Member Invited!' : 'Team Access Granted!'}
                </h3>
                <p className="text-xs text-[#5d5f60]">
                  {createdMemberData.is_new_user
                    ? 'A new account has been created and credentials generated.'
                    : 'Existing user was added to this workspace successfully.'}
                </p>
              </div>
            </div>

            {createdMemberData.is_new_user ? (
              <div className="space-y-3">
                <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs font-mono">
                  <div className="flex justify-between items-center text-slate-600">
                    <span>Portal URL:</span>
                    <span className="font-semibold text-slate-900 select-all">{createdMemberData.login_url}</span>
                  </div>
                  <div className="flex justify-between items-center text-slate-600">
                    <span>Login Email:</span>
                    <span className="font-semibold text-slate-900 select-all">{createdMemberData.email}</span>
                  </div>
                  <div className="flex justify-between items-center text-slate-600">
                    <span>Temporary Password:</span>
                    <span className="font-bold text-[#1d5fd6] bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded select-all">
                      {createdMemberData.password}
                    </span>
                  </div>
                  <div className="flex justify-between items-center text-slate-600">
                    <span>Assigned Role:</span>
                    <span className="capitalize font-semibold text-slate-900">{createdMemberData.role}</span>
                  </div>
                </div>

                <div className="flex items-start gap-2 p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                  <Mail className="w-4 h-4 shrink-0 text-emerald-600 mt-0.5" />
                  <span>An invitation email with these direct login credentials has also been sent to <strong>{createdMemberData.email}</strong>.</span>
                </div>

                <button
                  type="button"
                  onClick={() => {
                    const text = `FilterFunnel Workspace Access:\nWorkspace: ${createdMemberData.workspace_name}\nRole: ${createdMemberData.role}\nLogin URL: ${createdMemberData.login_url}\nEmail: ${createdMemberData.email}\nTemporary Password: ${createdMemberData.password}`;
                    navigator.clipboard.writeText(text);
                    setCopied(true);
                    setTimeout(() => setCopied(false), 2500);
                  }}
                  className="w-full py-2 px-3 rounded-xl border border-slate-300 hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  {copied ? (
                    <>
                      <Check className="w-4 h-4 text-emerald-600" />
                      <span className="text-emerald-700">Credentials Copied!</span>
                    </>
                  ) : (
                    <>
                      <Copy className="w-4 h-4 text-slate-500" />
                      <span>Copy Login Credentials</span>
                    </>
                  )}
                </button>
              </div>
            ) : (
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 space-y-1">
                <p className="font-medium">
                  <strong>{createdMemberData.email}</strong> already has a registered FilterFunnel account.
                </p>
                <p className="text-slate-500 text-[11px]">
                  They can log in with their existing credentials and switch to <strong>{createdMemberData.workspace_name}</strong> from the top workspace menu.
                </p>
              </div>
            )}

            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={() => setCreatedMemberData(null)}
                className="px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs cursor-pointer"
              >
                Done
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

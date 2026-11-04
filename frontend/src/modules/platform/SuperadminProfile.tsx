import React, { useEffect, useState } from 'react';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { ShieldCheck, User, KeyRound, CheckCircle, AlertTriangle, Save, Lock } from 'lucide-react';

export const SuperadminProfile: React.FC = () => {
  const { user, checkAuth } = useAuthStore();
  const [loading, setLoading] = useState(true);
  const [profileMsg, setProfileMsg] = useState('');
  const [profileErr, setProfileErr] = useState('');
  const [passwordMsg, setPasswordMsg] = useState('');
  const [passwordErr, setPasswordErr] = useState('');

  // Profile Form
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);

  // Password Form
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      const res = await api.get('/api/platform/profile');
      if (res.user) {
        setName(res.user.name || '');
        setEmail(res.user.email || '');
      }
    } catch (err: any) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setSavingProfile(true);
    setProfileMsg('');
    setProfileErr('');
    try {
      const res = await api.post('/api/platform/profile', { name, email });
      setProfileMsg('Super admin profile details successfully updated.');
      await checkAuth();
    } catch (err: any) {
      setProfileErr(err.message || 'Failed to update profile');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleUpdatePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setSavingPassword(true);
    setPasswordMsg('');
    setPasswordErr('');

    if (newPassword.length < 8) {
      setPasswordErr('New password must be at least 8 characters long.');
      setSavingPassword(false);
      return;
    }

    if (newPassword !== confirmPassword) {
      setPasswordErr('New password and confirmation password do not match.');
      setSavingPassword(false);
      return;
    }

    try {
      await api.post('/api/platform/profile/password', {
        current_password: currentPassword,
        new_password: newPassword,
        confirm_password: confirmPassword,
      });
      setPasswordMsg('Admin password successfully changed.');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (err: any) {
      setPasswordErr(err.message || 'Failed to change password');
    } finally {
      setSavingPassword(false);
    }
  };

  if (loading) {
    return (
      <div className="space-y-6 max-w-3xl mx-auto">
        <div className="h-10 w-64 rounded-xl skeleton-shimmer" />
        <div className="h-64 rounded-2xl skeleton-shimmer" />
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-3xl mx-auto">
      {/* Header */}
      <div>
        <div className="flex items-center gap-2">
          <span className="p-1.5 rounded-lg bg-[#dcf1ff] text-[#1d5fd6]">
            <ShieldCheck className="w-5 h-5" />
          </span>
          <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Super Admin Profile & Security</h1>
        </div>
        <p className="text-xs text-[#5d5f60] mt-1">
          Manage master administrative credentials, email notifications, and console security credentials.
        </p>
      </div>

      {/* Card 1: Admin Profile Details */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-4 border-b border-[#edf0f3]">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] font-bold text-sm flex items-center justify-center border border-[#b9e6fe]">
              {name.charAt(0) || 'S'}
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm font-bold text-[#131416]">{name || 'Super Admin'}</h3>
                <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-[#dcf1ff] text-[#1d5fd6] border border-[#b9e6fe]">
                  Master Admin
                </span>
              </div>
              <p className="text-[11px] text-[#5d5f60]">{email}</p>
            </div>
          </div>
        </div>

        {profileMsg && (
          <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
            <CheckCircle className="w-4 h-4 shrink-0" />
            <span>{profileMsg}</span>
          </div>
        )}

        {profileErr && (
          <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{profileErr}</span>
          </div>
        )}

        <form onSubmit={handleUpdateProfile} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Full Name *</label>
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Email Address *</label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
              <span className="text-[10px] text-[#5d5f60] mt-1 block">Used to sign in to the platform management console.</span>
            </div>
          </div>

          <div className="flex justify-end pt-2">
            <button
              type="submit"
              disabled={savingProfile}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#154bb3] transition disabled:opacity-60"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{savingProfile ? 'Updating...' : 'Update Profile Details'}</span>
            </button>
          </div>
        </form>
      </div>

      {/* Card 2: Change Password */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="pb-3 border-b border-[#edf0f3]">
          <h3 className="text-sm font-bold text-[#131416] flex items-center gap-2">
            <Lock className="w-4 h-4 text-[#1d5fd6]" />
            <span>Change Admin Password</span>
          </h3>
          <p className="text-xs text-[#5d5f60]">Ensure your administrative password is strong and unique.</p>
        </div>

        {passwordMsg && (
          <div className="p-3.5 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
            <CheckCircle className="w-4 h-4 shrink-0" />
            <span>{passwordMsg}</span>
          </div>
        )}

        {passwordErr && (
          <div className="p-3.5 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{passwordErr}</span>
          </div>
        )}

        <form onSubmit={handleUpdatePassword} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-[#131416] mb-1">Current Password *</label>
            <input
              type="password"
              required
              placeholder="Enter your current password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">New Password (Min 8 chars) *</label>
              <input
                type="password"
                required
                placeholder="New strong password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">Confirm New Password *</label>
              <input
                type="password"
                required
                placeholder="Repeat new password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition"
              />
            </div>
          </div>

          <div className="flex justify-end pt-2">
            <button
              type="submit"
              disabled={savingPassword}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#131416] text-white text-xs font-semibold hover:bg-black transition disabled:opacity-60"
            >
              <KeyRound className="w-3.5 h-3.5" />
              <span>{savingPassword ? 'Changing...' : 'Update Password'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

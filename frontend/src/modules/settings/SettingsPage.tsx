import React, { useState, useRef } from 'react';
import { api, resolveMediaUrl } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { showConfirm, showAlert } from '../../utils/swal';
import {
  Building2,
  User,
  Upload,
  Trash2,
  Save,
  CheckCircle2,
  AlertTriangle,
  Loader2,
  Image as ImageIcon
} from 'lucide-react';

export const SettingsPage: React.FC = () => {
  const { user, activeTenant, checkAuth } = useAuthStore();

  // Workspace state
  const [workspaceName, setWorkspaceName] = useState(activeTenant?.name || '');
  const [wsSaving, setWsSaving] = useState(false);
  const [wsUploading, setWsUploading] = useState(false);
  const [wsMsg, setWsMsg] = useState('');
  const [wsErr, setWsErr] = useState('');
  const wsFileInputRef = useRef<HTMLInputElement>(null);

  // Profile state
  const [userName, setUserName] = useState(user?.name || '');
  const [userEmail, setUserEmail] = useState(user?.email || '');
  const [password, setPassword] = useState('');
  const [profileSaving, setProfileSaving] = useState(false);
  const [avatarUploading, setAvatarUploading] = useState(false);
  const [userMsg, setUserMsg] = useState('');
  const [userErr, setUserErr] = useState('');
  const avatarFileInputRef = useRef<HTMLInputElement>(null);

  // ===============================
  // WORKSPACE HANDLERS
  // ===============================
  const handleUpdateWorkspace = async (e: React.FormEvent) => {
    e.preventDefault();
    setWsSaving(true);
    setWsMsg('');
    setWsErr('');
    try {
      await api.post('/api/settings/workspace', { name: workspaceName.trim() });
      setWsMsg('Workspace details successfully saved.');
      await checkAuth();
    } catch (err: any) {
      setWsErr(err.message || 'Failed to update workspace name.');
    } finally {
      setWsSaving(false);
    }
  };

  const handleLogoUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setWsErr('Logo file exceeds 5MB limit. Please choose a smaller image.');
      if (wsFileInputRef.current) wsFileInputRef.current.value = '';
      return;
    }

    setWsUploading(true);
    setWsMsg('');
    setWsErr('');

    try {
      const formData = new FormData();
      formData.append('logo', file);
      await api.post('/api/settings/workspace/logo', formData);
      setWsMsg('Workspace logo updated successfully.');
      await checkAuth();
    } catch (err: any) {
      setWsErr(err.message || 'Failed to upload workspace logo.');
    } finally {
      setWsUploading(false);
      if (wsFileInputRef.current) wsFileInputRef.current.value = '';
    }
  };

  const handleRemoveLogo = async () => {
    const confirmed = await showConfirm(
      'Remove Logo',
      'Are you sure you want to remove the workspace logo?',
      'Yes, Remove',
      true
    );
    if (!confirmed) return;
    setWsUploading(true);
    setWsMsg('');
    setWsErr('');
    try {
      await api.post('/api/settings/workspace', {
        name: workspaceName.trim() || activeTenant?.name,
        remove_logo: 1,
      });
      setWsMsg('Workspace logo removed. Reverted to default initial.');
      await checkAuth();
    } catch (err: any) {
      setWsErr(err.message || 'Failed to remove workspace logo.');
    } finally {
      setWsUploading(false);
    }
  };

  // ===============================
  // USER PROFILE HANDLERS
  // ===============================
  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setProfileSaving(true);
    setUserMsg('');
    setUserErr('');
    try {
      await api.post('/api/settings/profile', {
        name: userName.trim(),
        email: userEmail.trim(),
        password: password || undefined,
      });
      setUserMsg('Personal profile details successfully updated.');
      setPassword('');
      await checkAuth();
    } catch (err: any) {
      setUserErr(err.message || 'Failed to update profile details.');
    } finally {
      setProfileSaving(false);
    }
  };

  const handleAvatarUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setUserErr('Profile picture exceeds 5MB limit. Please choose a smaller image.');
      if (avatarFileInputRef.current) avatarFileInputRef.current.value = '';
      return;
    }

    setAvatarUploading(true);
    setUserMsg('');
    setUserErr('');

    try {
      const formData = new FormData();
      formData.append('avatar', file);
      await api.post('/api/settings/profile/avatar', formData);
      setUserMsg('Profile picture updated successfully.');
      await checkAuth();
    } catch (err: any) {
      setUserErr(err.message || 'Failed to upload profile picture.');
    } finally {
      setAvatarUploading(false);
      if (avatarFileInputRef.current) avatarFileInputRef.current.value = '';
    }
  };

  const handleRemoveAvatar = async () => {
    const confirmed = await showConfirm(
      'Remove Profile Picture',
      'Are you sure you want to remove your profile picture?',
      'Yes, Remove',
      true
    );
    if (!confirmed) return;
    setAvatarUploading(true);
    setUserMsg('');
    setUserErr('');
    try {
      await api.post('/api/settings/profile', {
        name: userName.trim() || user?.name,
        email: userEmail.trim() || user?.email,
        remove_avatar: 1,
      });
      setUserMsg('Profile picture removed. Reverted to initial badge.');
      await checkAuth();
    } catch (err: any) {
      setUserErr(err.message || 'Failed to remove profile picture.');
    } finally {
      setAvatarUploading(false);
    }
  };

  return (
    <div className="max-w-3xl mx-auto space-y-8">
      {/* Page Header */}
      <div>
        <h1 className="text-xl sm:text-2xl font-bold text-[#131416]">Workspace & Profile Settings</h1>
        <p className="text-xs text-[#5d5f60] mt-1">
          Manage your workspace identity, app branding logos, and personal profile credentials.
        </p>
      </div>

      {/* SECTION 1: Workspace Branding & App Logo */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-6">
        <div className="flex items-center gap-2.5 pb-4 border-b border-[#e2e5e9]">
          <div className="w-8 h-8 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center shrink-0">
            <Building2 className="w-4 h-4 stroke-[2]" />
          </div>
          <div>
            <h2 className="text-sm font-bold text-[#131416]">Workspace App Logo & Branding</h2>
            <p className="text-[11px] text-[#5d5f60]">
              The profile picture and logo used across your workspace switcher, header bar, and team views.
            </p>
          </div>
        </div>

        {wsMsg && (
          <div className="p-3 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 shrink-0" />
            <span>{wsMsg}</span>
          </div>
        )}

        {wsErr && (
          <div className="p-3 rounded-xl bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{wsErr}</span>
          </div>
        )}

        {/* Logo Preview & Upload Control */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-xl bg-[#f8fafc] border border-[#e2e5e9]">
          <div className="relative group shrink-0">
            {activeTenant?.logo_url ? (
              <img
                src={resolveMediaUrl(activeTenant.logo_url)}
                alt={activeTenant.name || 'Workspace Logo'}
                className="w-16 h-16 rounded-xl object-cover border border-[#e2e5e9] bg-white shadow-xs"
              />
            ) : (
              <div className="w-16 h-16 rounded-xl bg-[#1d5fd6] text-white font-bold text-xl flex items-center justify-center shadow-xs">
                {activeTenant?.name?.charAt(0).toUpperCase() || 'W'}
              </div>
            )}
            {wsUploading && (
              <div className="absolute inset-0 bg-black/40 rounded-xl flex items-center justify-center text-white">
                <Loader2 className="w-5 h-5 animate-spin" />
              </div>
            )}
          </div>

          <div className="flex-1 space-y-1.5 min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <input
                ref={wsFileInputRef}
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                onChange={handleLogoUpload}
                className="hidden"
                id="ws-logo-file-input"
              />
              <button
                type="button"
                onClick={() => wsFileInputRef.current?.click()}
                disabled={wsUploading}
                className="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition-colors disabled:opacity-60 shadow-xs"
              >
                <Upload className="w-3.5 h-3.5" />
                <span>{wsUploading ? 'Uploading...' : activeTenant?.logo_url ? 'Change Logo' : 'Upload Workspace Logo'}</span>
              </button>

              {activeTenant?.logo_url && (
                <button
                  type="button"
                  onClick={handleRemoveLogo}
                  disabled={wsUploading}
                  className="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg bg-white border border-[#e2e5e9] text-[#991b1b] hover:bg-[#fee2e2]/40 text-xs font-semibold transition-colors disabled:opacity-60"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                  <span>Remove Logo</span>
                </button>
              )}
            </div>
            <p className="text-[11px] text-[#5d5f60]">
              PNG, JPG, WebP, or SVG up to 5MB. Recommended square dimension (512x512px).
            </p>
          </div>
        </div>

        {/* Workspace Name Form */}
        <form onSubmit={handleUpdateWorkspace} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-[#131416] mb-1">
              Workspace Organization Name *
            </label>
            <input
              type="text"
              required
              value={workspaceName}
              onChange={(e) => setWorkspaceName(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition-colors"
              placeholder="Enter workspace name"
            />
          </div>

          <div className="flex justify-end pt-1">
            <button
              type="submit"
              disabled={wsSaving || !workspaceName.trim()}
              className="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl bg-[#131416] text-white text-xs font-semibold hover:bg-black transition-colors disabled:opacity-50 shadow-xs"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{wsSaving ? 'Saving...' : 'Update Workspace Details'}</span>
            </button>
          </div>
        </form>
      </div>

      {/* SECTION 2: Personal Profile & Avatar */}
      <div className="bg-white border border-[#e2e5e9] rounded-2xl p-6 shadow-xs space-y-6">
        <div className="flex items-center gap-2.5 pb-4 border-b border-[#e2e5e9]">
          <div className="w-8 h-8 rounded-lg bg-[#dcf1ff] text-[#1d5fd6] flex items-center justify-center shrink-0">
            <User className="w-4 h-4 stroke-[2]" />
          </div>
          <div>
            <h2 className="text-sm font-bold text-[#131416]">Personal Profile & User Avatar</h2>
            <p className="text-[11px] text-[#5d5f60]">
              Your personal profile picture and login credentials for this account.
            </p>
          </div>
        </div>

        {userMsg && (
          <div className="p-3 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#2f7a45] text-xs flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 shrink-0" />
            <span>{userMsg}</span>
          </div>
        )}

        {userErr && (
          <div className="p-3 rounded-xl bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{userErr}</span>
          </div>
        )}

        {/* User Avatar Preview & Upload Control */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-xl bg-[#f8fafc] border border-[#e2e5e9]">
          <div className="relative group shrink-0">
            {user?.avatar_url ? (
              <img
                src={resolveMediaUrl(user.avatar_url)}
                alt={user.name || 'User Avatar'}
                className="w-16 h-16 rounded-xl object-cover border border-[#e2e5e9] bg-white shadow-xs"
              />
            ) : (
              <div className="w-16 h-16 rounded-xl bg-[#dcf1ff] text-[#1d5fd6] font-bold text-xl flex items-center justify-center border border-[#b9e6fe] shadow-xs">
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </div>
            )}
            {avatarUploading && (
              <div className="absolute inset-0 bg-black/40 rounded-xl flex items-center justify-center text-white">
                <Loader2 className="w-5 h-5 animate-spin" />
              </div>
            )}
          </div>

          <div className="flex-1 space-y-1.5 min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <input
                ref={avatarFileInputRef}
                type="file"
                accept="image/png,image/jpeg,image/webp"
                onChange={handleAvatarUpload}
                className="hidden"
                id="user-avatar-file-input"
              />
              <button
                type="button"
                onClick={() => avatarFileInputRef.current?.click()}
                disabled={avatarUploading}
                className="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition-colors disabled:opacity-60 shadow-xs"
              >
                <Upload className="w-3.5 h-3.5" />
                <span>{avatarUploading ? 'Uploading...' : user?.avatar_url ? 'Change Picture' : 'Upload Profile Picture'}</span>
              </button>

              {user?.avatar_url && (
                <button
                  type="button"
                  onClick={handleRemoveAvatar}
                  disabled={avatarUploading}
                  className="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg bg-white border border-[#e2e5e9] text-[#991b1b] hover:bg-[#fee2e2]/40 text-xs font-semibold transition-colors disabled:opacity-60"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                  <span>Remove Picture</span>
                </button>
              )}
            </div>
            <p className="text-[11px] text-[#5d5f60]">
              PNG, JPG, or WebP up to 5MB. Displayed in sidebar footers and activity logs.
            </p>
          </div>
        </div>

        {/* Profile Details Form */}
        <form onSubmit={handleUpdateProfile} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">
                Full Name *
              </label>
              <input
                type="text"
                required
                value={userName}
                onChange={(e) => setUserName(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition-colors"
                placeholder="Enter your full name"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-[#131416] mb-1">
                Email Address *
              </label>
              <input
                type="email"
                required
                value={userEmail}
                onChange={(e) => setUserEmail(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition-colors"
                placeholder="name@company.com"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#131416] mb-1">
              New Password (Optional)
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Leave blank to keep existing password"
              className="w-full px-3 py-2 rounded-xl border border-[#e2e5e9] text-xs text-[#131416] focus:outline-hidden focus:border-[#1d5fd6] transition-colors"
            />
            <span className="text-[10px] text-[#5d5f60] mt-1 block">
              Leave this field empty unless you wish to change your current account password.
            </span>
          </div>

          <div className="flex justify-end pt-1">
            <button
              type="submit"
              disabled={profileSaving || !userName.trim() || !userEmail.trim()}
              className="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition-colors disabled:opacity-50 shadow-xs"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{profileSaving ? 'Saving...' : 'Save Profile Changes'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

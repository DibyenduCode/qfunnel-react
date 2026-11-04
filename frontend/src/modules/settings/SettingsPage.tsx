import React, { useState } from 'react';
import { api } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { Settings, User, Building, Save } from 'lucide-react';

export const SettingsPage: React.FC = () => {
  const { user, activeTenant, checkAuth } = useAuthStore();

  const [workspaceName, setWorkspaceName] = useState(activeTenant?.name || '');
  const [userName, setUserName] = useState(user?.name || '');
  const [userEmail, setUserEmail] = useState(user?.email || '');
  const [password, setPassword] = useState('');
  const [wsSaving, setWsSaving] = useState(false);
  const [userSaving, setUserSaving] = useState(false);
  const [wsMsg, setWsMsg] = useState('');
  const [userMsg, setUserMsg] = useState('');

  const handleUpdateWorkspace = async (e: React.FormEvent) => {
    e.preventDefault();
    setWsSaving(true);
    setWsMsg('');
    try {
      await api.post('/api/settings/workspace', { name: workspaceName });
      setWsMsg('Workspace renamed successfully.');
      checkAuth();
    } catch (err: any) {
      alert(err.message || 'Failed to update workspace');
    } finally {
      setWsSaving(false);
    }
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setUserSaving(true);
    setUserMsg('');
    try {
      await api.post('/api/settings/profile', {
        name: userName,
        email: userEmail,
        password: password || undefined,
      });
      setUserMsg('Profile updated successfully.');
      setPassword('');
      checkAuth();
    } catch (err: any) {
      alert(err.message || 'Failed to update profile');
    } finally {
      setUserSaving(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-lg font-bold text-[#171A21]">Workspace & Profile Settings</h1>
        <p className="text-xs text-[#687080] mt-0.5">Manage your organization details and user credentials</p>
      </div>

      {/* Workspace Settings */}
      <div className="bg-white border border-[#E4E7EC] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="flex items-center gap-2">
          <Building className="w-4 h-4 text-[#5B5BD6]" />
          <h2 className="text-sm font-bold text-[#171A21]">Workspace Details</h2>
        </div>

        {wsMsg && <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs">{wsMsg}</div>}

        <form onSubmit={handleUpdateWorkspace} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[#171A21] mb-1">Workspace Name</label>
            <input
              type="text"
              required
              value={workspaceName}
              onChange={(e) => setWorkspaceName(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
            />
          </div>

          <button
            type="submit"
            disabled={wsSaving}
            className="px-4 py-2 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] transition shadow-xs disabled:opacity-50 flex items-center gap-2"
          >
            <Save className="w-3.5 h-3.5" />
            <span>{wsSaving ? 'Saving...' : 'Update Workspace'}</span>
          </button>
        </form>
      </div>

      {/* User Account Settings */}
      <div className="bg-white border border-[#E4E7EC] rounded-2xl p-6 shadow-xs space-y-4">
        <div className="flex items-center gap-2">
          <User className="w-4 h-4 text-[#5B5BD6]" />
          <h2 className="text-sm font-bold text-[#171A21]">Personal Profile</h2>
        </div>

        {userMsg && <div className="p-2.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs">{userMsg}</div>}

        <form onSubmit={handleUpdateProfile} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[#171A21] mb-1">Your Name</label>
            <input
              type="text"
              required
              value={userName}
              onChange={(e) => setUserName(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#171A21] mb-1">Email Address</label>
            <input
              type="email"
              required
              value={userEmail}
              onChange={(e) => setUserEmail(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#171A21] mb-1">
              New Password (Leave blank to keep unchanged)
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="w-full px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21]"
            />
          </div>

          <button
            type="submit"
            disabled={userSaving}
            className="px-4 py-2 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] transition shadow-xs disabled:opacity-50 flex items-center gap-2"
          >
            <Save className="w-3.5 h-3.5" />
            <span>{userSaving ? 'Saving...' : 'Save Profile'}</span>
          </button>
        </form>
      </div>
    </div>
  );
};


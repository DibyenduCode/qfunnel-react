import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { UserPlus, AlertCircle, Loader2 } from 'lucide-react';

export const RegisterPage: React.FC = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [workspace, setWorkspace] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const { register } = useAuthStore();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMsg('');

    try {
      await register(name, email, password, workspace);
      navigate('/verify-email');
    } catch (err: any) {
      setErrorMsg(err.message || 'Registration failed. Please check inputs.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <h2 className="text-base font-semibold text-[#131416]">Create your workspace</h2>
        <p className="text-xs text-[#5d5f60] mt-1">Start building branching logic assessments</p>
      </div>

      {errorMsg && (
        <div className="p-3 rounded-lg bg-[#ffedea] border border-[#ffcfc9] text-[#831c1e] text-xs flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <div>
        <label className="block text-xs font-medium text-[#131416] mb-1">Your full name</label>
        <input
          type="text"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="Dr. Sarah Chen"
          className="w-full px-3 py-2 rounded-lg border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80]"
        />
      </div>

      <div>
        <label className="block text-xs font-medium text-[#131416] mb-1">Workspace name</label>
        <input
          type="text"
          required
          value={workspace}
          onChange={(e) => setWorkspace(e.target.value)}
          placeholder="Apex Health Studio"
          className="w-full px-3 py-2 rounded-lg border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80]"
        />
      </div>

      <div>
        <label className="block text-xs font-medium text-[#131416] mb-1">Work email</label>
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="sarah@apexhealth.org"
          className="w-full px-3 py-2 rounded-lg border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80]"
        />
      </div>

      <div>
        <label className="block text-xs font-medium text-[#131416] mb-1">Password</label>
        <input
          type="password"
          required
          minLength={6}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Min 6 characters"
          className="w-full px-3 py-2 rounded-lg border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80]"
        />
      </div>

      <button
        type="submit"
        disabled={isSubmitting}
        className="w-full h-8 px-4 rounded-lg bg-[#1d1f20] hover:bg-[#2e3133] text-white text-xs font-medium transition flex items-center justify-center gap-2 disabled:opacity-50"
      >
        {isSubmitting ? (
          <Loader2 className="w-3.5 h-3.5 animate-spin" />
        ) : (
          <UserPlus className="w-3.5 h-3.5" />
        )}
        <span>{isSubmitting ? 'Creating workspace...' : 'Get started'}</span>
      </button>

      <div className="pt-2 text-center text-xs text-[#5d5f60]">
        Already registered?{' '}
        <Link to="/login" className="text-[#1b6c80] font-medium hover:underline">
          Sign in
        </Link>
      </div>
    </form>
  );
};


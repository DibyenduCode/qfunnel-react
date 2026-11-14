import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { LogIn, AlertCircle, Loader2 } from 'lucide-react';

export const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const { login } = useAuthStore();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMsg('');

    try {
      await login(email, password);
      const isSuper = useAuthStore.getState().isPlatformAdmin;
      if (isSuper) {
        navigate('/admin/platform');
      } else {
        navigate('/admin/dashboard');
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Login failed. Please verify credentials.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <h2 className="text-base font-semibold text-[#131416]">Sign in to your account</h2>
        <p className="text-xs text-[#5d5f60] mt-1">Access your workspaces and funnel analytics</p>
      </div>

      {errorMsg && (
        <div className="p-3 rounded-lg bg-[#ffedea] border border-[#ffcfc9] text-[#831c1e] text-xs flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <div>
        <label className="block text-xs font-medium text-[#131416] mb-1">Email address</label>
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="owner@leadcollector.in"
          className="w-full px-3 py-2 rounded-lg border border-[#e8eaec] text-xs text-[#131416] focus:outline-none focus:border-[#1b6c80] focus:ring-1 focus:ring-[#1b6c80]"
        />
      </div>

      <div>
        <div className="flex items-center justify-between mb-1">
          <label className="block text-xs font-medium text-[#131416]">Password</label>
        </div>
        <input
          type="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="••••••••"
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
          <LogIn className="w-3.5 h-3.5" />
        )}
        <span>{isSubmitting ? 'Signing in...' : 'Sign in'}</span>
      </button>

      <div className="pt-2 text-center text-xs text-[#5d5f60]">
        Don't have an account?{' '}
        <Link to="/register" className="text-[#1b6c80] font-medium hover:underline">
          Create workspace
        </Link>
      </div>
    </form>
  );
};


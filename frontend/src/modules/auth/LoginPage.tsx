import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { LogIn, AlertCircle, Loader2 } from 'lucide-react';
import { SeoHelmet } from '../../components/SeoHelmet';

export const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const { user, isPlatformAdmin, isLoading, checkAuth, login } = useAuthStore();
  const navigate = useNavigate();

  // If already authenticated, redirect straight to the appropriate dashboard
  useEffect(() => {
    if (user) {
      navigate(isPlatformAdmin ? '/admin/platform' : '/admin/dashboard', { replace: true });
    } else {
      checkAuth().then((isAuthed) => {
        if (isAuthed) {
          const isSuper = useAuthStore.getState().isPlatformAdmin;
          navigate(isSuper ? '/admin/platform' : '/admin/dashboard', { replace: true });
        }
      });
    }
  }, [user, isPlatformAdmin]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMsg('');

    try {
      await login(email, password, rememberMe);
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
      <SeoHelmet
        title="Sign In — LeadCollector.in"
        description="Sign in to your LeadCollector account to access workspaces and visual funnel analytics."
        noindex
      />
      <div>
        <h2 className="text-base font-bold text-[#131416]">Sign in to your account</h2>
        <p className="text-xs text-[#5d5f60] mt-1">Access your workspaces and funnel analytics</p>
      </div>

      {errorMsg && (
        <div className="p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Email address</label>
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="owner@leadcollector.in"
          className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6] outline-hidden transition"
        />
      </div>

      <div>
        <div className="flex items-center justify-between mb-1.5">
          <label className="block text-xs font-semibold text-[#131416]">Password</label>
        </div>
        <input
          type="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="••••••••"
          className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6] outline-hidden transition"
        />
      </div>

      <div className="flex items-center justify-between text-xs pt-1">
        <label className="flex items-center gap-2 cursor-pointer select-none text-[#131416] font-medium">
          <input
            type="checkbox"
            checked={rememberMe}
            onChange={(e) => setRememberMe(e.target.checked)}
            className="rounded border-[#e2e5e9] text-[#1d5fd6] focus:ring-0"
          />
          <span>Remember me</span>
        </label>
        <Link to="/support" className="text-[#1d5fd6] hover:underline font-medium">
          Need help?
        </Link>
      </div>

      <button
        type="submit"
        disabled={isSubmitting}
        className="w-full h-10 px-4 rounded-xl bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-semibold transition flex items-center justify-center gap-2 disabled:opacity-50 shadow-xs cursor-pointer"
      >
        {isSubmitting ? (
          <>
            <Loader2 className="w-4 h-4 animate-spin" />
            <span>Signing in...</span>
          </>
        ) : (
          <>
            <LogIn className="w-4 h-4" />
            <span>Sign in to Dashboard</span>
          </>
        )}
      </button>

      <div className="text-center pt-2">
        <p className="text-xs text-[#5d5f60]">
          Don't have an account?{' '}
          <Link to="/register" className="text-[#1d5fd6] font-semibold hover:underline">
            Start free trial
          </Link>
        </p>
      </div>
    </form>
  );
};

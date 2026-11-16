import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { UserPlus, AlertCircle, Loader2, Eye, EyeOff } from 'lucide-react';
import { SeoHelmet } from '../../components/SeoHelmet';

export const RegisterPage: React.FC = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [workspace, setWorkspace] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const { user, isPlatformAdmin, checkAuth, register } = useAuthStore();
  const navigate = useNavigate();

  React.useEffect(() => {
    if (user) {
      if (!user.email_verified_at) {
        navigate('/verify-email', { replace: true });
      } else {
        navigate(isPlatformAdmin ? '/admin/platform' : '/admin/dashboard', { replace: true });
      }
    } else {
      checkAuth().then((isAuthed) => {
        if (isAuthed) {
          const u = useAuthStore.getState().user;
          const isSuper = useAuthStore.getState().isPlatformAdmin;
          if (u && !u.email_verified_at) {
            navigate('/verify-email', { replace: true });
          } else {
            navigate(isSuper ? '/admin/platform' : '/admin/dashboard', { replace: true });
          }
        }
      });
    }
  }, [user, isPlatformAdmin]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMsg('');

    if (password.length < 6) {
      setErrorMsg('Password must be at least 6 characters long.');
      setIsSubmitting(false);
      return;
    }

    if (password !== confirmPassword) {
      setErrorMsg('Passwords do not match. Please verify your password confirmation.');
      setIsSubmitting(false);
      return;
    }

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
      <SeoHelmet
        title="Create Account — FilterFunnel.in"
        description="Sign up for FilterFunnel and start creating high-converting interactive visual quiz funnels and quote calculators."
        noindex
      />
      <div>
        <h2 className="text-base font-bold text-[#131416]">Create your workspace</h2>
        <p className="text-xs text-[#5d5f60] mt-1">Start building branching logic assessments</p>
      </div>

      {errorMsg && (
        <div className="p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Your full name</label>
        <input
          type="text"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="Dr. Sarah Chen"
          className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6] outline-hidden transition"
        />
      </div>

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Workspace name</label>
        <input
          type="text"
          required
          value={workspace}
          onChange={(e) => setWorkspace(e.target.value)}
          placeholder="Apex Growth Studio"
          className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6] outline-hidden transition"
        />
      </div>

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Work email</label>
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="sarah@apexstudio.io"
          className="w-full px-3.5 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#1d5fd6] focus:ring-1 focus:ring-[#1d5fd6] outline-hidden transition"
        />
      </div>

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Password</label>
        <div className="relative">
          <input
            type={showPassword ? 'text' : 'password'}
            required
            minLength={6}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Min 6 characters"
            className="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-[#e2e5e9] bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:border-[#004cfd] focus:ring-2 focus:ring-[#004cfd]/15 outline-hidden transition"
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            tabIndex={-1}
            aria-label={showPassword ? 'Hide password' : 'Show password'}
            className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-[#8b919a] hover:text-[#131416] transition-colors rounded-md"
          >
            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
          </button>
        </div>
      </div>

      <div>
        <label className="block text-xs font-semibold text-[#131416] mb-1.5">Confirm Password</label>
        <div className="relative">
          <input
            type={showConfirmPassword ? 'text' : 'password'}
            required
            minLength={6}
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            placeholder="Repeat password"
            className={`w-full pl-3.5 pr-10 py-2.5 rounded-xl border bg-[#f8fafc] text-xs text-[#131416] focus:bg-white focus:ring-2 outline-hidden transition ${
              confirmPassword && confirmPassword !== password
                ? 'border-[#fecaca] focus:border-[#c4242a] focus:ring-[#c4242a]/15'
                : 'border-[#e2e5e9] focus:border-[#004cfd] focus:ring-[#004cfd]/15'
            }`}
          />
          <button
            type="button"
            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
            tabIndex={-1}
            aria-label={showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'}
            className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-[#8b919a] hover:text-[#131416] transition-colors rounded-md"
          >
            {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
          </button>
        </div>
        {confirmPassword && confirmPassword !== password && (
          <p className="text-[11px] text-[#c4242a] mt-1">Passwords do not match</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isSubmitting}
        className="w-full h-10 px-4 rounded-xl bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-semibold transition flex items-center justify-center gap-2 disabled:opacity-50 shadow-xs cursor-pointer"
      >
        {isSubmitting ? (
          <Loader2 className="w-4 h-4 animate-spin" />
        ) : (
          <UserPlus className="w-4 h-4" />
        )}
        <span>{isSubmitting ? 'Creating workspace...' : 'Get started'}</span>
      </button>

      <div className="pt-2 text-center text-xs text-[#5d5f60]">
        Already registered?{' '}
        <Link to="/login" className="text-[#1d5fd6] font-semibold hover:underline">
          Sign in
        </Link>
      </div>
    </form>
  );
};

import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, Navigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { Mail, CheckCircle2, AlertCircle, RefreshCw, LogOut, ShieldCheck, ArrowRight, Loader2 } from 'lucide-react';

export const EmailVerificationPage: React.FC = () => {
  const { user, verifyOtp, resendOtp, logout, lastDevOtp, error, clearError, isPlatformAdmin } = useAuthStore();
  const navigate = useNavigate();

  const [otpDigits, setOtpDigits] = useState<string[]>(['', '', '', '', '', '']);
  const [submitting, setSubmitting] = useState(false);
  const [resending, setResending] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');
  const [cooldown, setCooldown] = useState(60);
  const [devOtp, setDevOtp] = useState<string | null>(lastDevOtp || null);

  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  // If already verified or platform admin, go straight to dashboard
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  if (isPlatformAdmin) {
    return <Navigate to="/admin/platform" replace />;
  }
  if (user.email_verified_at) {
    return <Navigate to="/dashboard" replace />;
  }

  // 60-second cooldown timer
  useEffect(() => {
    if (cooldown <= 0) return;
    const timer = setInterval(() => {
      setCooldown((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [cooldown]);

  // Focus first input on mount
  useEffect(() => {
    inputRefs.current[0]?.focus();
  }, []);

  const handleDigitChange = (index: number, value: string) => {
    // Only accept numeric digit
    const cleaned = value.replace(/\D/g, '');
    if (!cleaned) {
      const nextDigits = [...otpDigits];
      nextDigits[index] = '';
      setOtpDigits(nextDigits);
      return;
    }

    // Single digit input
    const char = cleaned.slice(-1);
    const nextDigits = [...otpDigits];
    nextDigits[index] = char;
    setOtpDigits(nextDigits);

    // Auto advance focus
    if (index < 5) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !otpDigits[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    if (!pasted) return;

    const nextDigits = [...otpDigits];
    for (let i = 0; i < 6; i++) {
      nextDigits[i] = pasted[i] || '';
    }
    setOtpDigits(nextDigits);

    const focusIndex = Math.min(pasted.length, 5);
    inputRefs.current[focusIndex]?.focus();
  };

  const handleSubmit = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    clearError();
    setSuccessMsg('');

    const fullCode = otpDigits.join('');
    if (fullCode.length !== 6) {
      return;
    }

    setSubmitting(true);
    try {
      await verifyOtp(fullCode);
      setSuccessMsg('Email verified successfully! Redirecting...');
      setTimeout(() => {
        navigate('/dashboard');
      }, 700);
    } catch {
      // Error is set in store
      inputRefs.current[0]?.focus();
    } finally {
      setSubmitting(false);
    }
  };

  const handleResend = async () => {
    if (cooldown > 0 || resending) return;
    clearError();
    setSuccessMsg('');
    setResending(true);
    try {
      const res = await resendOtp();
      setCooldown(60);
      setSuccessMsg('A fresh 6-digit code has been dispatched to your email.');
      if (res.dev_otp) {
        setDevOtp(res.dev_otp);
      }
    } catch {
      // Error handled in store
    } finally {
      setResending(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const isComplete = otpDigits.join('').length === 6;

  return (
    <div className="min-h-screen bg-[#f8fafc] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md px-4">
        {/* Brand Icon */}
        <div className="flex justify-center mb-4">
          <div className="w-12 h-12 rounded-2xl bg-[#1d5fd6] text-white flex items-center justify-center font-bold shadow-xs">
            <Mail className="w-6 h-6" />
          </div>
        </div>

        <h1 className="text-xl sm:text-2xl font-bold text-center text-[#131416] tracking-tight">
          Verify your email address
        </h1>
        <p className="mt-2 text-center text-xs text-[#5d5f60] max-w-sm mx-auto leading-relaxed">
          We sent a 6-digit verification code to{' '}
          <span className="font-semibold text-[#131416]">{user.email}</span>. Enter the code below to access your workspace.
        </p>

        {/* Development Preview Banner */}
        {devOtp && (
          <div className="mt-4 p-3 rounded-xl bg-[#eff6ff] border border-[#bfdbfe] text-xs text-[#1d5fd6] flex items-center justify-between gap-2">
            <div className="flex items-center gap-2 min-w-0">
              <ShieldCheck className="w-4 h-4 shrink-0 text-[#1d5fd6]" />
              <span className="truncate">
                Local Dev Code: <strong className="font-mono text-sm tracking-wider">{devOtp}</strong>
              </span>
            </div>
            <button
              type="button"
              onClick={() => {
                const chars = devOtp.split('').slice(0, 6);
                setOtpDigits(chars);
                inputRefs.current[5]?.focus();
              }}
              className="text-[11px] font-semibold text-[#1d5fd6] hover:underline shrink-0"
            >
              Auto Fill
            </button>
          </div>
        )}

        {/* Card */}
        <div className="mt-5 bg-white py-8 px-6 sm:px-8 border border-[#e2e5e9] rounded-2xl shadow-xs">
          {error && (
            <div className="mb-5 p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          {successMsg && (
            <div className="mb-5 p-3 rounded-xl bg-[#dcfae5] border border-[#bbf7d0] text-[#166534] text-xs flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 shrink-0" />
              <span>{successMsg}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-xs font-semibold text-[#131416] text-center mb-3">
                Enter 6-Digit One-Time Password
              </label>

              {/* 6 Digit Inputs */}
              <div className="flex items-center justify-center gap-2 sm:gap-2.5">
                {otpDigits.map((digit, idx) => (
                  <input
                    key={idx}
                    ref={(el) => (inputRefs.current[idx] = el)}
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    maxLength={1}
                    value={digit}
                    onChange={(e) => handleDigitChange(idx, e.target.value)}
                    onKeyDown={(e) => handleKeyDown(idx, e)}
                    onPaste={handlePaste}
                    className="w-10 h-12 sm:w-11 sm:h-13 text-center text-lg sm:text-xl font-bold font-mono text-[#131416] bg-[#f8fafc] border border-[#cbd5e1] rounded-xl focus:bg-white focus:border-[#1d5fd6] focus:ring-2 focus:ring-[#1d5fd6]/15 outline-hidden transition"
                  />
                ))}
              </div>
              <p className="mt-2 text-center text-[11px] text-[#5d5f60]">
                Code expires in 15 minutes.
              </p>
            </div>

            <button
              type="submit"
              disabled={!isComplete || submitting}
              className="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-[#1d5fd6] hover:bg-[#154cb3] transition shadow-xs disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  <span>Verifying Code...</span>
                </>
              ) : (
                <>
                  <span>Verify and Open Dashboard</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </>
              )}
            </button>
          </form>

          {/* Resend Action */}
          <div className="mt-6 pt-5 border-t border-[#edf0f3] flex flex-col items-center gap-2">
            <p className="text-xs text-[#5d5f60]">Didn't receive the verification email?</p>
            {cooldown > 0 ? (
              <span className="text-xs font-semibold text-[#5d5f60]">
                Resend code in {cooldown}s
              </span>
            ) : (
              <button
                type="button"
                onClick={handleResend}
                disabled={resending}
                className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1d5fd6] hover:underline disabled:opacity-50"
              >
                <RefreshCw className={`w-3.5 h-3.5 ${resending ? 'animate-spin' : ''}`} />
                <span>Resend 6-Digit Code</span>
              </button>
            )}
          </div>
        </div>

        {/* Footer Navigation */}
        <div className="mt-6 text-center">
          <button
            type="button"
            onClick={handleLogout}
            className="inline-flex items-center gap-1.5 text-xs text-[#5d5f60] hover:text-[#131416] transition"
          >
            <LogOut className="w-3.5 h-3.5" />
            <span>Use a different account or sign out</span>
          </button>
        </div>
      </div>
    </div>
  );
};

import React, { useState, useEffect } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import {
  GitFork,
  ArrowRight,
  ShieldCheck,
  CheckCircle,
  ExternalLink,
  MessageCircle,
  PhoneCall,
  Mail,
  Heart,
  Menu,
  X
} from 'lucide-react';

export const PublicLayout: React.FC = () => {
  const { user, isPlatformAdmin } = useAuthStore();
  const location = useLocation();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [activeSection, setActiveSection] = useState<string>('');

  // Scroll to section helper with retry logic for cross-page navigation
  const scrollToSection = (sectionId: string) => {
    let attempts = 0;
    const interval = setInterval(() => {
      attempts++;
      const el = document.getElementById(sectionId);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
        clearInterval(interval);
      } else if (attempts > 12) {
        clearInterval(interval);
      }
    }, 40);
  };

  // Automatically scroll to anchor when hash changes or on initial cross-page navigation
  useEffect(() => {
    if (location.hash) {
      const id = location.hash.replace('#', '');
      scrollToSection(id);
      setActiveSection(id);
    } else if (location.pathname !== '/') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      setActiveSection('');
    }
  }, [location.pathname, location.hash]);

  // Scroll spy to highlight active nav link on home page
  useEffect(() => {
    if (location.pathname !== '/') {
      setActiveSection('');
      return;
    }

    const sectionIds = ['features', 'calculator', 'how-it-works', 'use-cases', 'pricing', 'faq'];
    const handleScroll = () => {
      const scrollPos = window.scrollY + 120;
      for (const id of sectionIds) {
        const el = document.getElementById(id);
        if (el) {
          const top = el.offsetTop;
          const height = el.offsetHeight;
          if (scrollPos >= top && scrollPos < top + height) {
            setActiveSection(id);
            return;
          }
        }
      }
      if (window.scrollY < 300) {
        setActiveSection('');
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
    return () => window.removeEventListener('scroll', handleScroll);
  }, [location.pathname]);

  // Close mobile menu on page change
  useEffect(() => {
    setMobileMenuOpen(false);
  }, [location.pathname]);

  // Smooth scroll handler for both same-page and cross-page anchor navigation
  const handleNavClick = (e: React.MouseEvent, sectionId: string) => {
    e.preventDefault();
    setMobileMenuOpen(false);
    setActiveSection(sectionId);

    if (location.pathname === '/') {
      scrollToSection(sectionId);
      window.history.pushState(null, '', `/#${sectionId}`);
    } else {
      // If on another route (/about, /support, /privacy, etc.), navigate to home with hash
      navigate(`/#${sectionId}`);
    }
  };

  const navLinks = [
    { name: 'Features', hash: 'features' },
    { name: 'Quote Calculator', hash: 'calculator' },
    { name: 'How It Works', hash: 'how-it-works' },
    { name: 'Solutions', hash: 'use-cases' },
    { name: 'Pricing', hash: 'pricing' },
    { name: 'FAQ', hash: 'faq' },
  ];

  return (
    <div className="min-h-screen bg-[#f8fafc] text-[#131416] flex flex-col font-sans">
      {/* 1. Global Navigation Bar */}
      <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-xs border-b border-[#e2e5e9] px-4 sm:px-8">
        <div className="max-w-7xl mx-auto flex items-center justify-between h-16">
          <div className="flex items-center gap-8">
            {/* Logo */}
            <Link to="/" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })} className="flex items-center gap-2.5 group">
              <div className="w-8 h-8 rounded-lg bg-[#1d5fd6] text-white flex items-center justify-center font-bold shadow-xs group-hover:bg-[#1853bf] transition">
                <GitFork className="w-4 h-4 stroke-[2.2]" />
              </div>
              <span className="font-bold text-[17px] tracking-tight text-[#0f172a]">
                QFunnel
              </span>
            </Link>

            {/* Desktop Navigation Links */}
            <nav className="hidden lg:flex items-center gap-1 text-[13px] font-medium text-[#475467]">
              {navLinks.map((item) => {
                const isActive = location.pathname === '/' && (activeSection === item.hash || (location.hash === `#${item.hash}` && !activeSection));
                return (
                  <a
                    key={item.hash}
                    href={`/#${item.hash}`}
                    onClick={(e) => handleNavClick(e, item.hash)}
                    className={`px-3 py-1.5 rounded-lg transition-colors cursor-pointer ${
                      isActive
                        ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]'
                        : 'hover:text-[#0f172a] hover:bg-slate-50'
                    }`}
                  >
                    {item.name}
                  </a>
                );
              })}
              <Link
                to="/about"
                className={`px-3 py-1.5 rounded-lg transition-colors ${
                  location.pathname === '/about'
                    ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]'
                    : 'hover:text-[#0f172a] hover:bg-slate-50'
                }`}
              >
                About
              </Link>
              <Link
                to="/support"
                className={`px-3 py-1.5 rounded-lg transition-colors ${
                  location.pathname === '/support'
                    ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]'
                    : 'hover:text-[#0f172a] hover:bg-slate-50'
                }`}
              >
                Support
              </Link>
            </nav>
          </div>

          {/* Desktop Auth Buttons & Mobile Menu Toggle */}
          <div className="flex items-center gap-3">
            <div className="hidden sm:flex items-center gap-2">
              {user ? (
                <Link
                  to={isPlatformAdmin ? "/admin/platform" : "/admin/dashboard"}
                  className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
                >
                  <span>{isPlatformAdmin ? "Super Admin Console" : "Dashboard"}</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </Link>
              ) : (
                <>
                  <Link
                    to="/login"
                    className="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-[#475467] hover:text-[#0f172a] hover:bg-slate-50 transition"
                  >
                    Sign In
                  </Link>
                  <Link
                    to="/register"
                    className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#1d5fd6] hover:bg-[#1853bf] text-white text-xs font-semibold transition shadow-xs"
                  >
                    <span>Get Started Free</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </>
              )}
            </div>

            {/* Mobile Hamburger Toggle Button */}
            <button
              type="button"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 rounded-lg border border-[#e2e5e9] bg-white text-[#131416] hover:bg-slate-50 transition"
              aria-label="Toggle navigation menu"
            >
              {mobileMenuOpen ? <X className="w-4 h-4" /> : <Menu className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {/* Mobile Slide-Down Dropdown Menu */}
        {mobileMenuOpen && (
          <div className="lg:hidden py-3 border-t border-[#e2e5e9] space-y-3 pb-3 animate-in fade-in slide-in-from-top-2 duration-150">
            <nav className="flex flex-col space-y-1 text-xs font-medium text-[#475467]">
              {navLinks.map((item) => {
                const isActive = location.pathname === '/' && activeSection === item.hash;
                return (
                  <a
                    key={item.hash}
                    href={`/#${item.hash}`}
                    onClick={(e) => handleNavClick(e, item.hash)}
                    className={`flex items-center px-3 py-2 rounded-lg transition ${
                      isActive
                        ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]'
                        : 'hover:bg-slate-50 hover:text-[#0f172a]'
                    }`}
                  >
                    {item.name}
                  </a>
                );
              })}
              <Link
                to="/about"
                onClick={() => setMobileMenuOpen(false)}
                className={`flex items-center px-3 py-2 rounded-lg transition ${
                  location.pathname === '/about' ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]' : 'hover:bg-slate-50 hover:text-[#0f172a]'
                }`}
              >
                About
              </Link>
              <Link
                to="/support"
                onClick={() => setMobileMenuOpen(false)}
                className={`flex items-center px-3 py-2 rounded-lg transition ${
                  location.pathname === '/support' ? 'text-[#1d5fd6] font-semibold bg-[#eff6ff]' : 'hover:bg-slate-50 hover:text-[#0f172a]'
                }`}
              >
                Support
              </Link>
            </nav>

            {/* Mobile Auth Buttons */}
            <div className="pt-2 border-t border-[#e2e5e9] flex flex-col gap-2">
              {user ? (
                <Link
                  to={isPlatformAdmin ? "/admin/platform" : "/admin/dashboard"}
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full flex items-center justify-center gap-1.5 py-2 rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
                >
                  <span>{isPlatformAdmin ? "Super Admin Console" : "Dashboard"}</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </Link>
              ) : (
                <div className="grid grid-cols-2 gap-2">
                  <Link
                    to="/login"
                    onClick={() => setMobileMenuOpen(false)}
                    className="py-2 text-center rounded-lg border border-[#e2e5e9] bg-white text-xs font-semibold text-[#131416] hover:bg-slate-50 transition"
                  >
                    Sign In
                  </Link>
                  <Link
                    to="/register"
                    onClick={() => setMobileMenuOpen(false)}
                    className="py-2 text-center rounded-lg bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
                  >
                    Get Started Free
                  </Link>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* 2. Main Page Content */}
      <main className="flex-1">
        <Outlet />
      </main>

      {/* 3. Comprehensive Premium SaaS Marketing Footer */}
      <footer className="mt-auto bg-white border-t border-[#e2e5e9] pt-14 pb-8 px-4 sm:px-8 text-xs text-[#5d5f60]">
        <div className="max-w-6xl mx-auto space-y-12">
          {/* Top 5-Column Grid */}
          <div className="grid grid-cols-1 md:grid-cols-5 gap-8">
            {/* Col 1 & 2: Brand & Mission */}
            <div className="md:col-span-2 space-y-4">
              <div className="flex items-center gap-2.5">
                <div className="w-7 h-7 rounded-lg bg-[#1d5fd6] text-white flex items-center justify-center font-bold">
                  <GitFork className="w-3.5 h-3.5" />
                </div>
                <span className="font-bold text-base text-[#131416] tracking-tight">QFunnel</span>
                <span className="text-[10px] font-semibold text-[#1d5fd6] bg-[#dcf1ff] px-1.5 py-0.5 rounded border border-[#b9e6fe]">
                  v2.4
                </span>
              </div>

              <p className="text-xs text-[#5d5f60] leading-relaxed max-w-sm">
                The interactive visual quiz and real-time quote calculator platform built for modern revenue teams. Replace low-converting static forms with personalized dynamic pathways.
              </p>

              <div className="flex flex-wrap items-center gap-2 pt-1 text-[11px] text-[#5d5f60]">
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-[#f4f6f8] border border-[#e2e5e9]">
                  <ShieldCheck className="w-3.5 h-3.5 text-[#2f7a45]" />
                  <span>256-Bit SSL Encrypted</span>
                </span>
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-[#f4f6f8] border border-[#e2e5e9]">
                  <CheckCircle className="w-3.5 h-3.5 text-[#1d5fd6]" />
                  <span>GDPR Compliant</span>
                </span>
              </div>
            </div>

            {/* Col 3: Product Capabilities */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold text-[#131416]">Product</h4>
              <ul className="space-y-2 text-xs">
                <li>
                  <a href="/#features" onClick={(e) => handleNavClick(e, 'features')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Visual Node Canvas
                  </a>
                </li>
                <li>
                  <a href="/#calculator" onClick={(e) => handleNavClick(e, 'calculator')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Live Quote Formula Engine
                  </a>
                </li>
                <li>
                  <a href="/#how-it-works" onClick={(e) => handleNavClick(e, 'how-it-works')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    1-Click WhatsApp Connect
                  </a>
                </li>
                <li>
                  <a href="/#how-it-works" onClick={(e) => handleNavClick(e, 'how-it-works')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Calendar Scheduling Steps
                  </a>
                </li>
                <li>
                  <a href="/#features" onClick={(e) => handleNavClick(e, 'features')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Drop-off & Conversion Analytics
                  </a>
                </li>
              </ul>
            </div>

            {/* Col 4: Solutions & Industries */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold text-[#131416]">Solutions</h4>
              <ul className="space-y-2 text-xs">
                <li>
                  <a href="/#use-cases" onClick={(e) => handleNavClick(e, 'use-cases')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Healthcare & Clinics
                  </a>
                </li>
                <li>
                  <a href="/#use-cases" onClick={(e) => handleNavClick(e, 'use-cases')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Marketing & Design Agencies
                  </a>
                </li>
                <li>
                  <a href="/#use-cases" onClick={(e) => handleNavClick(e, 'use-cases')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Contractors & Home Services
                  </a>
                </li>
                <li>
                  <a href="/#use-cases" onClick={(e) => handleNavClick(e, 'use-cases')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Financial Advisory & Insurance
                  </a>
                </li>
                <li>
                  <a href="/#pricing" onClick={(e) => handleNavClick(e, 'pricing')} className="hover:text-[#1d5fd6] transition cursor-pointer">
                    Agency White-labeling
                  </a>
                </li>
              </ul>
            </div>

            {/* Col 5: Company, Support & Legal */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold text-[#131416]">Company & Legal</h4>
              <ul className="space-y-2 text-xs">
                <li><Link to="/about" className="hover:text-[#1d5fd6] transition">About QFunnel</Link></li>
                <li><Link to="/support" className="hover:text-[#1d5fd6] transition">Help Center & Support</Link></li>
                <li><Link to="/privacy" className="hover:text-[#1d5fd6] transition">Privacy Policy</Link></li>
                <li><Link to="/terms" className="hover:text-[#1d5fd6] transition">Terms of Service</Link></li>
                <li><Link to="/cookies" className="hover:text-[#1d5fd6] transition">Cookie Policy</Link></li>
              </ul>
            </div>
          </div>

          {/* Bottom Divider & Copyright */}
          <div className="pt-8 border-t border-[#edf0f3] flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-[#5d5f60]">
            <div className="flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-[#2f7a45]" />
              <span>All systems fully operational</span>
              <span>•</span>
              <span>&copy; {new Date().getFullYear()} QFunnel Inc. All rights reserved.</span>
            </div>

            <div className="flex items-center gap-5">
              <Link to="/privacy" className="hover:text-[#1d5fd6]">Privacy</Link>
              <Link to="/terms" className="hover:text-[#1d5fd6]">Terms</Link>
              <Link to="/cookies" className="hover:text-[#1d5fd6]">Cookies</Link>
              <Link to="/support" className="hover:text-[#1d5fd6]">Contact</Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
};
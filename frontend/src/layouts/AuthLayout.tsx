import React from 'react';
import { Outlet, Link } from 'react-router-dom';
import { BrandLogo } from '../components/BrandLogo';

export const AuthLayout: React.FC = () => {
  return (
    <div className="min-h-screen bg-[#f8fafc] flex flex-col justify-center py-12 sm:px-6 lg:px-8 antialiased">
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <Link to="/" className="inline-flex items-center justify-center group transition">
          <BrandLogo size="lg" />
        </Link>
        <p className="mt-2.5 text-xs text-[#5d5f60] font-medium">
          Interactive Quiz Funnels & Lead Generation Platform
        </p>
      </div>

      <div className="mt-7 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div className="bg-white py-8 px-6 sm:px-9 border border-[#e2e5e9] rounded-2xl shadow-xs">
          <Outlet />
        </div>
      </div>
    </div>
  );
};

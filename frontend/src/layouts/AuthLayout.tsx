import React from 'react';
import { Outlet, Link } from 'react-router-dom';
import { BrandLogo } from '../components/BrandLogo';

export const AuthLayout: React.FC = () => {
  return (
    <div className="min-h-screen bg-[#f9fbfc] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <Link to="/" className="inline-flex items-center justify-center">
          <BrandLogo size="lg" />
        </Link>
        <p className="mt-2 text-xs text-[#5d5f60]">Interactive Funnel Builder & Growth Platform</p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div className="bg-white py-8 px-6 border border-[#e8eaec] rounded-[16px] sm:px-10">
          <Outlet />
        </div>
      </div>
    </div>
  );
};


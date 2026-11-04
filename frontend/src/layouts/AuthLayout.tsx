import React from 'react';
import { Outlet, Link } from 'react-router-dom';
import { Layers } from 'lucide-react';

export const AuthLayout: React.FC = () => {
  return (
    <div className="min-h-screen bg-[#f9fbfc] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <Link to="/" className="inline-flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-[#131416] flex items-center justify-center text-white">
            <Layers className="w-4 h-4 text-[#1b6c80]" />
          </div>
          <span className="text-lg font-semibold text-[#131416] tracking-tight">QFunnel</span>
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


import React, { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';

export const SuperAdminRoute: React.FC<{ children: React.ReactElement }> = ({ children }) => {
  const { user, isPlatformAdmin, isLoading, checkAuth } = useAuthStore();
  const location = useLocation();

  useEffect(() => {
    if (!user) {
      checkAuth();
    }
  }, []);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#f8fafc]">
        <div className="w-8 h-8 border-3 border-[#1d5fd6] border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!user || !isPlatformAdmin) {
    return <Navigate to="/login" replace />;
  }

  return children;
};


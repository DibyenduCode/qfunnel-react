import { create } from 'zustand';
import { api, setCsrfToken } from '../api/client';
import { User, Tenant, TenantMembership, PlanLimits, PlanFeatures } from '../types';

interface AuthState {
  user: User | null;
  activeTenant: Tenant | null;
  role: string | null;
  userTenants: TenantMembership[];
  limits: PlanLimits | null;
  features: PlanFeatures | null;
  isPlatformAdmin: boolean;
  isLoading: boolean;
  error: string | null;
  lastDevOtp: string | null;

  checkAuth: () => Promise<boolean>;
  login: (email: string, pass: string) => Promise<void>;
  register: (name: string, email: string, pass: string, workspace: string) => Promise<void>;
  verifyOtp: (otp: string) => Promise<void>;
  resendOtp: () => Promise<{ expires_in_minutes: number; dev_otp?: string; simulated?: boolean }>;
  switchWorkspace: (tenantId: number) => Promise<void>;
  logout: () => Promise<void>;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  activeTenant: null,
  role: null,
  userTenants: [],
  limits: null,
  features: null,
  isPlatformAdmin: false,
  isLoading: true,
  error: null,
  lastDevOtp: null,

  clearError: () => set({ error: null }),

  checkAuth: async () => {
    try {
      set({ isLoading: true, error: null });
      const data = await api.get('/api/auth/me');
      if (data.csrf_token) {
        setCsrfToken(data.csrf_token);
      }
      set({
        user: data.user,
        activeTenant: data.active_tenant,
        role: data.role,
        userTenants: data.user_tenants || [],
        limits: data.limits,
        features: data.features,
        isPlatformAdmin: Boolean(data.is_platform_admin),
        isLoading: false,
      });
      return true;
    } catch (err: any) {
      set({
        user: null,
        activeTenant: null,
        role: null,
        userTenants: [],
        limits: null,
        features: null,
        isPlatformAdmin: false,
        isLoading: false,
      });
      return false;
    }
  },

  login: async (email, password) => {
    set({ isLoading: true, error: null });
    try {
      const data = await api.post('/api/auth/login', { email, password });
      if (data.csrf_token) {
        setCsrfToken(data.csrf_token);
      }
      set({
        user: data.user,
        activeTenant: data.active_tenant,
        role: data.role,
        userTenants: data.user_tenants || [],
        isPlatformAdmin: Boolean(data.user?.is_platform_admin),
        isLoading: false,
      });
      // Fetch full limits
      get().checkAuth();
    } catch (err: any) {
      set({ error: err.message || 'Login failed', isLoading: false });
      throw err;
    }
  },

  register: async (name, email, password, workspace) => {
    set({ isLoading: true, error: null });
    try {
      const data = await api.post('/api/auth/register', { name, email, password, workspace });
      if (data.csrf_token) {
        setCsrfToken(data.csrf_token);
      }
      set({
        user: data.user,
        activeTenant: data.active_tenant,
        role: data.role,
        userTenants: data.user_tenants || [],
        isPlatformAdmin: false,
        isLoading: false,
        lastDevOtp: data.dev_otp || null,
      });
      get().checkAuth();
    } catch (err: any) {
      set({ error: err.message || 'Registration failed', isLoading: false });
      throw err;
    }
  },

  verifyOtp: async (otp: string) => {
    set({ isLoading: true, error: null });
    try {
      const data = await api.post('/api/auth/verify-otp', { otp });
      set({
        user: data.user,
        activeTenant: data.active_tenant || get().activeTenant,
        isLoading: false,
        lastDevOtp: null,
      });
      await get().checkAuth();
    } catch (err: any) {
      set({ error: err.message || 'Verification failed', isLoading: false });
      throw err;
    }
  },

  resendOtp: async () => {
    try {
      set({ error: null });
      const data = await api.post('/api/auth/resend-otp');
      if (data.dev_otp) {
        set({ lastDevOtp: data.dev_otp });
      }
      return data;
    } catch (err: any) {
      set({ error: err.message || 'Failed to resend verification code' });
      throw err;
    }
  },

  switchWorkspace: async (tenantId: number) => {
    try {
      set({ isLoading: true });
      await api.post('/api/auth/switch-workspace', { tenant_id: tenantId });
      await get().checkAuth();
    } catch (err: any) {
      set({ error: err.message || 'Could not switch workspace', isLoading: false });
      throw err;
    }
  },

  logout: async () => {
    try {
      await api.post('/api/auth/logout');
    } finally {
      set({
        user: null,
        activeTenant: null,
        role: null,
        userTenants: [],
        limits: null,
        features: null,
        isPlatformAdmin: false,
        isLoading: false,
      });
    }
  },
}));


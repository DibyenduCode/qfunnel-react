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

  checkAuth: () => Promise<boolean>;
  login: (email: string, pass: string) => Promise<void>;
  register: (name: string, email: string, pass: string, workspace: string) => Promise<void>;
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
      });
      get().checkAuth();
    } catch (err: any) {
      set({ error: err.message || 'Registration failed', isLoading: false });
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


let csrfToken: string | null = null;

const getBaseApiUrl = (): string => {
  if (typeof window === 'undefined') return '';
  // If running in Vite dev server (port 5173), Vite proxies /api to backend
  if (window.location.port === '5173') {
    return '';
  }
  // If hosted under Apache subfolder /qfunnel2
  if (window.location.pathname.startsWith('/qfunnel2')) {
    return '/qfunnel2';
  }
  return '';
};

export const resolveUrl = (endpoint: string): string => {
  if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
    return endpoint;
  }
  const base = getBaseApiUrl();
  const normalized = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  return `${base}${normalized}`;
};

export const resolveMediaUrl = (url: string | null | undefined): string => {
  if (!url) return '';
  const trimmed = url.trim();
  if (!trimmed) return '';
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }
  if (trimmed.startsWith('/qfunnel2/')) {
    return trimmed;
  }
  const base = getBaseApiUrl();
  const normalized = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
  return `${base}${normalized}`;
};

export async function fetchCsrfToken(): Promise<string> {
  if (csrfToken) return csrfToken;
  try {
    const res = await fetch(resolveUrl('/api/csrf-token'), { credentials: 'include' });
    const json = await res.json();
    if (json.success && json.data?.csrf_token) {
      csrfToken = json.data.csrf_token;
      return csrfToken || '';
    }
  } catch (err) {
    console.warn("Failed to fetch CSRF token", err);
  }
  return '';
}

export function setCsrfToken(token: string) {
  csrfToken = token;
}

let activeRequestCount = 0;
type LoadingSubscriber = (count: number) => void;
const loadingSubscribers = new Set<LoadingSubscriber>();

export function subscribeToLoading(fn: LoadingSubscriber): () => void {
  loadingSubscribers.add(fn);
  fn(activeRequestCount);
  return () => {
    loadingSubscribers.delete(fn);
  };
}

function notifyLoading(count: number) {
  loadingSubscribers.forEach((fn) => {
    try {
      fn(count);
    } catch (e) {
      console.warn('Loading subscriber error:', e);
    }
  });
}

interface RequestOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined | null>;
}

export async function apiRequest<T = any>(endpoint: string, options: RequestOptions = {}): Promise<T> {
  const token = await fetchCsrfToken();
  const headers = new Headers(options.headers || {});

  if (!headers.has('Content-Type') && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }
  headers.set('Accept', 'application/json');
  headers.set('X-Requested-With', 'XMLHttpRequest');

  if (token && !headers.has('X-CSRF-Token')) {
    headers.set('X-CSRF-Token', token);
  }

  let url = resolveUrl(endpoint);
  if (options.params) {
    const query = new URLSearchParams();
    Object.entries(options.params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') {
        query.append(k, String(v));
      }
    });
    const qs = query.toString();
    if (qs) {
      url += (url.includes('?') ? '&' : '?') + qs;
    }
  }

  activeRequestCount++;
  notifyLoading(activeRequestCount);

  try {
    const res = await fetch(url, {
      ...options,
      headers,
      credentials: 'include',
    });

    const contentType = res.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      if (!res.ok) {
        throw new Error(`Request failed with status ${res.status}`);
      }
      return (await res.text()) as any;
    }

    const data = await res.json();
    if (!res.ok || data.success === false) {
      const msg = data.error || data.message || `Request failed with status ${res.status}`;
      const err: any = new Error(msg);
      err.status = res.status;
      err.code = data.error_code;
      err.data = data.data;
      throw err;
    }

    return data.data !== undefined ? data.data : data;
  } finally {
    activeRequestCount = Math.max(0, activeRequestCount - 1);
    notifyLoading(activeRequestCount);
  }
}

export const api = {
  get: <T = any>(url: string, params?: Record<string, any>) => 
    apiRequest<T>(url, { method: 'GET', params }),
  post: <T = any>(url: string, body?: any) => 
    apiRequest<T>(url, { method: 'POST', body: body instanceof FormData ? body : JSON.stringify(body) }),
  put: <T = any>(url: string, body?: any) => 
    apiRequest<T>(url, { method: 'PUT', body: body instanceof FormData ? body : JSON.stringify(body) }),
  delete: <T = any>(url: string, body?: any) => 
    apiRequest<T>(url, { method: 'DELETE', body: body ? JSON.stringify(body) : undefined }),
};

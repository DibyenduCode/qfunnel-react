import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Uncaught error caught by ErrorBoundary:', error, errorInfo);
  }

  private handleReload = () => {
    window.location.reload();
  };

  private handleGoHome = () => {
    window.location.href = '/';
  };

  public render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="min-h-screen bg-[#f8fafc] flex items-center justify-center p-4">
          <div className="max-w-md w-full bg-white border border-[#e2e5e9] rounded-2xl p-6 sm:p-8 shadow-sm text-center">
            <div className="w-12 h-12 rounded-xl bg-[#fee2e2] text-[#dc2626] flex items-center justify-center mx-auto mb-4">
              <AlertTriangle className="w-6 h-6 stroke-[2]" />
            </div>
            <h2 className="text-base font-bold text-[#131416] mb-1">Something unexpected happened</h2>
            <p className="text-xs text-[#5d5f60] mb-6">
              An error occurred while loading this view. You can reload the page or return to the home screen.
            </p>
            {this.state.error?.message && (
              <div className="mb-6 p-3 rounded-xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-[11px] font-mono text-left overflow-x-auto">
                {this.state.error.message}
              </div>
            )}
            <div className="flex items-center justify-center gap-3">
              <button
                onClick={this.handleReload}
                className="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl bg-[#1d5fd6] text-white text-xs font-semibold hover:bg-[#1853bf] transition shadow-xs"
              >
                <RefreshCw className="w-3.5 h-3.5" />
                <span>Reload Page</span>
              </button>
              <button
                onClick={this.handleGoHome}
                className="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl bg-white border border-[#e2e5e9] text-[#131416] text-xs font-semibold hover:bg-[#f4f6f8] transition shadow-xs"
              >
                <Home className="w-3.5 h-3.5" />
                <span>Home</span>
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { HelmetProvider } from 'react-helmet-async';
import { AppRoutes } from './routes/AppRoutes';
import { TopProgressBar } from './components/TopProgressBar';
import { ErrorBoundary } from './components/ErrorBoundary';
import { useAuthStore } from './store/authStore';

const AuthBootstrap: React.FC = () => {
  const { checkAuth } = useAuthStore();
  React.useEffect(() => {
    checkAuth();
  }, []);
  return null;
};

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

export const App: React.FC = () => {
  return (
    <HelmetProvider>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <AuthBootstrap />
          <TopProgressBar />
          <ErrorBoundary>
            <AppRoutes />
          </ErrorBoundary>
        </BrowserRouter>
      </QueryClientProvider>
    </HelmetProvider>
  );
};

export default App;


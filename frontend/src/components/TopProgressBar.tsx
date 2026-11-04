import React, { useState, useEffect } from 'react';
import { subscribeToLoading } from '../api/client';

export const TopProgressBar: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    let timeout: NodeJS.Timeout;
    const unsubscribe = subscribeToLoading((count) => {
      if (count > 0) {
        setLoading(true);
        setVisible(true);
      } else {
        setLoading(false);
        timeout = setTimeout(() => {
          setVisible(false);
        }, 300);
      }
    });

    return () => {
      unsubscribe();
      clearTimeout(timeout);
    };
  }, []);

  if (!visible) return null;

  return (
    <div
      role="progressbar"
      aria-label="Loading data"
      aria-busy={loading}
      className={`fixed top-0 left-0 right-0 h-[2.5px] z-[99999] pointer-events-none overflow-hidden bg-transparent transition-opacity duration-300 ${
        loading ? 'opacity-100' : 'opacity-0'
      }`}
    >
      <div className="w-full h-full bg-[#1d5fd6] animate-top-bar origin-left" />
    </div>
  );
};

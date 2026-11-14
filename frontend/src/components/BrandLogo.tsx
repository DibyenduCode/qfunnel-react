import React from 'react';
import logoLight from '../assets/logo.png';
import logoWhite from '../assets/logo-white.png';
import logoMark from '../assets/logo-mark.png';

interface BrandLogoProps {
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  variant?: 'full' | 'mark' | 'white';
  showDomain?: boolean;
  className?: string;
}

export const BrandLogo: React.FC<BrandLogoProps> = ({
  size = 'md',
  variant = 'full',
  className = '',
}) => {
  // Height definitions for the logo image according to size (slightly bigger for prominence)
  const heights = {
    xs: { h: 'h-7', markH: 'h-7' },
    sm: { h: 'h-8 sm:h-9', markH: 'h-8 sm:h-9' },
    md: { h: 'h-10 sm:h-11', markH: 'h-10 sm:h-11' },
    lg: { h: 'h-12 sm:h-14', markH: 'h-12 sm:h-14' },
    xl: { h: 'h-16 sm:h-20', markH: 'h-16 sm:h-20' },
  }[size];

  const isWhite = variant === 'white';
  const isMark = variant === 'mark';

  const logoSrc = isMark ? logoMark : (isWhite ? logoWhite : logoLight);

  return (
    <div className={`inline-flex items-center select-none ${className}`}>
      <img
        src={logoSrc}
        alt="LeadCollector"
        className={`${isMark ? heights.markH : heights.h} w-auto object-contain shrink-0`}
        draggable={false}
      />
    </div>
  );
};

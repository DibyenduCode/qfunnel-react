import React from 'react';
import { Helmet } from 'react-helmet-async';

export interface SeoHelmetProps {
  title?: string;
  description?: string;
  canonicalUrl?: string;
  image?: string;
  type?: 'website' | 'article';
  siteName?: string;
  noindex?: boolean;
}

const DEFAULT_TITLE = 'FilterFunnel.in — Visual Interactive Quiz Funnels & Lead Generation Platform';
const DEFAULT_DESC =
  'Build high-converting interactive quiz funnels, calculator flows, and multi-step lead capture forms with FilterFunnel.in.';
const DEFAULT_IMAGE = '/dist/logo.png';
const DEFAULT_SITE_NAME = 'FilterFunnel.in';

export const SeoHelmet: React.FC<SeoHelmetProps> = ({
  title,
  description = DEFAULT_DESC,
  canonicalUrl,
  image = DEFAULT_IMAGE,
  type = 'website',
  siteName = DEFAULT_SITE_NAME,
  noindex = false,
}) => {
  const fullTitle = title
    ? title.includes('FilterFunnel')
      ? title
      : `${title} | FilterFunnel`
    : DEFAULT_TITLE;
  const currentUrl = canonicalUrl || (typeof window !== 'undefined' ? window.location.href : '');

  return (
    <Helmet>
      {/* Dynamic Title */}
      <title>{fullTitle}</title>

      {/* Meta Description */}
      <meta name="description" content={description} />

      {/* Robots Directive */}
      {noindex ? (
        <meta name="robots" content="noindex, nofollow" />
      ) : (
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
      )}

      {/* Canonical URL */}
      {currentUrl && <link rel="canonical" href={currentUrl} />}

      {/* Open Graph Tags */}
      <meta property="og:type" content={type} />
      <meta property="og:site_name" content={siteName} />
      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={description} />
      {currentUrl && <meta property="og:url" content={currentUrl} />}
      {image && <meta property="og:image" content={image} />}
      {image && <meta property="og:image:alt" content={fullTitle} />}

      {/* Twitter Card Tags */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={fullTitle} />
      <meta name="twitter:description" content={description} />
      {image && <meta name="twitter:image" content={image} />}
    </Helmet>
  );
};

export default SeoHelmet;

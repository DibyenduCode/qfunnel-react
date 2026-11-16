/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"IBM Plex Sans"', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      colors: {
        ground: '#f9fbfc',
        surface: '#ffffff',
        raised: '#f4f6f8',
        active: '#ebedee',
        borderBase: '#e8eaec',
        rule: '#f0f1f2',
        control: '#cdced1',
        primaryText: '#131416',
        secondaryText: '#5d5f60',
        mutedText: '#5d5f60',
        accent: '#004cfd',
        accentHover: '#003ecc',
        positive: '#1e7b4f',
        negative: '#c4242a',
        warning: '#92400e',
        chart1: '#004cfd',
        chart2: '#2563eb',
        chart3: '#f59e0b',
      },
      borderRadius: {
        card: '8px',
        control: '6px',
        sheet: '16px',
      }
    },
  },
  plugins: [],
}


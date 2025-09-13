import React from 'react';

/**
 * Simple Sparkline component.
 * Props:
 *  - prices: number[] (required)
 *  - width?: number (default 600)
 *  - height?: number (default 80)
 *  - strokeColor?: string (default 'rgb(99 102 241)') indigo-500
 *  - fillColor?: string (default 'rgba(99,102,241,0.15)')
 */
export default function Sparkline({
  prices = [],
  width = 600,
  height = 80,
  strokeColor = 'rgb(99 102 241)',
  fillColor = 'rgba(99,102,241,0.15)'
}) {
  if (!prices || prices.length < 2) {
    return (
      <div style={{ width, height }} className="flex items-center justify-center text-gray-500 text-sm">
        No data
      </div>
    );
  }

  const min = Math.min(...prices);
  const max = Math.max(...prices);
  const len = prices.length;
  const padding = 4;
  const w = width - padding * 2;
  const h = height - padding * 2;

  const scaleX = (i) => (i / (len - 1)) * w + padding;
  const scaleY = (v) => {
    if (max === min) return h / 2 + padding; // flat line when no variance
    return (1 - (v - min) / (max - min)) * h + padding;
  };

  const points = prices.map((v, i) => `${scaleX(i)},${scaleY(v)}`).join(' ');

  // Area path for tiny fill under line
  const areaPath = `M ${scaleX(0)} ${scaleY(prices[0])} L ${prices
    .map((v, i) => `${scaleX(i)} ${scaleY(v)}`)
    .join(' L ')} L ${scaleX(len - 1)} ${height - padding} L ${scaleX(0)} ${height - padding} Z`;

  const up = prices[prices.length - 1] >= prices[0];
  const dynamicStroke = strokeColor || (up ? 'rgb(22 163 74)' : 'rgb(220 38 38)'); // green/red fallback
  const dynamicFill = fillColor || (up ? 'rgba(22,163,74,0.15)' : 'rgba(220,38,38,0.15)');

  return (
    <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} role="img" aria-label="sparkline">
      <defs>
        <linearGradient id="spark-fill" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0%" stopColor={dynamicFill} />
          <stop offset="100%" stopColor="transparent" />
        </linearGradient>
      </defs>
      <path d={areaPath} fill="url(#spark-fill)" stroke="none" />
      <polyline
        fill="none"
        stroke={dynamicStroke}
        strokeWidth="2"
        strokeLinejoin="round"
        strokeLinecap="round"
        points={points}
      />
    </svg>
  );
}

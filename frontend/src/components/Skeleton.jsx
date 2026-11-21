import { useEffect, useState } from 'react';

/**
 * Skeleton loader for content placeholders.
 * @param {string} variant - 'text' | 'card' | 'table-row' | 'circle' | 'rect'
 * @param {object} style - Additional inline styles
 */
export default function Skeleton({ variant = 'text', style, className, ...props }) {
  const [animated, setAnimated] = useState(false);

  useEffect(() => {
    const t = setTimeout(() => setAnimated(true), 0);
    return () => clearTimeout(t);
  }, []);

  const baseStyle = {
    background: 'linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%)',
    backgroundSize: '200% 100%',
    animation: animated ? 'shimmer 1.5s infinite' : 'none',
    borderRadius: variant === 'circle' ? '50%' : variant === 'rect' ? '0' : '4px',
    ...style,
  };

  if (variant === 'card') {
    return (
      <div className={`adhub-card p-4 ${className || ''}`} style={baseStyle} {...props}>
        <div style={{ height: 24, width: '40%', marginBottom: 16 }} />
        <div style={{ height: 16, width: '60%', marginBottom: 8 }} />
        <div style={{ height: 16, width: '45%' }} />
      </div>
    );
  }

  if (variant === 'table-row') {
    return (
      <tr style={baseStyle} {...props}>
        <td><div style={{ height: 16, width: '80%' }} /></td>
        <td><div style={{ height: 16, width: '60%' }} /></td>
        <td className="text-end"><div style={{ height: 16, width: '50%', marginLeft: 'auto' }} /></td>
        <td className="text-end"><div style={{ height: 16, width: '40%', marginLeft: 'auto' }} /></td>
        <td><div style={{ height: 16, width: '70%' }} /></td>
        <td><div style={{ height: 16, width: '50%' }} /></td>
        <td className="text-end"><div style={{ height: 24, width: 60, marginLeft: 'auto' }} /></td>
      </tr>
    );
  }

  if (variant === 'circle') {
    return <div className={className} style={{ ...baseStyle, width: 40, height: 40 }} {...props} />;
  }

  if (variant === 'rect') {
    return <div className={className} style={{ ...baseStyle, height: 16, width: '100%' }} {...props} />;
  }

  return <div className={className} style={{ ...baseStyle, height: 16, width: '100%' }} {...props} />;
}
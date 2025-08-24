import React from 'react';

export type LoadingSpinnerProps = {
  label?: string;
  className?: string;
  size?: number; // px
};

export default function LoadingSpinner({ label = 'Loading…', className = '', size = 16 }: LoadingSpinnerProps) {
  return (
    <div className={`flex items-center gap-2 ${className}`} role="status" aria-live="polite" aria-busy="true">
      <svg
        className="animate-spin text-muted-foreground"
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden
      >
        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      <span className="text-sm text-muted-foreground select-none">{label}</span>
    </div>
  );
}

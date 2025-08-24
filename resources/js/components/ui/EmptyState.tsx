import React from 'react';

export type EmptyStateProps = {
  title?: string;
  description?: string;
  className?: string;
};

export default function EmptyState({ title = 'Nothing here', description = 'No data to display.', className = '' }: EmptyStateProps) {
  return (
    <div className={`flex flex-col items-center justify-center gap-1 py-6 ${className}`}>
      <div className="text-sm font-medium text-foreground/80">{title}</div>
      <div className="text-xs text-muted-foreground">{description}</div>
    </div>
  );
}

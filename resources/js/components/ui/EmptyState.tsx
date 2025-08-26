import React from 'react';
import { useTranslation } from 'react-i18next';

export type EmptyStateProps = {
  title?: string;
  description?: string;
  className?: string;
};

export default function EmptyState({ title, description, className = '' }: EmptyStateProps) {
  const { t } = useTranslation();
  const finalTitle = title ?? t('common.empty_title');
  const finalDesc = description ?? t('common.empty_description');
  return (
    <div className={`flex flex-col items-center justify-center gap-1 py-6 ${className}`}>
      <div className="text-sm font-medium text-foreground/80">{finalTitle}</div>
      <div className="text-xs text-muted-foreground">{finalDesc}</div>
    </div>
  );
}

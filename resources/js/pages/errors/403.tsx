import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';

type Props = {
  status: number;
  message?: string;
};

export default function Error403({ status = 403, message = 'Forbidden' }: Props) {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const isAdmin: boolean = Boolean(auth?.isAdmin);
  const isRoot: boolean = Boolean(auth?.isRoot);

  return (
    <AppLayout>
      <Head title={`${status} · ${t('errors.403.head')}`} />
      <div className="mx-auto max-w-xl p-6 text-center space-y-3">
        <div className="text-6xl font-bold">{status}</div>
        <div className="text-lg font-medium">{t('errors.403.title')}</div>
        <div className="text-sm text-muted-foreground">{message}</div>
        <div className="pt-2">
          {isRoot ? (
            <div className="flex items-center justify-center gap-2">
              <Link href={route('admin.roles.index.json')} className="rounded-md border px-3 py-2 text-sm">{t('errors.403.manage_roles')}</Link>
              <Link href={route('admin.users.index')} className="rounded-md border px-3 py-2 text-sm">{t('errors.403.manage_users')}</Link>
            </div>
          ) : isAdmin ? (
            <div className="flex items-center justify-center gap-2">
              <Link href={route('admin.users.index')} className="rounded-md border px-3 py-2 text-sm">{t('nav.users')}</Link>
              <Link href={route('admin.settings.ui')} className="rounded-md border px-3 py-2 text-sm">{t('nav.settings')}</Link>
            </div>
          ) : (
            <Link href={route('dashboard')} className="rounded-md border px-3 py-2 text-sm">{t('common.back_to_dashboard')}</Link>
          )}
        </div>
      </div>
    </AppLayout>
  );
}

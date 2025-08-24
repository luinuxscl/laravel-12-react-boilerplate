import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

type Props = {
  status: number;
  message?: string;
};

export default function Error403({ status = 403, message = 'Forbidden' }: Props) {
  const { auth } = usePage().props as any;
  const isAdmin: boolean = Boolean(auth?.isAdmin);
  const isRoot: boolean = Boolean(auth?.isRoot);

  return (
    <AppLayout>
      <Head title={`${status} · Forbidden`} />
      <div className="mx-auto max-w-xl p-6 text-center space-y-3">
        <div className="text-6xl font-bold">{status}</div>
        <div className="text-lg font-medium">You don’t have permission to access this page.</div>
        <div className="text-sm text-muted-foreground">{message}</div>
        <div className="pt-2">
          {isRoot ? (
            <div className="flex items-center justify-center gap-2">
              <Link href={route('admin.roles.index.json')} className="rounded-md border px-3 py-2 text-sm">Manage Roles</Link>
              <Link href={route('admin.users.index')} className="rounded-md border px-3 py-2 text-sm">Manage Users</Link>
            </div>
          ) : isAdmin ? (
            <div className="flex items-center justify-center gap-2">
              <Link href={route('admin.users.index')} className="rounded-md border px-3 py-2 text-sm">Users</Link>
              <Link href={route('admin.settings.ui')} className="rounded-md border px-3 py-2 text-sm">Settings</Link>
            </div>
          ) : (
            <Link href={route('dashboard')} className="rounded-md border px-3 py-2 text-sm">Back to Dashboard</Link>
          )}
        </div>
      </div>
    </AppLayout>
  );
}

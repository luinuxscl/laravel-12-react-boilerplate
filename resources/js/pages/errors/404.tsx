import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

type Props = {
  status: number;
  message?: string;
};

export default function Error404({ status = 404, message = 'Not Found' }: Props) {
  return (
    <AppLayout>
      <Head title={`${status} · Not Found`} />
      <div className="mx-auto max-w-xl p-6 text-center space-y-3">
        <div className="text-6xl font-bold">{status}</div>
        <div className="text-lg font-medium">The page you’re looking for doesn’t exist.</div>
        <div className="text-sm text-muted-foreground">{message}</div>
        <div className="pt-2">
          <Link href={route('dashboard')} className="rounded-md border px-3 py-2 text-sm">Back to Dashboard</Link>
        </div>
      </div>
    </AppLayout>
  );
}

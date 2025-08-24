import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

type Props = {
  status: number;
  message?: string;
};

export default function Error500({ status = 500, message = 'Server error' }: Props) {
  return (
    <AppLayout>
      <Head title={`${status} · Server Error`} />
      <div className="mx-auto max-w-xl p-6 text-center space-y-3">
        <div className="text-6xl font-bold">{status}</div>
        <div className="text-lg font-medium">Something went wrong on our side.</div>
        <div className="text-sm text-muted-foreground">{message}</div>
        <div className="pt-2">
          <Link href={route('dashboard')} className="rounded-md border px-3 py-2 text-sm">Back to Dashboard</Link>
        </div>
      </div>
    </AppLayout>
  );
}

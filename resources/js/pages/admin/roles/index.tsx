import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function AdminRolesPage() {
  const [roles, setRoles] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    fetch('/admin/roles')
      .then((r) => r.json())
      .then((j) => { if (mounted) setRoles(j.data || []); })
      .catch(() => {})
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, []);

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title="Admin · Roles" />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Roles</h1>
          <Link href={route('dashboard')} className="text-sm underline">Back to Dashboard</Link>
        </div>

        <div className="rounded-md border overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-muted/40">
              <tr>
                <th className="px-3 py-2">#</th>
                <th className="px-3 py-2">Name</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={2}>Loading...</td>
                </tr>
              )}
              {!loading && roles.length === 0 && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={2}>No roles found.</td>
                </tr>
              )}
              {!loading && roles.map((name, idx) => (
                <tr key={name} className="border-t">
                  <td className="px-3 py-2 w-16">{idx + 1}</td>
                  <td className="px-3 py-2">{name}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}

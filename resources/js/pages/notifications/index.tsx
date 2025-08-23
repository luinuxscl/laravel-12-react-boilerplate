import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

type Notification = {
  id: string;
  type: string;
  read_at: string | null;
  data: Record<string, any>;
  created_at: string;
};

export default function NotificationsPage() {
  const [unread, setUnread] = useState<Notification[]>([]);
  const [all, setAll] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(false);
  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

  async function load() {
    setLoading(true);
    try {
      const res = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      setUnread(json.unread || []);
      setAll(json.all || []);
      // Notify other UI (e.g., sidebar) to refresh unread badge immediately
      window.dispatchEvent(new Event('notifications:updated'));
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function markAsRead(id: string) {
    await fetch(`/notifications/${id}/read`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    load();
  }

  async function createDemo() {
    await fetch('/notifications/demo', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    load();
  }

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title="Notifications" />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Notifications</h1>
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={createDemo}>Create demo</button>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <div className="rounded-md border overflow-hidden">
            <div className="bg-muted/40 px-3 py-2 text-sm font-medium">Unread ({unread.length})</div>
            <ul className="divide-y">
              {loading && <li className="px-3 py-2 text-sm text-muted-foreground">Loading...</li>}
              {!loading && unread.length === 0 && <li className="px-3 py-2 text-sm text-muted-foreground">No unread notifications.</li>}
              {!loading && unread.map((n) => (
                <li key={n.id} className="px-3 py-2 text-sm flex items-start justify-between gap-4">
                  <div>
                    <div className="font-medium">{n.data?.title ?? 'Notification'}</div>
                    <div className="text-muted-foreground text-xs">{n.data?.body ?? n.type}</div>
                  </div>
                  <button className="rounded-md border px-2 py-1 text-xs" onClick={() => markAsRead(n.id)}>Mark as read</button>
                </li>
              ))}
            </ul>
          </div>

          <div className="rounded-md border overflow-hidden">
            <div className="bg-muted/40 px-3 py-2 text-sm font-medium">All ({all.length})</div>
            <ul className="divide-y">
              {loading && <li className="px-3 py-2 text-sm text-muted-foreground">Loading...</li>}
              {!loading && all.length === 0 && <li className="px-3 py-2 text-sm text-muted-foreground">No notifications.</li>}
              {!loading && all.map((n) => (
                <li key={n.id} className="px-3 py-2 text-sm">
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <div className="font-medium">{n.data?.title ?? 'Notification'}</div>
                      <div className="text-muted-foreground text-xs">{n.data?.body ?? n.type}</div>
                    </div>
                    {!n.read_at && (
                      <button className="rounded-md border px-2 py-1 text-xs" onClick={() => markAsRead(n.id)}>Mark as read</button>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

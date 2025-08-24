import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import LoadingSpinner from '@/components/ui/LoadingSpinner';
import EmptyState from '@/components/ui/EmptyState';
import ConfirmDialog from '@/components/ui/ConfirmDialog';

type Notification = {
  id: string;
  type: string;
  read_at: string | null;
  data: {
    title?: string;
    body?: string;
    [key: string]: unknown;
  };
  created_at: string;
};

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export default function NotificationsPage() {
  const [unread, setUnread] = useState<Paginated<Notification>>({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0 });
  const [all, setAll] = useState<Paginated<Notification>>({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0 });
  const [loading, setLoading] = useState(false);
  const [perPage] = useState(10);
  const [q, setQ] = useState('');
  const [allOnlyUnread, setAllOnlyUnread] = useState(false);
  const [confirmAllOpen, setConfirmAllOpen] = useState(false);
  const csrfToken = useMemo(() => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '', []);

  const load = useCallback(async (opts?: { unreadPage?: number; allPage?: number }) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      params.set('perPage', String(perPage));
      if (opts?.unreadPage) params.set('unreadPage', String(opts.unreadPage));
      if (opts?.allPage) params.set('allPage', String(opts.allPage));
      if (q.trim()) params.set('q', q.trim());
      if (allOnlyUnread) params.set('allOnlyUnread', '1');
      const res = await fetch(`/notifications?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      setUnread(json.unread || { data: [], current_page: 1, last_page: 1, per_page: perPage, total: 0 });
      setAll(json.all || { data: [], current_page: 1, last_page: 1, per_page: perPage, total: 0 });
      // Notify other UI (e.g., sidebar) to refresh unread badge immediately
      window.dispatchEvent(new Event('notifications:updated'));
    } finally {
      setLoading(false);
    }
  }, [perPage, q, allOnlyUnread]);

  useEffect(() => {
    load();
  }, [load]);

  async function markAsRead(id: string) {
    await fetch(`/notifications/${id}/read`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    load();
  }

  async function createDemo() {
    await fetch('/notifications/demo', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    load();
  }

  async function markAll() {
    await fetch('/notifications/read-all', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    setConfirmAllOpen(false);
    load();
  }

  function unreadPrev() {
    if (unread.current_page > 1) load({ unreadPage: unread.current_page - 1 });
  }

  function unreadNext() {
    if (unread.current_page < unread.last_page) load({ unreadPage: unread.current_page + 1 });
  }

  function allPrev() {
    if (all.current_page > 1) load({ allPage: all.current_page - 1 });
  }

  function allNext() {
    if (all.current_page < all.last_page) load({ allPage: all.current_page + 1 });
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
            <div className="bg-muted/40 px-3 py-2 text-sm font-medium flex items-center justify-between">
              <span>Unread ({unread.total})</span>
              {unread.total > 0 && (
                <button className="rounded-md border px-2 py-1 text-xs" onClick={() => setConfirmAllOpen(true)}>Mark all as read</button>
              )}
            </div>
            <ul className="divide-y">
              {loading && (
                <li className="px-3 py-2 text-sm text-muted-foreground">
                  <LoadingSpinner label="Loading unread…" />
                </li>
              )}
              {!loading && unread.data.length === 0 && (
                <li className="px-3 py-2 text-sm text-muted-foreground">
                  <EmptyState title="No unread notifications" description="You're all caught up." />
                </li>
              )}
              {!loading && unread.data.map((n) => (
                <li key={n.id} className="px-3 py-2 text-sm flex items-start justify-between gap-4">
                  <div>
                    <div className="font-medium">{n.data?.title ?? 'Notification'}</div>
                    <div className="text-muted-foreground text-xs">{n.data?.body ?? n.type}</div>
                  </div>
                  <button className="rounded-md border px-2 py-1 text-xs" onClick={() => markAsRead(n.id)}>Mark as read</button>
                </li>
              ))}
            </ul>
            <div className="flex items-center justify-between px-3 py-2 border-t text-xs">
              <button className="rounded-md border px-2 py-1 disabled:opacity-50" disabled={unread.current_page <= 1} onClick={unreadPrev}>Prev</button>
              <span>Page {unread.current_page} / {unread.last_page}</span>
              <button className="rounded-md border px-2 py-1 disabled:opacity-50" disabled={unread.current_page >= unread.last_page} onClick={unreadNext}>Next</button>
            </div>
          </div>

          <div className="rounded-md border overflow-hidden">
            <div className="bg-muted/40 px-3 py-2 text-sm font-medium">
              <div className="flex flex-col gap-2">
                <div className="flex items-center justify-between">
                  <span>All ({all.total})</span>
                  <div className="flex items-center gap-2">
                    <label className="flex items-center gap-1 text-xs">
                      <input type="checkbox" checked={allOnlyUnread} onChange={(e) => { setAllOnlyUnread(e.target.checked); load({ allPage: 1 }); }} />
                      Only unread
                    </label>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    onKeyDown={(e) => { if (e.key === 'Enter') load({ allPage: 1 }); }}
                    placeholder="Search title/body/type…"
                    className="w-full rounded-md border px-3 py-2 text-xs bg-background"
                  />
                  <button className="rounded-md border px-2 py-1 text-xs" onClick={() => load({ allPage: 1 })}>Search</button>
                </div>
              </div>
            </div>
            <ul className="divide-y">
              {loading && (
                <li className="px-3 py-2 text-sm text-muted-foreground">
                  <LoadingSpinner label="Loading all…" />
                </li>
              )}
              {!loading && all.data.length === 0 && (
                <li className="px-3 py-2 text-sm text-muted-foreground">
                  <EmptyState title="No notifications" description="There is nothing to show yet." />
                </li>
              )}
              {!loading && all.data.map((n) => (
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
            <div className="flex items-center justify-between px-3 py-2 border-t text-xs">
              <button className="rounded-md border px-2 py-1 disabled:opacity-50" disabled={all.current_page <= 1} onClick={allPrev}>Prev</button>
              <span>Page {all.current_page} / {all.last_page}</span>
              <button className="rounded-md border px-2 py-1 disabled:opacity-50" disabled={all.current_page >= all.last_page} onClick={allNext}>Next</button>
            </div>
          </div>
        </div>
        <ConfirmDialog
          open={confirmAllOpen}
          onClose={() => setConfirmAllOpen(false)}
          onConfirm={markAll}
          title="Mark all as read?"
          description="This will mark all unread notifications as read."
          confirmLabel="Mark all"
        />
      </div>
    </AppLayout>
  );
}

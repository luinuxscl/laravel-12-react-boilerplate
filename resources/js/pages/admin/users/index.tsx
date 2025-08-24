import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import DataTable, { Column } from '@/components/tables/DataTable';
import { useDataTable } from '@/hooks/useDataTable';
import { useToast } from '@/hooks/useToast';
import Modal from '@/components/ui/Modal';
import AppLayout from '@/layouts/app-layout';
import LoadingSpinner from '@/components/ui/LoadingSpinner';
import EmptyState from '@/components/ui/EmptyState';
import { TOOLTIP } from '@/lib/perm-tooltips';

// Read CSRF token from Blade layout meta tag
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
  is_root?: boolean;
}

export default function AdminUsersPage() {
  const { auth } = usePage().props as any;
  const meId: number | null = auth?.user?.id ?? null;
  const isRoot: boolean = Boolean(auth?.isRoot);
  const { page, perPage, search, sortBy, sortDir, setPage, setPerPage, setSearch, setSort } = useDataTable({});
  const { show } = useToast();
  const [data, setData] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  const [meta, setMeta] = useState({ total: 0, per_page: perPage, current_page: page, last_page: 1 });
  const [editOpen, setEditOpen] = useState(false);
  const [editing, setEditing] = useState<User | null>(null);
  const [editName, setEditName] = useState('');
  const [viewOpen, setViewOpen] = useState(false);
  const [viewing, setViewing] = useState<User | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [deleting, setDeleting] = useState<User | null>(null);
  const [roles, setRoles] = useState<string[]>([]);
  const [role, setRole] = useState<string>('');
  const [createdFrom, setCreatedFrom] = useState<string>('');
  const [createdTo, setCreatedTo] = useState<string>('');

  const columns: Column<User>[] = useMemo(() => [
    { key: 'id', header: 'ID' },
    { key: 'name', header: 'Name' },
    { key: 'email', header: 'Email' },
    { key: 'created_at', header: 'Created' },
    {
      key: 'actions',
      header: 'Actions',
      render: (row: User) => (
        <div className="flex gap-2">
          <button
            className="rounded-md border px-2 py-1 text-xs"
            onClick={() => {
              setViewing(row);
              setViewOpen(true);
            }}
          >
            View
          </button>
          {(() => {
            const blockedEdit = Boolean(row.is_root) && !isRoot;
            const titleEdit = blockedEdit ? TOOLTIP.onlyRootManageRootUser : undefined;
            return (
              <button
                className="rounded-md border px-2 py-1 text-xs disabled:opacity-50"
                disabled={blockedEdit}
                title={titleEdit}
                onClick={() => {
                  if (blockedEdit) return;
                  setEditing(row);
                  setEditName(row.name);
                  setEditOpen(true);
                }}
              >
                Edit
              </button>
            );
          })()}
          {(() => {
            const blockedRoot = Boolean(row.is_root) && !isRoot;
            const blockedSelf = meId === row.id;
            const blockedDelete = blockedRoot || blockedSelf;
            const titleDelete = blockedSelf
              ? TOOLTIP.cannotDeleteSelf
              : blockedRoot
              ? TOOLTIP.onlyRootManageRootUser
              : undefined;
            return (
              <button
                className="rounded-md border px-2 py-1 text-xs text-red-600 disabled:opacity-50"
                disabled={blockedDelete}
                title={titleDelete}
                onClick={() => {
                  if (blockedDelete) return;
                  setDeleting(row);
                  setConfirmOpen(true);
                }}
              >
                Delete
              </button>
            );
          })()}
        </div>
      ),
    },
  ], [meId, isRoot]);

  useEffect(() => {
    const controller = new AbortController();
    async function fetchData() {
      setLoading(true);
      try {
        const params = new URLSearchParams({
          page: String(page),
          perPage: String(perPage),
          search: search || '',
          sortBy,
          sortDir,
          role: role || '',
          created_from: createdFrom || '',
          created_to: createdTo || '',
        });
        const res = await fetch(`/admin/users?${params.toString()}`, { signal: controller.signal });
        if (!res.ok) throw new Error(`Request failed ${res.status}`);
        const json = await res.json();
        setData(json.data as User[]);
        setMeta(json.meta);
      } catch (e: unknown) {
        const isAbort = e instanceof DOMException && e.name === 'AbortError';
        if (!isAbort) {
          const msg = e instanceof Error ? e.message : 'Failed to load users';
          show({ title: 'Error', description: msg });
        }
      } finally {
        setLoading(false);
      }
    }
    fetchData();
    return () => controller.abort();
  }, [page, perPage, search, sortBy, sortDir, role, createdFrom, createdTo, show]);

  useEffect(() => {
    let mounted = true;
    fetch('/admin/roles')
      .then((r) => r.json())
      .then((j) => { if (mounted) setRoles(j.data || []); })
      .catch(() => {});
    return () => { mounted = false; };
  }, []);

  useEffect(() => {
    // Keep per_page + current_page in meta synced when perPage changes
    setMeta((m) => ({ ...m, per_page: perPage, current_page: page }));
  }, [perPage, page]);

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title="Admin · Users" />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Users</h1>
          <Link href={route('dashboard')} className="text-sm underline">Back to Dashboard</Link>
        </div>

      <div className="flex flex-wrap gap-2 items-center">
        <input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search by name or email"
          className="w-64 rounded-md border px-3 py-1.5 text-sm"
        />
        <select
          value={role}
          onChange={(e) => setRole(e.target.value)}
          className="rounded-md border px-2 py-1 text-sm"
        >
          <option value="">All roles</option>
          {roles.map((r) => (
            <option key={r} value={r}>{r}</option>
          ))}
        </select>
        <input
          type="date"
          value={createdFrom}
          onChange={(e) => setCreatedFrom(e.target.value)}
          className="rounded-md border px-2 py-1 text-sm"
        />
        <input
          type="date"
          value={createdTo}
          onChange={(e) => setCreatedTo(e.target.value)}
          className="rounded-md border px-2 py-1 text-sm"
        />
        <button
          className="rounded-md border px-3 py-1.5 text-sm"
          onClick={() => { setSearch(''); setRole(''); setCreatedFrom(''); setCreatedTo(''); setPage(1); }}
        >
          Clear
        </button>
        <select
          value={perPage}
          onChange={(e) => setPerPage(Number(e.target.value))}
          className="rounded-md border px-2 py-1 text-sm"
        >
          {[5, 10, 25, 50].map((n) => (
            <option key={n} value={n}>{n} / page</option>
          ))}
        </select>
        <div className="text-xs text-muted-foreground">Total: {meta.total}</div>
      </div>

      <div className="overflow-hidden rounded-md border">
        <DataTable<User>
          columns={columns.map((c) => ({
            ...c,
            header: (
              <button className="flex items-center gap-1" onClick={() => setSort(String(c.key))}>
                {c.header}
                {String(c.key) === sortBy && (
                  <span className="text-[10px]">{sortDir === 'asc' ? '▲' : '▼'}</span>
                )}
              </button>
            ),
          }))}
          data={data}
          loading={loading}
          loadingComponent={<LoadingSpinner label="Loading users…" />}
          emptyComponent={<EmptyState title="No users found" description="Try adjusting filters or search." />}
          total={meta.total}
          page={meta.current_page}
          perPage={meta.per_page}
        />
      </div>

      <div className="flex items-center justify-end gap-2">
        <button
          disabled={meta.current_page <= 1}
          onClick={() => setPage(meta.current_page - 1)}
          className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-50"
        >
          Prev
        </button>
        <div className="text-sm">Page {meta.current_page} / {meta.last_page}</div>
        <button
          disabled={meta.current_page >= meta.last_page}
          onClick={() => setPage(meta.current_page + 1)}
          className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-50"
        >
          Next
        </button>
      </div>

      {/* Edit User Modal */}
      <Modal
        open={editOpen}
        onClose={() => setEditOpen(false)}
        title={editing ? `Edit user #${editing.id}` : 'Edit user'}
        footer={(
          <div className="flex items-center justify-end gap-2">
            <button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => setEditOpen(false)}>Cancel</button>
            <button
              className="rounded-md border px-3 py-1.5 text-sm"
              onClick={async () => {
                if (!editing) return;
                try {
                  const res = await fetch(`/admin/users/${editing.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ name: editName }),
                  });
                  if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    const msg = err?.message || `Update failed (${res.status})`;
                    throw new Error(msg);
                  }
                  show({ title: 'Saved', description: 'User updated successfully' });
                  setEditOpen(false);
                  // Refresh table
                  const params = new URLSearchParams({
                    page: String(page), perPage: String(perPage), search: search || '', sortBy, sortDir,
                    role: role || '', created_from: createdFrom || '', created_to: createdTo || '',
                  });
                  const refetch = await fetch(`/admin/users?${params.toString()}`);
                  const json = await refetch.json();
                  setData(json.data as User[]);
                  setMeta(json.meta);
                } catch (e: unknown) {
                  const msg = e instanceof Error ? e.message : 'Failed to update user';
                  show({ title: 'Error', description: msg });
                }
              }}
            >
              Save
            </button>
          </div>
        )}
      >
        <div className="space-y-2">
          <label className="block text-sm">Name</label>
          <input
            value={editName}
            onChange={(e) => setEditName(e.target.value)}
            className="w-full rounded-md border px-3 py-1.5 text-sm"
            placeholder="Full name"
          />
        </div>
      </Modal>

      {/* View User Modal */}
      <Modal
        open={viewOpen}
        onClose={() => setViewOpen(false)}
        title={viewing ? `User #${viewing.id}` : 'User'}
        footer={<button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => setViewOpen(false)}>Close</button>}
      >
        {viewing && (
          <div className="space-y-2 text-sm">
            <div><span className="font-medium">ID:</span> {viewing.id}</div>
            <div><span className="font-medium">Name:</span> {viewing.name}</div>
            <div><span className="font-medium">Email:</span> {viewing.email}</div>
            <div><span className="font-medium">Created:</span> {new Date(viewing.created_at).toLocaleString()}</div>
          </div>
        )}
      </Modal>

      {/* Confirm Delete Modal */}
      <Modal
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Confirm delete"
        footer={(
          <div className="flex items-center justify-end gap-2">
            <button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => setConfirmOpen(false)}>Cancel</button>
            <button
              className="rounded-md border px-3 py-1.5 text-sm text-red-600"
              onClick={async () => {
                if (!deleting) return;
                try {
                  const res = await fetch(`/admin/users/${deleting.id}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
                  if (!res.ok && res.status !== 204) throw new Error(`Delete failed (${res.status})`);
                  show({ title: 'Deleted', description: `User #${deleting.id} deleted` });
                  setConfirmOpen(false);
                  // Refresh table
                  const params = new URLSearchParams({ page: String(page), perPage: String(perPage), search: search || '', sortBy, sortDir, role: role || '', created_from: createdFrom || '', created_to: createdTo || '' });
                  const refetch = await fetch(`/admin/users?${params.toString()}`);
                  const json = await refetch.json();
                  setData(json.data as User[]);
                  setMeta(json.meta);
                } catch (e: unknown) {
                  const msg = e instanceof Error ? e.message : 'Failed to delete user';
                  show({ title: 'Error', description: msg });
                }
              }}
            >
              Delete
            </button>
          </div>
        )}
      >
        <div className="text-sm">Are you sure you want to delete this user? This action cannot be undone.</div>
      </Modal>
      </div>
    </AppLayout>
  );
}

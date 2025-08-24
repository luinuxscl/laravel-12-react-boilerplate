import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import LoadingSpinner from '@/components/ui/LoadingSpinner';
import EmptyState from '@/components/ui/EmptyState';
import ConfirmDialog from '@/components/ui/ConfirmDialog';

export default function AdminRolesPage() {
  type Role = { id: number; name: string };
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(false);
  const [creating, setCreating] = useState(false);
  const [newName, setNewName] = useState('');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const csrfToken = useMemo(() => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '', []);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    fetch('/admin/roles')
      .then((r) => r.json())
      .then((j) => { if (mounted) setRoles(Array.isArray(j.data) ? j.data : []); })
      .catch(() => {})
      .finally(() => setLoading(false));
    return () => { mounted = false; };
  }, []);

  async function createRole(e: React.FormEvent) {
    e.preventDefault();
    if (!newName.trim()) return;
    setCreating(true);
    try {
      const res = await fetch('/admin/roles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ name: newName.trim() }),
      });
      if (res.ok) {
        const j = await res.json();
        setRoles((prev) => [...prev, j.data]);
        setNewName('');
      }
    } finally {
      setCreating(false);
    }
  }

  function askDeleteRole(id: number) {
    setDeletingId(id);
    setConfirmOpen(true);
  }

  async function deleteRoleConfirmed() {
    if (deletingId == null) return;
    await fetch(`/admin/roles/${deletingId}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    setRoles((prev) => prev.filter((r) => r.id !== deletingId));
    setConfirmOpen(false);
    setDeletingId(null);
  }

  function startEdit(role: Role) {
    setEditingId(role.id);
    setEditName(role.name);
  }

  function cancelEdit() {
    setEditingId(null);
    setEditName('');
  }

  async function saveEdit(id: number) {
    const name = editName.trim();
    if (!name) return;
    const res = await fetch(`/admin/roles/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ name }),
    });
    if (res.ok) {
      const j = await res.json();
      setRoles((prev) => prev.map((r) => (r.id === id ? j.data : r)));
      cancelEdit();
    }
  }

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title="Admin · Roles" />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Roles</h1>
          <Link href={route('dashboard')} className="text-sm underline">Back to Dashboard</Link>
        </div>

        <form onSubmit={createRole} className="rounded-md border p-3 flex items-center gap-2">
          <input
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            placeholder="New role name"
            className="w-full rounded-md border px-3 py-2 text-sm bg-background"
          />
          <button disabled={creating || !newName.trim()} className="rounded-md border px-3 py-2 text-sm disabled:opacity-50">
            {creating ? 'Creating…' : 'Create'}
          </button>
        </form>

        <div className="rounded-md border overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-muted/40">
              <tr>
                <th className="px-3 py-2">ID</th>
                <th className="px-3 py-2">Name</th>
                <th className="px-3 py-2 w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={3}>
                    <LoadingSpinner label="Loading roles…" />
                  </td>
                </tr>
              )}
              {!loading && roles.length === 0 && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={3}>
                    <EmptyState title="No roles found" description="Create a new role to get started." />
                  </td>
                </tr>
              )}
              {!loading && roles.map((role) => (
                <tr key={role.id} className="border-t">
                  <td className="px-3 py-2 w-16">{role.id}</td>
                  <td className="px-3 py-2">
                    {editingId === role.id ? (
                      <input
                        value={editName}
                        onChange={(e) => setEditName(e.target.value)}
                        className="w-full rounded-md border px-2 py-1 text-sm bg-background"
                      />
                    ) : (
                      role.name
                    )}
                  </td>
                  <td className="px-3 py-2 text-right">
                    {editingId === role.id ? (
                      <div className="inline-flex gap-2">
                        <button onClick={() => saveEdit(role.id)} className="rounded-md border px-2 py-1 text-xs">Save</button>
                        <button onClick={cancelEdit} className="rounded-md border px-2 py-1 text-xs">Cancel</button>
                      </div>
                    ) : (
                      <div className="inline-flex gap-2">
                        <button onClick={() => startEdit(role)} className="rounded-md border px-2 py-1 text-xs">Edit</button>
                        <button onClick={() => askDeleteRole(role.id)} className="rounded-md border px-2 py-1 text-xs text-red-600">Delete</button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <ConfirmDialog
          open={confirmOpen}
          onClose={() => setConfirmOpen(false)}
          onConfirm={deleteRoleConfirmed}
          title="Delete role?"
          description="This action cannot be undone. Users with this role will lose the assignment."
          confirmLabel="Delete"
        />
      </div>
    </AppLayout>
  );
}

import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import LoadingSpinner from '@/components/ui/LoadingSpinner';
import EmptyState from '@/components/ui/EmptyState';
import ConfirmDialog from '@/components/ui/ConfirmDialog';
import { TOOLTIP } from '@/lib/perm-tooltips';
import { makeAuthHelpers } from '@/lib/auth';
import { useTranslation } from 'react-i18next';

export default function AdminRolesPage() {
  const { t } = useTranslation();
  type Role = { id: number; name: string };
  const { auth } = usePage().props as any;
  const isRoot: boolean = Boolean(auth?.isRoot);
  const { canManageRoles } = makeAuthHelpers({ roles: auth?.roles || [], isAdmin: !!auth?.isAdmin, isRoot: !!auth?.isRoot });
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(false);
  const [creating, setCreating] = useState(false);
  const [newName, setNewName] = useState('');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const csrfToken = useMemo(() => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '', []);
  const isRootRole = (name: string) => name?.toLowerCase() === 'root';

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
    if (!canManageRoles()) return;
    if (!newName.trim()) return;
    if (isRootRole(newName) && !isRoot) {
      // Bloquear creación de rol root por no-root
      return;
    }
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
    if (!canManageRoles()) return;
    const r = roles.find((x) => x.id === id);
    if (r && isRootRole(r.name) && !isRoot) {
      // Bloquear eliminación de rol root por no-root
      return;
    }
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
    if (!canManageRoles()) return;
    if (isRootRole(role.name) && !isRoot) {
      return;
    }
    setEditingId(role.id);
    setEditName(role.name);
  }

  function cancelEdit() {
    setEditingId(null);
    setEditName('');
  }

  async function saveEdit(id: number) {
    if (!canManageRoles()) return;
    const name = editName.trim();
    if (!name) return;
    if (isRootRole(name) && !isRoot) {
      return;
    }
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
        <Head title={`${t('nav.admin')} · ${t('roles.title')}`} />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">{t('roles.title')}</h1>
          <Link href={route('dashboard')} className="text-sm underline">{t('common.back_to_dashboard')}</Link>
        </div>

        <form onSubmit={createRole} className="rounded-md border p-3 flex items-center gap-2">
          <input
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            placeholder={t('roles.new_role_placeholder')}
            className="w-full rounded-md border px-3 py-2 text-sm bg-background"
            disabled={!canManageRoles()}
          />
          <button
            disabled={!canManageRoles() || creating || !newName.trim() || (!isRoot && isRootRole(newName))}
            title={!isRoot && isRootRole(newName) ? TOOLTIP.onlyRootManageRootRole : undefined}
            className="rounded-md border px-3 py-2 text-sm disabled:opacity-50"
          >
            {creating ? t('roles.creating') : t('roles.create')}
          </button>
        </form>

        <div className="rounded-md border overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-muted/40">
              <tr>
                <th className="px-3 py-2">{t('roles.columns.id')}</th>
                <th className="px-3 py-2">{t('roles.columns.name')}</th>
                <th className="px-3 py-2 w-32 text-right">{t('roles.columns.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={3}>
                    <LoadingSpinner label={t('roles.loading')} />
                  </td>
                </tr>
              )}
              {!loading && roles.length === 0 && (
                <tr>
                  <td className="px-3 py-2 text-muted-foreground" colSpan={3}>
                    <EmptyState title={t('roles.empty_title')} description={t('roles.empty_description')} />
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
                        <button
                          onClick={() => saveEdit(role.id)}
                          className="rounded-md border px-2 py-1 text-xs"
                          disabled={!canManageRoles() || (!isRoot && isRootRole(editName))}
                          title={!isRoot && isRootRole(editName) ? TOOLTIP.onlyRootManageRootRole : undefined}
                        >
                          {t('roles.actions.save')}
                        </button>
                        <button onClick={cancelEdit} className="rounded-md border px-2 py-1 text-xs">{t('roles.actions.cancel')}</button>
                      </div>
                    ) : (
                      <div className="inline-flex gap-2">
                        <button
                          onClick={() => startEdit(role)}
                          className="rounded-md border px-2 py-1 text-xs"
                          disabled={!canManageRoles() || (!isRoot && isRootRole(role.name))}
                          title={!isRoot && isRootRole(role.name) ? TOOLTIP.onlyRootManageRootRole : undefined}
                        >
                          {t('roles.actions.edit')}
                        </button>
                        <button
                          onClick={() => askDeleteRole(role.id)}
                          className="rounded-md border px-2 py-1 text-xs text-red-600"
                          disabled={!canManageRoles() || (!isRoot && isRootRole(role.name))}
                          title={!isRoot && isRootRole(role.name) ? TOOLTIP.onlyRootManageRootRole : undefined}
                        >
                          {t('roles.actions.delete')}
                        </button>
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
          title={t('roles.confirm.delete_title')}
          description={t('roles.confirm.delete_description')}
          confirmLabel={t('roles.confirm.delete_label')}
        />
      </div>
    </AppLayout>
  );
}

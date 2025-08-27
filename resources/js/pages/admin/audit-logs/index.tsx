import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import DataTable, { Column } from '@/components/tables/DataTable';
import { useDataTable } from '@/hooks/useDataTable';
import { useToast } from '@/hooks/useToast';
import LoadingSpinner from '@/components/ui/LoadingSpinner';
import EmptyState from '@/components/ui/EmptyState';
import Modal from '@/components/ui/Modal';
import { useTranslation } from 'react-i18next';

interface AuditLog {
  id: number;
  user_id: number | null;
  tenant_id: number | null;
  entity_type: string;
  entity_id: number | null;
  action: 'create' | 'update' | 'delete';
  changes: any;
  ip: string | null;
  user_agent: string | null;
  created_at: string;
}

export default function AdminAuditLogsPage() {
  const { t } = useTranslation();
  const { tenant } = usePage().props as any;
  const baseHeaders = useMemo(() => ({
    'X-Requested-With': 'XMLHttpRequest',
    ...(tenant?.slug ? { 'X-Tenant': tenant.slug } : {}),
  }), [tenant?.slug]);

  const { show } = useToast();
  const { page, perPage, setPage, setPerPage } = useDataTable({});

  const [data, setData] = useState<AuditLog[]>([]);
  const [loading, setLoading] = useState(false);
  const [meta, setMeta] = useState({ total: 0, per_page: perPage, current_page: page, last_page: 1 });

  // filters
  const [entityType, setEntityType] = useState<string>('');
  const [action, setAction] = useState<string>('');
  const [userId, setUserId] = useState<string>('');
  const [entityId, setEntityId] = useState<string>('');
  const [createdFrom, setCreatedFrom] = useState<string>('');
  const [createdTo, setCreatedTo] = useState<string>('');
  const [search, setSearch] = useState<string>('');

  const columns: Column<AuditLog>[] = useMemo(() => ([
    { key: 'id', header: 'ID' },
    { key: 'created_at', header: t('users.columns.created') || 'Created' },
    { key: 'user_id', header: 'User' },
    { key: 'tenant_id', header: 'Tenant' },
    { key: 'entity_type', header: 'Entity' },
    { key: 'entity_id', header: 'Entity ID' },
    { key: 'action', header: 'Action' },
    {
      key: 'changes', header: 'Changes', render: (row: AuditLog) => (
        <button
          className="rounded-md border px-2 py-1 text-xs"
          onClick={() => {
            setViewing(row);
            setViewOpen(true);
          }}
        >
          {t('actions.view')}
        </button>
      ),
    },
  ]), [t]);

  useEffect(() => {
    const controller = new AbortController();
    async function fetchData() {
      setLoading(true);
      try {
        const params = new URLSearchParams({
          page: String(page),
          perPage: String(perPage),
          entity_type: entityType || '',
          action: action || '',
          user_id: userId || '',
          entity_id: entityId || '',
          created_from: createdFrom || '',
          created_to: createdTo || '',
          search: search || '',
        });
        const res = await fetch(`/admin/audit-logs?${params.toString()}` , {
          signal: controller.signal,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json', ...baseHeaders },
        });
        if (!res.ok) throw new Error(`Request failed ${res.status}`);
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Unexpected response (not JSON)');
        const json = await res.json();
        setData(json.data as AuditLog[]);
        setMeta(json.meta);
      } catch (e: any) {
        if (e?.name !== 'AbortError') {
          show({ title: t('status.error'), description: e?.message || 'Error' });
        }
      } finally {
        setLoading(false);
      }
    }
    fetchData();
    return () => controller.abort();
  }, [page, perPage, entityType, action, userId, entityId, createdFrom, createdTo, search, baseHeaders, show, t]);

  useEffect(() => {
    setMeta((m) => ({ ...m, per_page: perPage, current_page: page }));
  }, [perPage, page]);

  const [viewOpen, setViewOpen] = useState(false);
  const [viewing, setViewing] = useState<AuditLog | null>(null);

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title={`Admin · Audit Logs`} />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Audit Logs</h1>
          <Link href={route('dashboard')} className="text-sm underline">{t('common.back_to_dashboard')}</Link>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('common.search_placeholder')} className="w-64 rounded-md border px-3 py-1.5 text-sm" />
          <input value={entityType} onChange={(e) => setEntityType(e.target.value)} placeholder="entity_type"
                 className="w-40 rounded-md border px-2 py-1 text-sm" />
          <input value={entityId} onChange={(e) => setEntityId(e.target.value)} placeholder="entity_id"
                 className="w-32 rounded-md border px-2 py-1 text-sm" />
          <select value={action} onChange={(e) => setAction(e.target.value)} className="rounded-md border px-2 py-1 text-sm">
            <option value="">action</option>
            <option value="create">create</option>
            <option value="update">update</option>
            <option value="delete">delete</option>
          </select>
          <input type="text" value={userId} onChange={(e) => setUserId(e.target.value)} placeholder="user_id" className="w-32 rounded-md border px-2 py-1 text-sm" />
          <input type="date" value={createdFrom} onChange={(e) => setCreatedFrom(e.target.value)} className="rounded-md border px-2 py-1 text-sm" />
          <input type="date" value={createdTo} onChange={(e) => setCreatedTo(e.target.value)} className="rounded-md border px-2 py-1 text-sm" />
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => { setSearch(''); setEntityType(''); setEntityId(''); setAction(''); setUserId(''); setCreatedFrom(''); setCreatedTo(''); setPage(1); }}>Clear</button>
          <select value={perPage} onChange={(e) => setPerPage(Number(e.target.value))} className="rounded-md border px-2 py-1 text-sm">
            {[5,10,25,50,100].map((n) => <option key={n} value={n}>{t('users.per_page', { count: n })}</option>)}
          </select>
          <div className="text-xs text-muted-foreground">Total: {meta.total}</div>
        </div>

        <div className="overflow-hidden rounded-md border">
          <DataTable<AuditLog>
            columns={columns}
            data={data}
            rowKey="id"
            loading={loading}
            loadingComponent={<LoadingSpinner label={t('users.loading')} />}
            emptyComponent={<EmptyState title={t('users.empty_title')} description={t('users.empty_description')} />}
            total={meta.total}
            page={meta.current_page}
            perPage={meta.per_page}
          />
        </div>

        <div className="flex items-center justify-end gap-2">
          <button disabled={meta.current_page <= 1} onClick={() => setPage(meta.current_page - 1)} className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-50">{t('actions.prev')}</button>
          <div className="text-sm">{meta.current_page} / {meta.last_page}</div>
          <button disabled={meta.current_page >= meta.last_page} onClick={() => setPage(meta.current_page + 1)} className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-50">{t('actions.next')}</button>
        </div>

        {/* View Changes Modal */}
        <Modal
          open={viewOpen}
          onClose={() => setViewOpen(false)}
          title={viewing ? `Log #${viewing.id}` : 'Log'}
          footer={<button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => setViewOpen(false)}>{t('actions.close')}</button>}
        >
          {viewing && (
            <div className="space-y-2 text-sm">
              <div><span className="font-medium">ID:</span> {viewing.id}</div>
              <div><span className="font-medium">User:</span> {String(viewing.user_id ?? '')}</div>
              <div><span className="font-medium">Tenant:</span> {String(viewing.tenant_id ?? '')}</div>
              <div><span className="font-medium">Entity:</span> {viewing.entity_type} #{String(viewing.entity_id ?? '')}</div>
              <div><span className="font-medium">Action:</span> {viewing.action}</div>
              <div><span className="font-medium">IP:</span> {viewing.ip}</div>
              <div><span className="font-medium">Agent:</span> {viewing.user_agent}</div>
              <div><span className="font-medium">At:</span> {new Date(viewing.created_at).toLocaleString()}</div>
              <div className="overflow-auto rounded-md border p-2">
                <pre className="text-xs whitespace-pre-wrap">{JSON.stringify(viewing.changes, null, 2)}</pre>
              </div>
            </div>
          )}
        </Modal>
      </div>
    </AppLayout>
  );
}

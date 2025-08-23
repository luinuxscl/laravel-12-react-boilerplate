import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import DataTable, { Column } from '@/components/tables/DataTable';
import { useDataTable } from '@/hooks/useDataTable';
import { useToast } from '@/hooks/useToast';

interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export default function AdminUsersPage() {
  const { page, perPage, search, sortBy, sortDir, setPage, setPerPage, setSearch, setSort, query } = useDataTable({});
  const { show } = useToast();
  const [data, setData] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  const [meta, setMeta] = useState({ total: 0, per_page: perPage, current_page: page, last_page: 1 });

  const columns: Column<User>[] = useMemo(() => [
    { key: 'id', header: 'ID' },
    { key: 'name', header: 'Name' },
    { key: 'email', header: 'Email' },
    { key: 'created_at', header: 'Created' },
  ], []);

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
        });
        const res = await fetch(`/admin/users?${params.toString()}`, { signal: controller.signal });
        if (!res.ok) throw new Error(`Request failed ${res.status}`);
        const json = await res.json();
        setData(json.data as User[]);
        setMeta(json.meta);
      } catch (e: any) {
        if (e.name !== 'AbortError') {
          show({ title: 'Error', description: e.message ?? 'Failed to load users' });
        }
      } finally {
        setLoading(false);
      }
    }
    fetchData();
    return () => controller.abort();
  }, [page, perPage, search, sortBy, sortDir]);

  useEffect(() => {
    // Keep per_page + current_page in meta synced when perPage changes
    setMeta((m) => ({ ...m, per_page: perPage, current_page: page }));
  }, [perPage, page]);

  return (
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
            ) as any,
          }))}
          data={data}
          loading={loading}
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
    </div>
  );
}

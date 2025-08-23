import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { useToast } from '@/hooks/useToast';
import AppLayout from '@/layouts/app-layout';

// Read CSRF token from Blade layout meta tag
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

type SettingsMap = Record<string, any>;

export default function AdminSettingsPage() {
  const { show } = useToast();
  const [settings, setSettings] = useState<SettingsMap>({});
  const [loading, setLoading] = useState(false);
  const [keyInput, setKeyInput] = useState('');
  const [valueInput, setValueInput] = useState<string>('{}');
  const [jsonError, setJsonError] = useState<string>('');

  async function load() {
    setLoading(true);
    try {
      const res = await fetch('/admin/settings');
      if (!res.ok) throw new Error(`Load failed (${res.status})`);
      const json = await res.json();
      setSettings(json.data || {});
    } catch (e: any) {
      show({ title: 'Error', description: e.message || 'Failed to load settings' });
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  function pretty(v: any) {
    try {
      return JSON.stringify(v, null, 2);
    } catch {
      return String(v);
    }
  }

  function parseValue(input: string) {
    // allow raw string as fallback if not valid JSON
    try {
      return JSON.parse(input);
    } catch (e: any) {
      return undefined;
    }
  }

  async function upsert() {
    setJsonError('');
    if (!keyInput.trim()) {
      setJsonError('Key is required');
      return;
    }
    const parsed = parseValue(valueInput);
    if (parsed === undefined && valueInput.trim() !== '') {
      setJsonError('Value must be valid JSON (or empty for null)');
      return;
    }
    try {
      const res = await fetch('/admin/settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ key: keyInput.trim(), value: valueInput.trim() === '' ? null : parsed }),
      });
      if (!res.ok) throw new Error(`Save failed (${res.status})`);
      show({ title: 'Saved', description: `Setting "${keyInput}" saved` });
      setKeyInput('');
      setValueInput('{}');
      await load();
    } catch (e: any) {
      show({ title: 'Error', description: e.message || 'Failed to save' });
    }
  }

  async function removeKey(key: string) {
    try {
      const res = await fetch(`/admin/settings/${encodeURIComponent(key)}`, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      if (!res.ok && res.status !== 204) throw new Error(`Delete failed (${res.status})`);
      show({ title: 'Deleted', description: `Setting "${key}" deleted` });
      await load();
    } catch (e: any) {
      show({ title: 'Error', description: e.message || 'Failed to delete' });
    }
  }

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title="Admin · Settings" />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">Settings</h1>
          <Link href={route('dashboard')} className="text-sm underline">Back to Dashboard</Link>
        </div>

      <div className="rounded-md border p-3 space-y-2">
        <div className="text-sm font-medium">Create / Update</div>
        <div className="flex flex-wrap gap-2 items-start">
          <input
            className="w-64 rounded-md border px-3 py-1.5 text-sm"
            placeholder="key (e.g. site.appearance)"
            value={keyInput}
            onChange={(e) => setKeyInput(e.target.value)}
          />
          <textarea
            className="w-full max-w-xl rounded-md border px-3 py-1.5 text-sm font-mono"
            placeholder='JSON value (e.g. {"theme":"dark"})'
            rows={4}
            value={valueInput}
            onChange={(e) => setValueInput(e.target.value)}
          />
        </div>
        {jsonError && <div className="text-sm text-red-600">{jsonError}</div>}
        <div className="flex items-center gap-2">
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={upsert}>Save</button>
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => { setKeyInput(''); setValueInput('{}'); setJsonError(''); }}>Clear</button>
        </div>
      </div>

      <div className="rounded-md border overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b">
              <th className="px-3 py-2 font-medium">Key</th>
              <th className="px-3 py-2 font-medium">Value</th>
              <th className="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td className="px-3 py-6" colSpan={3}>Loading...</td>
              </tr>
            )}
            {!loading && Object.keys(settings).length === 0 && (
              <tr>
                <td className="px-3 py-6 text-center text-muted-foreground" colSpan={3}>No settings</td>
              </tr>
            )}
            {!loading && Object.entries(settings).map(([key, value]) => (
              <tr key={key} className="border-b align-top">
                <td className="px-3 py-2 font-medium">{key}</td>
                <td className="px-3 py-2"><pre className="whitespace-pre-wrap text-xs">{pretty(value)}</pre></td>
                <td className="px-3 py-2">
                  <div className="flex gap-2">
                    <button
                      className="rounded-md border px-2 py-1 text-xs"
                      onClick={() => { setKeyInput(key); setValueInput(pretty(value)); }}
                    >Edit</button>
                    <button
                      className="rounded-md border px-2 py-1 text-xs text-red-600"
                      onClick={() => removeKey(key)}
                    >Delete</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      </div>
    </AppLayout>
  );
}

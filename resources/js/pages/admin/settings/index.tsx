import React, { useCallback, useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useToast } from '@/hooks/useToast';
import AppLayout from '@/layouts/app-layout';
import { makeAuthHelpers } from '@/lib/auth';
import { useTranslation } from 'react-i18next';

// Read CSRF token from Blade layout meta tag
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

type SettingsMap = Record<string, unknown>;

export default function AdminSettingsPage() {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const { canManageSettings } = makeAuthHelpers({ roles: auth?.roles || [], isAdmin: !!auth?.isAdmin, isRoot: !!auth?.isRoot });
  const { show } = useToast();
  const [settings, setSettings] = useState<SettingsMap>({});
  const [loading, setLoading] = useState(false);
  const [keyInput, setKeyInput] = useState('');
  const [valueInput, setValueInput] = useState<string>('{}');
  const [jsonError, setJsonError] = useState<string>('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch('/admin/settings');
      if (!res.ok) throw new Error(`Load failed (${res.status})`);
      const json = await res.json();
      setSettings(json.data || {});
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : t('settingsPage.table.loading');
      show({ title: t('status.error'), description: msg });
    } finally {
      setLoading(false);
    }
  }, [show, t]);

  useEffect(() => { load(); }, [load]);

  function pretty(v: unknown) {
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
    } catch {
      return undefined;
    }
  }

  async function upsert() {
    if (!canManageSettings()) return;
    setJsonError('');
    if (!keyInput.trim()) {
      setJsonError(t('settingsPage.errors.key_required'));
      return;
    }
    const parsed = parseValue(valueInput);
    if (parsed === undefined && valueInput.trim() !== '') {
      setJsonError(t('settingsPage.errors.value_invalid'));
      return;
    }
    try {
      const res = await fetch('/admin/settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ key: keyInput.trim(), value: valueInput.trim() === '' ? null : parsed }),
      });
      if (!res.ok) throw new Error(`Save failed (${res.status})`);
      show({ title: t('status.saved'), description: t('settingsPage.toasts.saved_desc', { key: keyInput }) });
      setKeyInput('');
      setValueInput('{}');
      await load();
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : t('status.error');
      show({ title: t('status.error'), description: msg });
    }
  }

  async function removeKey(key: string) {
    if (!canManageSettings()) return;
    try {
      const res = await fetch(`/admin/settings/${encodeURIComponent(key)}`, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      if (!res.ok && res.status !== 204) throw new Error(`Delete failed (${res.status})`);
      show({ title: t('status.deleted'), description: t('settingsPage.toasts.deleted_desc', { key }) });
      await load();
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : t('status.error');
      show({ title: t('status.error'), description: msg });
    }
  }

  return (
    <AppLayout>
      <div className="space-y-4 p-4">
        <Head title={`${t('nav.admin')} · ${t('nav.settings')}`} />
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold">{t('settingsPage.title')}</h1>
          <Link href={route('dashboard')} className="text-sm underline">{t('common.back_to_dashboard')}</Link>
        </div>

      <div className="rounded-md border p-3 space-y-2">
        <div className="text-sm font-medium">{t('settingsPage.section_title')}</div>
        <div className="flex flex-wrap gap-2 items-start">
          <input
            className="w-64 rounded-md border px-3 py-1.5 text-sm"
            placeholder={t('settingsPage.key_placeholder')}
            value={keyInput}
            onChange={(e) => setKeyInput(e.target.value)}
            disabled={!canManageSettings()}
          />
          <textarea
            className="w-full max-w-xl rounded-md border px-3 py-1.5 text-sm font-mono"
            placeholder={t('settingsPage.value_placeholder')}
            rows={4}
            value={valueInput}
            onChange={(e) => setValueInput(e.target.value)}
            disabled={!canManageSettings()}
          />
        </div>
        {jsonError && <div className="text-sm text-red-600">{jsonError}</div>}
        <div className="flex items-center gap-2">
          <button className="rounded-md border px-3 py-1.5 text-sm disabled:opacity-50" onClick={upsert} disabled={!canManageSettings()}>{t('settingsPage.buttons.save')}</button>
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={() => { setKeyInput(''); setValueInput('{}'); setJsonError(''); }}>{t('settingsPage.buttons.clear')}</button>
        </div>
      </div>

      <div className="rounded-md border overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b">
              <th className="px-3 py-2 font-medium">{t('settingsPage.table.key')}</th>
              <th className="px-3 py-2 font-medium">{t('settingsPage.table.value')}</th>
              <th className="px-3 py-2 font-medium">{t('settingsPage.table.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td className="px-3 py-6" colSpan={3}>{t('settingsPage.table.loading')}</td>
              </tr>
            )}
            {!loading && Object.keys(settings).length === 0 && (
              <tr>
                <td className="px-3 py-6 text-center text-muted-foreground" colSpan={3}>{t('settingsPage.table.empty')}</td>
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
                      disabled={!canManageSettings()}
                    >{t('settingsPage.buttons.edit')}</button>
                    <button
                      className="rounded-md border px-2 py-1 text-xs text-red-600"
                      onClick={() => removeKey(key)}
                      disabled={!canManageSettings()}
                    >{t('settingsPage.buttons.delete')}</button>
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

import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';

export default function AdminBrandingPage() {
    const { t } = useTranslation();
    const page = usePage<SharedData>();
    const app = (page.props.app || {}) as SharedData['app'];

    const [siteName, setSiteName] = useState<string>(app?.name || '');
    const [theme, setTheme] = useState<'system' | 'light' | 'dark'>(app?.appearance?.theme || 'system');

    const [logoUrl, setLogoUrl] = useState<string | null>(app?.brand?.logo_url || null);
    const [faviconUrl, setFaviconUrl] = useState<string | null>(app?.brand?.favicon_url || null);

    useEffect(() => {
        setSiteName(app?.name || '');
        setTheme((app?.appearance?.theme as 'system' | 'light' | 'dark') || 'system');
        setLogoUrl(app?.brand?.logo_url || null);
        setFaviconUrl(app?.brand?.favicon_url || null);
    }, [app]);

    const csrf = (typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : '') || '';

    const updateSetting = async (key: string, value: unknown) => {
        await fetch('/admin/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ key, value }),
        });
    };

    const handleSaveBasics = async () => {
        await updateSetting('site.name', siteName);
        await updateSetting('site.appearance', { theme });
        window.location.reload();
    };

    const upload = async (endpoint: string, file: File) => {
        const fd = new FormData();
        fd.append('file', file);
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: fd,
        });
        if (!res.ok) throw new Error('Upload failed');
        const data = await res.json();
        return data?.data?.url as string;
    };

    const handleLogoChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const url = await upload('/admin/branding/logo', file);
        setLogoUrl(url);
    };

    const handleFaviconChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const url = await upload('/admin/branding/favicon', file);
        setFaviconUrl(url);
    };

    const resetLogo = async () => {
        await updateSetting('site.brand', { logo_url: null, favicon_url: faviconUrl });
        setLogoUrl(null);
    };

    const resetFavicon = async () => {
        await updateSetting('site.brand', { logo_url: logoUrl, favicon_url: null });
        setFaviconUrl(null);
    };

    // Debug state
    const [settingsDump, setSettingsDump] = useState<any>(null);
    const [logoTest, setLogoTest] = useState<{ url?: string; status?: number; ok?: boolean; error?: string } | null>(
        null,
    );
    const [faviconTest, setFaviconTest] = useState<{
        url?: string;
        status?: number;
        ok?: boolean;
        error?: string;
    } | null>(null);

    const testFetch = async (url?: string | null) => {
        if (!url) return { error: 'URL vacía' } as const;
        try {
            const res = await fetch(url, { method: 'GET' });
            return { url, status: res.status, ok: res.ok } as const;
        } catch (e: any) {
            return { url, error: e?.message || 'Error en fetch' } as const;
        }
    };

    // Normalizar URLs a origin actual si path es /storage/*
    const normalizeUrl = (raw?: string | null) => {
        if (!raw || typeof window === 'undefined') return raw || null;
        try {
            const u = new URL(raw, window.location.origin);
            if (u.pathname.startsWith('/storage/')) return `${window.location.origin}${u.pathname}`;
            return u.toString();
        } catch {
            return raw;
        }
    };
    const normalizedLogoUrl = normalizeUrl(logoUrl);
    const normalizedFaviconUrl = normalizeUrl(faviconUrl || undefined);

    // Preview URLs with cache-busting when the URL changes
    const [logoPreviewUrl, setLogoPreviewUrl] = useState<string | null>(normalizedLogoUrl || null);
    const [faviconPreviewUrl, setFaviconPreviewUrl] = useState<string | null>(normalizedFaviconUrl || null);
    useEffect(() => {
        if (!normalizedLogoUrl) return setLogoPreviewUrl(null);
        const sep = normalizedLogoUrl.includes('?') ? '&' : '?';
        setLogoPreviewUrl(`${normalizedLogoUrl}${sep}v=${Date.now()}`);
    }, [normalizedLogoUrl]);
    useEffect(() => {
        if (!normalizedFaviconUrl) return setFaviconPreviewUrl(null);
        const sep = normalizedFaviconUrl.includes('?') ? '&' : '?';
        setFaviconPreviewUrl(`${normalizedFaviconUrl}${sep}v=${Date.now()}`);
    }, [normalizedFaviconUrl]);

    // Image error fallbacks
    const [logoImgError, setLogoImgError] = useState(false);
    const [faviconImgError, setFaviconImgError] = useState(false);
    useEffect(() => {
        setLogoImgError(false);
    }, [normalizedLogoUrl]);
    useEffect(() => {
        setFaviconImgError(false);
    }, [normalizedFaviconUrl]);

    const refreshSettingsDump = async () => {
        try {
            const res = await fetch('/admin/settings', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            setSettingsDump(json?.data ?? json);
        } catch (e: any) {
            setSettingsDump({ error: e?.message || 'Error cargando settings' });
        }
    };

    return (
        <div className="mx-auto w-full max-w-3xl space-y-8 p-6">
            <Head title={t('branding.title')} />
            <h1 className="text-2xl font-semibold">{t('branding.title')}</h1>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">{t('branding.basic')}</h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="site-name">{t('branding.site_name')}</Label>
                        <Input id="site-name" value={siteName} onChange={(e) => setSiteName(e.target.value)} />
                    </div>
                    <div>
                        <Label>{t('branding.theme')}</Label>
                        <Select value={theme} onValueChange={(v: 'system' | 'light' | 'dark') => setTheme(v)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('branding.theme')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="system">{t('branding.theme_system')}</SelectItem>
                                <SelectItem value="light">{t('branding.theme_light')}</SelectItem>
                                <SelectItem value="dark">{t('branding.theme_dark')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div>
                    <Button onClick={handleSaveBasics}>{t('actions.save')}</Button>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">{t('branding.logo')}</h2>
                <div className="flex items-center gap-4">
                    <div className="flex size-16 items-center justify-center overflow-hidden rounded bg-neutral-100 p-1 dark:bg-neutral-800">
                        {logoPreviewUrl && !logoImgError ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                                src={logoPreviewUrl}
                                alt={t('branding.logo_alt')}
                                className="max-h-full max-w-full object-contain"
                                onError={() => setLogoImgError(true)}
                            />
                        ) : (
                            <div className="text-sm text-neutral-500">{t('branding.no_logo')}</div>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Input type="file" accept=".png,.svg,.webp" onChange={handleLogoChange} />
                        <Button variant="outline" onClick={resetLogo} disabled={!logoUrl}>{t('actions.remove')}</Button>
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">{t('branding.favicon')}</h2>
                <div className="flex items-center gap-4">
                    <div className="flex size-12 items-center justify-center overflow-hidden rounded bg-neutral-100 p-1 dark:bg-neutral-800">
                        {faviconPreviewUrl && !faviconImgError ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                                src={faviconPreviewUrl}
                                alt={t('branding.favicon_alt')}
                                className="max-h-full max-w-full object-contain"
                                onError={() => setFaviconImgError(true)}
                            />
                        ) : (
                            <div className="text-sm text-neutral-500">{t('branding.no_favicon')}</div>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Input type="file" accept=".ico,.png,.svg" onChange={handleFaviconChange} />
                        <Button variant="outline" onClick={resetFavicon} disabled={!faviconUrl}>{t('actions.remove')}</Button>
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">{t('branding.debug')}</h2>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <div className="text-sm text-neutral-400">{t('branding.current_values')}</div>
                        <pre className="overflow-auto rounded bg-neutral-900 p-3 text-xs text-neutral-100 dark:bg-neutral-800">
{JSON.stringify({
    siteName,
    theme,
    logoUrl,
    normalizedLogoUrl,
    faviconUrl,
    normalizedFaviconUrl,
    origin: typeof window !== 'undefined' ? window.location.origin : undefined,
}, null, 2)}
                        </pre>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={async () => setLogoTest(await testFetch(logoUrl))}
                            disabled={!logoUrl}
                            title={t('branding.test_logo')}
                        >
                            {t('branding.test_logo')}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setLogoTest(await testFetch(normalizedLogoUrl))}
                            disabled={!normalizedLogoUrl}
                            title={t('branding.test_logo_normalized')}
                        >
                            {t('branding.test_logo_normalized')}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setFaviconTest(await testFetch(faviconUrl))}
                            disabled={!faviconUrl}
                            title={t('branding.test_favicon')}
                        >
                            {t('branding.test_favicon')}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setFaviconTest(await testFetch(normalizedFaviconUrl))}
                            disabled={!normalizedFaviconUrl}
                            title={t('branding.test_favicon_normalized')}
                        >
                            {t('branding.test_favicon_normalized')}
                        </Button>
                        <Button variant="secondary" onClick={refreshSettingsDump} title={t('branding.refresh_settings')}>
                            {t('branding.refresh_settings')}
                        </Button>
                    </div>

                    {(logoTest || faviconTest) && (
                        <div className="grid gap-2">
                            <div className="text-sm text-neutral-400">{t('branding.test_results')}</div>
                            <pre className="overflow-auto rounded bg-neutral-900 p-3 text-xs text-neutral-100 dark:bg-neutral-800">
{JSON.stringify({ logoTest, faviconTest }, null, 2)}
                            </pre>
                        </div>
                    )}

                    {settingsDump && (
                        <div className="grid gap-2">
                            <div className="text-sm text-neutral-400">{t('branding.settings_dump')}</div>
                            <pre className="overflow-auto rounded bg-neutral-900 p-3 text-xs text-neutral-100 dark:bg-neutral-800">
{JSON.stringify(settingsDump, null, 2)}
                            </pre>
                        </div>
                    )}
                </div>
            </section>
        </div>
    );
}

// Layout y breadcrumbs
AdminBrandingPage.layout = (page: any) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Settings', href: '/admin/settings-ui' },
            { title: 'Branding', href: '/admin/branding-ui' },
        ]}
    >
        {page}
    </AppLayout>
);

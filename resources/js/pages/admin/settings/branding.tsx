import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function AdminBrandingPage() {
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
            <Head title="Branding" />
            <h1 className="text-2xl font-semibold">Branding</h1>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">Básico</h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="site-name">Nombre del sitio</Label>
                        <Input id="site-name" value={siteName} onChange={(e) => setSiteName(e.target.value)} />
                    </div>
                    <div>
                        <Label>Tema</Label>
                        <Select value={theme} onValueChange={(v) => setTheme(v as any)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Tema" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="system">Sistema</SelectItem>
                                <SelectItem value="light">Claro</SelectItem>
                                <SelectItem value="dark">Oscuro</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div>
                    <Button onClick={handleSaveBasics}>Guardar</Button>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">Logo</h2>
                <div className="flex items-center gap-4">
                    <div className="flex size-16 items-center justify-center overflow-hidden rounded bg-neutral-100 p-1 dark:bg-neutral-800">
                        {logoPreviewUrl && !logoImgError ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                                src={logoPreviewUrl}
                                alt="Logo"
                                className="max-h-full max-w-full object-contain"
                                onError={() => setLogoImgError(true)}
                            />
                        ) : (
                            <div className="text-sm text-neutral-500">Sin logo</div>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Input type="file" accept=".png,.svg,.webp" onChange={handleLogoChange} />
                        <Button variant="outline" onClick={resetLogo} disabled={!logoUrl}>
                            Quitar
                        </Button>
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">Favicon</h2>
                <div className="flex items-center gap-4">
                    <div className="flex size-12 items-center justify-center overflow-hidden rounded bg-neutral-100 p-1 dark:bg-neutral-800">
                        {faviconPreviewUrl && !faviconImgError ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                                src={faviconPreviewUrl}
                                alt="Favicon"
                                className="max-h-full max-w-full object-contain"
                                onError={() => setFaviconImgError(true)}
                            />
                        ) : (
                            <div className="text-sm text-neutral-500">Sin favicon</div>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Input type="file" accept=".ico,.png,.svg" onChange={handleFaviconChange} />
                        <Button variant="outline" onClick={resetFavicon} disabled={!faviconUrl}>
                            Quitar
                        </Button>
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-lg border p-4">
                <h2 className="text-lg font-medium">Depuración</h2>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <div className="text-sm text-neutral-400">Valores actuales</div>
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
                            title="GET logo URL"
                        >
                            Probar logo
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setLogoTest(await testFetch(normalizedLogoUrl))}
                            disabled={!normalizedLogoUrl}
                            title="GET normalized logo URL"
                        >
                            Probar logo normalizado
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setFaviconTest(await testFetch(faviconUrl))}
                            disabled={!faviconUrl}
                            title="GET favicon URL"
                        >
                            Probar favicon
                        </Button>
                        <Button
                            variant="outline"
                            onClick={async () => setFaviconTest(await testFetch(normalizedFaviconUrl))}
                            disabled={!normalizedFaviconUrl}
                            title="GET normalized favicon URL"
                        >
                            Probar favicon normalizado
                        </Button>
                        <Button variant="secondary" onClick={refreshSettingsDump} title="GET /admin/settings">
                            Refrescar settings
                        </Button>
                    </div>

                    {(logoTest || faviconTest) && (
                        <div className="grid gap-2">
                            <div className="text-sm text-neutral-400">Resultados de prueba</div>
                            <pre className="overflow-auto rounded bg-neutral-900 p-3 text-xs text-neutral-100 dark:bg-neutral-800">
{JSON.stringify({ logoTest, faviconTest }, null, 2)}
                            </pre>
                        </div>
                    )}

                    {settingsDump && (
                        <div className="grid gap-2">
                            <div className="text-sm text-neutral-400">Dump de /admin/settings</div>
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

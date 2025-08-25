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
                        {logoUrl ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img src={logoUrl} alt="Logo" className="max-h-full max-w-full object-contain" />
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
                        {faviconUrl ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img src={faviconUrl} alt="Favicon" className="max-h-full max-w-full object-contain" />
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
        </div>
    );
}

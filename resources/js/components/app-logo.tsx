import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    const page = usePage<SharedData>();
    const app = (page.props.app || {}) as SharedData['app'];
    const logoUrl = app?.brand?.logo_url || null;
    // Normalize URL to current origin if needed (dev often differs by port)
    let displayLogoUrl: string | null = logoUrl;
    try {
        if (logoUrl && typeof window !== 'undefined') {
            const u = new URL(logoUrl, window.location.origin);
            // If absolute URL with a different origin but same path under /storage,
            // rebuild using current origin to avoid port/origin mismatches in dev.
            if (u.pathname.startsWith('/storage/')) {
                displayLogoUrl = `${window.location.origin}${u.pathname}`;
            } else {
                displayLogoUrl = u.toString();
            }
        }
    } catch {
        // keep original if parsing fails
        displayLogoUrl = logoUrl;
    }
    const [imgError, setImgError] = useState(false as boolean);
    const name = app?.name || 'Laravel Starter Kit';

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {displayLogoUrl && !imgError ? (
                    <img
                        src={displayLogoUrl}
                        alt={name}
                        className="h-full w-full object-cover"
                        onError={() => setImgError(true)}
                    />
                ) : (
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">{name}</span>
            </div>
        </>
    );
}

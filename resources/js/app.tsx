import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { ToastProvider } from './hooks/useToast';
import './lib/i18n';
import i18n from 'i18next';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Sync initial locale from server-provided Inertia props
        const initialLocale = ((props as any)?.initialPage?.props as any)?.locale ?? 'en';
        void i18n.changeLanguage(initialLocale);

        // Update language on every successful Inertia navigation/visit
        router.on('success', (event: any) => {
            const nextLocale = ((event?.detail?.page?.props) as any)?.locale;
            if (nextLocale && i18n.language !== nextLocale) {
                void i18n.changeLanguage(nextLocale);
            }
        });

        root.render(
            <ToastProvider>
                <App {...props} />
            </ToastProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

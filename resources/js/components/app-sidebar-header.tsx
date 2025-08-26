import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SharedData } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const page = usePage<SharedData>();
    const currentLocale = page.props.locale ?? 'en';
    const { t } = useTranslation();

    const setLocale = (locale: 'es' | 'en') => {
        if (locale === currentLocale) return;
        router.post('/locale', { locale }, { preserveScroll: true });
    };

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="px-2 text-xs md:text-sm">
                            {currentLocale.toUpperCase()}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => setLocale('es')}>
                            <span className="mr-2 text-muted-foreground">ES</span>
                            {t('language.spanish')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setLocale('en')}>
                            <span className="mr-2 text-muted-foreground">EN</span>
                            {t('language.english')}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ $siteName ?? config('app.name', 'Laravel') }}</title>

        @if (!empty($faviconUrl))
            @php
                $fav = $faviconUrl;
                $favPath = parse_url($fav, PHP_URL_PATH) ?? $fav;
                // Normalizar a origen actual si es /storage/* (evita mismatch de puerto en dev)
                if (str_starts_with($favPath, '/storage/')) {
                    $fav = request()->getSchemeAndHttpHost() . $favPath;
                }
                $ext = strtolower(pathinfo($favPath, PATHINFO_EXTENSION));
                $sep = str_contains($fav, '?') ? '&' : '?';
                // cache-busting para forzar actualización del favicon en la pestaña
                $favVer = $fav . $sep . 'v=' . time();
            @endphp
            @if ($ext === 'ico')
                <link rel="icon" href="{{ $favVer }}" sizes="any" type="image/x-icon">
                <link rel="shortcut icon" href="{{ $favVer }}" type="image/x-icon">
            @elseif ($ext === 'svg')
                <link rel="icon" href="{{ $favVer }}" type="image/svg+xml">
            @else
                <link rel="icon" href="{{ $favVer }}" type="image/png">
            @endif
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

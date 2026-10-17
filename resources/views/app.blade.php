<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'dark') !== 'light'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0E0F11">

        {{-- Inline script to apply the saved appearance immediately (dark by default) --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "dark" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    document.documentElement.classList.toggle('dark', prefersDark);
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #F2F2F0;
            }

            html.dark {
                background-color: #0E0F11;
            }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Tarmac') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

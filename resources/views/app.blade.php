<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Panel admin untuk memantau langganan pengguna aplikasi POS.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{--
            Terapkan tema sebelum React memuat, supaya tidak ada kedipan putih.
            Kunci di bawah HARUS sama dengan `KUNCI_TEMA` di stores/temaStore.ts.
        --}}
        <script>
            (function () {
                try {
                    var tersimpan = localStorage.getItem('tema-admin-pos')
                    var tema = tersimpan ? JSON.parse(tersimpan).state.tema : 'sistem'
                    var gelap =
                        tema === 'gelap' ||
                        (tema === 'sistem' &&
                            window.matchMedia('(prefers-color-scheme: dark)').matches)
                    if (gelap) document.documentElement.classList.add('dark')
                } catch (e) {
                    /* abaikan — tema default terang */
                }
            })()
        </script>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Jualin Aja') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

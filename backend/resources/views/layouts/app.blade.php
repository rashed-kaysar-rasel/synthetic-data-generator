<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>@yield('title', 'Synthetic Data Generator')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-background text-foreground">
        <main class="min-h-screen bg-background flex flex-col items-center">
            <div class="w-full max-w-4xl p-4">
                @yield('content')
            </div>
        </main>
    </body>
</html>

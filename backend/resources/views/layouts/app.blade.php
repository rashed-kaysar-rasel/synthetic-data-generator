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
            <header class="w-full border-b border-slate-200 bg-white/80 backdrop-blur">
                <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('generator.index') }}"
                            class="text-base font-semibold tracking-tight text-slate-900 hover:text-slate-700">
                            Synthetic Data Generator
                        </a>
                        <span class="hidden text-xs font-medium uppercase tracking-[0.2em] text-slate-400 sm:inline">
                            Studio
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        @auth
                            <span class="hidden text-slate-500 sm:inline">Signed in as</span>
                            <span class="font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                            <a class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:border-slate-400 hover:bg-slate-50"
                                href="{{ route('profile.show') }}">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:border-slate-400 hover:bg-slate-50">
                                    Log out
                                </button>
                            </form>
                        @else
                            <a class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-800 hover:border-slate-400 hover:bg-slate-50"
                                href="{{ route('login') }}">Log in</a>
                            <a class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800"
                                href="{{ route('register') }}">Register</a>
                        @endauth
                    </div>
                </div>
            </header>
            <div class="w-full max-w-6xl p-4 sm:p-6">
                @yield('content')
            </div>
        </main>
        @yield('scripts')
    </body>
</html>

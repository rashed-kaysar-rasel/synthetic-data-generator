@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Log In</h1>
        <p class="mt-1 text-sm text-slate-600">Access your account.</p>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <form class="mt-6 space-y-4" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700" for="password">Password</label>
                <input id="password" name="password" type="password"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center justify-between text-sm text-slate-600">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-slate-300" />
                    Remember me
                </label>
                <a class="font-medium text-slate-900 hover:underline" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            </div>
            <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Log In
            </button>
        </form>

        <p class="mt-4 text-sm text-slate-600">
            Don't have an account?
            <a class="font-medium text-slate-900 hover:underline" href="{{ route('register') }}">Register</a>
        </p>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Create Account</h1>
        <p class="mt-1 text-sm text-slate-600">Register with your name and email.</p>

        <form class="mt-6 space-y-4" method="POST" action="{{ route('register.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700" for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
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
            <div>
                <label class="text-sm font-medium text-slate-700" for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
            </div>
            <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Register
            </button>
        </form>

        <p class="mt-4 text-sm text-slate-600">
            Already have an account?
            <a class="font-medium text-slate-900 hover:underline" href="{{ route('login') }}">Log in</a>
        </p>
    </div>
@endsection

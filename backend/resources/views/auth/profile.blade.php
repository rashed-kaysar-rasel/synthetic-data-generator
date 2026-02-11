@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="mx-auto max-w-xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Profile</h1>
        <p class="mt-1 text-sm text-slate-600">Update your account details.</p>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <form class="mt-6 space-y-4" method="POST" action="{{ route('profile.update') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700" for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-800">Change Password</p>
                <p class="mt-1 text-xs text-slate-500">Leave blank to keep your current password.</p>

                <div class="mt-3 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="current_password">Current Password</label>
                        <input id="current_password" name="current_password" type="password"
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="password">New Password</label>
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
                </div>
            </div>

            <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Save Changes
            </button>
        </form>
    </div>
@endsection

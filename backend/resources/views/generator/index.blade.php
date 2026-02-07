@extends('layouts.app')

@section('title', 'Upload Schema')

@section('content')
    <div class="flex justify-center items-center min-h-[70vh]">
        <div class="w-full max-w-3xl space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h1 class="text-xl font-semibold text-slate-900">Upload SQL Schema</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Upload a SQL DDL file (`.sql`) that contains `CREATE TABLE` statements.
                    </p>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('schema.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="ddl_file" class="text-sm font-medium text-slate-700">DDL File</label>
                            <input
                                id="ddl_file"
                                name="ddl_file"
                                type="file"
                                class="mt-2 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-800 file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                                required
                            />
                            @if ($errors->has('ddl_file'))
                                <p class="mt-2 text-xs text-red-600">{{ $errors->first('ddl_file') }}</p>
                            @endif
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Upload and Configure
                        </button>
                    </form>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-xl font-semibold text-slate-900">Connect Database</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Provide connection details to extract tables automatically.
                    </p>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('schema.connect') }}" class="space-y-4">
                        @csrf
                        @if ($errors->has('db_connection'))
                            <p class="text-xs text-red-600">{{ $errors->first('db_connection') }}</p>
                        @endif
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="db_driver" class="text-sm font-medium text-slate-700">Database Type</label>
                                <select
                                    id="db_driver"
                                    name="db_driver"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                    required
                                >
                                    <option value="mysql" @selected(old('db_driver') === 'mysql')>MySQL</option>
                                    <option value="pgsql" @selected(old('db_driver') === 'pgsql')>PostgreSQL</option>
                                </select>
                                @if ($errors->has('db_driver'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_driver') }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="db_host" class="text-sm font-medium text-slate-700">Host</label>
                                <input
                                    id="db_host"
                                    name="db_host"
                                    type="text"
                                    value="{{ old('db_host') }}"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                    required
                                />
                                @if ($errors->has('db_host'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_host') }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="db_port" class="text-sm font-medium text-slate-700">Port</label>
                                <input
                                    id="db_port"
                                    name="db_port"
                                    type="number"
                                    value="{{ old('db_port') }}"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                />
                                @if ($errors->has('db_port'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_port') }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="db_database" class="text-sm font-medium text-slate-700">Database Name</label>
                                <input
                                    id="db_database"
                                    name="db_database"
                                    type="text"
                                    value="{{ old('db_database') }}"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                    required
                                />
                                @if ($errors->has('db_database'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_database') }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="db_username" class="text-sm font-medium text-slate-700">Username</label>
                                <input
                                    id="db_username"
                                    name="db_username"
                                    type="text"
                                    value="{{ old('db_username') }}"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                    required
                                />
                                @if ($errors->has('db_username'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_username') }}</p>
                                @endif
                            </div>
                            <div>
                                <label for="db_password" class="text-sm font-medium text-slate-700">Password</label>
                                <input
                                    id="db_password"
                                    name="db_password"
                                    type="password"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900"
                                />
                                @if ($errors->has('db_password'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('db_password') }}</p>
                                @endif
                            </div>
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Connect and Configure
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

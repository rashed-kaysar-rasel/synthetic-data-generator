@extends('layouts.app')

@section('title', 'Upload Schema')

@section('content')
    <div class="flex justify-center items-center min-h-[70vh]">
        <div class="w-full max-w-lg rounded-lg border border-slate-200 bg-white shadow-sm">
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
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Configure Schema')

@section('content')
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 lg:text-4xl mb-6">Configure Schema</h1>

    <form id="generation-form" class="space-y-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="format" class="text-sm font-medium text-slate-700">Format:</label>
                <select id="format"
                    class="w-[120px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900">
                    <option value="sql">SQL</option>
                    <option value="csv">CSV</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="seed" class="text-sm font-medium text-slate-700">Seed (Optional):</label>
                <input id="seed" type="number" placeholder="Random Seed"
                    class="w-[150px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900" />
            </div>
        </div>

        @foreach ($schema['tables'] as $table)
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $table['name'] }}</h2>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Rows:</span>
                        <input type="number" min="0" value="1000" data-row-count data-table="{{ $table['name'] }}"
                            class="w-[120px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900" />
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead
                                class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Column</th>
                                    <th class="px-3 py-2">Data Type</th>
                                    <th class="px-3 py-2">Constraints</th>
                                    <th class="px-3 py-2 text-right">Data Provider</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($table['columns'] as $column)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-900">{{ $column['name'] }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $column['dataType'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">
                                            @if ($column['isPrimaryKey'])
                                                <span
                                                    class="inline-flex items-center rounded-full border border-slate-300 px-2 py-0.5 text-xs font-medium text-slate-700">PK</span>
                                            @endif
                                            @if ($column['isForeignKey'])
                                                <span
                                                    class="ml-1 inline-flex items-center rounded-full border border-slate-300 px-2 py-0.5 text-xs font-medium text-slate-700">FK</span>
                                            @endif
                                            @if (!empty($column['isUnique']))
                                                <span
                                                    class="ml-1 inline-flex items-center rounded-full border border-slate-300 px-2 py-0.5 text-xs font-medium text-slate-700">UQ</span>
                                            @endif
                                            @if (isset($column['nullable']) && !$column['nullable'])
                                                <span
                                                    class="ml-1 inline-flex items-center rounded-full border border-slate-300 px-2 py-0.5 text-xs font-medium text-slate-700">NN</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <select data-provider data-table="{{ $table['name'] }}"
                                                data-column="{{ $column['name'] }}"
                                                class="w-full min-w-[200px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900">
                                                <option value="">Default</option>
                                                @foreach ($dataProviders as $group => $providers)
                                                    <optgroup label="{{ ucfirst($group) }}">
                                                        @foreach ($providers as $provider)
                                                            <option value="{{ $group }}.{{ $provider }}">
                                                                {{ $provider }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
        <div id="job-alert" class="hidden rounded-md border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="job-title" class="text-sm font-semibold text-slate-900">Job Status</h2>
                    <p id="job-message" class="mt-1 text-sm text-slate-700"></p>
                </div>
                <button id="job-retry" type="button"
                    class="hidden rounded-md border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Retry
                </button>
            </div>
        </div>
        <button id="generate-button" type="submit"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Generate Data
        </button>

    </form>

    <script>
        window.generatorSchema = @json($schema);
        window.generatorRoutes = {
            generate: "{{ route('generate.store') }}",
            jobStatusBase: "{{ url('/jobs') }}"
        };
    </script>
@endsection

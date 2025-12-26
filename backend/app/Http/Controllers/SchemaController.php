<?php

namespace App\Http\Controllers;

use App\Services\SqlParserService;
use App\Services\TopologicalSortService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SchemaController extends Controller
{
    public function index()
    {
        return view('generator.index');
    }

    public function store(Request $request, SqlParserService $parser, TopologicalSortService $sorter)
    {
        $request->validate([
            'ddl_file' => ['required', 'file', 'mimes:sql,txt'],
        ]);

        $sql = $request->file('ddl_file')->get();

        $schema = $parser->parse($sql);

        if (!empty($schema['error'])) {
            return back()->withErrors(['ddl_file' => $schema['error']]);
        }

        if (empty($schema['tables'])) {
            return back()->withErrors(['ddl_file' => 'Could not parse any tables from the provided file.']);
        }

        $sortedTables = $sorter->sort($schema['tables'], $schema['relationships']);
        $schema['tables'] = $sortedTables;

        $request->session()->put('schema', $schema);

        return redirect()->route('generator.configure');
    }

    public function show(Request $request)
    {
        $schema = $request->session()->get('schema');

        if (!$schema) {
            return redirect()->route('generator.index');
        }

        return view('generator.configure', [
            'schema' => $schema,
            'dataProviders' => Config::get('data_providers.providers'),
        ]);
    }
}

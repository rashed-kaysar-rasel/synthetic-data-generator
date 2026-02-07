<?php

namespace App\Http\Controllers;

use App\Services\SqlParserService;
use App\Services\TopologicalSortService;
use App\Services\DatabaseSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

    public function storeFromConnection(Request $request, DatabaseSchemaService $schemaService, TopologicalSortService $sorter)
    {
        $validated = $request->validate([
            'db_driver' => ['required', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string'],
            'db_port' => ['nullable', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $connectionName = 'external';
        $driver = $validated['db_driver'];
        $port = $validated['db_port'] ?? ($driver === 'pgsql' ? 5432 : 3306);

        Config::set("database.connections.{$connectionName}", [
            'driver' => $driver,
            'host' => $validated['db_host'],
            'port' => $port,
            'database' => $validated['db_database'],
            'username' => $validated['db_username'],
            'password' => $validated['db_password'] ?? '',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'collation' => $driver === 'pgsql' ? null : 'utf8mb4_unicode_ci',
            'prefix' => '',
            'schema' => $driver === 'pgsql' ? 'public' : null,
        ]);

        try {
            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $connection->getPdo();
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'db_connection' => 'Unable to connect to the database. Please verify the credentials.',
            ])->withInput();
        }

        $schema = $schemaService->extract($connection, $driver);

        if (!empty($schema['error'])) {
            return back()->withErrors(['db_connection' => $schema['error']])->withInput();
        }

        if (empty($schema['tables'])) {
            return back()->withErrors(['db_connection' => 'No tables found in the selected database.'])->withInput();
        }

        $sortedTables = $sorter->sort($schema['tables'], $schema['relationships']);
        $schema['tables'] = $sortedTables;

        $request->session()->put('schema', $schema);
        $request->session()->put('connection_meta', [
            'driver' => $driver,
            'host' => $validated['db_host'],
            'port' => $port,
            'database' => $validated['db_database'],
            'username' => $validated['db_username'],
        ]);

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
            'connectionMeta' => $request->session()->get('connection_meta'),
        ]);
    }
}

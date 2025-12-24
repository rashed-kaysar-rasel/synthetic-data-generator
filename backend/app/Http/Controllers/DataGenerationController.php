<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class DataGenerationController extends Controller
{
    /**
     * Store a newly created resource in storage and dispatch a job.
     */
    public function store(Request $request)
    {
        $schema = Session::get('schema');

        if (!$schema) {
            return Redirect::back()->withErrors(['message' => 'No schema found in session. Please upload a SQL DDL file first.']);
        }

        $schemaTables = collect($schema['tables'])->keyBy('name');

        $validated = $request->validate([
            'format' => 'required|in:sql,csv',
            'seed' => 'nullable|integer',
            'tables' => 'required|array',
            'tables.*.rowCount' => 'required|integer|min:0',
            'tables.*.columns' => 'required|array',
            'tables.*.columns.*.provider' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    [$group, $provider] = explode('.', $value);
                    $providers = Config::get('data_providers.providers');
                    if (!isset($providers[$group]) || !in_array($provider, $providers[$group])) {
                        $fail("The provider '$value' is invalid.");
                    }
                },
            ],
        ]);

        $userId = auth()->id() ?? 1; // Fallback for now

        $job = new GenerateDataJob($validated, $userId);
        $jobId = dispatch($job);

        return Redirect::back()->with('job', [
            'job_id' => $jobId,
            'status' => 'pending',
        ]);
    }

    /**
     * Display the status of a data generation job.
     */
    public function show(string $job_id)
    {
        $job = DB::table('jobs')->find($job_id);

        if (!$job) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $status = 'pending';
        $downloadUrl = null;
        $error = null;

        if ($job->failed_at) {
            $status = 'failed';
            // A more robust implementation would store the actual error message
            $error = 'Job processing failed.';
        } elseif (!$job->payload) { // Assuming job is deleted on success
            // This logic is simple. A better approach is a dedicated status column.
            // For now, we'll check for the output file's existence.
            $fileName = $job_id . '.zip';
            $filePath = storage_path('app/public/generated_data/' . $fileName);
            if (File::exists($filePath)) {
                $status = 'completed';
                $downloadUrl = route('generate.download', ['file_name' => $fileName]);
            }
        }

        // If the job is still in the `jobs` table and not failed, it's pending.
        return response()->json([
            'status' => $status,
            'download_url' => $downloadUrl,
            'error' => $error,
        ]);
    }

    /**
     * Download the generated data file.
     */
    public function download(string $file_name)
    {
        $filePath = storage_path('app/public/generated_data/' . $file_name);

        if (!File::exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath);
    }
}

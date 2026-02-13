<?php

namespace App\Http\Controllers;

use App\Models\SavedConfiguration;
use App\Services\SchemaSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SavedConfigController extends Controller
{
    public function index(Request $request)
    {
        $configs = SavedConfiguration::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'schema_signature', 'updated_at']);

        return response()->json(['data' => $configs]);
    }

    public function store(Request $request, SchemaSignatureService $signatureService)
    {
        $schema = Session::get('schema');
        if (!$schema) {
            return response()->json(['errors' => ['schema' => ['No schema found in session.']]], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $exists = SavedConfiguration::query()
            ->where('user_id', $request->user()->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'errors' => ['name' => ['Configuration name already exists.']],
            ], 422);
        }

        $signature = $signatureService->compute($schema);

        $config = SavedConfiguration::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'schema_signature' => $signature,
            'payload' => $validated['payload'],
        ]);

        return response()->json([
            'data' => [
                'id' => $config->id,
                'name' => $config->name,
                'schema_signature' => $config->schema_signature,
            ],
        ]);
    }

    public function show(Request $request, SavedConfiguration $config, SchemaSignatureService $signatureService)
    {
        if ($config->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $schema = Session::get('schema');
        if (!$schema) {
            return response()->json(['errors' => ['schema' => ['No schema found in session.']]], 422);
        }

        $signature = $signatureService->compute($schema);
        if ($signature !== $config->schema_signature) {
            return response()->json([
                'errors' => ['schema' => ['Saved configuration does not match the current schema.']],
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $config->id,
                'name' => $config->name,
                'payload' => $config->payload,
            ],
        ]);
    }
}

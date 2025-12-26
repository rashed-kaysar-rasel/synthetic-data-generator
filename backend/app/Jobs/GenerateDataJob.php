<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\DataGeneratorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class GenerateDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The generation configuration.
     *
     * @var array
     */
    public $generationConfig;
    
    /**
     * The schema.
     *
     * @var array
     */
    public $schema;

    /**
     * The ID of the user who initiated the job.
     *
     * @var int
     */
    public $userId;

    /**
     * Output file name for generated data.
     *
     * @var string
     */
    public $outputFileName;

    /**
     * Create a new job instance.
     *
     * @param array $generationConfig
     * @param int $userId
     * @return void
     */
    public function __construct(array $generationConfig, int $userId, string $outputFileName)
    {
        $this->generationConfig = $generationConfig;
        $this->userId = $userId;
        $this->schema = Session::get('schema');
        $this->outputFileName = $outputFileName;
    }

    /**
     * Execute the job.
     *
     * @param  DataGeneratorService  $generatorService
     * @return void
     */
    public function handle(DataGeneratorService $generatorService)
    {
        try {
            // Generate the data
            $filePath = $generatorService->generate(
                $this->generationConfig,
                $this->schema,
                $this->outputFileName
            );

            // TODO: Notify user on completion
            // event(new DataGenerationCompleted($this->userId, $filePath));

        } catch (Throwable $e) {
            Log::error("Data generation job failed for user {$this->userId}: {$e->getMessage()}");
            // TODO: Notify user on failure
            // event(new DataGenerationFailed($this->userId, $e->getMessage()));
            $this->fail($e);
        }
    }
}

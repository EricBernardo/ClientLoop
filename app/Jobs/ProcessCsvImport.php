<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\CsvImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $importRunId) {}

    public function handle(CsvImportService $imports): void
    {
        $run = ImportRun::withoutGlobalScopes()->with('company')->findOrFail($this->importRunId);
        $run->update(['status' => 'processing']);

        try {
            $result = $run->type === 'customers'
                ? $imports->customers($run->company, Storage::disk('local')->path($run->path), $run->mapping ?? [])
                : $imports->appointments($run->company, Storage::disk('local')->path($run->path), $run->mapping ?? []);
            $run->update(['status' => 'completed', 'created_count' => $result['created'], 'updated_count' => $result['updated'], 'errors' => $result['errors']]);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => 'failed', 'errors' => ['arquivo' => $exception->getMessage()]]);
        }
    }
}

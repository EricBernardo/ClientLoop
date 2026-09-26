<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\CsvImportService;
use App\Services\StaffNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $importRunId) {}

    public function handle(CsvImportService $imports, StaffNotifier $notifier): void
    {
        $run = ImportRun::withoutGlobalScopes()->with('company')->findOrFail($this->importRunId);
        $run->update(['status' => 'processing']);

        try {
            if (Storage::disk('local')->missing($run->path)) {
                throw new \RuntimeException('O arquivo enviado não está disponível para processamento. Envie o CSV novamente.');
            }

            $result = $imports->customers($run->company, Storage::disk('local')->path($run->path), $run->mapping ?? []);
            $run->update(['status' => 'completed', 'created_count' => $result['created'], 'updated_count' => $result['updated'], 'errors' => $result['errors']]);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => 'failed', 'errors' => ['arquivo' => $this->failureMessage($exception)]]);
        }

        $notifier->importFinished($run->fresh());
    }

    private function failureMessage(\Throwable $exception): string
    {
        if (str_contains($exception->getMessage(), 'não está disponível para processamento')) {
            return $exception->getMessage();
        }

        return 'Não foi possível processar o arquivo. Tente enviar o CSV novamente. Se o problema continuar, confira se as colunas e os dados seguem o modelo disponibilizado.';
    }
}

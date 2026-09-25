<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CsvImportService;
use Illuminate\Console\Command;

class ImportCsv extends Command
{
    protected $signature = 'clientloop:import {companyId} {path}';

    protected $description = 'Importa responsáveis e pets de um CSV com cabeçalho.';

    public function handle(CsvImportService $importer): int
    {
        $company = Company::findOrFail($this->argument('companyId'));
        $result = $importer->customers($company, $this->argument('path'));
        $this->table(['Criados', 'Atualizados', 'Erros'], [[$result['created'], $result['updated'], count($result['errors'])]]);
        foreach ($result['errors'] as $line => $error) {
            $this->warn("Linha {$line}: {$error}");
        }

        return self::SUCCESS;
    }
}

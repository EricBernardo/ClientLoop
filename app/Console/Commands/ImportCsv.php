<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CsvImportService;
use Illuminate\Console\Command;

class ImportCsv extends Command
{
    protected $signature = 'clientloop:import {type : customers|appointments} {companyId} {path}';

    protected $description = 'Importa clientes ou agendamentos de um CSV com cabeçalho.';

    public function handle(CsvImportService $importer): int
    {
        $company = Company::findOrFail($this->argument('companyId'));
        $result = match ($this->argument('type')) {
            'customers' => $importer->customers($company, $this->argument('path')), 'appointments' => $importer->appointments($company, $this->argument('path')), default => throw new \InvalidArgumentException('Tipo deve ser customers ou appointments.')
        };
        $this->table(['Criados', 'Atualizados', 'Erros'], [[$result['created'], $result['updated'], count($result['errors'])]]);
        foreach ($result['errors'] as $line => $error) {
            $this->warn("Linha {$line}: {$error}");
        }

        return self::SUCCESS;
    }
}

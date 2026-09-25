<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureDemoData extends Command
{
    protected $signature = 'clientloop:ensure-demo';

    protected $description = 'Cria os dados de demonstração somente em um banco de dados vazio.';

    public function handle(): int
    {
        if (User::query()->exists() || Company::query()->exists()) {
            $this->components->info('Banco de dados existente preservado; dados de demonstração não foram recriados.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--force' => true]);
        $this->components->info('Banco de dados vazio preenchido com os dados de demonstração.');

        return self::SUCCESS;
    }
}

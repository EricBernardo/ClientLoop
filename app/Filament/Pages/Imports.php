<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessCsvImport;
use App\Models\ImportRun;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Imports extends Page
{
    use AuthorizesRequests;
    use WithFileUploads;

    protected static ?string $navigationLabel = 'Importações';

    protected static ?string $title = 'Importar dados';

    protected string $view = 'filament.pages.imports';

    public string $type = 'customers';

    public ?TemporaryUploadedFile $file = null;

    /** @var array<int, string> */
    public array $headers = [];

    /** @var array<string, string> */
    public array $mapping = [];

    public function updatedFile(): void
    {
        if (! $this->file) {
            return;
        }
        $handle = fopen($this->file->getRealPath(), 'r');
        $this->headers = array_values(array_filter(array_map('trim', fgetcsv($handle) ?: [])));
        fclose($handle);
        $this->mapping = collect($this->fields())->mapWithKeys(fn (string $label, string $field) => [$field => in_array($field, $this->headers, true) ? $field : ''])->all();
    }

    public function start(): void
    {
        $rules = ['type' => ['required', 'in:customers,appointments'], 'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']];
        foreach (array_keys($this->requiredFields()) as $field) {
            $rules['mapping.'.$field] = ['required', 'in:'.implode(',', $this->headers)];
        }
        $this->validate($rules);
        $path = $this->file->store('imports', 'local');
        $run = ImportRun::create(['company_id' => auth()->user()->company_id, 'user_id' => auth()->id(), 'type' => $this->type, 'path' => $path, 'mapping' => $this->mapping]);
        ProcessCsvImport::dispatch($run->id);
        $this->reset('file');
        Notification::make()->title('Importação adicionada à fila')->success()->send();
    }

    public function getRunsProperty()
    {
        return ImportRun::query()->latest()->limit(12)->get();
    }

    /** @return array<string, string> */
    public function fields(): array
    {
        return $this->type === 'customers'
            ? ['name' => 'Nome', 'phone' => 'Telefone', 'email' => 'E-mail', 'tags' => 'Etiquetas', 'last_activity_at' => 'Último atendimento', 'next_return_at' => 'Próximo retorno', 'opted_out' => 'Não receber contato']
            : ['phone' => 'Telefone', 'customer_name' => 'Nome do cliente', 'service' => 'Serviço', 'scheduled_at' => 'Data e hora', 'external_id' => 'Identificador externo', 'status' => 'Situação', 'potential_value' => 'Valor potencial', 'realized_value' => 'Valor realizado', 'next_return_at' => 'Próximo retorno'];
    }

    /** @return array<string, string> */
    public function requiredFields(): array
    {
        return $this->type === 'customers' ? ['name' => 'Nome', 'phone' => 'Telefone'] : ['phone' => 'Telefone', 'customer_name' => 'Nome do cliente', 'service' => 'Serviço', 'scheduled_at' => 'Data e hora'];
    }
}

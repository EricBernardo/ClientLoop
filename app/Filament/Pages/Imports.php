<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessCsvImport;
use App\Models\ImportRun;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class Imports extends Page
{
    use AuthorizesRequests;
    use WithFileUploads;

    protected static ?string $navigationLabel = 'Importações';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Importar dados';

    protected string $view = 'filament.pages.imports';

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
        $this->headers = array_values(array_filter(array_map(fn (mixed $header): string => $this->normalizeHeader((string) $header), fgetcsv($handle) ?: [])));
        fclose($handle);
        $this->mapping = collect($this->fields())
            ->mapWithKeys(fn (string $label, string $field) => [$field => $this->autoMapHeader($field)])
            ->all();
    }

    public function start(): void
    {
        $rules = ['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']];
        foreach (array_keys($this->requiredFields()) as $field) {
            $rules['mapping.'.$field] = ['required', 'in:'.implode(',', $this->headers)];
        }
        $this->validate($rules);
        $path = $this->file->store('imports', 'local');
        $run = ImportRun::create(['company_id' => auth()->user()->company_id, 'user_id' => auth()->id(), 'type' => 'customers', 'path' => $path, 'mapping' => $this->mapping]);
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
        return ['responsible_name' => 'Nome do responsável', 'responsible_phone' => 'Telefone do responsável', 'pet_name' => 'Nome do pet', 'last_activity_at' => 'Último atendimento', 'next_return_at' => 'Retorno previsto (opcional)', 'opted_out' => 'Não receber contato'];
    }

    /** @return array<string, string> */
    public function requiredFields(): array
    {
        return ['responsible_name' => 'Nome do responsável', 'responsible_phone' => 'Telefone do responsável'];
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim($header);

        return preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
    }

    private function autoMapHeader(string $field): string
    {
        $aliases = [
            'responsible_name' => [
                'responsible_name', 'customer_name', 'name',
                'nome', 'nome do responsavel', 'nome do responsável', 'responsavel', 'responsável',
            ],
            'responsible_phone' => [
                'responsible_phone', 'phone',
                'telefone', 'celular', 'whatsapp', 'fone',
            ],
            'pet_name' => ['pet_name', 'pet', 'nome do pet', 'animal'],
            'last_activity_at' => ['last_activity_at', 'ultimo atendimento', 'último atendimento', 'last_visit'],
            'next_return_at' => ['next_return_at', 'retorno', 'retorno previsto', 'next_return'],
            'opted_out' => ['opted_out', 'nao receber contato', 'não receber contato', 'opt_out'],
        ];

        $normalizedHeaders = collect($this->headers)
            ->mapWithKeys(fn (string $header): array => [$this->normalizeAlias($header) => $header])
            ->all();

        foreach ($aliases[$field] ?? [$field] as $alias) {
            $key = $this->normalizeAlias($alias);
            if (isset($normalizedHeaders[$key])) {
                return $normalizedHeaders[$key];
            }
        }

        return '';
    }

    private function normalizeAlias(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}

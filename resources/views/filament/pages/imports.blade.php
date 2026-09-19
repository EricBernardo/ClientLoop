<x-filament-panels::page>
    <form wire:submit="start" class="space-y-4 rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="import-type">Tipo de arquivo</label>
            <select id="import-type" wire:model="type" class="fi-input mt-2 block w-full rounded-lg border-gray-300">
                <option value="customers">Clientes</option>
                <option value="appointments">Agendamentos e histórico</option>
            </select>
        </div>
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="import-file">Arquivo CSV</label>
            <input id="import-file" type="file" wire:model="file" accept=".csv,text/csv" class="mt-2 block w-full" />
            <p class="mt-2 text-sm text-gray-500">Clientes: nome e telefone. Agendamentos: telefone, cliente, serviço e data. O processamento ocorre em segundo plano.</p>
            @error('file') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>
        @if ($headers)
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($this->fields() as $field => $label)
                    <label class="text-sm font-medium">{{ $label }}{{ array_key_exists($field, $this->requiredFields()) ? ' *' : '' }}
                        <select wire:model="mapping.{{ $field }}" class="fi-input mt-1 block w-full rounded-lg border-gray-300">
                            <option value="">Não importar</option>
                            @foreach ($headers as $header)<option value="{{ $header }}">{{ $header }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        @endif
        <x-filament::button type="submit" wire:loading.attr="disabled">Enviar para importação</x-filament::button>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300"><tr><th class="p-3">Tipo</th><th class="p-3">Situação</th><th class="p-3">Criados</th><th class="p-3">Atualizados</th><th class="p-3">Erros</th></tr></thead>
            <tbody>
                @forelse ($this->runs as $run)
                    <tr class="border-t dark:border-gray-800"><td class="p-3">{{ $run->type === 'customers' ? 'Clientes' : 'Agendamentos' }}</td><td class="p-3">{{ ['queued' => 'Na fila', 'processing' => 'Processando', 'completed' => 'Concluída', 'failed' => 'Falhou'][$run->status] }}</td><td class="p-3">{{ $run->created_count }}</td><td class="p-3">{{ $run->updated_count }}</td><td class="p-3">{{ count($run->errors ?? []) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="p-4 text-gray-500">Nenhuma importação realizada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>

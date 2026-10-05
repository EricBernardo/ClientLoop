<x-filament-panels::page>
    @php($statement = $this->statement())
    <div class="max-w-4xl space-y-5">
        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500">Entradas</p>
                <p class="text-2xl font-bold text-gray-950 dark:text-white">R$ {{ $statement['income'] }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500">Saídas</p>
                <p class="text-2xl font-bold text-gray-950 dark:text-white">R$ {{ $statement['expense'] }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500">Saldo</p>
                <p class="text-2xl font-bold text-gray-950 dark:text-white">R$ {{ $statement['balance'] }}</p>
            </article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <form wire:submit="addExpense" class="space-y-6">
                {{ $this->form }}
                <x-filament::button type="submit">Lançar saída</x-filament::button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">Data</th>
                        <th class="px-4 py-3 font-medium">Lançamento</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 text-right font-medium">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['entries'] as $entry)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-3">{{ $entry->occurred_on->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $entry->description() }}@if($entry->notes)<span class="block text-gray-500">{{ $entry->notes }}</span>@endif</td>
                            <td class="px-4 py-3">{{ $entry->direction->label() }}</td>
                            <td class="px-4 py-3 text-right">R$ {{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum lançamento neste período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>

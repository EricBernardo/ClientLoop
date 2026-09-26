<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taxa de confirmação (mês)</p>
            <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $stats['confirmation_rate'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Confirmados ou concluídos sobre os agendamentos do mês.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taxa de falta (mês)</p>
            <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $stats['no_show_rate'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Faltas sobre concluídos + faltas no mês.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ocupação de hoje</p>
            <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $stats['occupancy_today'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minutos agendados sobre o expediente de hoje.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pacotes vendidos (mês)</p>
            <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $stats['packages_sold_month'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Vendas registradas neste mês.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tarefas pendentes</p>
            <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $stats['pending_tasks'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contatos na fila aguardando ação.</p>
        </div>
    </div>
</x-filament-panels::page>

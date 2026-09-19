<x-filament-panels::page>
    <section>
        <h2 class="mb-3 text-lg font-semibold">Próximos 7 dias</h2>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->appointments as $appointment)
                <a href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-400 dark:border-gray-800 dark:bg-gray-900">
                    <div class="font-medium">{{ $appointment->customer->name }}</div>
                    <div class="text-sm text-gray-500">{{ $appointment->service?->name ?? 'Sem serviço' }}</div>
                    <div class="mt-2 text-sm">{{ $appointment->scheduled_at->format('d/m, H:i') }} · {{ ['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'reschedule_requested' => 'Alteração solicitada'][$appointment->status] ?? $appointment->status }}</div>
                </a>
            @empty
                <p class="text-sm text-gray-500">Não há agendamentos nos próximos sete dias.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8">
        <h2 class="mb-3 text-lg font-semibold">Funil comercial</h2>
        <div class="grid gap-4 overflow-x-auto lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($this->stages as $stage)
                <div class="min-w-52 rounded-xl bg-gray-100 p-3 dark:bg-gray-800">
                    <h3 class="mb-3 text-sm font-semibold">{{ $stage['label'] }} <span class="text-gray-500">({{ $stage['items']->count() }})</span></h3>
                    <div class="space-y-2">
                        @forelse ($stage['items'] as $opportunity)
                            <a href="{{ \App\Filament\Resources\Opportunities\OpportunityResource::getUrl('index') }}" class="block rounded-lg bg-white p-3 text-sm shadow-sm dark:bg-gray-900">
                                <div class="font-medium">{{ $opportunity->title }}</div>
                                <div class="text-gray-500">{{ $opportunity->customer->name }}</div>
                                @if ($opportunity->potential_value)<div class="mt-1 text-primary-600">R$ {{ number_format((float) $opportunity->potential_value, 2, ',', '.') }}</div>@endif
                            </a>
                        @empty
                            <p class="text-xs text-gray-500">Sem oportunidades</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-filament-panels::page>

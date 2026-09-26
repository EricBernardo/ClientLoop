<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold tracking-tight text-gray-950 dark:text-white">Configure sua pet shop</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Faça estes passos uma vez. Depois a rotina diária fica na Agenda e na Fila de contatos.</p>
                </div>
                <x-filament::button color="gray" size="sm" wire:click="dismiss" wire:confirm="Ocultar o checklist? Você ainda pode abrir Como usar.">
                    Já configurei — ocultar
                </x-filament::button>
            </div>

            <ol class="grid gap-2 sm:grid-cols-2">
                @foreach ($this->steps as $step)
                    <li>
                        <a href="{{ $step['url'] }}" class="flex items-start gap-3 rounded-xl border border-gray-200 px-3 py-3 text-sm transition hover:border-primary-400 hover:bg-primary-50/40 dark:border-gray-700 dark:hover:border-primary-500 dark:hover:bg-primary-950/30">
                            <span @class([
                                'mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[0.7rem] font-bold',
                                'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200' => $step['done'],
                                'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300' => ! $step['done'],
                            ])>
                                {{ $step['done'] ? '✓' : $loop->iteration }}
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $step['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

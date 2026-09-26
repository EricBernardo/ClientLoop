<x-filament-panels::page>
    <div class="max-w-3xl space-y-5">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">Quando você atende e como contata?</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">A agenda respeita estes horários; confirmação e reativação usam as regras de contato abaixo.</p>
            <form wire:submit="save" class="mt-6 space-y-6">
                {{ $this->form }}
                <x-filament::button type="submit">Salvar</x-filament::button>
            </form>
        </section>
    </div>
</x-filament-panels::page>

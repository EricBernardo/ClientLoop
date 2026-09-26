@php
    use App\Support\FirstVisitGuide;

    $user = auth()->user();
    $company = $user?->company;
    $step = null;

    if ($company && $company->setup_wizard_completed_at === null && $user->role !== 'attendant') {
        $step = FirstVisitGuide::current($company);
    }

    $currentIndex = $step ? array_search($step['key'], array_keys(FirstVisitGuide::STEPS), true) : false;
@endphp

@if ($step)
    <div style="margin-bottom: 1.5rem;">
        <x-filament::section
            compact
            :heading="$step['title']"
            :description="$step['body']"
            icon="heroicon-o-map"
            icon-color="primary"
        >
            <x-slot name="afterHeader">
                @if ($step['can_finish'])
                    <form method="POST" action="{{ route('filament.company.first-visit.finish') }}">
                        @csrf
                        <x-filament::button type="submit" size="sm">Ir para o painel</x-filament::button>
                    </form>
                @else
                    <x-filament::button tag="a" href="{{ url('/admin') }}" size="sm" color="gray">
                        Continuar
                    </x-filament::button>
                @endif
            </x-slot>

            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @foreach (FirstVisitGuide::STEPS as $key => $label)
                    <x-filament::badge
                        :color="match (true) {
                            $key === $step['key'] => 'primary',
                            $loop->index < $currentIndex => 'success',
                            default => 'gray',
                        }"
                        size="sm"
                    >
                        {{ $loop->iteration }}. {{ $label }}
                    </x-filament::badge>
                @endforeach
            </div>
        </x-filament::section>
    </div>
@endif

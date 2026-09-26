<x-filament-panels::page>
    <style>
        .pet-calendar { display:grid; gap:1.25rem; min-width:0; }
        .pet-calendar__toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.8rem; }
        .pet-calendar__range { color:#344054; font-weight:750; }
        .pet-calendar__controls { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pet-calendar__button { display:inline-flex; align-items:center; justify-content:center; min-height:2.35rem; padding:.45rem .75rem; border:1px solid #d0d5dd; border-radius:.55rem; background:#fff; color:#344054; font-size:.86rem; font-weight:700; text-decoration:none; }
        .pet-calendar__button:hover,.pet-calendar__button--active { border-color:#0f766e; background:#f0fdfa; color:#0f766e; }
        .pet-calendar__button--primary { border-color:#0f766e; background:#0f766e; color:#fff; }
        .pet-calendar__button--primary:hover { background:#115e59; color:#fff; }
        .pet-calendar__scroller { overflow-x:auto; padding-bottom:.25rem; }
        .pet-calendar__grid { display:grid; min-width:{{ $this->mode === 'day' ? '38rem' : '70rem' }}; grid-template-columns:repeat({{ count($this->days) }}, minmax(9rem, 1fr)); gap:.7rem; }
        .calendar-day { min-width:0; }
        .calendar-day__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:.55rem; padding:.65rem .7rem; border:1px solid #dce8e5; border-radius:.75rem; background:#fff; color:#344054; }
        .calendar-day__head strong { font-size:.86rem; text-transform:capitalize; }
        .calendar-day__head span { color:#667085; font-size:.78rem; }
        .calendar-day__head--today { border-color:#5eead4; background:#f0fdfa; color:#115e59; }
        .calendar-day__body { position:relative; height:42rem; border:1px solid #e4e7ec; border-radius:.8rem; overflow:hidden; background:repeating-linear-gradient(to bottom,#fff 0,#fff calc({{ 100 / ($this->businessEndsAtHour - $this->businessStartsAtHour) }}% - 1px),#edf1f4 calc({{ 100 / ($this->businessEndsAtHour - $this->businessStartsAtHour) }}% - 1px),#edf1f4 {{ 100 / ($this->businessEndsAtHour - $this->businessStartsAtHour) }}%); }
        .calendar-hour { position:absolute; left:.45rem; color:#98a2b3; font-size:.68rem; transform:translateY(-50%); pointer-events:none; }
        .calendar-slot { position:absolute; inset-inline:0; height:10%; border:0; background:transparent; cursor:pointer; }
        .calendar-slot:hover { background:rgb(20 184 166 / .08); }
        .calendar-event { position:absolute; z-index:2; inset-inline:.45rem; display:block; overflow:hidden; padding:.5rem .55rem; border:1px solid #99f6e4; border-radius:.6rem; background:#ecfdf5; color:#134e4a; box-shadow:0 1px 2px rgb(15 118 110 / .08); text-decoration:none; }
        .calendar-event:hover { border-color:#0f766e; box-shadow:0 5px 12px rgb(15 118 110 / .12); }
        .calendar-event--confirmed,.calendar-event--completed { border-color:#86efac; background:#f0fdf4; color:#166534; }
        .calendar-event--reschedule_requested { border-color:#fed7aa; background:#fff7ed; color:#9a3412; }
        .calendar-event__name { overflow:hidden; font-size:.76rem; font-weight:800; line-height:1.25; text-overflow:ellipsis; white-space:nowrap; }
        .calendar-event__meta { margin-top:.18rem; overflow:hidden; color:inherit; font-size:.68rem; opacity:.85; text-overflow:ellipsis; white-space:nowrap; }
        .calendar-event__status { font-size:.64rem; font-weight:750; }
        .pet-calendar__mobile { display:none; gap:1rem; }
        .pet-calendar__day-list { display:grid; gap:.55rem; padding:1rem; border:1px solid #dce8e5; border-radius:.9rem; background:#fff; }
        .pet-calendar__day-list h3 { margin:0; font-size:.92rem; color:#134e4a; }
        .pet-calendar__empty { margin:0; color:#667085; font-size:.88rem; }
        .pet-calendar__item { display:grid; gap:.2rem; padding:.85rem .9rem; border:1px solid #99f6e4; border-radius:.7rem; background:#ecfdf5; color:#134e4a; text-decoration:none; }
        .pet-calendar__item strong { font-size:.9rem; }
        .pet-calendar__item span { font-size:.78rem; opacity:.9; }
        .pet-calendar__slots { display:flex; flex-wrap:wrap; gap:.4rem; }
        .pet-calendar__slots a { display:inline-flex; min-height:2.1rem; align-items:center; padding:.35rem .65rem; border:1px solid #d0d5dd; border-radius:.5rem; background:#f8fafc; color:#344054; font-size:.78rem; font-weight:700; text-decoration:none; }
        .pet-calendar__slots a:hover { border-color:#0f766e; color:#0f766e; background:#f0fdfa; }
        .dark .pet-calendar__button,.dark .calendar-day__head,.dark .pet-calendar__day-list { border-color:#475467; background:#1f2937; color:#e5e7eb; }
        .dark .calendar-day__body { border-color:#475467; background:repeating-linear-gradient(to bottom,#111827 0,#111827 calc(10% - 1px),#374151 calc(10% - 1px),#374151 10%); }
        .dark .calendar-event,.dark .pet-calendar__item { background:#134e4a; color:#ccfbf1; border-color:#0f766e; }
        @media(max-width:700px){
            .pet-calendar__toolbar{align-items:stretch}
            .pet-calendar__controls{justify-content:space-between}
            .pet-calendar__desktop{display:none}
            .pet-calendar__mobile{display:grid}
        }
    </style>

    @php($start = $this->days[0])
    @php($previous = $this->mode === 'day' ? $start->copy()->subDay() : $start->copy()->subWeek())
    @php($next = $this->mode === 'day' ? $start->copy()->addDay() : $start->copy()->addWeek())
    <div class="pet-calendar">
        <div class="pet-calendar__toolbar">
            <div class="pet-calendar__range">{{ $this->mode === 'day' ? $start->translatedFormat('l, d \d\e F') : $start->translatedFormat('d/m').' — '.$start->copy()->addDays(6)->translatedFormat('d/m') }}</div>
            <div class="pet-calendar__controls">
                <a class="pet-calendar__button" href="{{ $this->calendarUrl($previous) }}">← Anterior</a>
                <a class="pet-calendar__button" href="{{ $this->calendarUrl(today(), $this->mode) }}">Hoje</a>
                <a class="pet-calendar__button" href="{{ $this->calendarUrl($next) }}">Próximo →</a>
                <a class="pet-calendar__button {{ $this->mode === 'day' ? 'pet-calendar__button--active' : '' }}" href="{{ $this->calendarUrl($start, 'day') }}">Dia</a>
                <a class="pet-calendar__button {{ $this->mode === 'week' ? 'pet-calendar__button--active' : '' }}" href="{{ $this->calendarUrl($start, 'week') }}">Semana</a>
                <a class="pet-calendar__button pet-calendar__button--primary" href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create') }}">Novo agendamento</a>
            </div>
        </div>

        <div class="pet-calendar__mobile">
            @foreach ($this->days as $day)
                @php($events = $this->appointments->filter(fn ($appointment) => $appointment->scheduled_at->isSameDay($day))->values())
                <section class="pet-calendar__day-list">
                    <h3>{{ $day->translatedFormat('l, d/m') }}</h3>
                    @forelse ($events as $appointment)
                        <a class="pet-calendar__item" href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]) }}">
                            <strong>{{ $appointment->scheduled_at->format('H:i') }} · {{ $appointment->pet?->name ?? 'Pet não informado' }}</strong>
                            <span>{{ $appointment->customer->name }} · {{ $appointment->service?->name ?? 'Serviço' }} · {{ \App\Support\InterfaceLabels::appointmentStatus($appointment->status) }}</span>
                        </a>
                    @empty
                        <p class="pet-calendar__empty">Nenhum atendimento neste dia. Escolha um horário livre abaixo.</p>
                    @endforelse
                    @if($this->isBusinessDay($day))
                        <div class="pet-calendar__slots" aria-label="Horários livres">
                            @for ($hour = $this->businessStartsAtHour; $hour < $this->businessEndsAtHour; $hour++)
                                <a href="{{ $this->createUrl($day, $hour) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</a>
                            @endfor
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="pet-calendar__scroller pet-calendar__desktop"><div class="pet-calendar__grid">
            @foreach ($this->days as $day)
                @php($events = $this->appointments->filter(fn ($appointment) => $appointment->scheduled_at->isSameDay($day)))
                <section class="calendar-day">
                    <header class="calendar-day__head {{ $day->isToday() ? 'calendar-day__head--today' : '' }}"><strong>{{ $day->translatedFormat('D') }}</strong><span>{{ $day->format('d/m') }}</span></header>
                    <div class="calendar-day__body">
                        @for ($hour = $this->businessStartsAtHour; $hour <= $this->businessEndsAtHour; $hour++)
                            @php($top = (($hour - $this->businessStartsAtHour) / ($this->businessEndsAtHour - $this->businessStartsAtHour)) * 100)
                            <span class="calendar-hour" style="top:{{ $top }}%">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</span>
                            @if($hour < $this->businessEndsAtHour && $this->isBusinessDay($day))<a class="calendar-slot" style="top:{{ $top }}%" href="{{ $this->createUrl($day, $hour) }}" aria-label="Agendar às {{ $hour }} horas"></a>@endif
                        @endfor
                        @foreach ($events as $appointment)
                            @php($position = $this->position($appointment))
                            <a class="calendar-event calendar-event--{{ $appointment->status }}" style="top:{{ $position['top'] }}%;height:{{ $position['height'] }}%" href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]) }}">
                                <div class="calendar-event__name">{{ $appointment->pet?->name ?? 'Pet não informado' }}</div>
                                <div class="calendar-event__meta">{{ $appointment->customer->name }} · {{ $appointment->service?->name ?? 'Serviço' }}</div>
                                <div class="calendar-event__status">{{ $appointment->scheduled_at->format('H:i') }} · {{ $appointment->duration_minutes }} min · {{ \App\Support\InterfaceLabels::appointmentStatus($appointment->status) }}</div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div></div>
    </div>
</x-filament-panels::page>

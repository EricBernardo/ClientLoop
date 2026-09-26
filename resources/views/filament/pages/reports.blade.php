<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $confirmation = (int) ($stats['confirmation_rate'] ?? 0);
        $noShow = (int) ($stats['no_show_rate'] ?? 0);
        $occupancy = (int) ($stats['occupancy_today'] ?? 0);
        $packages = (int) ($stats['packages_sold_month'] ?? 0);
        $pending = (int) ($stats['pending_tasks'] ?? 0);
        $monthLabel = now()->translatedFormat('F \d\e Y');
    @endphp

    <style>
        .reports-shell{display:grid;gap:1.5rem;max-width:72rem}
        .reports-hero{padding:1.6rem 1.7rem;border:1px solid #99d9ca;border-radius:1.15rem;background:linear-gradient(135deg,#ecfdf5,#f0fdfa)}
        .reports-eyebrow{margin:0 0 .55rem;color:#0f766e;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .reports-hero h2{margin:0;color:#134e4a;font-size:clamp(1.2rem,2.4vw,1.55rem);font-weight:800;letter-spacing:-.03em;line-height:1.2}
        .reports-hero p{margin:.55rem 0 0;max-width:40rem;color:#315d56;font-size:.95rem;line-height:1.55}
        .reports-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
        .report-card{display:flex;min-width:0;flex-direction:column;padding:1.2rem 1.25rem;border:1px solid #dce8e5;border-radius:1rem;background:#fff;box-shadow:0 1px 2px #1018280a;text-decoration:none;color:inherit;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
        a.report-card:hover{border-color:#99f6e4;box-shadow:0 8px 20px #0f766e14;transform:translateY(-1px)}
        .report-card__top{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}
        .report-card__label{margin:0;color:#667085;font-size:.82rem;font-weight:700;line-height:1.35}
        .report-card__icon{display:grid;place-items:center;width:2.15rem;height:2.15rem;flex-shrink:0;border-radius:.7rem;background:#ccfbf1;color:#0f766e}
        .report-card__icon svg{width:1.1rem;height:1.1rem}
        .report-card__value{margin:.85rem 0 .15rem;color:#172033;font-size:clamp(1.85rem,3vw,2.25rem);font-weight:850;letter-spacing:-.04em;line-height:1}
        .report-card__hint{margin:0;color:#98a2b3;font-size:.8rem;line-height:1.45}
        .report-card__bar{margin-top:1rem;height:.45rem;overflow:hidden;border-radius:999px;background:#eef2f6}
        .report-card__bar > span{display:block;height:100%;border-radius:inherit;background:#0f766e}
        .report-card--warn .report-card__icon{background:#ffedd5;color:#c2410c}
        .report-card--warn .report-card__bar > span{background:#ea580c}
        .report-card--danger .report-card__icon{background:#fee2e2;color:#b91c1c}
        .report-card--danger .report-card__bar > span{background:#dc2626}
        .report-card--good .report-card__icon{background:#dcfce7;color:#15803d}
        .report-card--good .report-card__bar > span{background:#16a34a}
        .report-card__foot{display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:auto;padding-top:1rem}
        .report-card__link{color:#0f766e;font-size:.78rem;font-weight:750}
        .report-empty{padding:1.5rem;border:1px dashed #d0d5dd;border-radius:1rem;background:#f8fafc;color:#667085;text-align:center}
        .dark .reports-hero{border-color:#167c72;background:linear-gradient(135deg,#064e3b99,#134e4a80)}
        .dark .reports-hero h2{color:#ccfbf1}
        .dark .reports-hero p,.dark .reports-eyebrow{color:#b6e5dc}
        .dark .report-card{border-color:#374151;background:#111827;box-shadow:none}
        .dark .report-card__label,.dark .report-card__hint{color:#98a2b3}
        .dark .report-card__value{color:#f9fafb}
        .dark .report-card__bar{background:#1f2937}
        .dark .report-card__icon{background:#14b8a63d;color:#99f6e4}
        .dark .report-card--warn .report-card__icon{background:#9a34124d;color:#fdba74}
        .dark .report-card--danger .report-card__icon{background:#7f1d1d4d;color:#fca5a5}
        .dark .report-card--good .report-card__icon{background:#14532d4d;color:#86efac}
        .dark .report-card__link{color:#5eead4}
        .dark .report-empty{border-color:#475467;background:#1f2937;color:#98a2b3}
        @media(max-width:1100px){.reports-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.reports-hero{padding:1.25rem}.reports-grid{grid-template-columns:1fr}}
    </style>

    <div class="reports-shell">
        <section class="reports-hero">
            <p class="reports-eyebrow">Operação · {{ $monthLabel }}</p>
            <h2>Como está a rotina do pet shop</h2>
            <p>Confirmações, faltas, ocupação do dia e o que ainda precisa de ação. Os números atualizam conforme a agenda e a fila de contatos.</p>
        </section>

        @if ($stats === [])
            <div class="report-empty">Não há empresa vinculada à sua conta para montar os relatórios.</div>
        @else
            <div class="reports-grid">
                @php($confirmationTone = $confirmation >= 70 ? 'good' : ($confirmation < 40 ? 'warn' : ''))
                <a class="report-card {{ $confirmationTone ? 'report-card--'.$confirmationTone : '' }}" href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('index') }}">
                    <div class="report-card__top">
                        <p class="report-card__label">Taxa de confirmação (mês)</p>
                        <span class="report-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </span>
                    </div>
                    <p class="report-card__value">{{ $confirmation }}%</p>
                    <p class="report-card__hint">Confirmados ou concluídos sobre os agendamentos do mês.</p>
                    <div class="report-card__bar" aria-hidden="true"><span style="width:{{ $confirmation }}%"></span></div>
                    <div class="report-card__foot"><span class="report-card__link">Ver atendimentos →</span></div>
                </a>

                @php($noShowTone = $noShow >= 20 ? 'danger' : ($noShow >= 10 ? 'warn' : 'good'))
                <a class="report-card report-card--{{ $noShowTone }}" href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('index') }}">
                    <div class="report-card__top">
                        <p class="report-card__label">Taxa de falta (mês)</p>
                        <span class="report-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        </span>
                    </div>
                    <p class="report-card__value">{{ $noShow }}%</p>
                    <p class="report-card__hint">Faltas sobre concluídos + faltas no mês.</p>
                    <div class="report-card__bar" aria-hidden="true"><span style="width:{{ $noShow }}%"></span></div>
                    <div class="report-card__foot"><span class="report-card__link">Ver atendimentos →</span></div>
                </a>

                @php($occupancyTone = $occupancy >= 80 ? 'warn' : ($occupancy >= 40 ? 'good' : ''))
                <a class="report-card {{ $occupancyTone ? 'report-card--'.$occupancyTone : '' }}" href="{{ \App\Filament\Pages\Calendar::getUrl(['date' => today()->toDateString(), 'mode' => 'day']) }}">
                    <div class="report-card__top">
                        <p class="report-card__label">Ocupação de hoje</p>
                        <span class="report-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </span>
                    </div>
                    <p class="report-card__value">{{ $occupancy }}%</p>
                    <p class="report-card__hint">Minutos agendados sobre o expediente de hoje.</p>
                    <div class="report-card__bar" aria-hidden="true"><span style="width:{{ $occupancy }}%"></span></div>
                    <div class="report-card__foot"><span class="report-card__link">Abrir agenda →</span></div>
                </a>

                <a class="report-card" href="{{ \App\Filament\Resources\PetPackages\PetPackageResource::getUrl('index') }}">
                    <div class="report-card__top">
                        <p class="report-card__label">Pacotes vendidos (mês)</p>
                        <span class="report-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                        </span>
                    </div>
                    <p class="report-card__value">{{ $packages }}</p>
                    <p class="report-card__hint">Vendas registradas neste mês.</p>
                    <div class="report-card__foot"><span class="report-card__link">Ver pacotes →</span></div>
                </a>

                <a class="report-card {{ $pending > 0 ? 'report-card--warn' : 'report-card--good' }}" href="{{ \App\Filament\Resources\ContactTasks\ContactTaskResource::getUrl('index', ['view' => 'pending']) }}">
                    <div class="report-card__top">
                        <p class="report-card__label">Tarefas pendentes</p>
                        <span class="report-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3.75-3.75a9.297 9.297 0 0 1-2.25.25c-5.03 0-9.12-3.582-9.12-8S5.47 2.25 10.5 2.25c1.39 0 2.7.297 3.867.826"/></svg>
                        </span>
                    </div>
                    <p class="report-card__value">{{ $pending }}</p>
                    <p class="report-card__hint">Contatos na fila aguardando ação.</p>
                    <div class="report-card__foot"><span class="report-card__link">Abrir fila →</span></div>
                </a>
            </div>
        @endif
    </div>
</x-filament-panels::page>

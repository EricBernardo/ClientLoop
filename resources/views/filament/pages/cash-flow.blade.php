<x-filament-panels::page>
    @php($statement = $this->statement())

    <style>
        .cash-flow{display:grid;gap:1.25rem;max-width:72rem}
        .cash-flow__stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
        .cash-stat{display:flex;min-width:0;flex-direction:column;padding:1.2rem 1.25rem;border:1px solid #dce8e5;border-radius:1rem;background:#fff;box-shadow:0 1px 2px #1018280a}
        .cash-stat__label{margin:0;color:#667085;font-size:.82rem;font-weight:700;line-height:1.35}
        .cash-stat__value{margin:.7rem 0 0;color:#172033;font-size:clamp(1.45rem,2.4vw,1.85rem);font-weight:800;letter-spacing:-.04em;line-height:1}
        .cash-stat--in{border-top:3px solid #0f766e}
        .cash-stat--out{border-top:3px solid #c2410c}
        .cash-stat--balance{border-top:3px solid #134e4a}
        .cash-flow__ledger{overflow:hidden;border:1px solid #dce8e5;border-radius:1rem;background:#fff;box-shadow:0 1px 2px #1018280a}
        .cash-flow__table-wrap{overflow-x:auto}
        .cash-flow__ledger-head{padding:1rem 1.25rem;border-bottom:1px solid #e6eeec}
        .cash-flow__ledger-head h2{margin:0;color:#172033;font-size:1rem;font-weight:800}
        .cash-flow__ledger-head p{margin:.25rem 0 0;color:#667085;font-size:.82rem}
        .cash-table{width:100%;border-collapse:collapse;font-size:.9rem}
        .cash-table th{padding:.75rem 1.25rem;background:#f8fafc;color:#667085;font-size:.75rem;font-weight:700;letter-spacing:.04em;text-align:left;text-transform:uppercase}
        .cash-table td{padding:.9rem 1.25rem;border-top:1px solid #eef2f6;color:#172033;vertical-align:top}
        .cash-table th.num,.cash-table td.num{text-align:right;white-space:nowrap}
        .cash-table__note{display:block;margin-top:.15rem;color:#667085;font-size:.8rem}
        .cash-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.15rem .55rem;font-size:.75rem;font-weight:700}
        .cash-pill--entrada{background:#ccfbf1;color:#0f766e}
        .cash-pill--saida{background:#ffedd5;color:#c2410c}
        .cash-empty{padding:1.5rem;color:#667085;text-align:center}
        .dark .cash-stat,.dark .cash-flow__ledger{border-color:#374151;background:#111827;box-shadow:none}
        .dark .cash-stat__label,.dark .cash-flow__ledger-head p,.dark .cash-table th,.dark .cash-table__note,.dark .cash-empty{color:#98a2b3}
        .dark .cash-stat__value,.dark .cash-flow__ledger-head h2,.dark .cash-table td{color:#f9fafb}
        .dark .cash-flow__ledger-head,.dark .cash-table td{border-color:#1f2937}
        .dark .cash-table th{background:#0f172a}
        .dark .cash-pill--entrada{background:#134e4a;color:#99f6e4}
        .dark .cash-pill--saida{background:#7c2d12;color:#fdba74}
        @media(max-width:760px){.cash-flow__stats{grid-template-columns:1fr}}
    </style>

    <div class="cash-flow">
        <section class="cash-flow__stats" aria-label="Resumo do período">
            <article class="cash-stat cash-stat--in">
                <p class="cash-stat__label">Entradas</p>
                <p class="cash-stat__value">R$ {{ $statement['income'] }}</p>
            </article>
            <article class="cash-stat cash-stat--out">
                <p class="cash-stat__label">Saídas</p>
                <p class="cash-stat__value">R$ {{ $statement['expense'] }}</p>
            </article>
            <article class="cash-stat cash-stat--balance">
                <p class="cash-stat__label">Saldo</p>
                <p class="cash-stat__value">R$ {{ $statement['balance'] }}</p>
            </article>
        </section>

        <form wire:submit="addExpense">
            {{ $this->form }}
            <div style="margin-top:1rem">
                <x-filament::button type="submit">Lançar saída</x-filament::button>
            </div>
        </form>

        <section class="cash-flow__ledger">
            <div class="cash-flow__ledger-head">
                <h2>Lançamentos</h2>
                <p>Entradas do recibo pago e saídas lançadas neste período.</p>
            </div>
            <div class="cash-flow__table-wrap">
            <table class="cash-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Lançamento</th>
                        <th>Tipo</th>
                        <th class="num">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['entries'] as $entry)
                        <tr>
                            <td>{{ $entry->occurred_on->format('d/m/Y') }}</td>
                            <td>
                                {{ $entry->description() }}
                                @if($entry->notes)
                                    <span class="cash-table__note">{{ $entry->notes }}</span>
                                @endif
                            </td>
                            <td><span class="cash-pill cash-pill--{{ $entry->direction->value }}">{{ $entry->direction->label() }}</span></td>
                            <td class="num">R$ {{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="cash-empty">Nenhum lançamento neste período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>

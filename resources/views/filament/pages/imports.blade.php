<x-filament-panels::page>
    <style>
        .imports-shell{display:grid;gap:1.5rem}.imports-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(17rem,.8fr);gap:1.5rem;align-items:start}.import-card{border:1px solid rgb(229 231 235);border-radius:1rem;background:#fff;box-shadow:0 1px 2px #1018280d}.dark .import-card{background:rgb(17 24 39);border-color:rgb(55 65 81)}.import-card__head{padding:1.3rem 1.4rem;border-bottom:1px solid rgb(229 231 235)}.dark .import-card__head{border-color:rgb(55 65 81)}.import-card__head h2{font-size:1.02rem;font-weight:700;margin:0}.import-card__head p{font-size:.86rem;color:rgb(107 114 128);margin:.25rem 0 0}.import-card__body{padding:1.4rem}.import-field{display:grid;gap:.45rem;margin-bottom:1.15rem}.import-field label,.mapping-label{font-size:.88rem;font-weight:650;color:rgb(55 65 81)}.dark .import-field label,.dark .mapping-label{color:rgb(229 231 235)}.import-control{width:100%;min-height:2.7rem;padding:.6rem .75rem;border:1px solid rgb(209 213 219);border-radius:.65rem;background:#fff;color:rgb(17 24 39);font:inherit}.dark .import-control{background:rgb(31 41 55);border-color:rgb(75 85 99);color:#fff}.file-drop{display:flex;gap:.85rem;align-items:center;padding:1rem;border:1px dashed rgb(148 163 184);border-radius:.8rem;background:rgb(248 250 252)}.dark .file-drop{background:rgb(31 41 55);border-color:rgb(75 85 99)}.file-icon{width:2.4rem;height:2.4rem;display:grid;place-items:center;flex:none;border-radius:.65rem;background:rgb(220 252 231);color:rgb(21 128 61);font-size:1.2rem}.file-drop p{margin:0;font-size:.87rem;color:rgb(75 85 99)}.dark .file-drop p{color:rgb(209 213 219)}.file-drop small{display:block;margin-top:.12rem;color:rgb(107 114 128)}.mapping-area{margin-top:1.5rem;padding-top:1.4rem;border-top:1px solid rgb(229 231 235)}.dark .mapping-area{border-color:rgb(55 65 81)}.mapping-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem}.required{color:rgb(220 38 38)}.help-card{padding:1.4rem;border-radius:1rem;background:linear-gradient(145deg,#ecfdf5,#f0fdfa);border:1px solid #b7e4d7}.dark .help-card{background:rgb(20 83 75 / .25);border-color:rgb(19 78 74)}.help-card h2{font-size:1rem;margin:0 0 .6rem}.help-card p,.help-card li{font-size:.87rem;color:#315d56}.dark .help-card p,.dark .help-card li{color:#b6e5dc}.help-card ul{padding-left:1.1rem;margin:.8rem 0}.history-card{overflow:hidden}.history-table{width:100%;border-collapse:collapse;text-align:left}.history-table th{padding:.8rem 1.35rem;background:rgb(249 250 251);font-size:.73rem;color:rgb(107 114 128);font-weight:750;letter-spacing:.04em;text-transform:uppercase}.dark .history-table th{background:rgb(31 41 55);color:rgb(156 163 175)}.history-table td{padding:1rem 1.35rem;border-top:1px solid rgb(229 231 235);font-size:.9rem;vertical-align:top}.dark .history-table td{border-color:rgb(55 65 81)}.status{display:inline-flex;border-radius:99px;padding:.25rem .6rem;font-size:.76rem;font-weight:700}.status--queued{background:#eff6ff;color:#1d4ed8}.status--processing{background:#fff7ed;color:#c2410c}.status--completed{background:#ecfdf5;color:#047857}.status--failed{background:#fef2f2;color:#b91c1c}.import-errors summary{cursor:pointer;color:#b42318;font-weight:700;white-space:nowrap}.import-errors summary:hover{text-decoration:underline}.import-errors ul{margin:.65rem 0 0;padding-left:1.1rem;min-width:18rem;max-width:28rem}.import-errors li{margin:.35rem 0;color:rgb(71 84 103);line-height:1.4}.dark .import-errors li{color:rgb(209 213 219)}.empty-history{text-align:center;color:rgb(107 114 128);padding:2.5rem!important}@media(max-width:900px){.imports-grid{grid-template-columns:1fr}.mapping-grid{grid-template-columns:1fr}.history-table th,.history-table td{padding:.8rem}.history-table{min-width:52rem}.history-card{overflow-x:auto}}
        .template-links{display:grid;gap:.55rem;margin-top:1rem}.template-link{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.65rem .75rem;border:1px solid #99d9ca;border-radius:.6rem;background:#fff;color:#115e59;font-size:.84rem;font-weight:700;text-decoration:none}.template-link:hover{background:#f0fdfa}.dark .template-link{background:rgb(17 24 39);border-color:rgb(20 184 166);color:#99f6e4}
    </style>

    <div class="imports-shell">
        <div class="imports-grid">
            <form wire:submit="start" class="import-card">
                <div class="import-card__head">
                    <h2>Importar responsáveis e pets</h2>
                    <p>Envie a sua lista e informe como as colunas do arquivo devem ser lidas.</p>
                </div>
                <div class="import-card__body">
                    <div class="import-field">
                        <label for="import-file">Arquivo CSV</label>
                        <div class="file-drop">
                            <div class="file-icon">↥</div>
                            <div><p>Selecione um arquivo CSV de até 10 MB</p><small>Após selecionar, confira o mapeamento das colunas abaixo.</small></div>
                        </div>
                        <input id="import-file" type="file" wire:model="file" accept=".csv,text/csv" class="import-control" />
                        @error('file') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($headers)
                        <div class="mapping-area">
                            <div class="import-field"><label>Mapeamento de colunas</label><span class="text-sm text-gray-500">Associe cada campo do ClientLoop à coluna correspondente do seu arquivo.</span></div>
                            <div class="mapping-grid">
                                @foreach ($this->fields() as $field => $label)
                                    <div class="import-field" style="margin-bottom:0">
                                        <label class="mapping-label" for="mapping-{{ $field }}">{{ $label }}@if(array_key_exists($field, $this->requiredFields())) <span class="required">*</span>@endif</label>
                                        <select id="mapping-{{ $field }}" wire:model="mapping.{{ $field }}" class="import-control">
                                            <option value="">Não importar</option>
                                            @foreach ($headers as $header)<option value="{{ $header }}">{{ $header }}</option>@endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-6"><x-filament::button type="submit" size="lg" wire:loading.attr="disabled" wire:target="file,start">Enviar para importação</x-filament::button></div>
                </div>
            </form>

            <aside class="help-card">
                <h2>Antes de importar</h2>
                <p>O processamento acontece em segundo plano. Você poderá acompanhar o resultado nesta página.</p>
                <ul>
                    <li><strong>Responsáveis:</strong> nome e telefone são obrigatórios.</li>
                    <li><strong>Pet:</strong> é opcional, mas ajuda a começar a usar a agenda logo depois.</li>
                    <li>Telefones duplicados atualizam o responsável, sem criar outra pessoa.</li>
                </ul>
                <p>Use datas com dia, mês e ano quando preencher os campos opcionais de atendimento.</p>
                <div class="template-links">
                    <a class="template-link" href="{{ route('imports.template', ['type' => 'customers']) }}">Baixar modelo de responsáveis e pets <span>↓</span></a>
                </div>
            </aside>
        </div>

        <section class="import-card history-card">
            <div class="import-card__head"><h2>Histórico de importações</h2><p>Últimas 12 importações realizadas pela sua empresa.</p></div>
            <table class="history-table">
                <thead><tr><th>Tipo</th><th>Situação</th><th>Criados</th><th>Atualizados</th><th>Erros</th><th>Detalhes</th></tr></thead>
                <tbody>
                    @forelse ($this->runs as $run)
                        @php($status = ['queued' => 'Na fila', 'processing' => 'Processando', 'completed' => 'Concluída', 'failed' => 'Falhou'][$run->status] ?? $run->status)
                        @php($errors = $run->displayErrors())
                        <tr><td>Responsáveis e pets</td><td><span class="status status--{{ $run->status }}">{{ $status }}</span></td><td>{{ $run->created_count }}</td><td>{{ $run->updated_count }}</td><td>{{ count($errors) }}</td><td>@if (count($errors))<details class="import-errors"><summary>Ver erros</summary><ul>@foreach ($errors as $line => $message)<li><strong>{{ is_numeric($line) ? 'Linha '.$line : 'Arquivo' }}:</strong> {{ $message }}</li>@endforeach</ul></details>@else<span class="text-gray-500">Sem erros</span>@endif</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty-history">Nenhuma importação realizada ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>

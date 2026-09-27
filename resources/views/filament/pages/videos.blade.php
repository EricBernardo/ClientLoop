<x-filament-panels::page>
    <style>
        .videos-shell{display:grid;max-width:72rem;gap:1.75rem}.videos-hero{padding:2rem;border:1px solid #99d9ca;border-radius:1.25rem;background:linear-gradient(135deg,#ecfdf5,#f0fdfa)}.videos-eyebrow{margin:0 0 .65rem;color:#0f766e;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.videos-hero h2{max-width:42rem;margin:0;color:#134e4a;font-size:clamp(1.45rem,3vw,2.15rem);font-weight:800;letter-spacing:-.04em;line-height:1.15}.videos-hero p{max-width:45rem;margin:.8rem 0 0;color:#315d56;font-size:1rem;line-height:1.6}.videos-hero a{color:#0f766e;font-weight:800}.videos-list{display:grid;gap:1rem}.videos-card{display:grid;gap:1rem;padding:1.2rem;border:1px solid #dce8e5;border-radius:1rem;background:#fff;box-shadow:0 1px 2px #1018280a}@media(min-width:800px){.videos-card{grid-template-columns:16rem minmax(0,1fr);align-items:center}}.videos-number{display:grid;place-items:center;width:2rem;height:2rem;margin-bottom:.85rem;border-radius:.65rem;background:#ccfbf1;color:#0f766e;font-size:.85rem;font-weight:850}.videos-card h3{margin:0;color:#172033;font-size:1rem;font-weight:800}.videos-card p{margin:.45rem 0 0;color:#667085;font-size:.88rem;line-height:1.5}.videos-player,.videos-audio{width:100%;border-radius:.85rem}.videos-player{background:#0f172a}.videos-audio{display:block}.videos-missing{padding:1rem;border:1px dashed #99d9ca;border-radius:.85rem;background:#f8fafc;color:#315d56;font-size:.82rem;line-height:1.5}.videos-missing code{color:#134e4a;font-weight:700}.dark .videos-hero{border-color:#167c72;background:linear-gradient(135deg,#064e3b99,#134e4a80)}.dark .videos-hero h2{color:#ccfbf1}.dark .videos-hero p{color:#b6e5dc}.dark .videos-hero a{color:#99f6e4}.dark .videos-card{border-color:#374151;background:#111827}.dark .videos-number{background:#14b8a63d;color:#99f6e4}.dark .videos-card h3{color:#f9fafb}.dark .videos-card p,.dark .videos-missing{color:#98a2b3}.dark .videos-missing{border-color:#167c72;background:#1f2937}.dark .videos-missing code{color:#ccfbf1}@media(max-width:640px){.videos-hero{padding:1.4rem}}
    </style>

    <div class="videos-shell">
        <section class="videos-hero">
            <p class="videos-eyebrow">Série de uso</p>
            <h2>Veja o pet shop funcionando, um vídeo por etapa.</h2>
            <p>São oito vídeos curtos, na ordem da rotina. O texto de cada gravação está no roteiro. O passo a passo escrito continua em <a href="{{ \App\Filament\Pages\HowToUse::getUrl() }}">Como usar</a>.</p>
        </section>

        <div class="videos-list">
            @foreach ($videos as $video)
                <article class="videos-card">
                    <div>
                        <div class="videos-number">{{ $loop->iteration }}</div>
                        <h3>{{ $video['title'] }}</h3>
                        <p>{{ $video['summary'] }}</p>
                    </div>

                    @if ($video['videoUrl'])
                        <video class="videos-player" controls preload="metadata" src="{{ $video['videoUrl'] }}"></video>
                    @elseif ($video['audioUrl'])
                        <div>
                            <audio class="videos-audio" controls preload="metadata" src="{{ $video['audioUrl'] }}"></audio>
                            <p>Narração pronta. A gravação da tela substitui este áudio quando o arquivo <code>{{ $video['file'] }}</code> estiver na mesma pasta.</p>
                        </div>
                    @else
                        <div class="videos-missing">
                            Ainda sem mídia. A narração fica em <code>storage/app/public/videos/{{ $video['audioFile'] }}</code> e a gravação da tela em <code>storage/app/public/videos/{{ $video['file'] }}</code>.
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

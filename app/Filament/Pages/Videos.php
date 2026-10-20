<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LimitsToPetShop;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class Videos extends Page
{
    use LimitsToPetShop;

    protected static ?string $navigationLabel = 'Vídeos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'videos';

    protected static ?string $title = 'Vídeos do ClientLoop';

    protected string $view = 'filament.pages.videos';

    /**
     * @return list<array{title: string, file: string, summary: string, onLanding: bool}>
     */
    public static function catalog(): array
    {
        return [
            [
                'title' => 'O que o ClientLoop faz',
                'file' => '01-o-que-o-clientloop-faz.mp4',
                'summary' => 'O painel, a agenda de hoje e o que a rotina pede todo dia.',
                'onLanding' => true,
            ],
            [
                'title' => 'Horários e serviços',
                'file' => '02-horarios-e-servicos.mp4',
                'summary' => 'Expediente, intervalo da agenda e a duração de banho e tosa.',
                'onLanding' => false,
            ],
            [
                'title' => 'Responsável, pet e lista pronta',
                'file' => '03-responsavel-pet-e-lista.mp4',
                'summary' => 'Quem recebe o WhatsApp, o pet da agenda e a importação da planilha.',
                'onLanding' => false,
            ],
            [
                'title' => 'Pacotes',
                'file' => '04-pacotes.mp4',
                'summary' => 'O modelo, a ordem das visitas e a venda marcada como paga.',
                'onLanding' => false,
            ],
            [
                'title' => 'Marcar um horário',
                'file' => '05-marcar-um-horario.mp4',
                'summary' => 'O horário livre, o pacote da próxima etapa e a duração na agenda.',
                'onLanding' => true,
            ],
            [
                'title' => 'Confirmar pelo WhatsApp',
                'file' => '06-confirmar-pelo-whatsapp.mp4',
                'summary' => 'A mensagem pronta na fila e o que acontece quando ninguém responde.',
                'onLanding' => true,
            ],
            [
                'title' => 'Concluir, faltar, cancelar e marcar a próxima visita',
                'file' => '07-concluir-e-proxima-visita.mp4',
                'summary' => 'A baixa do crédito, a falta, o cancelamento e a etapa seguinte do pacote.',
                'onLanding' => false,
            ],
            [
                'title' => 'O que o painel acompanha depois',
                'file' => '08-o-que-o-painel-acompanha.mp4',
                'summary' => 'Sino, relatórios, lista de espera, campanhas, tosador e histórico.',
                'onLanding' => false,
            ],
        ];
    }

    /**
     * @return list<array{title: string, summary: string, videoUrl: string}>
     */
    public static function forLanding(): array
    {
        $disk = Storage::disk('videos');
        $videos = [];

        foreach (self::catalog() as $video) {
            if (! $video['onLanding'] || ! $disk->exists($video['file'])) {
                continue;
            }

            $videos[] = [
                'title' => $video['title'],
                'summary' => $video['summary'],
                'videoUrl' => $disk->url($video['file']),
            ];
        }

        return $videos;
    }

    /**
     * @return array{videos: list<array{title: string, file: string, audioFile: string, summary: string, videoUrl: ?string, audioUrl: ?string}>}
     */
    protected function getViewData(): array
    {
        $disk = Storage::disk('videos');
        $videos = [];

        foreach (self::catalog() as $video) {
            $audioFile = str_replace('.mp4', '.mp3', $video['file']);
            $videoPath = $video['file'];
            $audioPath = $audioFile;

            $videos[] = [
                ...$video,
                'audioFile' => $audioFile,
                'videoUrl' => $disk->exists($videoPath) ? $disk->url($videoPath) : null,
                'audioUrl' => $disk->exists($audioPath) ? $disk->url($audioPath) : null,
            ];
        }

        return ['videos' => $videos];
    }
}

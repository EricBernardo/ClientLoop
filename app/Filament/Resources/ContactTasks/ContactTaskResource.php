<?php

namespace App\Filament\Resources\ContactTasks;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\ContactTasks\Pages\ListContactTasks;
use App\Models\ContactTask;
use App\Services\ContactTaskService;
use App\Services\WhatsAppSender;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ContactTaskResource extends Resource
{
    protected static ?string $model = ContactTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return 'Fila de contatos';
    }

    public static function getModelLabel(): string
    {
        return 'tarefa de contato';
    }

    public static function getPluralModelLabel(): string
    {
        return 'tarefas de contato';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_at', 'asc')
            ->modifyQueryUsing(function (Builder $query): Builder {
                return match (request('view')) {
                    'pending' => $query->where('status', 'pending'),
                    'today' => $query->where('status', 'pending')->whereDate('due_at', today()),
                    'late' => $query->where('status', 'pending')->where('due_at', '<', now()),
                    'confirmation' => $query->where('status', 'pending')->where('type', 'confirmation'),
                    'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                    default => $query,
                };
            })
            ->columns([
                TextColumn::make('customer.name')->label('Responsável')->searchable(), TextColumn::make('type')->label('Tipo')->badge()->color(fn (?string $state): string => InterfaceLabels::contactTypeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::contactType($state)), TextColumn::make('priority')->label('Prioridade')->badge()->color(fn (?string $state): string => InterfaceLabels::priorityColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::priority($state)), TextColumn::make('due_at')->label('Vencimento')->dateTime('d/m H:i')->sortable(), TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::taskStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::taskStatus($state)), TextColumn::make('outcome')->label('Resultado')->badge()->color(fn (?string $state): string => InterfaceLabels::taskOutcomeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::taskOutcome($state)),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options(['pending' => 'Pendente', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'])->searchable(), SelectFilter::make('type')->label('Tipo')->options(InterfaceLabels::contactTypes())->searchable(),
            ])
            ->recordActions([
                Action::make('whatsappRegistrar')
                    ->label('WhatsApp e registrar')
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->visible(fn (ContactTask $record) => $record->status === 'pending' && $record->whatsappUrl() !== null)
                    ->modalHeading('Contatar no WhatsApp')
                    ->modalDescription('Abra a conversa, envie a mensagem e registre o resultado nesta mesma tela.')
                    ->modalSubmitActionLabel('Salvar resultado')
                    ->form([
                        Placeholder::make('open_whatsapp')
                            ->label('1. Enviar mensagem')
                            ->content(function (ContactTask $record): HtmlString {
                                $apiConfigured = filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
                                $cacheKey = 'whatsapp-send-ui-'.$record->id.'-'.(auth()->id() ?? 'guest');
                                $result = cache()->remember($cacheKey, now()->addMinutes(5), function () use ($record, $apiConfigured): array {
                                    return $apiConfigured
                                        ? app(WhatsAppSender::class)->send($record)
                                        : ['mode' => 'manual', 'url' => $record->whatsappUrl(), 'sent' => false, 'error' => null];
                                });

                                $note = '';
                                if ($apiConfigured && ($result['sent'] ?? false)) {
                                    $note = '<p class="mt-2 text-sm text-success-600 dark:text-success-400">Mensagem enviada automaticamente pela API do WhatsApp.</p>';
                                } elseif ($apiConfigured && filled($result['error'] ?? null)) {
                                    $note = '<p class="mt-2 text-sm text-warning-600 dark:text-warning-400">API configurada, mas o envio automático falhou: '.e((string) $result['error']).'. Use o link manual abaixo.</p>';
                                } elseif ($apiConfigured) {
                                    $note = '<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">API do WhatsApp configurada neste ambiente.</p>';
                                }

                                $url = $result['url'] ?? $record->whatsappUrl();

                                return new HtmlString(
                                    '<a href="'.e((string) $url).'" target="_blank" rel="noopener" class="fi-link text-sm font-bold text-primary-600 underline dark:text-primary-400">Abrir WhatsApp com a mensagem pronta →</a>'
                                    .$note
                                    .'<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Depois volte aqui e escolha o resultado abaixo.</p>'
                                );
                            }),
                        Select::make('outcome')
                            ->label('2. Resultado')
                            ->options([
                                'confirmed' => 'Confirmou',
                                'reschedule_requested' => 'Pediu alteração',
                                'scheduled' => 'Agendou',
                                'no_response' => 'Sem resposta',
                                'opt_out' => 'Não receber contato',
                            ])
                            ->helperText(fn (ContactTask $record): ?string => $record->type === 'confirmation' && $record->attempts()->count() === 0
                                ? 'Em confirmação, a primeira “Sem resposta” mantém a tarefa na fila para uma segunda tentativa.'
                                : null)
                            ->searchable()
                            ->required(),
                        Textarea::make('note')->label('Observação'),
                    ])
                    ->action(function (ContactTask $record, array $data) {
                        app(ContactTaskService::class)->complete($record, $data['outcome'], $data['note'] ?? null);

                        if ($data['outcome'] === 'scheduled') {
                            return redirect(AppointmentResource::getUrl('create', [
                                'customer_id' => $record->customer_id,
                            ]));
                        }
                    }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhuma tarefa na fila')
            ->emptyStateDescription('Quando houver confirmações ou retornos pendentes, eles aparecem aqui.')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactTasks::route('/'),
        ];
    }
}

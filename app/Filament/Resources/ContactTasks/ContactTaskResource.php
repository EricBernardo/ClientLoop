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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalSubmitActionLabel('Salvar resultado')
                    ->form([
                        View::make('filament.contact-tasks.whatsapp-modal')
                            ->viewData(fn (ContactTask $record): array => self::whatsappModalData($record)),
                        Section::make('Registrar resultado')
                            ->description('Escolha o que a pessoa respondeu.')
                            ->compact()
                            ->schema([
                                ToggleButtons::make('outcome')
                                    ->label('Resultado')
                                    ->options([
                                        'confirmed' => 'Confirmou',
                                        'reschedule_requested' => 'Pediu alteração',
                                        'scheduled' => 'Agendou',
                                        'no_response' => 'Sem resposta',
                                        'opt_out' => 'Não receber contato',
                                    ])
                                    ->colors([
                                        'confirmed' => 'success',
                                        'reschedule_requested' => 'warning',
                                        'scheduled' => 'info',
                                        'no_response' => 'gray',
                                        'opt_out' => 'danger',
                                    ])
                                    ->inline()
                                    ->helperText(fn (ContactTask $record): ?string => $record->type === 'confirmation' && $record->attempts()->where('outcome', 'no_response')->count() === 0
                                        ? 'Em confirmação, a primeira “Sem resposta” mantém a tarefa na fila para uma segunda tentativa.'
                                        : null)
                                    ->required(),
                                Textarea::make('note')->label('Observação')->rows(3),
                            ]),
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

    /**
     * @return array{task: ContactTask, url: string, apiConfigured: bool, sent: bool, error: ?string}
     */
    private static function whatsappModalData(ContactTask $record): array
    {
        app(ContactTaskService::class)->ensureConfirmationLink($record);
        $record->refresh();
        $record->loadMissing(['customer', 'appointment.pet', 'appointment.service']);

        $apiConfigured = filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
        $cacheKey = 'whatsapp-send-ui-'.$record->id.'-'.md5((string) $record->rendered_message).'-'.(auth()->id() ?? 'guest');
        $result = cache()->remember($cacheKey, now()->addMinutes(5), function () use ($record, $apiConfigured): array {
            return $apiConfigured
                ? app(WhatsAppSender::class)->send($record)
                : ['mode' => 'manual', 'url' => $record->whatsappUrl(), 'sent' => false, 'error' => null];
        });

        return [
            'task' => $record,
            'url' => (string) ($result['url'] ?? $record->whatsappUrl()),
            'apiConfigured' => $apiConfigured,
            'sent' => (bool) ($result['sent'] ?? false),
            'error' => filled($result['error'] ?? null) ? (string) $result['error'] : null,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactTasks::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ContactTasks;

use App\Filament\Resources\ContactTasks\Pages\ListContactTasks;
use App\Models\ContactTask;
use App\Services\ContactTaskService;
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
                            ->content(fn (ContactTask $record): HtmlString => new HtmlString(
                                '<a href="'.e($record->whatsappUrl()).'" target="_blank" rel="noopener" class="fi-link text-sm font-bold text-primary-600 underline dark:text-primary-400">Abrir WhatsApp com a mensagem pronta →</a>'
                                .'<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Depois volte aqui e escolha o resultado abaixo.</p>'
                            )),
                        Select::make('outcome')
                            ->label('2. Resultado')
                            ->options([
                                'confirmed' => 'Confirmou',
                                'reschedule_requested' => 'Pediu alteração',
                                'scheduled' => 'Agendou',
                                'no_response' => 'Sem resposta',
                                'opt_out' => 'Não receber contato',
                            ])
                            ->searchable()
                            ->required(),
                        Textarea::make('note')->label('Observação'),
                    ])
                    ->action(fn (ContactTask $record, array $data) => app(ContactTaskService::class)->complete($record, $data['outcome'], $data['note'] ?? null)),
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

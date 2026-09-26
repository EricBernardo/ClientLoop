<?php

namespace App\Filament\Resources\ContactTasks;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use App\Filament\Resources\ContactTasks\Pages\ListContactTasks;
use App\Models\ContactTask;
use App\Services\ContactTaskService;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactTaskResource extends Resource
{
    protected static ?string $model = ContactTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
        return $schema
            ->components([
                Select::make('customer_id')->label('Responsável')->relationship('customer', 'name')->searchable()->preload()->required(), Select::make('type')->label('Tipo')->options(InterfaceLabels::contactTypes())->searchable()->required(), Select::make('priority')->label('Prioridade')->options(InterfaceLabels::priorities())->searchable()->default('normal'), HourlyDateTimePicker::make('due_at')->label('Vencimento')->required(), Textarea::make('rendered_message')->label('Mensagem preparada')->columnSpanFull(),
            ]);
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
                Action::make('whatsapp')->label('Abrir WhatsApp')->color('success')->icon('heroicon-o-chat-bubble-left-right')->url(fn (ContactTask $record) => $record->whatsappUrl())->openUrlInNewTab()->visible(fn (ContactTask $record) => $record->status === 'pending' && $record->whatsappUrl() !== null),
                Action::make('registrar')->label('Registrar resultado')->color('info')->visible(fn (ContactTask $record) => $record->status === 'pending')->form([Select::make('outcome')->label('Resultado')->options(['confirmed' => 'Confirmou', 'reschedule_requested' => 'Pediu alteração', 'scheduled' => 'Agendou', 'no_response' => 'Sem resposta', 'opt_out' => 'Não receber contato'])->searchable()->required(), Textarea::make('note')->label('Observação')])->action(fn (ContactTask $record, array $data) => app(ContactTaskService::class)->complete($record, $data['outcome'], $data['note'] ?? null)),
                DeleteAction::make(),
            ])
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

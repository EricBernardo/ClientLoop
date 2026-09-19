<?php

namespace App\Filament\Resources\ContactTasks;

use App\Filament\Resources\ContactTasks\Pages\ListContactTasks;
use App\Models\ContactTask;
use App\Services\ContactTaskService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
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
                Select::make('customer_id')->relationship('customer', 'name')->searchable()->preload()->required(), Select::make('type')->options(['confirmation' => 'Confirmação', 'recall' => 'Recall', 'reactivation' => 'Reativação', 'follow_up' => 'Follow-up'])->required(), Select::make('priority')->options(['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta'])->default('normal'), DateTimePicker::make('due_at')->required(), Textarea::make('rendered_message')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return match (request('view')) {
                    'today' => $query->where('status', 'pending')->whereDate('due_at', today()),
                    'late' => $query->where('status', 'pending')->where('due_at', '<', now()),
                    'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                    default => $query,
                };
            })
            ->columns([
                TextColumn::make('customer.name')->label('Cliente')->searchable(), TextColumn::make('type')->badge(), TextColumn::make('priority')->badge(), TextColumn::make('due_at')->dateTime('d/m H:i')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('outcome')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pendente', 'completed' => 'Concluída', 'cancelled' => 'Cancelada']), SelectFilter::make('type')->options(['confirmation' => 'Confirmação', 'recall' => 'Recall', 'reactivation' => 'Reativação', 'follow_up' => 'Follow-up']),
            ])
            ->recordActions([
                Action::make('whatsapp')->label('Abrir WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->url(fn (ContactTask $record) => $record->whatsappUrl())->openUrlInNewTab()->visible(fn (ContactTask $record) => $record->status === 'pending' && $record->whatsappUrl() !== null),
                Action::make('registrar')->label('Registrar resultado')->visible(fn (ContactTask $record) => $record->status === 'pending')->form([Select::make('outcome')->label('Resultado')->options(['confirmed' => 'Confirmou', 'reschedule_requested' => 'Pediu alteração', 'interested' => 'Interessado', 'scheduled' => 'Agendou', 'no_response' => 'Sem resposta', 'lost' => 'Perdido', 'opt_out' => 'Não receber contato'])->required(), Textarea::make('note')->label('Observação')])->action(fn (ContactTask $record, array $data) => app(ContactTaskService::class)->complete($record, $data['outcome'], $data['note'] ?? null)),
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

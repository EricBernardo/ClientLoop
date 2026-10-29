<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use App\Support\InterfaceLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'contactTasks';

    protected static ?string $title = 'Tarefas de contato';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')->label('Cliente')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->color(fn (?string $state): string => InterfaceLabels::contactTypeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::contactType($state)),
                TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::taskStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::taskStatus($state)),
                TextColumn::make('due_at')->label('Vencimento')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('outcome')->label('Resultado')->badge()->color(fn (?string $state): string => InterfaceLabels::taskOutcomeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::taskOutcome($state))->placeholder('—'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhuma tarefa de contato');
    }
}

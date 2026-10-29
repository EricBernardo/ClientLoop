<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsageRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'usageRecords';

    protected static ?string $title = 'Uso mensal';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('period', 'desc')
            ->columns([
                TextColumn::make('period')->label('Período')->searchable(),
                TextColumn::make('contacts_count')->label('Contatos'),
                TextColumn::make('tasks_count')->label('Tarefas'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhum registro de uso');
    }
}

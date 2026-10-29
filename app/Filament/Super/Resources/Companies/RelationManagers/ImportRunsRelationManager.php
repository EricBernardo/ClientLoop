<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'importRuns';

    protected static ?string $title = 'Imports';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')->label('Tipo'),
                TextColumn::make('status')->label('Situação')->badge(),
                TextColumn::make('created_count')->label('Criados'),
                TextColumn::make('updated_count')->label('Atualizados'),
                TextColumn::make('user.name')->label('Quem')->placeholder('—'),
                TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhum import');
    }
}

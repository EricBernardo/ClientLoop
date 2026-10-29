<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static ?string $title = 'Clientes';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('phone')->label('Telefone')->searchable(),
                IconColumn::make('can_contact')->label('Pode contatar')->boolean()->getStateUsing(fn ($record): bool => $record->can_contact),
                TextColumn::make('last_activity_at')->label('Última atividade')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('created_at')->label('Cadastro')->dateTime('d/m/Y'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhum cliente');
    }
}

<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use App\Support\InterfaceLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Usuários';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Papel')->formatStateUsing(fn (?string $state): string => InterfaceLabels::userRole($state)),
                IconColumn::make('is_super_admin')->label('Superadmin')->boolean(),
                TextColumn::make('created_at')->label('Criado')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhum usuário');
    }
}

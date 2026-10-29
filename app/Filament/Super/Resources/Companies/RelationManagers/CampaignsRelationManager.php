<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use App\Support\InterfaceLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'campaigns';

    protected static ?string $title = 'Campanhas';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->color(fn (?string $state): string => InterfaceLabels::contactTypeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::contactType($state)),
                TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::campaignStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::campaignStatus($state)),
                TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('recipients_count')->label('Destinatários')->counts('recipients'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhuma campanha');
    }
}

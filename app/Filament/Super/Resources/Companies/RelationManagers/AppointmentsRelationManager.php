<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use App\Support\InterfaceLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Agendamentos';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'service', 'pet', 'vehicle']))
            ->columns([
                TextColumn::make('scheduled_at')->label('Data e horário')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer.name')->label('Cliente')->searchable(),
                TextColumn::make('service.name')->label('Serviço')->placeholder('—'),
                TextColumn::make('subject')->label('Pet / Veículo')->state(fn ($record): string => $record->pet?->name ?? $record->vehicle?->plate ?? '—'),
                TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::appointmentStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::appointmentStatus($state)),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhum agendamento');
    }
}

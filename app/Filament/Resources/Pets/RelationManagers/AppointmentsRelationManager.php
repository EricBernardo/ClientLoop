<?php

namespace App\Filament\Resources\Pets\RelationManagers;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Support\InterfaceLabels;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Atendimentos deste pet';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('service.name')->label('Serviço'),
                TextColumn::make('scheduled_at')->label('Data e horário')->dateTime('d/m/Y H:i'),
                TextColumn::make('duration_minutes')->label('Duração')->suffix(' min'),
                TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::appointmentStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::appointmentStatus($state)),
            ])
            ->headerActions([
                Action::make('novoAgendamento')->label('Novo agendamento')->icon('heroicon-o-calendar-days')->url(fn (): string => AppointmentResource::getUrl('create', ['customer_id' => $this->getOwnerRecord()->customer_id, 'pet_id' => $this->getOwnerRecord()->getKey()])),
            ])
            ->recordActions([
                EditAction::make()->url(fn (Appointment $record): string => AppointmentResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

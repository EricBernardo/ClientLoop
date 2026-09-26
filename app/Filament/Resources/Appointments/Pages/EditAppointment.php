<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\HandlesFriendlyValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Services\AppointmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAppointment extends EditRecord
{
    use HandlesFriendlyValidation;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('concluir')->label('Concluir atendimento')->color('success')->icon('heroicon-o-check-circle')->visible(in_array($record->status, ['scheduled', 'confirmed'], true))->action(function () use ($record): void {
                try {
                    app(AppointmentService::class)->complete($record);
                    $this->redirect(AppointmentResource::getUrl('edit', ['record' => $record->fresh()]));
                } catch (ValidationException $exception) {
                    $this->showFriendlyValidation($exception);
                }
            }),
            Action::make('falta')
                ->label('Falta')
                ->color('danger')
                ->icon('heroicon-o-user-minus')
                ->requiresConfirmation()
                ->modalHeading('Marcar falta')
                ->modalDescription('O horário fica livre na agenda. O crédito do pacote não é baixado.')
                ->visible(in_array($record->status, ['scheduled', 'confirmed'], true))
                ->action(function () use ($record): void {
                    try {
                        app(AppointmentService::class)->markNoShow($record);
                        $this->redirect(AppointmentResource::getUrl('edit', ['record' => $record->fresh()]));
                    } catch (ValidationException $exception) {
                        $this->showFriendlyValidation($exception);
                    }
                }),
            Action::make('cancelar')
                ->label('Cancelar')
                ->color('gray')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Cancelar agendamento')
                ->modalDescription('O horário fica livre na agenda. O crédito do pacote não é baixado.')
                ->visible(in_array($record->status, ['scheduled', 'confirmed', 'reschedule_requested'], true))
                ->action(function () use ($record): void {
                    try {
                        app(AppointmentService::class)->cancel($record);
                        $this->redirect(AppointmentResource::getUrl('edit', ['record' => $record->fresh()]));
                    } catch (ValidationException $exception) {
                        $this->showFriendlyValidation($exception);
                    }
                }),
            Action::make('agendarProximaEtapa')->label('Agendar próxima etapa')->color('primary')->icon('heroicon-o-calendar-days')->visible($record->status === 'completed' && AppointmentResource::nextPackageAppointmentUrl($record) !== null)->url(AppointmentResource::nextPackageAppointmentUrl($record) ?? AppointmentResource::getUrl('index')),
            DeleteAction::make(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            $this->showFriendlyValidation($exception);
        }
    }
}

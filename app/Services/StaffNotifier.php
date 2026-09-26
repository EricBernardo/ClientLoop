<?php

namespace App\Services;

use App\Filament\Pages\Imports;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\ImportRun;
use App\Models\UsageRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class StaffNotifier
{
    public function appointmentConfirmed(Appointment $appointment): void
    {
        $this->send($appointment->company_id, Notification::make()
            ->title('Presença confirmada')
            ->success()
            ->body($this->appointmentSummary($appointment))
            ->actions([
                Action::make('open')
                    ->label('Abrir horário')
                    ->button()
                    ->url(AppointmentResource::getUrl('edit', ['record' => $appointment], panel: 'company')),
            ]));
    }

    public function appointmentCancelled(Appointment $appointment): void
    {
        $this->send($appointment->company_id, Notification::make()
            ->title('Horário cancelado')
            ->warning()
            ->body($this->appointmentSummary($appointment))
            ->actions([
                Action::make('open')
                    ->label('Abrir horário')
                    ->button()
                    ->url(AppointmentResource::getUrl('edit', ['record' => $appointment], panel: 'company')),
            ]));
    }

    public function taskQuotaExhausted(Company $company): void
    {
        $record = UsageRecord::withoutGlobalScopes()->firstOrCreate([
            'company_id' => $company->id,
            'period' => now()->format('Y-m'),
        ]);

        $claimed = UsageRecord::withoutGlobalScopes()
            ->whereKey($record->id)
            ->whereNull('task_quota_notified_at')
            ->update(['task_quota_notified_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $this->send($company, Notification::make()
            ->title('Cota de tarefas esgotada')
            ->warning()
            ->body('Confirmações e retornos não serão criados até o próximo mês.'));
    }

    public function importFinished(ImportRun $run): void
    {
        $run->loadMissing('company');

        if ($run->status === 'failed') {
            $message = (string) ($run->displayErrors()['arquivo'] ?? 'Não foi possível processar o arquivo.');

            $this->send($run->company, Notification::make()
                ->title('Importação falhou')
                ->danger()
                ->body($message)
                ->actions([$this->importsAction()]));

            return;
        }

        if ($run->status !== 'completed') {
            return;
        }

        $this->send($run->company, Notification::make()
            ->title('Importação concluída')
            ->success()
            ->body($this->importSummary($run))
            ->actions([$this->importsAction()]));
    }

    public function campaignLaunched(Campaign $campaign, int $created): void
    {
        $tasks = $created === 1
            ? '1 tarefa criada na fila de contatos.'
            : "{$created} tarefas criadas na fila de contatos.";

        $this->send($campaign->company, Notification::make()
            ->title('Campanha ativada')
            ->success()
            ->body("{$campaign->name}: {$tasks}")
            ->actions([
                Action::make('open')
                    ->label('Ver campanha')
                    ->button()
                    ->url(CampaignResource::getUrl('edit', ['record' => $campaign], panel: 'company')),
            ]));
    }

    private function importsAction(): Action
    {
        return Action::make('open')
            ->label('Ver importações')
            ->button()
            ->url(Imports::getUrl(panel: 'company'));
    }

    private function appointmentSummary(Appointment $appointment): string
    {
        $appointment->loadMissing(['customer', 'pet', 'company']);
        $customer = $appointment->customer?->name ?? 'Responsável';
        $pet = $appointment->pet?->name;
        $who = $pet ? "{$pet} ({$customer})" : $customer;
        $timezone = $appointment->company?->timezone ?: config('app.timezone');
        $when = $appointment->scheduled_at->timezone($timezone)->format('d/m/Y H:i');

        return "{$who} em {$when}.";
    }

    private function importSummary(ImportRun $run): string
    {
        $created = (int) $run->created_count;
        $updated = (int) $run->updated_count;
        $createdText = $created === 1 ? '1 responsável criado' : "{$created} responsáveis criados";
        $updatedText = $updated === 1 ? '1 atualizado' : "{$updated} atualizados";
        $summary = "{$createdText}, {$updatedText}.";
        $errors = count($run->displayErrors());

        if ($errors === 1) {
            $summary .= ' 1 linha com erro.';
        } elseif ($errors > 1) {
            $summary .= " {$errors} linhas com erro.";
        }

        return $summary;
    }

    private function send(Company|int|null $company, Notification $notification): void
    {
        if (is_int($company)) {
            $company = Company::query()->find($company);
        }

        if (! $company) {
            return;
        }

        $users = $company->users()->where('is_super_admin', false)->get();

        if ($users->isEmpty()) {
            return;
        }

        $notification->sendToDatabase($users);
    }
}

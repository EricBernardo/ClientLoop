<?php

namespace App\Filament\Super\Resources\Companies\Pages;

use App\Filament\Super\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function resolveRecord(int|string $key): Company
    {
        /** @var Company $record */
        $record = parent::resolveRecord($key);
        $record->load(['owner', 'subscription.plan', 'currentUsage']);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('suspender')
                ->label('Suspender')
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->status === 'active')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'suspended']);
                    Notification::make()->success()->title('Empresa suspensa')->send();
                }),
            Action::make('ativar')
                ->label('Ativar')
                ->color('success')
                ->visible(fn (): bool => in_array($this->getRecord()->status, ['trial', 'suspended'], true))
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'active']);
                    Notification::make()->success()->title('Empresa ativada')->send();
                }),
            EditAction::make()->color('info'),
            CompanyResource::deleteCompanyAction()
                ->successRedirectUrl(CompanyResource::getUrl('index')),
        ];
    }
}

<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\RedirectsFirstVisit;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Company;
use App\Services\PhoneNormalizer;
use App\Services\QuotaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCustomer extends CreateRecord
{
    use RedirectsFirstVisit;

    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            app(QuotaService::class)->consumeContact(Company::findOrFail(auth()->user()->company_id));
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Não foi possível cadastrar o responsável')
                ->body((string) (collect($exception->errors())->flatten()->first() ?? 'Limite do plano atingido.'))
                ->send();

            $this->halt();
        }

        $data['phone'] = app(PhoneNormalizer::class)->normalize($data['phone']);

        return $data;
    }
}

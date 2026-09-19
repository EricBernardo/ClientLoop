<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Company;
use App\Services\PhoneNormalizer;
use App\Services\QuotaService;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        app(QuotaService::class)->consumeContact(Company::findOrFail(auth()->user()->company_id));
        $data['phone'] = app(PhoneNormalizer::class)->normalize($data['phone']);

        return $data;
    }
}

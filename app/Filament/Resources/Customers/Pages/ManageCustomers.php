<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Company;
use App\Services\PhoneNormalizer;
use App\Services\QuotaService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(function (array $data): array {
            $company = Company::findOrFail(auth()->user()->company_id);
            app(QuotaService::class)->consumeContact($company);
            $data['phone'] = app(PhoneNormalizer::class)->normalize($data['phone']);

            return $data;
        })];
    }
}

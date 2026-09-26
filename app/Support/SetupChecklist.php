<?php

namespace App\Support;

use App\Filament\Pages\BusinessSettings;
use App\Filament\Pages\HowToUse;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Company;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Pet;
use App\Models\Service;

class SetupChecklist
{
    /**
     * @return list<array{key:string,label:string,done:bool,url:string}>
     */
    public static function steps(Company $company): array
    {
        $hasServices = Service::query()->where('company_id', $company->id)->exists();
        $hasCustomers = Customer::query()->where('company_id', $company->id)->exists();
        $hasPets = Pet::query()->where('company_id', $company->id)->exists();
        $hasTemplates = MessageTemplate::query()->where('company_id', $company->id)->where('active', true)->exists();

        return [
            [
                'key' => 'hours',
                'label' => 'Confirmar horários de atendimento',
                'done' => $company->hours_configured_at !== null,
                'url' => BusinessSettings::getUrl(),
            ],
            [
                'key' => 'services',
                'label' => 'Cadastrar pelo menos um serviço',
                'done' => $hasServices,
                'url' => ServiceResource::getUrl('index'),
            ],
            [
                'key' => 'customers',
                'label' => 'Cadastrar um responsável',
                'done' => $hasCustomers,
                'url' => CustomerResource::getUrl('index'),
            ],
            [
                'key' => 'pets',
                'label' => 'Cadastrar um pet',
                'done' => $hasPets,
                'url' => PetResource::getUrl('index'),
            ],
            [
                'key' => 'templates',
                'label' => 'Deixar um modelo de mensagem pronto',
                'done' => $hasTemplates,
                'url' => MessageTemplateResource::getUrl('index'),
            ],
            [
                'key' => 'guide',
                'label' => 'Ver o guia completo',
                'done' => $company->guide_viewed_at !== null,
                'url' => HowToUse::getUrl(),
            ],
        ];
    }

    public static function coreComplete(Company $company): bool
    {
        return Service::query()->where('company_id', $company->id)->exists()
            && Customer::query()->where('company_id', $company->id)->exists()
            && Pet::query()->where('company_id', $company->id)->exists()
            && $company->hours_configured_at !== null;
    }

    public static function shouldShow(Company $company): bool
    {
        if ($company->onboarding_completed_at) {
            return false;
        }

        return ! self::coreComplete($company);
    }
}

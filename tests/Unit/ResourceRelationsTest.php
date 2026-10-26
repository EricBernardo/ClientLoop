<?php

namespace Tests\Unit;

use App\Enums\CompanyVertical;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\RelationManagers\AppointmentsRelationManager as CustomerAppointmentsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\PetsRelationManager;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Pets\RelationManagers\AppointmentsRelationManager as PetAppointmentsRelationManager;
use App\Filament\Resources\Pets\RelationManagers\PackagesRelationManager;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class ResourceRelationsTest extends TestCase
{
    public function test_responsavel_displays_its_pets_and_appointments(): void
    {
        $this->actingAsCompany();

        $this->assertSame([
            PetsRelationManager::class,
            CustomerAppointmentsRelationManager::class,
        ], CustomerResource::getRelations());
    }

    public function test_automotive_customer_hides_pets_and_appointments(): void
    {
        $this->actingAsCompany(CompanyVertical::Automotive);

        $this->assertSame([], CustomerResource::getRelations());
    }

    public function test_pet_displays_its_appointments_and_packages(): void
    {
        $this->assertSame([
            PetAppointmentsRelationManager::class,
            PackagesRelationManager::class,
        ], PetResource::getRelations());
    }

    private function actingAsCompany(CompanyVertical $vertical = CompanyVertical::PetShop): void
    {
        $company = new Company;
        $company->vertical = $vertical;
        $user = new User;
        $user->setRelation('company', $company);

        $this->actingAs($user);
    }
}

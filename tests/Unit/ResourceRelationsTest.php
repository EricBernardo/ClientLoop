<?php

namespace Tests\Unit;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\RelationManagers\AppointmentsRelationManager as CustomerAppointmentsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\PetsRelationManager;
use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Pets\RelationManagers\AppointmentsRelationManager as PetAppointmentsRelationManager;
use App\Filament\Resources\Pets\RelationManagers\PackagesRelationManager;
use PHPUnit\Framework\TestCase;

class ResourceRelationsTest extends TestCase
{
    public function test_responsavel_displays_its_pets_and_appointments(): void
    {
        $this->assertSame([
            PetsRelationManager::class,
            CustomerAppointmentsRelationManager::class,
        ], CustomerResource::getRelations());
    }

    public function test_pet_displays_its_appointments_and_packages(): void
    {
        $this->assertSame([
            PetAppointmentsRelationManager::class,
            PackagesRelationManager::class,
        ], PetResource::getRelations());
    }
}

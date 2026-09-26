<?php

namespace App\Filament\Resources\Pets\Pages;

use App\Filament\Concerns\RedirectsFirstVisit;
use App\Filament\Resources\Pets\PetResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePet extends CreateRecord
{
    use RedirectsFirstVisit;

    protected static string $resource = PetResource::class;
}

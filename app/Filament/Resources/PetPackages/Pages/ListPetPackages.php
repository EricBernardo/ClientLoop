<?php

namespace App\Filament\Resources\PetPackages\Pages;

use App\Filament\Resources\PetPackages\PetPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPetPackages extends ListRecords
{
    protected static string $resource = PetPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Vender pacote')->url(PetPackageResource::getUrl('create'))];
    }
}

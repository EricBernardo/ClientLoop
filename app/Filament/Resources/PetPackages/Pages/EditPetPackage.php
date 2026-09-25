<?php

namespace App\Filament\Resources\PetPackages\Pages;

use App\Filament\Resources\PetPackages\PetPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPetPackage extends EditRecord
{
    protected static string $resource = PetPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

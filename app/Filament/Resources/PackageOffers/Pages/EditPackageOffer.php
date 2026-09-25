<?php

namespace App\Filament\Resources\PackageOffers\Pages;

use App\Filament\Resources\PackageOffers\PackageOfferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPackageOffer extends EditRecord
{
    protected static string $resource = PackageOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

<?php

namespace App\Filament\Resources\PackageOffers\Pages;

use App\Filament\Resources\PackageOffers\PackageOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackageOffers extends ListRecords
{
    protected static string $resource = PackageOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Novo modelo')->url(PackageOfferResource::getUrl('create'))];
    }
}

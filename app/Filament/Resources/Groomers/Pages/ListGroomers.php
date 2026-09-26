<?php

namespace App\Filament\Resources\Groomers\Pages;

use App\Filament\Resources\Groomers\GroomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroomers extends ListRecords
{
    protected static string $resource = GroomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo tosador')->url(GroomerResource::getUrl('create')),
        ];
    }
}

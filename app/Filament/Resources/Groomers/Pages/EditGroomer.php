<?php

namespace App\Filament\Resources\Groomers\Pages;

use App\Filament\Resources\Groomers\GroomerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroomer extends EditRecord
{
    protected static string $resource = GroomerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

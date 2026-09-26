<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Concerns\RedirectsFirstVisit;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    use RedirectsFirstVisit;

    protected static string $resource = ServiceResource::class;
}

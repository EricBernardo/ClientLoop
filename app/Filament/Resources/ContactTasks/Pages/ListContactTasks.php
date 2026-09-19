<?php

namespace App\Filament\Resources\ContactTasks\Pages;

use App\Filament\Resources\ContactTasks\ContactTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListContactTasks extends ListRecords
{
    protected static string $resource = ContactTaskResource::class;
}

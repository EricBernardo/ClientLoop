<?php

namespace App\Filament\Resources\ContactTasks\Pages;

use App\Filament\Resources\ContactTasks\ContactTaskResource;
use Filament\Resources\Pages\ManageRecords;

class ManageContactTasks extends ManageRecords
{
    protected static string $resource = ContactTaskResource::class;
}

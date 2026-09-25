<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class HowToUse extends Page
{
    protected static ?string $navigationLabel = 'Como usar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInformationCircle;

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'how-to-use';

    protected static ?string $title = 'Como usar o ClientLoop';

    protected string $view = 'filament.pages.how-to-use';
}

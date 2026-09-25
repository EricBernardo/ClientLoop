<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\DateTimePicker;

class HourlyDateTimePicker extends DateTimePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hoursStep(1)
            ->minutesStep(60)
            ->seconds(false)
            ->weekStartsOnMonday();
    }
}

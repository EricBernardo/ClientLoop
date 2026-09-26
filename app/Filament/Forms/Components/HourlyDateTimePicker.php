<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\DateTimePicker;

class HourlyDateTimePicker extends DateTimePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $slotMinutes = (int) (auth()->user()?->company?->appointment_slot_minutes ?? 60);
        if (! in_array($slotMinutes, [15, 30, 60], true)) {
            $slotMinutes = 60;
        }

        $this
            ->hoursStep(1)
            ->minutesStep($slotMinutes)
            ->seconds(false)
            ->weekStartsOnMonday();
    }
}

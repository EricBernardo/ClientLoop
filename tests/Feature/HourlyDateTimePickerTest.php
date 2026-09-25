<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use Tests\TestCase;

class HourlyDateTimePickerTest extends TestCase
{
    public function test_it_offers_only_full_hour_time_slots(): void
    {
        $picker = HourlyDateTimePicker::make('scheduled_at');

        $this->assertSame(1, $picker->getHoursStep());
        $this->assertSame(60, $picker->getMinutesStep());
        $this->assertFalse($picker->hasSeconds());
    }
}

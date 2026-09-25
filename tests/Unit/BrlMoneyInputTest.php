<?php

namespace Tests\Unit;

use App\Filament\Forms\Components\BrlMoneyInput;
use PHPUnit\Framework\TestCase;

class BrlMoneyInputTest extends TestCase
{
    public function test_it_normalizes_brazilian_currency_values_for_storage(): void
    {
        $this->assertSame('1250.00', BrlMoneyInput::normalizeValue('1.250,00'));
        $this->assertSame('45.00', BrlMoneyInput::normalizeValue('45'));
        $this->assertSame('45.50', BrlMoneyInput::normalizeValue('45,5'));
    }

    public function test_it_formats_currency_values_for_display(): void
    {
        $this->assertSame('1.250,00', BrlMoneyInput::formatValue('1250.00'));
        $this->assertSame('45,00', BrlMoneyInput::formatValue(45));
    }

    public function test_it_normalizes_values_created_by_the_typing_mask(): void
    {
        $this->assertSame('4564.65', BrlMoneyInput::normalizeValue('4.564,65'));
    }
}

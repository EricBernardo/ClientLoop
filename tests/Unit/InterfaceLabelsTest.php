<?php

namespace Tests\Unit;

use App\Support\InterfaceLabels;
use PHPUnit\Framework\TestCase;

class InterfaceLabelsTest extends TestCase
{
    public function test_internal_values_have_portuguese_labels(): void
    {
        $this->assertSame('Agendado', InterfaceLabels::appointmentStatus('scheduled'));
        $this->assertSame('Concluído', InterfaceLabels::appointmentStatus('completed'));
        $this->assertSame('Retorno', InterfaceLabels::contactType('recall'));
        $this->assertSame('Confirmou', InterfaceLabels::taskOutcome('confirmed'));
        $this->assertSame('Agendou', InterfaceLabels::taskOutcome('scheduled'));
        $this->assertNotSame('Interessado', InterfaceLabels::taskOutcome('interested'));
        $this->assertNotSame('Perdido', InterfaceLabels::taskOutcome('lost'));
        $this->assertSame('Rascunho', InterfaceLabels::campaignStatus('draft'));
        $this->assertSame('Ativa', InterfaceLabels::campaignStatus('active'));
    }
}

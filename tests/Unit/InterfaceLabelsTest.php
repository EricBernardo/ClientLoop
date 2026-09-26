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
        $this->assertSame('Falta registrada', InterfaceLabels::activityEvent('appointment.no_show'));
        $this->assertSame('Conclusão desfeita', InterfaceLabels::activityEvent('appointment.undo_complete'));
        $this->assertSame('Sem resposta (nova tentativa)', InterfaceLabels::activityEvent('contact_task.no_response_retry'));
        $this->assertSame(['owner' => 'Dono', 'attendant' => 'Atendente'], InterfaceLabels::userRoles());
        $this->assertSame('Atendente', InterfaceLabels::userRole('attendant'));
        $this->assertNotSame('Interessado', InterfaceLabels::taskOutcome('interested'));
        $this->assertNotSame('Perdido', InterfaceLabels::taskOutcome('lost'));
        $this->assertSame('Rascunho', InterfaceLabels::campaignStatus('draft'));
        $this->assertSame('Ativa', InterfaceLabels::campaignStatus('active'));
        $this->assertSame(['normal' => 'Normal', 'high' => 'Alta'], InterfaceLabels::priorities());
        $this->assertArrayNotHasKey('low', InterfaceLabels::priorities());
    }
}

<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactTaskService
{
    public function __construct(private QuotaService $quota, private TemplateRenderer $renderer) {}

    public function create(Company $company, Customer $customer, string $type, \DateTimeInterface $dueAt, array $links = [], ?MessageTemplate $template = null): ?ContactTask
    {
        if (! $customer->can_contact || ! $this->quota->canCreateTasks($company)) {
            return null;
        }
        $template ??= MessageTemplate::withoutGlobalScopes()->where('company_id', $company->id)->where('type', $type)->where('active', true)->first();
        $cycleKey = $links['cycle_key'] ?? null;
        $existing = ContactTask::withoutGlobalScopes()->where('company_id', $company->id)->where('customer_id', $customer->id)->where('type', $type)->when($cycleKey, fn ($query) => $query->where('cycle_key', $cycleKey))->when($links['appointment'] ?? null, fn ($q, $a) => $q->where('appointment_id', $a->id))->when($links['opportunity'] ?? null, fn ($q, $o) => $q->where('opportunity_id', $o->id))->exists();
        if ($existing) {
            return null;
        }

        return DB::transaction(function () use ($company, $customer, $type, $dueAt, $links, $template) {
            $this->quota->consumeTasks($company);
            $appointment = $links['appointment'] ?? null;

            return ContactTask::withoutGlobalScopes()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'appointment_id' => $appointment?->id, 'opportunity_id' => ($links['opportunity'] ?? null)?->id, 'campaign_id' => ($links['campaign'] ?? null)?->id, 'message_template_id' => $template?->id, 'type' => $type, 'cycle_key' => $links['cycle_key'] ?? null, 'priority' => $type === 'confirmation' ? 'high' : 'normal', 'due_at' => $dueAt, 'rendered_message' => $template ? $this->renderer->render($template->body, $customer, $links['service'] ?? null, $appointment?->scheduled_at) : null]);
        });
    }

    public function complete(ContactTask $task, string $outcome, ?string $note = null): void
    {
        if (($task->status ?? 'pending') !== 'pending') {
            throw ValidationException::withMessages(['task' => 'A tarefa já foi encerrada.']);
        }
        if (! in_array($outcome, ['confirmed', 'reschedule_requested', 'interested', 'scheduled', 'no_response', 'lost', 'opt_out'], true)) {
            throw ValidationException::withMessages(['outcome' => 'Resultado inválido.']);
        }
        DB::transaction(function () use ($task, $outcome, $note) {
            $task->attempts()->create(['user_id' => auth()->id(), 'outcome' => $outcome, 'note' => $note, 'attempted_at' => now()]);
            $task->update(['status' => 'completed', 'outcome' => $outcome, 'outcome_note' => $note, 'completed_at' => now()]);
            if ($outcome === 'opt_out') {
                $this->optOut($task->customer, $note);
            }
            if ($task->appointment) {
                $this->applyAppointmentOutcome($task->appointment, $outcome);
            }
            if ($task->opportunity) {
                $this->applyOpportunityOutcome($task, $outcome, $note);
            }
            $this->log($task->company_id, 'contact_task.completed', $task, ['outcome' => $outcome, 'note' => $note]);
        });
    }

    public function optOut(Customer $customer, ?string $note = null): void
    {
        $customer->update(['opted_out_at' => now(), 'opt_out_note' => $note]);
        ContactTask::withoutGlobalScopes()->where('customer_id', $customer->id)->where('status', 'pending')->update(['status' => 'cancelled', 'outcome' => 'opt_out', 'completed_at' => now()]);
        $this->log($customer->company_id, 'customer.opted_out', $customer, ['note' => $note]);
    }

    public function optIn(Customer $customer, string $consentNote): void
    {
        if (blank($consentNote)) {
            throw ValidationException::withMessages(['consent' => 'Registre o novo consentimento para reativar o contato.']);
        }

        $customer->update(['opted_out_at' => null, 'opt_out_note' => 'Novo consentimento: '.$consentNote]);
        $this->log($customer->company_id, 'customer.opted_in', $customer, ['consent' => $consentNote]);
    }

    public function reschedule(Appointment $appointment, \DateTimeInterface $scheduledAt): Appointment
    {
        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'], true)) {
            throw ValidationException::withMessages(['appointment' => 'Este agendamento não pode ser reagendado.']);
        }

        return DB::transaction(function () use ($appointment, $scheduledAt) {
            ContactTask::withoutGlobalScopes()->where('appointment_id', $appointment->id)->where('status', 'pending')->update(['status' => 'cancelled', 'outcome' => 'rescheduled', 'completed_at' => now()]);
            $appointment->update(['scheduled_at' => $scheduledAt, 'status' => 'scheduled']);
            $this->log($appointment->company_id, 'appointment.rescheduled', $appointment, ['scheduled_at' => $scheduledAt->format(DATE_ATOM)]);

            return $appointment->fresh();
        });
    }

    private function applyAppointmentOutcome(Appointment $appointment, string $outcome): void
    {
        if ($outcome === 'confirmed') {
            $appointment->update(['status' => 'confirmed']);
        } if ($outcome === 'reschedule_requested') {
            $appointment->update(['status' => 'reschedule_requested']);
        }
    }

    private function applyOpportunityOutcome(ContactTask $task, string $outcome, ?string $note): void
    {
        $opportunity = $task->opportunity;
        if ($outcome === 'interested') {
            $opportunity->update(['stage' => 'qualification']);
        }
        if ($outcome === 'scheduled') {
            $opportunity->update(['stage' => 'scheduling']);
        }
        if ($outcome === 'lost') {
            $opportunity->update(['stage' => 'lost', 'loss_reason' => $opportunity->loss_reason ?? 'other', 'notes' => trim(($opportunity->notes ?? '')."\n".$note)]);
        }
        if (in_array($outcome, ['scheduled', 'lost', 'opt_out'], true)) {
            $opportunity->update(['next_follow_up_at' => null]);
        }
        if ($outcome === 'no_response' && $task->type === 'follow_up') {
            $completed = ContactTask::withoutGlobalScopes()->where('opportunity_id', $opportunity->id)->where('type', 'follow_up')->where('status', 'completed')->count();
            $days = $opportunity->company->follow_up_days ?? [1, 3, 7];
            $next = $days[$completed] ?? null;
            $opportunity->update(['next_follow_up_at' => $next ? now()->addDays($next) : null]);
        }
    }

    private function log(int $companyId, string $event, object $subject, array $properties = []): void
    {
        ActivityLog::withoutGlobalScopes()->create(['company_id' => $companyId, 'user_id' => auth()->id(), 'event' => $event, 'subject_type' => $subject::class, 'subject_id' => $subject->id, 'properties' => $properties]);
    }
}

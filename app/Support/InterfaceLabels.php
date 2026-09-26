<?php

namespace App\Support;

final class InterfaceLabels
{
    /** @var array<string, string> */
    private const APPOINTMENT_STATUSES = [
        'scheduled' => 'Agendado',
        'confirmed' => 'Confirmado',
        'reschedule_requested' => 'Alteração solicitada',
        'cancelled' => 'Cancelado',
        'no_show' => 'Não compareceu',
        'completed' => 'Concluído',
    ];

    /** @var array<string, string> */
    private const CONTACT_TYPES = [
        'confirmation' => 'Confirmação',
        'recall' => 'Retorno',
        'reactivation' => 'Reativação',
    ];

    /** @var array<string, string> */
    private const TASK_STATUSES = [
        'pending' => 'Pendente',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ];

    /** @var array<string, string> */
    private const TASK_OUTCOMES = [
        'confirmed' => 'Confirmou',
        'reschedule_requested' => 'Pediu alteração',
        'rescheduled' => 'Reagendado',
        'scheduled' => 'Agendou',
        'no_response' => 'Sem resposta',
        'opt_out' => 'Não receber contato',
        'no_show' => 'Falta no horário',
        'cancelled' => 'Cancelada com o horário',
    ];

    /** @var array<string, string> */
    private const PRIORITIES = [
        'normal' => 'Normal',
        'high' => 'Alta',
    ];

    /** @var array<string, string> */
    private const CAMPAIGN_STATUSES = [
        'draft' => 'Rascunho',
        'active' => 'Ativa',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ];

    /** @var array<string, string> */
    private const COMPANY_STATUSES = [
        'trial' => 'Período de teste',
        'active' => 'Ativa',
        'suspended' => 'Suspensa',
        'cancelled' => 'Cancelada',
    ];

    /** @var array<string, string> */
    private const ACTIVITY_EVENTS = [
        'contact_task.completed' => 'Tarefa concluída',
        'contact_task.no_response_retry' => 'Sem resposta (nova tentativa)',
        'customer.opted_out' => 'Opt-out de contato',
        'customer.opted_in' => 'Novo consentimento',
        'appointment.rescheduled' => 'Agendamento reagendado',
        'appointment.no_show' => 'Falta registrada',
        'appointment.cancelled' => 'Agendamento cancelado',
        'appointment.undo_complete' => 'Conclusão desfeita',
    ];

    /** @var array<string, string> */
    private const USER_ROLES = [
        'owner' => 'Dono',
        'attendant' => 'Atendente',
    ];

    public static function appointmentStatus(?string $value): string
    {
        return self::label(self::APPOINTMENT_STATUSES, $value);
    }

    public static function appointmentStatusColor(?string $value): string
    {
        return match ($value) {
            'confirmed', 'completed' => 'success',
            'reschedule_requested' => 'warning',
            'cancelled', 'no_show' => 'danger',
            default => 'info',
        };
    }

    /** @return array<string, string> */
    public static function contactTypes(): array
    {
        return self::CONTACT_TYPES;
    }

    public static function contactType(?string $value): string
    {
        return self::label(self::CONTACT_TYPES, $value);
    }

    public static function contactTypeColor(?string $value): string
    {
        return match ($value) {
            'confirmation' => 'info',
            'recall' => 'success',
            'reactivation' => 'primary',
            default => 'warning',
        };
    }

    public static function taskStatus(?string $value): string
    {
        return self::label(self::TASK_STATUSES, $value);
    }

    public static function taskStatusColor(?string $value): string
    {
        return match ($value) {
            'completed' => 'success',
            'cancelled' => 'gray',
            default => 'warning',
        };
    }

    public static function taskOutcome(?string $value): string
    {
        return self::label(self::TASK_OUTCOMES, $value);
    }

    public static function taskOutcomeColor(?string $value): string
    {
        return match ($value) {
            'confirmed', 'scheduled' => 'success',
            'reschedule_requested', 'rescheduled' => 'warning',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return self::PRIORITIES;
    }

    public static function priority(?string $value): string
    {
        return self::label(self::PRIORITIES, $value);
    }

    public static function priorityColor(?string $value): string
    {
        return match ($value) {
            'high' => 'danger',
            default => 'gray',
        };
    }

    public static function campaignStatus(?string $value): string
    {
        return self::label(self::CAMPAIGN_STATUSES, $value);
    }

    public static function campaignStatusColor(?string $value): string
    {
        return match ($value) {
            'active' => 'success',
            'completed' => 'info',
            'cancelled' => 'gray',
            default => 'warning',
        };
    }

    public static function companyStatus(?string $value): string
    {
        return self::label(self::COMPANY_STATUSES, $value);
    }

    public static function companyStatusColor(?string $value): string
    {
        return match ($value) {
            'active' => 'success',
            'suspended' => 'danger',
            'cancelled' => 'gray',
            default => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function activityEvents(): array
    {
        return self::ACTIVITY_EVENTS;
    }

    public static function activityEvent(?string $value): string
    {
        return self::label(self::ACTIVITY_EVENTS, $value);
    }

    public static function activityEventColor(?string $value): string
    {
        return match ($value) {
            'customer.opted_in', 'contact_task.completed' => 'success',
            'appointment.rescheduled', 'contact_task.no_response_retry', 'appointment.undo_complete' => 'warning',
            'customer.opted_out', 'appointment.no_show', 'appointment.cancelled' => 'danger',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function userRoles(): array
    {
        return self::USER_ROLES;
    }

    public static function userRole(?string $value): string
    {
        return self::label(self::USER_ROLES, $value);
    }

    /** @param array<string, string> $labels */
    private static function label(array $labels, ?string $value): string
    {
        return $labels[$value ?? ''] ?? (string) $value;
    }
}

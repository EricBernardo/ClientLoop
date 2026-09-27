<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ContactTask;
use App\Services\AppointmentService;
use App\Services\ContactTaskService;
use App\Services\StaffNotifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentConfirmationController extends Controller
{
    public function show(string $token): View
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with(['customer', 'pet', 'service', 'company'])
            ->where('confirmation_token', $token)
            ->firstOrFail();

        return view('booking.confirm', ['appointment' => $appointment]);
    }

    public function confirm(string $token, StaffNotifier $notifier, ContactTaskService $tasks)
    {
        $appointment = Appointment::withoutGlobalScopes()->where('confirmation_token', $token)->firstOrFail();

        if (in_array($appointment->status, ['scheduled', 'reschedule_requested'], true)) {
            $pending = ContactTask::withoutGlobalScopes()->where('appointment_id', $appointment->id)->where('type', 'confirmation')->where('status', 'pending')->get();

            if ($pending->isEmpty()) {
                $appointment->update(['status' => 'confirmed']);
            } else {
                $pending->each(fn (ContactTask $task) => $tasks->complete($task, 'confirmed'));
            }

            $notifier->appointmentConfirmed($appointment->fresh());
        }

        return redirect()->route('appointment.confirm.show', $token)->with('status', 'Presença confirmada. Obrigado!');
    }

    public function cancel(Request $request, string $token, AppointmentService $appointments, StaffNotifier $notifier)
    {
        $appointment = Appointment::withoutGlobalScopes()->where('confirmation_token', $token)->firstOrFail();

        if (in_array($appointment->status, ['scheduled', 'confirmed', 'reschedule_requested'], true)) {
            $appointments->cancel($appointment);
            $notifier->appointmentCancelled($appointment->fresh());
        }

        return redirect()->route('appointment.confirm.show', $token)->with('status', 'Horário cancelado.');
    }
}

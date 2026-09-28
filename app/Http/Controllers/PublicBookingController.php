<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Services\ContactTaskService;
use App\Services\PhoneNormalizer;
use App\Services\QuotaService;
use App\Services\StaffNotifier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class PublicBookingController extends Controller
{
    public function show(string $token): View
    {
        $company = Company::query()->where('public_booking_token', $token)->whereIn('status', ['trial', 'active'])->firstOrFail();

        $customer = null;
        $openAppointment = null;
        if (filled(request('customer_id'))) {
            $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->whereKey(request('customer_id'))->first();
            $openAppointment = $customer ? $this->openAppointment($company, $customer) : null;
        }

        return view('booking.public', [
            'company' => $company,
            'services' => Service::withoutGlobalScopes()->where('company_id', $company->id)->where('active', true)->orderBy('name')->get(),
            'customer' => $customer,
            'openAppointment' => $openAppointment,
        ]);
    }

    public function store(Request $request, string $token, QuotaService $quota, StaffNotifier $notifier, ContactTaskService $tasks)
    {
        $company = Company::query()->where('public_booking_token', $token)->whereIn('status', ['trial', 'active'])->firstOrFail();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'pet_name' => ['required', 'string', 'max:255'],
            'service_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer'],
        ]);

        try {
            $phone = app(PhoneNormalizer::class)->normalize($data['customer_phone']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['customer_phone' => $exception->getMessage()]);
        }

        $service = Service::withoutGlobalScopes()->where('company_id', $company->id)->whereKey($data['service_id'])->where('active', true)->firstOrFail();
        $customer = $this->resolveCustomer($company, $data, $phone, $quota);
        if ($customer instanceof RedirectResponse) {
            return $customer;
        }

        $pet = Pet::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => $data['pet_name']],
            [],
        );

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $openAppointment = $this->openAppointment($company, $customer, $pet);

        if ($openAppointment) {
            return $this->rescheduleOpenAppointment($company, $customer, $openAppointment, $service, $pet, $scheduledAt, $token, $notifier, $tasks);
        }

        try {
            $appointment = Appointment::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'pet_id' => $pet->id,
                'service_id' => $service->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $service->duration_minutes,
                'status' => 'scheduled',
                'confirmation_token' => Str::random(40),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        $notifier->appointmentRequested($appointment);
        $this->queueConfirmation($company, $customer, $appointment, $service, $pet, $tasks);

        return redirect()->route('booking.show', $token)->with('status', 'Horário solicitado com sucesso. A loja vai confirmar pelo WhatsApp.');
    }

    private function rescheduleOpenAppointment(Company $company, Customer $customer, Appointment $appointment, Service $service, Pet $pet, Carbon $scheduledAt, string $token, StaffNotifier $notifier, ContactTaskService $tasks): RedirectResponse
    {
        $sameSlot = $appointment->scheduled_at?->format('Y-m-d H:i') === $scheduledAt->format('Y-m-d H:i');

        if ($sameSlot && (int) $appointment->service_id === (int) $service->id) {
            return redirect()->route('booking.show', $token)->with('status', 'Esse horário já está marcado.');
        }

        try {
            if ((int) $appointment->service_id !== (int) $service->id) {
                $appointment->service_id = $service->id;
                $appointment->duration_minutes = $service->duration_minutes;
            }

            if (! $sameSlot) {
                $appointment = $tasks->reschedule($appointment, $scheduledAt);
            } else {
                $appointment->save();
            }
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        $notifier->appointmentRescheduled($appointment);
        $this->queueConfirmation($company, $customer, $appointment, $service, $pet, $tasks);

        return redirect()->route('booking.show', $token)->with('status', 'Horário atualizado. O horário anterior deste pet foi substituído.');
    }

    private function openAppointment(Company $company, Customer $customer, ?Pet $pet = null): ?Appointment
    {
        $open = Appointment::withoutGlobalScopes()
            ->with(['pet', 'service', 'tasks' => fn ($query) => $query->where('status', 'pending')->where('type', 'confirmation')])
            ->where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])
            ->when($pet, fn ($query) => $query->where('pet_id', $pet->id))
            ->orderBy('scheduled_at')
            ->get();

        return $open->first(fn (Appointment $appointment): bool => $appointment->tasks->isNotEmpty()) ?? $open->first();
    }

    /**
     * @param  array{customer_name: string, customer_phone: string, customer_id?: int|null}  $data
     */
    private function resolveCustomer(Company $company, array $data, string $phone, QuotaService $quota): Customer|RedirectResponse
    {
        if (filled($data['customer_id'] ?? null)) {
            $linked = Customer::withoutGlobalScopes()->where('company_id', $company->id)->whereKey($data['customer_id'])->first();
            if ($linked) {
                return $linked;
            }
        }

        $existing = Customer::withoutGlobalScopes()->where('company_id', $company->id)->where('phone', $phone)->first();
        if ($existing) {
            return $existing;
        }

        try {
            $quota->consumeContact($company);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'phone' => $phone,
            'name' => $data['customer_name'],
        ]);
    }

    private function queueConfirmation(Company $company, Customer $customer, Appointment $appointment, Service $service, Pet $pet, ContactTaskService $tasks): void
    {
        $hours = $company->confirmation_hours ?: 24;
        $scheduledAt = $appointment->scheduled_at;
        if ($scheduledAt->lt(now()) || $scheduledAt->gt(now()->copy()->addHours($hours))) {
            return;
        }

        $tasks->create($company, $customer, 'confirmation', $scheduledAt->copy()->subHours($hours), [
            'appointment' => $appointment,
            'service' => $service,
            'pet' => $pet,
            'cycle_key' => 'appointment:'.$appointment->id,
        ]);
    }
}

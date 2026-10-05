<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Vehicle;
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
            'confirmation' => $this->confirmation($token),
        ]);
    }

    public function store(Request $request, string $token, QuotaService $quota, StaffNotifier $notifier, ContactTaskService $tasks)
    {
        $company = Company::query()->where('public_booking_token', $token)->whereIn('status', ['trial', 'active'])->firstOrFail();

        if ($this->confirmation($token) !== null) {
            return redirect()->route('booking.show', $token);
        }

        $rules = [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'service_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer'],
        ];
        if ($company->isAutomotive()) {
            $rules['plate'] = ['required', 'string', 'max:20'];
            $rules['brand'] = ['nullable', 'string', 'max:80'];
            $rules['model'] = ['nullable', 'string', 'max:80'];
        } else {
            $rules['pet_name'] = ['required', 'string', 'max:255'];
        }
        $data = $request->validate($rules);

        try {
            $phone = app(PhoneNormalizer::class)->normalize($data['customer_phone']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['customer_phone' => $exception->getMessage()]);
        }

        $service = Service::withoutGlobalScopes()->where('company_id', $company->id)->whereKey($data['service_id'])->where('active', true)->firstOrFail();
        $customer = $this->resolveCustomer($company, $data, $phone, $quota, $notifier);
        if ($customer instanceof RedirectResponse) {
            return $customer;
        }

        $pet = null;
        $vehicle = null;
        if ($company->isAutomotive()) {
            $vehicle = $this->resolveVehicle($company, $customer, $data);
            if ($vehicle instanceof RedirectResponse) {
                return $vehicle;
            }
        } else {
            $pet = Pet::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => $data['pet_name']],
                [],
            );
        }

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $openAppointment = $this->openAppointment($company, $customer, $pet, $vehicle);

        if ($openAppointment) {
            return $this->rescheduleOpenAppointment($company, $customer, $openAppointment, $service, $scheduledAt, $token, $notifier, $tasks);
        }

        try {
            $appointment = Appointment::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'pet_id' => $pet?->id,
                'vehicle_id' => $vehicle?->id,
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
        $this->queueConfirmation($company, $customer, $appointment, $service, $tasks);

        return $this->booked($company, $token, 'Horário solicitado com sucesso. A loja vai confirmar pelo WhatsApp.', $appointment);
    }

    private function rescheduleOpenAppointment(Company $company, Customer $customer, Appointment $appointment, Service $service, Carbon $scheduledAt, string $token, StaffNotifier $notifier, ContactTaskService $tasks): RedirectResponse
    {
        $sameSlot = $appointment->scheduled_at?->format('Y-m-d H:i') === $scheduledAt->format('Y-m-d H:i');

        if ($sameSlot && (int) $appointment->service_id === (int) $service->id) {
            return $this->booked($company, $token, 'Esse horário já está marcado.', $appointment);
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
        $this->queueConfirmation($company, $customer, $appointment, $service, $tasks);

        $subject = $company->isAutomotive() ? 'veículo' : 'pet';

        return $this->booked($company, $token, "Horário atualizado. O horário anterior deste {$subject} foi substituído.", $appointment);
    }

    private function booked(Company $company, string $token, string $message, Appointment $appointment): RedirectResponse
    {
        $appointment->loadMissing(['pet', 'vehicle', 'service']);
        $when = $appointment->scheduled_at?->timezone($company->timezone ?: config('app.timezone'));

        session()->put('public_booking.'.$token, [
            'appointment_id' => $appointment->id,
            'message' => $message,
            'subject' => $appointment->vehicle?->label() ?? $appointment->pet?->name,
            'service' => $appointment->service?->name,
            'date' => $when?->format('d/m/Y'),
            'time' => $when?->format('H:i'),
        ]);

        return redirect()->route('booking.show', $token);
    }

    /**
     * @return array{appointment_id: int, message: string, subject: ?string, service: ?string, date: ?string, time: ?string}|null
     */
    private function confirmation(string $token): ?array
    {
        $confirmation = session('public_booking.'.$token);

        if (! is_array($confirmation)) {
            return null;
        }

        $appointment = Appointment::withoutGlobalScopes()->find($confirmation['appointment_id'] ?? null);

        if ($appointment === null || ! in_array($appointment->status, ['scheduled', 'confirmed', 'reschedule_requested'], true)) {
            session()->forget('public_booking.'.$token);

            return null;
        }

        return $confirmation;
    }

    private function openAppointment(Company $company, Customer $customer, ?Pet $pet = null, ?Vehicle $vehicle = null): ?Appointment
    {
        $open = Appointment::withoutGlobalScopes()
            ->with(['pet', 'vehicle', 'service', 'tasks' => fn ($query) => $query->where('status', 'pending')->where('type', 'confirmation')])
            ->where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])
            ->when($pet, fn ($query) => $query->where('pet_id', $pet->id))
            ->when($vehicle, fn ($query) => $query->where('vehicle_id', $vehicle->id))
            ->orderBy('scheduled_at')
            ->get();

        return $open->first(fn (Appointment $appointment): bool => $appointment->tasks->isNotEmpty()) ?? $open->first();
    }

    /**
     * @param  array{customer_name: string, customer_phone: string, customer_id?: int|null}  $data
     */
    private function resolveCustomer(Company $company, array $data, string $phone, QuotaService $quota, StaffNotifier $notifier): Customer|RedirectResponse
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
            $notifier->contactQuotaExhausted($company);

            return back()->withInput()->withErrors($exception->errors());
        }

        return Customer::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'phone' => $phone,
            'name' => $data['customer_name'],
        ]);
    }

    /**
     * @param  array{plate: string, brand?: string|null, model?: string|null}  $data
     */
    private function resolveVehicle(Company $company, Customer $customer, array $data): Vehicle|RedirectResponse
    {
        $plate = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $data['plate']));
        $existing = Vehicle::withoutGlobalScopes()->where('company_id', $company->id)->where('plate', $plate)->first();

        if ($existing && (int) $existing->customer_id !== (int) $customer->id) {
            return back()->withInput()->withErrors(['plate' => 'Esta placa já está cadastrada para outro cliente.']);
        }

        if ($existing) {
            return $existing;
        }

        return Vehicle::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'plate' => $plate,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
        ]);
    }

    private function queueConfirmation(Company $company, Customer $customer, Appointment $appointment, Service $service, ContactTaskService $tasks): void
    {
        $hours = $company->confirmation_hours ?: 24;
        $scheduledAt = $appointment->scheduled_at;
        if ($scheduledAt->lt(now()) || $scheduledAt->gt(now()->copy()->addHours($hours))) {
            return;
        }

        $appointment->loadMissing(['pet', 'vehicle']);
        $tasks->create($company, $customer, 'confirmation', $scheduledAt->copy()->subHours($hours), [
            'appointment' => $appointment,
            'service' => $service,
            'pet' => $appointment->pet,
            'vehicle' => $appointment->vehicle,
            'cycle_key' => 'appointment:'.$appointment->id,
        ]);
    }
}

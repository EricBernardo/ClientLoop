<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function show(string $token): View
    {
        $company = Company::query()->where('public_booking_token', $token)->whereIn('status', ['trial', 'active'])->firstOrFail();

        return view('booking.public', [
            'company' => $company,
            'services' => Service::withoutGlobalScopes()->where('company_id', $company->id)->where('active', true)->orderBy('name')->get(),
            'customerId' => request('customer_id'),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $company = Company::query()->where('public_booking_token', $token)->whereIn('status', ['trial', 'active'])->firstOrFail();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'pet_name' => ['required', 'string', 'max:255'],
            'service_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $service = Service::withoutGlobalScopes()->where('company_id', $company->id)->whereKey($data['service_id'])->where('active', true)->firstOrFail();

        $customer = Customer::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $company->id, 'phone' => preg_replace('/\D+/', '', $data['customer_phone'])],
            ['name' => $data['customer_name']],
        );

        $pet = Pet::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => $data['pet_name']],
            [],
        );

        try {
            Appointment::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'pet_id' => $pet->id,
                'service_id' => $service->id,
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'duration_minutes' => $service->duration_minutes,
                'status' => 'scheduled',
                'confirmation_token' => Str::random(40),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()->route('booking.show', $token)->with('status', 'Horário solicitado com sucesso. A loja vai confirmar pelo WhatsApp.');
    }
}

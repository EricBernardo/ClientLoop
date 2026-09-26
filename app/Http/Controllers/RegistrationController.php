<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\DefaultMessageTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request, DefaultMessageTemplateService $messageTemplates)
    {
        $data = $request->validate(['company_name' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:12']]);
        $requireVerification = (bool) config('clientloop.require_email_verification');

        $user = DB::transaction(function () use ($data, $requireVerification, $messageTemplates) {
            $company = Company::create(['name' => $data['company_name'], 'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(6))]);
            $plan = Plan::where('is_default', true)->first() ?? Plan::firstOrCreate(
                ['name' => 'Teste gratuito'],
                ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => true],
            );
            CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'trial', 'starts_at' => now(), 'ends_at' => now()->addDays(14)]);
            $messageTemplates->provision($company);

            return User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
                'email_verified_at' => $requireVerification ? null : now(),
            ]);
        });
        Auth::login($user);

        if ($requireVerification) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice');
        }

        return redirect('/admin');
    }
}

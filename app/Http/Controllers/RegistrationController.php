<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
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

    public function store(Request $request)
    {
        $data = $request->validate(['company_name' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:12']]);
        $user = DB::transaction(function () use ($data) {
            $company = Company::create(['name' => $data['company_name'], 'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(6)), 'follow_up_days' => [1, 3, 7]]);
            $plan = Plan::where('is_default', true)->first() ?? Plan::firstOrCreate(
                ['name' => 'Trial'],
                ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => true],
            );
            CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'trial', 'starts_at' => now()]);

            return User::create(['company_id' => $company->id, 'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        });
        Auth::login($user);
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Support\FirstVisitGuide;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinishFirstVisitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user?->company;

        abort_unless($company !== null && $user->role !== 'attendant', 403);

        $completed = Appointment::query()
            ->where('company_id', $company->id)
            ->where('status', 'completed')
            ->exists();

        if (! $completed) {
            return redirect()->to(FirstVisitGuide::url($company));
        }

        $company->update(['setup_wizard_completed_at' => now()]);

        return redirect()->to(Dashboard::getUrl(panel: 'company'));
    }
}

<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Onboarding;
use App\Support\FirstVisitGuide;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupWizardComplete
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user?->company;

        if ($company === null || $company->setup_wizard_completed_at !== null) {
            return $next($request);
        }

        if ($request->routeIs('filament.company.auth.logout')) {
            return $next($request);
        }

        $panel = Filament::getPanel('company');

        if ($user->role === 'attendant') {
            $path = trim($panel->getPath().'/'.Onboarding::getSlug($panel), '/');

            if ($request->is($path)) {
                return $next($request);
            }

            return redirect()->to(Onboarding::getUrl(panel: 'company'));
        }

        if ($request->routeIs('filament.company.first-visit.finish')) {
            return $next($request);
        }

        $step = FirstVisitGuide::current($company);

        if (! FirstVisitGuide::matches($request, $step)) {
            return redirect()->to($step['url']);
        }

        $viewingQueue = $step['key'] === 'queue';

        try {
            return $next($request);
        } finally {
            if ($viewingQueue) {
                session([FirstVisitGuide::QUEUE_SESSION => $company->id]);
            }
        }
    }
}

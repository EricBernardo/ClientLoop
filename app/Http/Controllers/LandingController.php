<?php

namespace App\Http\Controllers;

use App\Enums\CompanyVertical;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function home(Request $request): View|RedirectResponse
    {
        $vertical = CompanyVertical::fromCookie($request->cookie(CompanyVertical::RememberedNicheCookie));

        if ($vertical instanceof CompanyVertical) {
            return redirect()->route($vertical->landingRoute());
        }

        return view('marketing.choose');
    }

    public function pet(): Response
    {
        return $this->niche(CompanyVertical::PetShop, 'marketing.pet');
    }

    public function automotive(): Response
    {
        return $this->niche(CompanyVertical::Automotive, 'marketing.automotive');
    }

    private function niche(CompanyVertical $vertical, string $view): Response
    {
        return response()
            ->view($view)
            ->cookie(
                CompanyVertical::RememberedNicheCookie,
                $vertical->value,
                CompanyVertical::RememberedNicheCookieMinutes,
            );
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\ContactTask;
use App\Models\PetPackage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends Page
{
    protected static ?string $navigationLabel = 'Relatórios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'reports';

    protected static ?string $title = 'Relatórios';

    protected string $view = 'filament.pages.reports';

    /** @return array<string, mixed> */
    public function getStats(): array
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return [];
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthAppointments = Appointment::query()
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $eligibleForConfirmation = $monthAppointments->whereIn('status', ['confirmed', 'completed', 'scheduled', 'no_show', 'reschedule_requested']);
        $confirmed = $eligibleForConfirmation->whereIn('status', ['confirmed', 'completed']);
        $confirmationRate = $eligibleForConfirmation->count() > 0
            ? round(($confirmed->count() / $eligibleForConfirmation->count()) * 100)
            : 0;

        $attendedOrMissed = $monthAppointments->whereIn('status', ['completed', 'no_show']);
        $noShows = $attendedOrMissed->where('status', 'no_show');
        $noShowRate = $attendedOrMissed->count() > 0
            ? round(($noShows->count() / $attendedOrMissed->count()) * 100)
            : 0;

        $startsHour = $company->business_starts_at_hour ?? 9;
        $endsHour = $company->business_ends_at_hour ?? 17;
        $availableMinutes = max(0, ($endsHour - $startsHour) * 60);
        $bookedMinutes = (int) Appointment::query()
            ->whereDate('scheduled_at', today())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->sum('duration_minutes');
        $occupancyToday = $availableMinutes > 0
            ? min(100, round(($bookedMinutes / $availableMinutes) * 100))
            : 0;

        $packagesSoldThisMonth = PetPackage::query()
            ->whereBetween('purchased_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        $pendingTasks = ContactTask::query()->where('status', 'pending')->count();

        return [
            'confirmation_rate' => $confirmationRate,
            'no_show_rate' => $noShowRate,
            'occupancy_today' => $occupancyToday,
            'packages_sold_month' => $packagesSoldThisMonth,
            'pending_tasks' => $pendingTasks,
        ];
    }
}

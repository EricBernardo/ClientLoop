<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Calendar extends Page
{
    protected static ?string $navigationLabel = 'Agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Agenda';

    protected string $view = 'filament.pages.calendar';

    public string $date;

    public string $mode;

    public function mount(): void
    {
        $this->date = Carbon::parse(request('date', today()->toDateString()))->toDateString();
        // Default day view — week forces heavy horizontal scroll on phones (see calendar.blade.php min-widths).
        $this->mode = in_array(request('mode', 'day'), ['day', 'week'], true) ? request('mode', 'day') : 'day';
    }

    /** @return Collection<int, Appointment> */
    public function getAppointmentsProperty(): Collection
    {
        $start = $this->firstDay();
        $end = $this->mode === 'day' ? $start->copy()->endOfDay() : $start->copy()->addDays(6)->endOfDay();

        return Appointment::query()
            ->with(['pet', 'customer', 'service'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('scheduled_at')
            ->get();
    }

    /** @return array<int, Carbon> */
    public function getDaysProperty(): array
    {
        $start = $this->firstDay();

        return $this->mode === 'day' ? [$start] : collect(range(0, 6))->map(fn (int $day): Carbon => $start->copy()->addDays($day))->all();
    }

    /** @return array{top:float,height:float} */
    public function position(Appointment $appointment): array
    {
        $start = $appointment->scheduled_at->copy()->setTime($this->businessStartsAtHour, 0);
        $totalMinutes = ($this->businessEndsAtHour - $this->businessStartsAtHour) * 60;
        $minutes = max(0, min($totalMinutes, $start->diffInMinutes($appointment->scheduled_at, false)));

        return [
            'top' => ($minutes / $totalMinutes) * 100,
            'height' => max(7, min(100 - (($minutes / $totalMinutes) * 100), (($appointment->duration_minutes ?: 60) / $totalMinutes) * 100)),
        ];
    }

    public function getBusinessStartsAtHourProperty(): int
    {
        return auth()->user()?->company?->business_starts_at_hour ?? 9;
    }

    public function getBusinessEndsAtHourProperty(): int
    {
        return auth()->user()?->company?->business_ends_at_hour ?? 17;
    }

    /** @return array<int, int> */
    public function getBusinessDaysProperty(): array
    {
        return auth()->user()?->company?->business_days ?: [1, 2, 3, 4, 5, 6];
    }

    public function isBusinessDay(Carbon $day): bool
    {
        return in_array($day->dayOfWeekIso, $this->businessDays, true);
    }

    public function calendarUrl(Carbon $date, ?string $mode = null): string
    {
        return static::getUrl(['date' => $date->toDateString(), 'mode' => $mode ?? $this->mode]);
    }

    public function createUrl(Carbon $day, int $hour): string
    {
        return AppointmentResource::getUrl('create', ['scheduled_at' => $day->copy()->setTime($hour, 0)->format('Y-m-d H:i:s')]);
    }

    private function firstDay(): Carbon
    {
        $date = Carbon::parse($this->date)->startOfDay();

        return $this->mode === 'week' ? $date->startOfWeek() : $date;
    }
}

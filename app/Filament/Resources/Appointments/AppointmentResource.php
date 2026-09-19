<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Services\ContactTaskService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return 'Agenda';
    }

    public static function getModelLabel(): string
    {
        return 'agendamento';
    }

    public static function getPluralModelLabel(): string
    {
        return 'agendamentos';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')->relationship('customer', 'name')->searchable()->preload()->required(), Select::make('service_id')->relationship('service', 'name')->searchable(), DateTimePicker::make('scheduled_at')->required(), Select::make('status')->options(['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'reschedule_requested' => 'Alteração solicitada', 'cancelled' => 'Cancelado', 'no_show' => 'Não compareceu', 'completed' => 'Concluído'])->default('scheduled')->required(), TextInput::make('potential_value')->numeric()->prefix('R$'), TextInput::make('realized_value')->numeric()->prefix('R$'), DateTimePicker::make('next_return_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return match (request('view')) {
                    'risk' => $query->whereIn('status', ['scheduled', 'reschedule_requested'])->whereBetween('scheduled_at', [now(), now()->copy()->addDay()]),
                    'revenue-month' => $query->where('status', 'completed')->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()]),
                    default => $query,
                };
            })
            ->columns([
                TextColumn::make('customer.name')->label('Cliente')->searchable(), TextColumn::make('service.name')->label('Serviço'), TextColumn::make('scheduled_at')->dateTime('d/m H:i')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('potential_value')->money('BRL'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'reschedule_requested' => 'Alteração solicitada', 'cancelled' => 'Cancelado', 'no_show' => 'Não compareceu', 'completed' => 'Concluído']),
            ])
            ->recordActions([
                Action::make('reagendar')->label('Reagendar')->icon('heroicon-o-calendar-days')->visible(fn (Appointment $record) => in_array($record->status, ['scheduled', 'confirmed', 'reschedule_requested'], true))->form([DateTimePicker::make('scheduled_at')->label('Nova data e horário')->required()->after('now')])->action(fn (Appointment $record, array $data) => app(ContactTaskService::class)->reschedule($record, $data['scheduled_at'])),
                EditAction::make()->url(fn (Appointment $record) => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}

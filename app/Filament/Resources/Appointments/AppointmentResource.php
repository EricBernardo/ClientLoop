<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use App\Filament\Pages\Calendar;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\ContactTaskService;
use App\Services\PackageService;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return 'Lista de atendimentos';
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
                Select::make('customer_id')->label('Responsável')->relationship('customer', 'name')->searchable()->preload()->live()->afterStateUpdated(function (Set $set): void {
                    $set('pet_id', null);
                    $set('service_id', null);
                    $set('pet_package_id', null);
                })->default(fn (): ?string => request('customer_id'))->required(),
                Select::make('pet_id')->label('Pet')->options(function (Get $get): array {
                    $customerId = $get('customer_id');

                    return blank($customerId) ? [] : Pet::query()->where('customer_id', $customerId)->orderBy('name')->pluck('name', 'id')->all();
                })->searchable()->preload()->live()->disabled(fn (Get $get): bool => blank($get('customer_id')))->afterStateUpdated(function (Set $set): void {
                    $set('service_id', null);
                    $set('pet_package_id', null);
                })->default(fn (): ?string => request('pet_id'))->required(fn (?Appointment $record): bool => $record === null)->helperText('Escolha primeiro o responsável para ver os pets.'),
                Select::make('service_id')->label('Serviço')->options(fn (Get $get): array => blank($get('pet_id')) ? [] : Service::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->live()->disabled(fn (Get $get): bool => blank($get('pet_id')))->default(fn (): ?string => request('service_id'))->afterStateUpdated(function (Set $set, ?string $state): void {
                    $set('pet_package_id', null);
                    $service = Service::query()->find($state);
                    if ($service) {
                        $set('duration_minutes', $service->duration_minutes);
                    }
                })->required(),
                Select::make('pet_package_id')->label('Pacote (opcional)')->options(function (Get $get): array {
                    $petId = $get('pet_id');
                    $serviceId = $get('service_id');

                    if (blank($petId) || blank($serviceId)) {
                        return [];
                    }

                    return PetPackage::query()->with('pet')->where('payment_status', 'paid')->where('pet_id', $petId)->get()->filter(fn (PetPackage $package): bool => $package->isUsableFor((int) $petId, $serviceId))->mapWithKeys(fn (PetPackage $package): array => [$package->id => $package->name.' — próxima etapa: '.app(PackageService::class)->nextItem($package)?->service_name])->all();
                })->searchable()->preload()->disabled(fn (Get $get): bool => blank($get('pet_id')) || blank($get('service_id')))->default(fn (): ?string => request('pet_package_id'))->helperText('Escolha pet e serviço para ver os pacotes compatíveis.'),
                HourlyDateTimePicker::make('scheduled_at')->label('Data e horário')->default(fn (): string => request('scheduled_at', now()->startOfHour()->format('Y-m-d H:i:s')))->required(),
                TextInput::make('duration_minutes')->label('Duração (minutos)')->numeric()->integer()->minValue(5)->default(fn (): int => Service::query()->find(request('service_id'))?->duration_minutes ?? 60)->required(),
                Hidden::make('status')->default('scheduled'),
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
                TextColumn::make('pet.name')->label('Pet')->searchable(), TextColumn::make('customer.name')->label('Responsável')->searchable(), TextColumn::make('service.name')->label('Serviço'), TextColumn::make('scheduled_at')->label('Data e horário')->dateTime('d/m H:i')->sortable(), TextColumn::make('duration_minutes')->label('Duração')->suffix(' min'), TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::appointmentStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::appointmentStatus($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options(['scheduled' => 'Agendado', 'confirmed' => 'Confirmado', 'reschedule_requested' => 'Alteração solicitada', 'cancelled' => 'Cancelado', 'no_show' => 'Não compareceu', 'completed' => 'Concluído'])->searchable(),
            ])
            ->recordActions([
                Action::make('concluir')->label('Concluir atendimento')->color('success')->icon('heroicon-o-check-circle')->visible(fn (Appointment $record): bool => in_array($record->status, ['scheduled', 'confirmed'], true))->action(function (Appointment $record): void {
                    try {
                        app(AppointmentService::class)->complete($record);
                        $record->refresh();
                        Notification::make()->success()->title('Atendimento concluído')->send();
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('Não foi possível concluir o atendimento')->body(collect($exception->errors())->flatten()->first())->send();
                    }
                }),
                Action::make('agendarProximaEtapa')->label('Agendar próxima etapa')->color('primary')->icon('heroicon-o-calendar-days')->visible(fn (Appointment $record): bool => $record->status === 'completed' && self::nextPackageAppointmentUrl($record) !== null)->url(fn (Appointment $record): string => self::nextPackageAppointmentUrl($record) ?? self::getUrl('index')),
                Action::make('reagendar')->label('Reagendar')->color('info')->icon('heroicon-o-calendar-days')->visible(fn (Appointment $record) => in_array($record->status, ['scheduled', 'confirmed', 'reschedule_requested'], true))->form([HourlyDateTimePicker::make('scheduled_at')->label('Nova data e horário')->required()->after('now')])->action(function (Appointment $record, array $data): void {
                    try {
                        app(ContactTaskService::class)->reschedule($record, $data['scheduled_at']);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('Não foi possível reagendar')->body(collect($exception->errors())->flatten()->first())->send();
                    }
                }),
                EditAction::make()->color('info')->url(fn (Appointment $record) => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum agendamento encontrado')
            ->emptyStateDescription('Use a Agenda para marcar um horário livre ou crie um atendimento aqui.')
            ->emptyStateActions([
                Action::make('calendar')->label('Abrir agenda')->url(Calendar::getUrl()),
                Action::make('create')->label('Novo agendamento')->url(static::getUrl('create')),
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

    public static function nextPackageAppointmentUrl(Appointment $appointment): ?string
    {
        if (! $appointment->pet_package_id || ! $appointment->package) {
            return null;
        }

        $item = app(PackageService::class)->nextItem($appointment->package);
        if (! $item || ! $item->service_id) {
            return null;
        }

        return self::getUrl('create', [
            'customer_id' => $appointment->customer_id,
            'pet_id' => $appointment->pet_id,
            'service_id' => $item->service_id,
            'pet_package_id' => $appointment->pet_package_id,
            'scheduled_at' => $appointment->scheduled_at->copy()->addWeek()->format('Y-m-d H:i:s'),
        ]);
    }
}

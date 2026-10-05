<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\PetsRelationManager;
use App\Models\Customer;
use App\Services\ContactTaskService;
use App\Support\CurrentCompany;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return CurrentCompany::get()?->vertical->customerPlural() ?? 'Responsáveis';
    }

    public static function getModelLabel(): string
    {
        return CurrentCompany::get()?->vertical->customerSingular() ?? 'responsável';
    }

    public static function getPluralModelLabel(): string
    {
        return CurrentCompany::isAutomotive() ? 'clientes' : 'responsáveis';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(fn (): string => CurrentCompany::isAutomotive() ? 'Nome do cliente' : 'Nome do responsável')->required()->maxLength(255),
                TextInput::make('phone')->label('Telefone')->required()->helperText('DDD + número; o sistema normaliza para +55.'),
                Textarea::make('notes')->label('Observações')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(fn (): string => CurrentCompany::isAutomotive() ? 'Cliente' : 'Responsável')->searchable()->sortable(),
                TextColumn::make('phone')->label('Telefone')->searchable(),
                TextColumn::make('pets.name')->label('Pets')->badge()->separator(',')->limitList(3)->visible(fn (): bool => CurrentCompany::isPetShop()),
                TextColumn::make('vehicles.plate')->label('Veículos')->badge()->separator(',')->limitList(3)->visible(fn (): bool => CurrentCompany::isAutomotive()),
                TextColumn::make('last_activity_at')->dateTime('d/m/Y')->placeholder('Ainda não registrado')->label('Último atendimento')->sortable()->visible(fn (): bool => CurrentCompany::isPetShop()),
                TextColumn::make('next_return_at')->dateTime('d/m/Y')->placeholder('Ainda não calculado')->label('Retorno previsto')->sortable()->visible(fn (): bool => CurrentCompany::isPetShop()),
                IconColumn::make('opted_out_at')
                    ->boolean()
                    ->label('Bloqueado')
                    ->visible(fn (): bool => CurrentCompany::isPetShop())
                    ->tooltip(fn (Customer $record): ?string => $record->opted_out_at ? ($record->opt_out_note ?: 'Contato bloqueado') : null),
                TextColumn::make('opt_out_note')
                    ->label('Motivo do bloqueio')
                    ->placeholder('—')
                    ->visible(fn (): bool => CurrentCompany::isPetShop())
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state, Customer $record): ?string => $record->opted_out_at ? $state : null),
            ])
            ->filters([
                TernaryFilter::make('opted_out_at')->label('Contato bloqueado')->visible(fn (): bool => CurrentCompany::isPetShop()),
            ])
            ->recordActions([
                Action::make('ajustarRetorno')->label('Ajustar retorno previsto')->icon('heroicon-o-calendar-days')->visible(fn (): bool => CurrentCompany::isPetShop())->fillForm(fn (Customer $record): array => ['next_return_at' => $record->next_return_at])->form([HourlyDateTimePicker::make('next_return_at')->label('Data e horário do retorno')->helperText('Normalmente calculado ao concluir um atendimento. Ajuste somente para uma exceção.')])->action(fn (Customer $record, array $data) => $record->update(['next_return_at' => $data['next_return_at'] ?? null])),
                Action::make('bloquearContato')->label('Bloquear contato')->color('warning')->icon('heroicon-o-no-symbol')->visible(fn (Customer $record): bool => CurrentCompany::isPetShop() && $record->can_contact)->form([Textarea::make('note')->label('Motivo ou observação')->required()])->requiresConfirmation()->action(fn (Customer $record, array $data) => app(ContactTaskService::class)->optOut($record, $data['note'])),
                Action::make('novoConsentimento')->label('Registrar novo consentimento')->visible(fn (Customer $record): bool => CurrentCompany::isPetShop() && ! $record->can_contact)->form([Textarea::make('consent')->label('Como e quando a pessoa autorizou novo contato?')->required()])->action(fn (Customer $record, array $data) => app(ContactTaskService::class)->optIn($record, $data['consent'])),
                Action::make('exportarLgpd')
                    ->label('Exportar LGPD')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Customer $record) {
                        $record->loadMissing(['pets', 'appointments', 'vehicles']);
                        $payload = [
                            'exported_at' => now()->toIso8601String(),
                            'customer' => $record->only(['id', 'name', 'phone', 'notes', 'opted_out_at', 'opt_out_note', 'last_activity_at', 'next_return_at', 'created_at', 'updated_at']),
                        ];

                        if ($record->company?->isAutomotive()) {
                            $payload['vehicles'] = $record->vehicles->map->only(['id', 'plate', 'brand', 'model', 'year', 'mileage', 'notes', 'created_at'])->all();
                        } else {
                            $payload['pets'] = $record->pets->map->only(['id', 'name', 'species', 'breed', 'size', 'temperament', 'coat', 'allergies', 'weight_kg', 'notes', 'created_at'])->all();
                            $payload['appointments'] = $record->appointments->map->only(['id', 'pet_id', 'service_id', 'scheduled_at', 'duration_minutes', 'status', 'created_at'])->all();
                        }

                        return response()->streamDownload(
                            fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                            'lgpd-cliente-'.$record->id.'.json',
                            ['Content-Type' => 'application/json'],
                        );
                    }),
                EditAction::make()->url(fn (Customer $record) => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(CurrentCompany::isAutomotive() ? 'Nenhum cliente ainda' : 'Nenhum responsável ainda')
            ->emptyStateDescription(CurrentCompany::isAutomotive() ? 'Cadastre a pessoa dona do veículo.' : 'Cadastre a pessoa que recebe confirmações pelo WhatsApp.')
            ->emptyStateActions([
                Action::make('create')->label(CurrentCompany::isAutomotive() ? 'Novo cliente' : 'Novo responsável')->url(static::getUrl('create')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        if (! CurrentCompany::isPetShop()) {
            return [];
        }

        return [
            PetsRelationManager::class,
            AppointmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}

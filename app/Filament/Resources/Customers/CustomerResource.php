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
        return 'Responsáveis';
    }

    public static function getModelLabel(): string
    {
        return 'responsável';
    }

    public static function getPluralModelLabel(): string
    {
        return 'responsáveis';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nome do responsável')->required()->maxLength(255),
                TextInput::make('phone')->label('Telefone')->required()->helperText('DDD + número; o sistema normaliza para +55.'),
                Textarea::make('notes')->label('Observações')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Responsável')->searchable()->sortable(),
                TextColumn::make('phone')->label('Telefone')->searchable(),
                TextColumn::make('pets.name')->label('Pets')->badge()->separator(',')->limitList(3),
                TextColumn::make('last_activity_at')->dateTime('d/m/Y')->placeholder('Ainda não registrado')->label('Último atendimento')->sortable(),
                TextColumn::make('next_return_at')->dateTime('d/m/Y')->placeholder('Ainda não calculado')->label('Retorno previsto')->sortable(),
                IconColumn::make('opted_out_at')
                    ->boolean()
                    ->label('Bloqueado')
                    ->tooltip(fn (Customer $record): ?string => $record->opted_out_at ? ($record->opt_out_note ?: 'Contato bloqueado') : null),
                TextColumn::make('opt_out_note')
                    ->label('Motivo do bloqueio')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state, Customer $record): ?string => $record->opted_out_at ? $state : null),
            ])
            ->filters([
                TernaryFilter::make('opted_out_at')->label('Contato bloqueado'),
            ])
            ->recordActions([
                Action::make('ajustarRetorno')->label('Ajustar retorno previsto')->icon('heroicon-o-calendar-days')->fillForm(fn (Customer $record): array => ['next_return_at' => $record->next_return_at])->form([HourlyDateTimePicker::make('next_return_at')->label('Data e horário do retorno')->helperText('Normalmente calculado ao concluir um atendimento. Ajuste somente para uma exceção.')])->action(fn (Customer $record, array $data) => $record->update(['next_return_at' => $data['next_return_at'] ?? null])),
                Action::make('bloquearContato')->label('Bloquear contato')->color('warning')->icon('heroicon-o-no-symbol')->visible(fn (Customer $record) => $record->can_contact)->form([Textarea::make('note')->label('Motivo ou observação')->required()])->requiresConfirmation()->action(fn (Customer $record, array $data) => app(ContactTaskService::class)->optOut($record, $data['note'])),
                Action::make('novoConsentimento')->label('Registrar novo consentimento')->visible(fn (Customer $record) => ! $record->can_contact)->form([Textarea::make('consent')->label('Como e quando a pessoa autorizou novo contato?')->required()])->action(fn (Customer $record, array $data) => app(ContactTaskService::class)->optIn($record, $data['consent'])),
                EditAction::make()->url(fn (Customer $record) => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum responsável ainda')
            ->emptyStateDescription('Cadastre a pessoa que recebe confirmações pelo WhatsApp.')
            ->emptyStateActions([
                Action::make('create')->label('Novo responsável')->url(static::getUrl('create')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
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

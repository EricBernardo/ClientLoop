<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Services\ContactTaskService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return 'Clientes';
    }

    public static function getModelLabel(): string
    {
        return 'cliente';
    }

    public static function getPluralModelLabel(): string
    {
        return 'clientes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('phone')->required()->helperText('DDD + número; o sistema normaliza para +55.'),
                TextInput::make('email')->email(), TagsInput::make('tags'),
                DateTimePicker::make('next_return_at')->label('Próximo retorno'),
                DateTimePicker::make('opted_out_at')->label('Bloqueio de contato')->helperText('Preencha somente após consentimento explícito de não receber contato.'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(), TextColumn::make('phone')->searchable(),
                TextColumn::make('next_return_at')->dateTime('d/m/Y')->label('Retorno')->sortable(),
                IconColumn::make('opted_out_at')->boolean()->label('Bloqueado'),
            ])
            ->filters([
                TernaryFilter::make('opted_out_at')->label('Contato bloqueado'),
            ])
            ->recordActions([
                Action::make('novoConsentimento')->label('Registrar novo consentimento')->visible(fn (Customer $record) => ! $record->can_contact)->form([Textarea::make('consent')->label('Como e quando a pessoa autorizou novo contato?')->required()])->action(fn (Customer $record, array $data) => app(ContactTaskService::class)->optIn($record, $data['consent'])),
                EditAction::make()->url(fn (Customer $record) => self::getUrl('edit', ['record' => $record])),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Vehicles;

use App\Filament\Concerns\LimitsToAutomotive;
use App\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Resources\Vehicles\Pages\ListVehicles;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VehicleResource extends Resource
{
    use LimitsToAutomotive;

    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Veículos';
    }

    public static function getModelLabel(): string
    {
        return 'veículo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'veículos';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'name')->searchable()->preload()->required(),
            TextInput::make('plate')->label('Placa')->required()->maxLength(10)->extraInputAttributes(['style' => 'text-transform:uppercase']),
            TextInput::make('brand')->label('Marca')->maxLength(80),
            TextInput::make('model')->label('Modelo')->maxLength(80),
            TextInput::make('year')->label('Ano')->numeric()->integer()->minValue(1950)->maxValue((int) now()->year + 1),
            TextInput::make('mileage')->label('Quilometragem')->numeric()->integer()->minValue(0)->suffix('km'),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('plate')->label('Placa')->searchable()->sortable(),
            TextColumn::make('customer.name')->label('Cliente')->searchable(),
            TextColumn::make('brand')->label('Marca')->searchable(),
            TextColumn::make('model')->label('Modelo')->searchable(),
            TextColumn::make('year')->label('Ano'),
            TextColumn::make('mileage')->label('Km')->numeric(),
        ])->recordActions([
            EditAction::make()->url(fn (Vehicle $record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->emptyStateHeading('Nenhum veículo cadastrado')
            ->emptyStateDescription('Cadastre a placa ligada ao cliente antes de abrir uma ordem de serviço.')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'edit' => EditVehicle::route('/{record}/edit'),
        ];
    }
}

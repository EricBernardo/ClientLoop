<?php

namespace App\Filament\Resources\PackageOffers;

use App\Filament\Forms\Components\BrlMoneyInput;
use App\Filament\Resources\PackageOffers\Pages\CreatePackageOffer;
use App\Filament\Resources\PackageOffers\Pages\EditPackageOffer;
use App\Filament\Resources\PackageOffers\Pages\ListPackageOffers;
use App\Models\PackageOffer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PackageOfferResource extends Resource
{
    protected static ?string $model = PackageOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 15;

    public static function getNavigationLabel(): string
    {
        return 'Modelos de pacotes';
    }

    public static function getModelLabel(): string
    {
        return 'modelo de pacote';
    }

    public static function getPluralModelLabel(): string
    {
        return 'modelos de pacotes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('credits')->default(1),
            Section::make('Dados do pacote')
                ->description('Defina como este pacote será identificado e vendido.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nome do pacote')->placeholder('Ex.: 4 banhos')->required()->columnSpanFull(),
                    BrlMoneyInput::make('suggested_price')->label('Preço sugerido'),
                    Toggle::make('active')->label('Disponível para venda')->default(true),
                ])
                ->columnSpanFull(),
            Section::make('Sequência de atendimentos')
                ->description('Organize os serviços na mesma ordem em que serão realizados.')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('service_id')->label('Serviço')->relationship('service', 'name')->searchable()->preload()->required(),
                        ])
                        ->orderColumn('position')
                        ->defaultItems(4)
                        ->minItems(1)
                        ->addActionLabel('Adicionar atendimento')
                        ->helperText('Cada item representa uma visita do pacote.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Modelo')->searchable(),
            TextColumn::make('items')->label('Sequência')->state(fn (PackageOffer $record): string => $record->items->map(fn ($item): string => $item->service?->name ?? 'Serviço removido')->implode(' → '))->wrap(),
            TextColumn::make('credits')->label('Atendimentos'),
            TextColumn::make('suggested_price')->label('Preço sugerido')->money('BRL'),
            IconColumn::make('active')->label('Disponível')->boolean(),
        ])->recordActions([
            EditAction::make()->color('info')->url(fn (PackageOffer $record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPackageOffers::route('/'), 'create' => CreatePackageOffer::route('/create'), 'edit' => EditPackageOffer::route('/{record}/edit')];
    }
}

<?php

namespace App\Filament\Resources\Services;

use App\Filament\Forms\Components\BrlMoneyInput;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Serviços';
    }

    public static function getModelLabel(): string
    {
        return 'serviço';
    }

    public static function getPluralModelLabel(): string
    {
        return 'serviços';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nome')->required(), BrlMoneyInput::make('suggested_price')->label('Preço sugerido'), TextInput::make('duration_minutes')->label('Duração padrão (minutos)')->numeric()->integer()->minValue(5)->default(60)->required(), TextInput::make('return_interval_months')->numeric()->integer()->minValue(1)->label('Retorno padrão (meses)')->hintIcon(Heroicon::OutlinedInformationCircle, tooltip: 'Depois de concluir o último atendimento ou pacote, o sistema prevê o próximo retorno usando esta quantidade de meses. Deixe em branco se este serviço não tiver retorno automático.'), Toggle::make('active')->label('Ativo')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(), TextColumn::make('duration_minutes')->label('Duração')->suffix(' min'), TextColumn::make('suggested_price')->label('Preço sugerido')->money('BRL'), TextColumn::make('return_interval_months')->label('Retorno padrão')->suffix(' meses'), IconColumn::make('active')->label('Ativo')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->url(fn (Service $record) => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum serviço cadastrado')
            ->emptyStateDescription('Crie Banho, Tosa e outros serviços com duração e preço.')
            ->emptyStateActions([
                Action::make('create')->label('Novo serviço')->url(static::getUrl('create')),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}

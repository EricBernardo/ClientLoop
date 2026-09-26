<?php

namespace App\Filament\Resources\Pets;

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Filament\Resources\Pets\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Pets\RelationManagers\PackagesRelationManager;
use App\Models\Pet;
use BackedEnum;
use Filament\Actions\Action;
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

class PetResource extends Resource
{
    protected static ?string $model = Pet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Pets';
    }

    public static function getModelLabel(): string
    {
        return 'pet';
    }

    public static function getPluralModelLabel(): string
    {
        return 'pets';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Responsável')->relationship('customer', 'name')->searchable()->preload()->default(fn (): ?string => request('customer_id'))->required(),
            TextInput::make('name')->label('Nome do pet')->required()->maxLength(255),
            TextInput::make('species')->label('Espécie')->placeholder('Ex.: Cachorro'),
            TextInput::make('breed')->label('Raça'),
            Select::make('size')->label('Porte')->options(['small' => 'Pequeno', 'medium' => 'Médio', 'large' => 'Grande'])->searchable(),
            TextInput::make('temperament')->label('Temperamento')->placeholder('Ex.: Calmo, agitado'),
            TextInput::make('coat')->label('Pelagem')->placeholder('Ex.: Curta, longa'),
            TextInput::make('allergies')->label('Alergias'),
            TextInput::make('weight_kg')->label('Peso (kg)')->numeric()->minValue(0)->step(0.01),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Pet')->searchable()->sortable(),
            TextColumn::make('customer.name')->label('Responsável')->searchable(),
            TextColumn::make('species')->label('Espécie'),
            TextColumn::make('breed')->label('Raça'),
            TextColumn::make('size')->label('Porte')->formatStateUsing(fn (?string $state): string => ['small' => 'Pequeno', 'medium' => 'Médio', 'large' => 'Grande'][$state] ?? '—'),
        ])->recordActions([
            EditAction::make()->color('info')->url(fn (Pet $record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])
            ->emptyStateHeading('Nenhum pet cadastrado')
            ->emptyStateDescription('Cadastre o pet do responsável para agendar banhos e pacotes.')
            ->emptyStateActions([
                Action::make('create')->label('Novo pet')->url(static::getUrl('create')),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            AppointmentsRelationManager::class,
            PackagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ListPets::route('/'), 'create' => CreatePet::route('/create'), 'edit' => EditPet::route('/{record}/edit')];
    }
}

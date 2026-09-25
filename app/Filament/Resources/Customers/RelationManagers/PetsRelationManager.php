<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Pets\PetResource;
use App\Models\Pet;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PetsRelationManager extends RelationManager
{
    protected static string $relationship = 'pets';

    protected static ?string $title = 'Pets deste responsável';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome do pet')->required()->maxLength(255),
            TextInput::make('species')->label('Espécie')->placeholder('Ex.: Cachorro'),
            TextInput::make('breed')->label('Raça'),
            Select::make('size')->label('Porte')->options(['small' => 'Pequeno', 'medium' => 'Médio', 'large' => 'Grande'])->searchable(),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Pet')->searchable(),
                TextColumn::make('species')->label('Espécie')->placeholder('—'),
                TextColumn::make('breed')->label('Raça')->placeholder('—'),
                TextColumn::make('size')->label('Porte')->formatStateUsing(fn (?string $state): string => ['small' => 'Pequeno', 'medium' => 'Médio', 'large' => 'Grande'][$state] ?? '—'),
            ])
            ->headerActions([
                Action::make('novoPet')->label('Novo pet')->icon('heroicon-o-heart')->url(fn (): string => PetResource::getUrl('create', ['customer_id' => $this->getOwnerRecord()->getKey()])),
            ])
            ->recordActions([
                EditAction::make()->url(fn (Pet $record): string => PetResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Super\Resources\Plans;

use App\Filament\Super\Resources\Plans\Pages\CreatePlan;
use App\Filament\Super\Resources\Plans\Pages\EditPlan;
use App\Filament\Super\Resources\Plans\Pages\ListPlans;
use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationLabel = 'Planos';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('contact_limit')->label('Limite de responsáveis')->numeric()->required()->minValue(1),
            TextInput::make('task_limit')->label('Limite mensal de tarefas')->numeric()->required()->minValue(1),
            Toggle::make('is_default')->label('Plano inicial'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nome')->searchable(), TextColumn::make('contact_limit')->label('Responsáveis'), TextColumn::make('task_limit')->label('Tarefas/mês'), IconColumn::make('is_default')->label('Inicial')->boolean()])->recordActions([EditAction::make()->url(fn (Plan $record) => self::getUrl('edit', ['record' => $record])), DeleteAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPlans::route('/'), 'create' => CreatePlan::route('/create'), 'edit' => EditPlan::route('/{record}/edit')];
    }
}

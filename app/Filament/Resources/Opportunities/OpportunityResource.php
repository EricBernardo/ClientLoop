<?php

namespace App\Filament\Resources\Opportunities;

use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Models\Opportunity;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return 'Oportunidades';
    }

    public static function getModelLabel(): string
    {
        return 'oportunidade';
    }

    public static function getPluralModelLabel(): string
    {
        return 'oportunidades';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')->relationship('customer', 'name')->searchable()->preload()->required(), Select::make('service_id')->relationship('service', 'name')->searchable(), TextInput::make('title')->required(), Select::make('stage')->options(['new' => 'Novo', 'qualification' => 'Triagem', 'proposal' => 'Orçamento', 'scheduling' => 'Aguardando agendamento', 'won' => 'Ganho', 'lost' => 'Perdido'])->default('new')->required(), Select::make('urgency')->options(['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta'])->default('normal'), TextInput::make('potential_value')->numeric()->prefix('R$'), TextInput::make('realized_value')->numeric()->prefix('R$'), DateTimePicker::make('next_follow_up_at'), Select::make('loss_reason')->options(['price' => 'Preço', 'delay' => 'Demora', 'withdrawal' => 'Desistência', 'competitor' => 'Concorrente', 'no_response' => 'Sem resposta', 'other' => 'Outro']), Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => request('view') === 'active' ? $query->whereNotIn('stage', ['won', 'lost']) : $query)
            ->columns([
                TextColumn::make('title')->searchable(), TextColumn::make('customer.name')->label('Cliente')->searchable(), TextColumn::make('stage')->badge(), TextColumn::make('potential_value')->money('BRL'), TextColumn::make('next_follow_up_at')->dateTime('d/m H:i'),
            ])
            ->filters([
                SelectFilter::make('stage')->options(['new' => 'Novo', 'qualification' => 'Triagem', 'proposal' => 'Orçamento', 'scheduling' => 'Aguardando agendamento', 'won' => 'Ganho', 'lost' => 'Perdido']),
            ])
            ->recordActions([
                EditAction::make()->url(fn (Opportunity $record) => self::getUrl('edit', ['record' => $record])),
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
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }
}

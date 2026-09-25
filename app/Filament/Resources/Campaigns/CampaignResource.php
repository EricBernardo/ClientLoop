<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Forms\Components\HourlyDateTimePicker;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Models\Campaign;
use App\Services\CampaignService;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return 'Campanhas';
    }

    public static function getModelLabel(): string
    {
        return 'campanha';
    }

    public static function getPluralModelLabel(): string
    {
        return 'campanhas';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required(),
            Select::make('type')->label('Tipo')->options(['recall' => 'Retorno', 'reactivation' => 'Reativação'])->searchable()->live()->afterStateUpdated(function (Set $set): void {
                $set('message_template_id', null);
                $set('filters.return_months', null);
            })->required(),
            Select::make('message_template_id')->label('Modelo de mensagem')->relationship('messageTemplate', 'name', fn (Builder $query, Get $get): Builder => $query->where('active', true)->when($get('type'), fn (Builder $query, string $type): Builder => $query->where('type', $type)))->searchable()->preload()->required(),
            HourlyDateTimePicker::make('starts_at')->label('Início')->helperText('Deixe em branco para iniciar imediatamente.'),
            Section::make('Segmento de retorno')->schema([
                Select::make('filters.return_months')->label('Retornos dos últimos')->options([3 => '3 meses', 6 => '6 meses', 12 => '12 meses'])->searchable()->visible(fn ($get) => $get('type') === 'recall'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nome')->searchable(),
            TextColumn::make('type')->label('Tipo')->badge()->color(fn (?string $state): string => InterfaceLabels::contactTypeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::contactType($state)),
            TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::campaignStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::campaignStatus($state)),
            TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i'),
            TextColumn::make('recipients_count')->label('Destinatários')->counts('recipients'),
        ])->recordActions([
            Action::make('launch')->label('Ativar campanha')->color('success')->icon(Heroicon::OutlinedPlay)->visible(fn (Campaign $record) => $record->status === 'draft')->requiresConfirmation()->action(fn (Campaign $record) => app(CampaignService::class)->launch($record)),
            EditAction::make()->color('info')->url(fn (Campaign $record) => self::getUrl('edit', ['record' => $record]))->visible(fn (Campaign $record) => $record->status === 'draft'),
            DeleteAction::make()->visible(fn (Campaign $record) => $record->status === 'draft'),
        ])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCampaigns::route('/'), 'create' => CreateCampaign::route('/create'), 'edit' => EditCampaign::route('/{record}/edit')];
    }
}

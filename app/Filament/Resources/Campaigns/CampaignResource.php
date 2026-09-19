<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Models\Campaign;
use App\Services\CampaignService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

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
            Select::make('type')->label('Tipo')->options(['recall' => 'Recall', 'reactivation' => 'Reativação'])->required(),
            Select::make('message_template_id')->label('Modelo de mensagem')->relationship('messageTemplate', 'name', fn ($query) => $query->where('active', true))->required(),
            DateTimePicker::make('starts_at')->label('Início')->helperText('Deixe em branco para iniciar imediatamente.'),
            Section::make('Segmento de recall')->schema([
                Select::make('filters.return_months')->label('Retornos dos últimos')->options([3 => '3 meses', 6 => '6 meses', 12 => '12 meses'])->visible(fn ($get) => $get('type') === 'recall'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nome')->searchable(),
            TextColumn::make('type')->label('Tipo')->badge(),
            TextColumn::make('status')->label('Situação')->badge(),
            TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i'),
            TextColumn::make('recipients_count')->label('Destinatários')->counts('recipients'),
        ])->recordActions([
            Action::make('launch')->label('Ativar campanha')->icon(Heroicon::OutlinedPlay)->visible(fn (Campaign $record) => $record->status === 'draft')->requiresConfirmation()->action(fn (Campaign $record) => app(CampaignService::class)->launch($record)),
            EditAction::make()->url(fn (Campaign $record) => self::getUrl('edit', ['record' => $record]))->visible(fn (Campaign $record) => $record->status === 'draft'),
            DeleteAction::make()->visible(fn (Campaign $record) => $record->status === 'draft'),
        ])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCampaigns::route('/'), 'create' => CreateCampaign::route('/create'), 'edit' => EditCampaign::route('/{record}/edit')];
    }
}

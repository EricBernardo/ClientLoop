<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Models\MessageTemplate;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return 'Modelos de mensagem';
    }

    public static function getModelLabel(): string
    {
        return 'modelo de mensagem';
    }

    public static function getPluralModelLabel(): string
    {
        return 'modelos de mensagem';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required(),
            Select::make('type')->label('Uso')->options(InterfaceLabels::contactTypes())->searchable()->required(),
            Textarea::make('body')->label('Mensagem')->required()->rows(7)->helperText('Variáveis: {{responsavel}} ou {{cliente}}, {{pet}}, {{empresa}}, {{servico}}, {{data}}, {{horario}}, {{link_agendamento}}.')->columnSpanFull(),
            Toggle::make('active')->label('Ativo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nome')->searchable(),
            TextColumn::make('type')->label('Uso')->badge()->color(fn (?string $state): string => InterfaceLabels::contactTypeColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::contactType($state)),
            IconColumn::make('active')->label('Ativo')->boolean(),
            TextColumn::make('updated_at')->label('Atualizado')->dateTime('d/m/Y H:i'),
        ])->recordActions([EditAction::make()->color('info')->url(fn (MessageTemplate $record) => self::getUrl('edit', ['record' => $record])), DeleteAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMessageTemplates::route('/'), 'create' => CreateMessageTemplate::route('/create'), 'edit' => EditMessageTemplate::route('/{record}/edit')];
    }
}

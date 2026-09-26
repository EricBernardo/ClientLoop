<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return 'Histórico de ações';
    }

    public static function getModelLabel(): string
    {
        return 'registro de ação';
    }

    public static function getPluralModelLabel(): string
    {
        return 'histórico de ações';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('event')->label('Ação')->badge()->color(fn (?string $state): string => InterfaceLabels::activityEventColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::activityEvent($state))->searchable(),
                TextColumn::make('user.name')->label('Quem')->placeholder('Sistema'),
                TextColumn::make('subject_type')->label('Sobre')->formatStateUsing(fn (?string $state, ActivityLog $record): string => self::subjectLabel($record)),
                TextColumn::make('properties')->label('Detalhes')->formatStateUsing(function (mixed $state): string {
                    if (is_string($state)) {
                        $decoded = json_decode($state, true);
                        $state = is_array($decoded) ? $decoded : [];
                    }

                    if (! is_array($state) || $state === []) {
                        return '—';
                    }

                    return collect($state)
                        ->map(fn (mixed $value, string|int $key): string => $key.': '.(is_scalar($value) || $value === null ? (string) $value : json_encode($value)))
                        ->implode(' · ');
                })->wrap()->limit(80),
            ])
            ->filters([
                SelectFilter::make('event')->label('Ação')->options(InterfaceLabels::activityEvents())->searchable(),
            ])
            ->emptyStateHeading('Nenhuma ação registrada')
            ->emptyStateDescription('Opt-outs, conclusões de tarefas e mudanças de agenda aparecem aqui.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    private static function subjectLabel(ActivityLog $record): string
    {
        $type = class_basename((string) $record->subject_type);

        return match ($type) {
            'ContactTask' => 'Tarefa #'.$record->subject_id,
            'Customer' => 'Responsável #'.$record->subject_id,
            'Appointment' => 'Agendamento #'.$record->subject_id,
            default => $type.' #'.$record->subject_id,
        };
    }
}

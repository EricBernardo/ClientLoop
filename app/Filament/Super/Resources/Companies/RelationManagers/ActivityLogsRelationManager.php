<?php

namespace App\Filament\Super\Resources\Companies\RelationManagers;

use App\Models\ActivityLog;
use App\Support\InterfaceLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Histórico de ações';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('event')->label('Ação')->badge()->color(fn (?string $state): string => InterfaceLabels::activityEventColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::activityEvent($state)),
                TextColumn::make('user.name')->label('Quem')->placeholder('Sistema'),
                TextColumn::make('subject_type')->label('Sobre')->formatStateUsing(fn (?string $state, ActivityLog $record): string => self::subjectLabel($record)),
                TextColumn::make('properties')->label('Detalhes')->state(fn (ActivityLog $record): string => self::formatProperties($record->properties))->wrap()->limit(80),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Nenhuma ação registrada')
            ->emptyStateDescription('Opt-outs, conclusões de tarefas e mudanças de agenda aparecem aqui.');
    }

    private static function formatProperties(mixed $state): string
    {
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

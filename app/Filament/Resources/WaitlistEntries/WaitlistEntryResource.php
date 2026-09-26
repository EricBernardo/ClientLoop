<?php

namespace App\Filament\Resources\WaitlistEntries;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\WaitlistEntries\Pages\ListWaitlistEntries;
use App\Models\Pet;
use App\Models\WaitlistEntry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WaitlistEntryResource extends Resource
{
    protected static ?string $model = WaitlistEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Lista de espera';
    }

    public static function getModelLabel(): string
    {
        return 'entrada na lista de espera';
    }

    public static function getPluralModelLabel(): string
    {
        return 'lista de espera';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('Responsável')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('pet_id', null))
                ->required(),
            Select::make('pet_id')
                ->label('Pet')
                ->options(function (Get $get): array {
                    $customerId = $get('customer_id');

                    return blank($customerId)
                        ? []
                        : Pet::query()->where('customer_id', $customerId)->orderBy('name')->pluck('name', 'id')->all();
                })
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get): bool => blank($get('customer_id')))
                ->helperText('Escolha primeiro o responsável para ver os pets.')
                ->required(),
            Select::make('service_id')
                ->label('Serviço')
                ->relationship('service', 'name', fn ($query) => $query->where('active', true))
                ->searchable()
                ->preload(),
            DatePicker::make('preferred_date')->label('Data preferida')->required(),
            TextInput::make('preferred_time')->label('Horário preferido')->placeholder('Ex.: 10:00'),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
            Select::make('status')
                ->label('Situação')
                ->options([
                    'waiting' => 'Aguardando',
                    'contacted' => 'Contatado',
                    'scheduled' => 'Agendado',
                    'cancelled' => 'Cancelado',
                ])
                ->searchable()
                ->default('waiting')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('preferred_date')
            ->columns([
                TextColumn::make('customer.name')->label('Responsável')->searchable(),
                TextColumn::make('pet.name')->label('Pet')->searchable(),
                TextColumn::make('service.name')->label('Serviço')->placeholder('—'),
                TextColumn::make('preferred_date')->label('Data preferida')->date('d/m/Y')->sortable(),
                TextColumn::make('preferred_time')->label('Horário')->placeholder('—'),
                TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn (?string $state): string => match ($state) {
                    'waiting' => 'Aguardando',
                    'contacted' => 'Contatado',
                    'scheduled' => 'Agendado',
                    'cancelled' => 'Cancelado',
                    default => (string) $state,
                })->color(fn (?string $state): string => match ($state) {
                    'scheduled' => 'success',
                    'contacted' => 'info',
                    'cancelled' => 'gray',
                    default => 'warning',
                }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options([
                    'waiting' => 'Aguardando',
                    'contacted' => 'Contatado',
                    'scheduled' => 'Agendado',
                    'cancelled' => 'Cancelado',
                ])->searchable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Nova entrada'),
            ])
            ->recordActions([
                Action::make('promover')
                    ->label('Promover')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->visible(fn (WaitlistEntry $record): bool => in_array($record->status, ['waiting', 'contacted'], true))
                    ->url(function (WaitlistEntry $record): string {
                        $scheduledAt = $record->preferred_date?->format('Y-m-d');
                        if ($scheduledAt && filled($record->preferred_time)) {
                            $time = preg_match('/^\d{1,2}:\d{2}/', (string) $record->preferred_time, $m)
                                ? $m[0]
                                : '09:00';
                            $scheduledAt .= ' '.$time.':00';
                        } elseif ($scheduledAt) {
                            $scheduledAt .= ' 09:00:00';
                        }

                        return AppointmentResource::getUrl('create', array_filter([
                            'customer_id' => $record->customer_id,
                            'pet_id' => $record->pet_id,
                            'service_id' => $record->service_id,
                            'scheduled_at' => $scheduledAt,
                        ]));
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Lista de espera vazia')
            ->emptyStateDescription('Quando não houver horário, registre o interessado aqui e promova depois.')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitlistEntries::route('/'),
        ];
    }
}

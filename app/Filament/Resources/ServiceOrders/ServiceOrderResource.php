<?php

namespace App\Filament\Resources\ServiceOrders;

use App\Enums\ReceiptPaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\ServiceOrderStatus;
use App\Filament\Concerns\LimitsToAutomotive;
use App\Filament\Forms\Components\BrlMoneyInput;
use App\Filament\Resources\ServiceOrders\Pages\CreateServiceOrder;
use App\Filament\Resources\ServiceOrders\Pages\EditServiceOrder;
use App\Filament\Resources\ServiceOrders\Pages\ListServiceOrders;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\Vehicle;
use App\Services\ServiceReceiptService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ServiceOrderResource extends Resource
{
    use LimitsToAutomotive;

    protected static ?string $model = ServiceOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Ordens de serviço';
    }

    public static function getModelLabel(): string
    {
        return 'ordem de serviço';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ordens de serviço';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('Cliente')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                    if (blank($get('vehicle_id'))) {
                        return;
                    }

                    $belongsToCustomer = Vehicle::query()
                        ->whereKey($get('vehicle_id'))
                        ->where('customer_id', $state)
                        ->exists();

                    if (! $belongsToCustomer) {
                        $set('vehicle_id', null);
                    }
                })
                ->required(),
            Select::make('vehicle_id')
                ->label('Veículo')
                ->options(function (Get $get): array {
                    if (blank($get('customer_id'))) {
                        return [];
                    }

                    return Vehicle::query()
                        ->where('customer_id', $get('customer_id'))
                        ->orderBy('plate')
                        ->get()
                        ->mapWithKeys(fn (Vehicle $vehicle): array => [$vehicle->id => $vehicle->label()])
                        ->all();
                })
                ->searchable()
                ->required(),
            DatePicker::make('opened_on')->label('Abertura')->default(fn (): string => today()->toDateString())->required(),
            Select::make('status')->label('Situação')->options(ServiceOrderStatus::options())->default(ServiceOrderStatus::Open->value)->searchable()->required(),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
            Repeater::make('items')
                ->relationship()
                ->label('Itens')
                ->schema([
                    Select::make('service_id')
                        ->label('Serviço cadastrado')
                        ->options(fn (): array => Service::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $service = Service::query()->find($state);

                            if (! $service) {
                                return;
                            }

                            $set('description', $service->name);

                            if ($service->suggested_price !== null) {
                                $set('unit_price', $service->suggested_price);
                            }
                        }),
                    TextInput::make('description')->label('Descrição')->required()->maxLength(255),
                    TextInput::make('quantity')->label('Quantidade')->numeric()->default(1)->minValue(0.01)->required(),
                    BrlMoneyInput::make('unit_price')->label('Valor unitário')->required()->minValue(0),
                ])
                ->columns(4)
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Adicionar item')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['customer', 'vehicle', 'items', 'receipt']))
            ->columns([
                TextColumn::make('id')->label('OS'),
                TextColumn::make('opened_on')->label('Abertura')->date('d/m/Y')->sortable(),
                TextColumn::make('customer.name')->label('Cliente')->searchable(),
                TextColumn::make('vehicle.plate')->label('Placa')->searchable(),
                TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn (ServiceOrderStatus|string|null $state): string => $state instanceof ServiceOrderStatus ? $state->label() : ServiceOrderStatus::from((string) $state)->label())->color(fn (ServiceOrderStatus|string|null $state): string => $state instanceof ServiceOrderStatus ? $state->color() : ServiceOrderStatus::from((string) $state)->color()),
                TextColumn::make('total')->label('Total')->state(fn (ServiceOrder $record): string => $record->totalAmount())->money('BRL'),
                TextColumn::make('receipt.status')->label('Recibo')->placeholder('Sem recibo')->formatStateUsing(fn (ReceiptStatus|string|null $state): string => $state instanceof ReceiptStatus ? $state->label() : ($state ? ReceiptStatus::from($state)->label() : 'Sem recibo')),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options(ServiceOrderStatus::options())->searchable(),
            ])
            ->recordActions([
                EditAction::make()->url(fn (ServiceOrder $record): string => self::getUrl('edit', ['record' => $record])),
                ...self::receiptActions(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhuma ordem de serviço')
            ->emptyStateDescription('Abra uma OS com o cliente, o veículo e os itens cobrados.')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<int, Action> */
    public static function receiptActions(): array
    {
        return [
            Action::make('lancarRecibo')
                ->label('Lançar recibo')
                ->icon('heroicon-o-document-text')
                ->visible(fn (ServiceOrder $record): bool => $record->receipt === null && $record->status !== ServiceOrderStatus::Cancelled)
                ->fillForm(fn (ServiceOrder $record): array => [
                    'amount' => $record->totalAmount(),
                    'payment_method' => ReceiptPaymentMethod::Pix->value,
                    'status' => ReceiptStatus::Pending->value,
                    'issued_on' => today()->toDateString(),
                ])
                ->form([
                    BrlMoneyInput::make('amount')->label('Valor')->required()->minValue(0),
                    Select::make('payment_method')->label('Forma de pagamento')->options(ReceiptPaymentMethod::options())->searchable()->required(),
                    Select::make('status')->label('Situação')->options(ReceiptStatus::options())->searchable()->required(),
                    DatePicker::make('issued_on')->label('Data')->required(),
                ])
                ->action(function (ServiceOrder $record, array $data): void {
                    try {
                        app(ServiceReceiptService::class)->issue(
                            $record,
                            (string) $data['amount'],
                            ReceiptPaymentMethod::from($data['payment_method']),
                            ReceiptStatus::from($data['status']),
                            Carbon::parse($data['issued_on']),
                        );
                        Notification::make()->success()->title('Recibo lançado.')->send();
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();
                    }
                }),
            Action::make('marcarPago')
                ->label('Marcar como pago')
                ->color('success')
                ->visible(fn (ServiceOrder $record): bool => $record->receipt?->status === ReceiptStatus::Pending)
                ->form([
                    DatePicker::make('paid_on')->label('Data do pagamento')->default(fn (): string => today()->toDateString())->required(),
                ])
                ->action(function (ServiceOrder $record, array $data): void {
                    app(ServiceReceiptService::class)->markPaid($record->receipt, Carbon::parse($data['paid_on']));
                    Notification::make()->success()->title('Recibo marcado como pago.')->send();
                }),
            Action::make('voltarPendente')
                ->label('Voltar para pendente')
                ->color('warning')
                ->visible(fn (ServiceOrder $record): bool => $record->receipt?->status === ReceiptStatus::Paid)
                ->requiresConfirmation()
                ->modalDescription('A entrada deste recibo sai do fluxo de caixa.')
                ->action(function (ServiceOrder $record): void {
                    app(ServiceReceiptService::class)->markPending($record->receipt);
                    Notification::make()->success()->title('Recibo voltou para pendente.')->send();
                }),
            Action::make('imprimirRecibo')
                ->label('Imprimir recibo')
                ->icon('heroicon-o-printer')
                ->visible(fn (ServiceOrder $record): bool => $record->receipt !== null)
                ->url(fn (ServiceOrder $record): string => route('receipts.show', $record->receipt))
                ->openUrlInNewTab(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'vehicle', 'items', 'receipt']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceOrders::route('/'),
            'create' => CreateServiceOrder::route('/create'),
            'edit' => EditServiceOrder::route('/{record}/edit'),
        ];
    }
}

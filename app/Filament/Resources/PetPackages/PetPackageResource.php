<?php

namespace App\Filament\Resources\PetPackages;

use App\Filament\Forms\Components\BrlMoneyInput;
use App\Filament\Resources\PackageOffers\PackageOfferResource;
use App\Filament\Resources\PetPackages\Pages\CreatePetPackage;
use App\Filament\Resources\PetPackages\Pages\EditPetPackage;
use App\Filament\Resources\PetPackages\Pages\ListPetPackages;
use App\Models\PetPackage;
use App\Services\PackageService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PetPackageResource extends Resource
{
    protected static ?string $model = PetPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Pacotes';
    }

    public static function getModelLabel(): string
    {
        return 'pacote vendido';
    }

    public static function getPluralModelLabel(): string
    {
        return 'pacotes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pet_id')->label('Pet')->relationship('pet', 'name')->searchable()->preload()->default(fn (): ?string => request('pet_id'))->required(),
            Select::make('package_offer_id')->label('Modelo de pacote')->relationship('offer', 'name', fn (Builder $query): Builder => $query->where('active', true))->searchable()->preload()->required()->disabled(fn (?PetPackage $record): bool => $record !== null),
            BrlMoneyInput::make('price')->label('Valor cobrado')->helperText('Deixe em branco para usar o preço sugerido.'),
            Select::make('payment_status')->label('Situação do pagamento')->options(['paid' => 'Pago', 'pending' => 'Pendente'])->searchable()->default('paid')->helperText('O pacote é pago no início e só pode ser usado depois da confirmação do pagamento.')->required(),
            DatePicker::make('purchased_at')->label('Data da venda')->default(today())->required(),
            DatePicker::make('valid_until')->label('Válido até')->helperText('Deixe em branco quando não houver data de validade.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query): Builder {
            return match (request('view')) {
                'low' => $query->whereRaw('total_credits - (select count(*) from package_redemptions where package_redemptions.pet_package_id = pet_packages.id) <= 1'),
                'expired' => $query->whereDate('valid_until', '<', today()),
                default => $query,
            };
        })->columns([
            TextColumn::make('name')->label('Pacote')->searchable(),
            TextColumn::make('pet.name')->label('Pet')->searchable(),
            TextColumn::make('items')->label('Próxima etapa')->state(function (PetPackage $record): string {
                $item = app(PackageService::class)->nextItem($record);

                return $item ? $item->position.'. '.$item->service_name : 'Pacote concluído';
            }),
            TextColumn::make('remaining_credits')->label('Saldo')->state(fn (PetPackage $record): string => $record->remaining_credits.' de '.$record->total_credits),
            TextColumn::make('payment_status')->label('Pagamento')->badge()->formatStateUsing(fn (?string $state): string => $state === 'paid' ? 'Pago' : 'Pendente')->color(fn (?string $state): string => $state === 'paid' ? 'success' : 'warning'),
            TextColumn::make('valid_until')->label('Validade')->date('d/m/Y')->placeholder('Sem validade'),
        ])->filters([
            SelectFilter::make('payment_status')->label('Pagamento')->options(['paid' => 'Pago', 'pending' => 'Pendente'])->searchable(),
        ])->recordActions([
            EditAction::make()->color('info')->url(fn (PetPackage $record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([
            Action::make('modelos')->label('Gerenciar modelos')->color('gray')->url(PackageOfferResource::getUrl('index')),
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPetPackages::route('/'), 'create' => CreatePetPackage::route('/create'), 'edit' => EditPetPackage::route('/{record}/edit')];
    }
}

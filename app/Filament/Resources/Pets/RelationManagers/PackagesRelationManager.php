<?php

namespace App\Filament\Resources\Pets\RelationManagers;

use App\Filament\Resources\PetPackages\PetPackageResource;
use App\Models\PetPackage;
use App\Services\PackageService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'Pacotes deste pet';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchased_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Pacote'),
                TextColumn::make('remaining_credits')->label('Saldo')->state(fn (PetPackage $record): string => $record->remaining_credits.' de '.$record->total_credits),
                TextColumn::make('payment_status')->label('Pagamento')->badge()->formatStateUsing(fn (?string $state): string => $state === 'paid' ? 'Pago' : 'Pendente')->color(fn (?string $state): string => $state === 'paid' ? 'success' : 'warning'),
                TextColumn::make('valid_until')->label('Validade')->date('d/m/Y')->placeholder('Sem validade'),
                TextColumn::make('items')->label('Próxima etapa')->state(function (PetPackage $record): string {
                    $item = app(PackageService::class)->nextItem($record);

                    return $item ? $item->position.'. '.$item->service_name : 'Pacote concluído';
                }),
            ])
            ->headerActions([
                Action::make('novoPacote')->label('Vender pacote')->icon('heroicon-o-ticket')->url(fn (): string => PetPackageResource::getUrl('create', ['pet_id' => $this->getOwnerRecord()->getKey()])),
            ])
            ->recordActions([
                EditAction::make()->url(fn (PetPackage $record): string => PetPackageResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

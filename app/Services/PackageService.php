<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PackageRedemption;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\PetPackageItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function assertCanUse(?int $packageId, ?int $petId, ?int $serviceId): void
    {
        if (! $packageId) {
            return;
        }

        $package = PetPackage::query()->find($packageId);
        if (! $package || ! $package->isUsableFor((int) $petId, $serviceId)) {
            throw ValidationException::withMessages(['pet_package_id' => 'Escolha um pacote pago, válido, com saldo e com a próxima etapa igual ao serviço selecionado.']);
        }
    }

    public function nextItem(PetPackage $package): ?PetPackageItem
    {
        return PetPackageItem::withoutGlobalScopes()
            ->where('pet_package_id', $package->id)
            ->whereDoesntHave('redemption')
            ->orderBy('position')
            ->first();
    }

    public function consume(Appointment $appointment): void
    {
        if (! $appointment->pet_package_id) {
            return;
        }

        DB::transaction(function () use ($appointment): void {
            if (PackageRedemption::withoutGlobalScopes()->where('appointment_id', $appointment->id)->exists()) {
                return;
            }

            $package = PetPackage::withoutGlobalScopes()->lockForUpdate()->find($appointment->pet_package_id);
            $item = $package ? $this->nextItem($package) : null;
            if (! $package || ! $item || ! $package->isUsableFor((int) $appointment->pet_id, $appointment->service_id)) {
                throw ValidationException::withMessages(['pet_package_id' => 'O pacote não está disponível para concluir este atendimento.']);
            }

            PackageRedemption::withoutGlobalScopes()->create([
                'company_id' => $appointment->company_id,
                'pet_package_id' => $package->id,
                'pet_package_item_id' => $item->id,
                'appointment_id' => $appointment->id,
                'redeemed_at' => now(),
            ]);
        });
    }

    public function transfer(PetPackage $package, Pet $targetPet): PetPackage
    {
        return DB::transaction(function () use ($package, $targetPet): PetPackage {
            $package = PetPackage::withoutGlobalScopes()->lockForUpdate()->findOrFail($package->id);
            $package->loadMissing('pet.customer');

            if ($targetPet->company_id !== $package->company_id) {
                throw ValidationException::withMessages(['pet_id' => 'O pet de destino precisa ser da mesma loja.']);
            }

            if ($targetPet->customer_id !== $package->pet?->customer_id) {
                throw ValidationException::withMessages(['pet_id' => 'Só é possível transferir saldo entre pets do mesmo responsável.']);
            }

            if ($package->pet_id === $targetPet->id) {
                return $package;
            }

            if (Appointment::withoutGlobalScopes()
                ->where('pet_package_id', $package->id)
                ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])
                ->exists()) {
                throw ValidationException::withMessages(['pet_id' => 'Cancele ou conclua os horários futuros deste pacote antes de transferir.']);
            }

            $package->update(['pet_id' => $targetPet->id]);

            return $package->fresh();
        });
    }
}

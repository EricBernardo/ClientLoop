<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ContactTask;
use App\Models\Customer;
use App\Models\PackageOffer;
use App\Models\PackageRedemption;
use App\Models\Pet;
use App\Models\PetPackage;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Services\DefaultMessageTemplateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::firstOrCreate(['name' => 'Plano demonstração'], ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => false]);
        $company = Company::updateOrCreate(['slug' => 'petshop-patinhas-demo'], [
            'name' => 'Pet Shop Patinhas',
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'confirmation_hours' => 24,
            'reactivation_months' => 6,
            'business_days' => [1, 2, 3, 4, 5, 6],
            'business_starts_at_hour' => 9,
            'business_ends_at_hour' => 17,
            'appointment_slot_minutes' => 60,
        ]);
        CompanySubscription::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id], ['plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subMonth()]);
        User::updateOrCreate(['email' => 'demo@clientloop.test'], ['company_id' => $company->id, 'name' => 'Marina Costa', 'email_verified_at' => now(), 'password' => Hash::make('clientloop123')]);
        User::updateOrCreate(['email' => 'admin@clientloop.test'], ['name' => 'Administração ClientLoop', 'email_verified_at' => now(), 'password' => Hash::make('clientloop123'), 'is_super_admin' => true]);

        $services = collect([
            ['name' => 'Banho', 'suggested_price' => 45, 'duration_minutes' => 60],
            ['name' => 'Tosa', 'suggested_price' => 80, 'duration_minutes' => 120],
            ['name' => 'Banho com higiênico e hidratação', 'suggested_price' => 55, 'duration_minutes' => 60],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Service::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'name' => $data['name']], [...$data, 'company_id' => $company->id, 'active' => true])]);

        $responsibles = collect([
            ['name' => 'Ana Beatriz Lima', 'phone' => '5511998765432'],
            ['name' => 'Carlos Eduardo Alves', 'phone' => '5511987654321'],
            ['name' => 'Fernanda Souza', 'phone' => '5511976543210'],
            ['name' => 'João Pedro Martins', 'phone' => '5511965432109', 'last_activity_at' => now()->subMonths(9)],
            ['name' => 'Mariana Oliveira', 'phone' => '5511954321098'],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Customer::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'phone' => $data['phone']], [...$data, 'company_id' => $company->id])]);

        $pets = collect([
            ['name' => 'Thor', 'responsible' => 'Ana Beatriz Lima', 'species' => 'Cachorro', 'breed' => 'Shih-tzu', 'size' => 'small'],
            ['name' => 'Mel', 'responsible' => 'Carlos Eduardo Alves', 'species' => 'Cachorro', 'breed' => 'Labrador', 'size' => 'large'],
            ['name' => 'Nina', 'responsible' => 'Fernanda Souza', 'species' => 'Gato', 'breed' => 'Siamês', 'size' => 'small'],
            ['name' => 'Bob', 'responsible' => 'João Pedro Martins', 'species' => 'Cachorro', 'breed' => 'Vira-lata', 'size' => 'medium'],
            ['name' => 'Amora', 'responsible' => 'Mariana Oliveira', 'species' => 'Cachorro', 'breed' => 'Poodle', 'size' => 'small'],
            ['name' => 'Pingo', 'responsible' => 'Mariana Oliveira', 'species' => 'Gato', 'breed' => 'SRD', 'size' => 'small'],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Pet::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'customer_id' => $responsibles[$data['responsible']]->id, 'name' => $data['name']], ['species' => $data['species'], 'breed' => $data['breed'], 'size' => $data['size']])]);

        $offerDefinitions = [
            ['name' => '4 banhos', 'sequence' => ['Banho', 'Banho', 'Banho', 'Banho com higiênico e hidratação'], 'suggested_price' => 160],
            ['name' => '4 tosas', 'sequence' => ['Tosa', 'Tosa', 'Tosa', 'Tosa'], 'suggested_price' => 290],
        ];
        $offers = collect($offerDefinitions)->mapWithKeys(function (array $data) use ($company, $services): array {
            $offer = PackageOffer::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'name' => $data['name']], [
                'credits' => count($data['sequence']),
                'suggested_price' => $data['suggested_price'],
                'active' => true,
            ]);
            $offer->items()->delete();
            foreach ($data['sequence'] as $position => $serviceName) {
                $offer->items()->create(['company_id' => $company->id, 'service_id' => $services[$serviceName]->id, 'position' => $position + 1]);
            }

            return [$data['name'] => $offer->fresh()];
        });

        $packages = [
            'thor' => PetPackage::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'pet_id' => $pets['Thor']->id, 'package_offer_id' => $offers['4 banhos']->id],
                $this->packageAttributes($offers['4 banhos'], 'paid', today()->subDays(10)),
            ),
            'mel' => PetPackage::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'pet_id' => $pets['Mel']->id, 'package_offer_id' => $offers['4 tosas']->id],
                $this->packageAttributes($offers['4 tosas'], 'pending', today()),
            ),
            'amora' => PetPackage::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'pet_id' => $pets['Amora']->id, 'package_offer_id' => $offers['4 banhos']->id],
                $this->packageAttributes($offers['4 banhos'], 'paid', today()->subDays(3)),
            ),
        ];
        foreach (['thor' => '4 banhos', 'mel' => '4 tosas', 'amora' => '4 banhos'] as $packageKey => $offerName) {
            $package = $packages[$packageKey];
            $offer = $offers[$offerName];
            $package->update(['name' => $offer->name, 'total_credits' => $offer->credits]);
            $package->items()->delete();
            foreach ($offer->items()->with('service')->orderBy('position')->get() as $item) {
                $package->items()->create(['company_id' => $company->id, 'service_id' => $item->service_id, 'service_name' => $item->service->name, 'duration_minutes' => $item->service->duration_minutes, 'position' => $item->position]);
            }
            $packages[$packageKey] = $package->fresh();
        }

        $nextBusinessDay = $this->nextBusinessDay();
        $followingBusinessDay = $this->nextBusinessDay($nextBusinessDay);
        $lastBusinessDay = $this->previousBusinessDay();

        $appointments = collect([
            ['key' => 'thor-agendado', 'pet' => 'Thor', 'service' => 'Banho', 'scheduled_at' => $nextBusinessDay->copy()->setTime(9, 0), 'status' => 'scheduled', 'package' => 'thor'],
            ['key' => 'mel-confirmado', 'pet' => 'Mel', 'service' => 'Tosa', 'scheduled_at' => $nextBusinessDay->copy()->setTime(11, 0), 'status' => 'confirmed'],
            ['key' => 'amora-agendado', 'pet' => 'Amora', 'service' => 'Banho', 'scheduled_at' => $nextBusinessDay->copy()->setTime(14, 0), 'status' => 'scheduled', 'package' => 'amora'],
            ['key' => 'nina-confirmado', 'pet' => 'Nina', 'service' => 'Banho com higiênico e hidratação', 'scheduled_at' => $followingBusinessDay->copy()->setTime(10, 0), 'status' => 'confirmed'],
            ['key' => 'thor-concluido', 'pet' => 'Thor', 'service' => 'Banho', 'scheduled_at' => $lastBusinessDay->copy()->setTime(9, 0), 'status' => 'completed', 'package' => 'thor'],
        ])->mapWithKeys(function (array $data) use ($company, $pets, $responsibles, $services, $packages): array {
            $pet = $pets[$data['pet']];
            $service = $services[$data['service']];
            $appointment = Appointment::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'scheduled_at' => $data['scheduled_at']], [
                'customer_id' => $responsibles->firstWhere('id', $pet->customer_id)->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'pet_package_id' => isset($data['package']) ? $packages[$data['package']]->id : null,
                'scheduled_at' => $data['scheduled_at'], 'duration_minutes' => $service->duration_minutes, 'status' => $data['status'],
            ]);

            return [$data['key'] => $appointment];
        });

        $templates = app(DefaultMessageTemplateService::class)->provision($company);

        $campaign = Campaign::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'name' => 'Pets para reativar'], ['type' => 'reactivation', 'status' => 'draft', 'message_template_id' => $templates['Reativação']->id, 'filters' => []]);
        ContactTask::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'appointment_id' => $appointments['thor-agendado']->id, 'type' => 'confirmation'], ['customer_id' => $responsibles['Ana Beatriz Lima']->id, 'message_template_id' => $templates['Confirmação de banho']->id, 'priority' => 'high', 'due_at' => now(), 'rendered_message' => 'Olá, Ana Beatriz Lima! O banho de Thor está marcado para '.$nextBusinessDay->format('d/m').' às 09:00. Podemos confirmar?', 'status' => 'pending']);
        ContactTask::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'appointment_id' => $appointments['amora-agendado']->id, 'type' => 'confirmation'], ['customer_id' => $responsibles['Mariana Oliveira']->id, 'message_template_id' => $templates['Confirmação de banho']->id, 'priority' => 'normal', 'due_at' => now()->addHour(), 'rendered_message' => 'Olá, Mariana Oliveira! O banho de Amora está marcado para '.$nextBusinessDay->format('d/m').' às 14:00. Podemos confirmar?', 'status' => 'pending']);
        ContactTask::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id, 'customer_id' => $responsibles['João Pedro Martins']->id, 'type' => 'reactivation'], ['campaign_id' => $campaign->id, 'message_template_id' => $templates['Reativação']->id, 'priority' => 'normal', 'due_at' => now()->subHour(), 'rendered_message' => 'Olá, João Pedro Martins! Faz um tempo que não vemos Bob. Quer reservar um horário?', 'status' => 'pending']);

        PackageRedemption::withoutGlobalScopes()->updateOrCreate(['appointment_id' => $appointments['thor-concluido']->id], ['company_id' => $company->id, 'pet_package_id' => $packages['thor']->id, 'pet_package_item_id' => $packages['thor']->items()->orderBy('position')->first()?->id, 'redeemed_at' => now()->subDay()]);
    }

    private function nextBusinessDay(?Carbon $after = null): Carbon
    {
        $date = ($after ?? now('America/Sao_Paulo'))->copy()->startOfDay();

        do {
            $date->addDay();
        } while ($date->isSunday());

        return $date;
    }

    private function previousBusinessDay(): Carbon
    {
        $date = now('America/Sao_Paulo')->copy()->startOfDay();

        do {
            $date->subDay();
        } while ($date->isSunday());

        return $date;
    }

    /** @return array<string, mixed> */
    private function packageAttributes(PackageOffer $offer, string $paymentStatus, Carbon $purchasedAt): array
    {
        return [
            'name' => $offer->name,
            'total_credits' => $offer->credits,
            'price' => $offer->suggested_price,
            'payment_status' => $paymentStatus,
            'purchased_at' => $purchasedAt,
        ];
    }
}

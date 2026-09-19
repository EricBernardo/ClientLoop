<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;

class CsvImportService
{
    public function __construct(private PhoneNormalizer $phones, private QuotaService $quota) {}

    /** @return array{created:int,updated:int,errors:array<int,string>} */
    public function customers(Company $company, string $path, array $mapping = []): array
    {
        return $this->rows($path, function (array $row) use ($company) {
            $phone = $this->phones->normalize($row['phone'] ?? '');
            $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->where('phone', $phone)->first();
            $payload = ['name' => $row['name'] ?? '', 'phone' => $phone, 'email' => $row['email'] ?? null, 'tags' => isset($row['tags']) ? array_filter(array_map('trim', explode('|', $row['tags']))) : null, 'last_activity_at' => $this->date($row['last_activity_at'] ?? null), 'next_return_at' => $this->date($row['next_return_at'] ?? null), 'opted_out_at' => filter_var($row['opted_out'] ?? false, FILTER_VALIDATE_BOOLEAN) ? now() : null];
            if ($customer) {
                $customer->update($payload);

                return 'updated';
            }
            $this->quota->consumeContact($company);
            Customer::withoutGlobalScopes()->create(['company_id' => $company->id, ...$payload]);

            return 'created';
        }, $mapping);
    }

    /** @return array{created:int,updated:int,errors:array<int,string>} */
    public function appointments(Company $company, string $path, array $mapping = []): array
    {
        return $this->rows($path, function (array $row) use ($company) {
            $phone = $this->phones->normalize($row['phone'] ?? '');
            $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->where('phone', $phone)->first();
            if (! $customer) {
                $this->quota->consumeContact($company);
                $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, 'phone' => $phone, 'name' => $row['customer_name'] ?? $phone]);
            }
            $service = ! empty($row['service']) ? Service::withoutGlobalScopes()->firstOrCreate(['company_id' => $company->id, 'name' => trim($row['service'])]) : null;
            $scheduled = $this->date($row['scheduled_at'] ?? null) ?? throw new \InvalidArgumentException('scheduled_at é obrigatório.');
            $query = Appointment::withoutGlobalScopes()->where('company_id', $company->id);
            $existing = ! empty($row['external_id']) ? $query->where('external_id', $row['external_id'])->first() : $query->where('customer_id', $customer->id)->where('service_id', $service?->id)->where('scheduled_at', $scheduled)->first();
            $payload = ['customer_id' => $customer->id, 'service_id' => $service?->id, 'external_id' => $row['external_id'] ?? null, 'scheduled_at' => $scheduled, 'status' => $row['status'] ?? 'scheduled', 'potential_value' => $row['potential_value'] ?? null, 'realized_value' => $row['realized_value'] ?? null, 'next_return_at' => $this->date($row['next_return_at'] ?? null)];
            if ($existing) {
                $existing->update($payload);

                return 'updated';
            }
            Appointment::withoutGlobalScopes()->create(['company_id' => $company->id, ...$payload]);

            return 'created';
        }, $mapping);
    }

    private function rows(string $path, callable $handler, array $mapping = []): array
    {
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn ($value) => trim((string) $value), $file->fgetcsv());
        $result = ['created' => 0, 'updated' => 0, 'errors' => []];
        $line = 1;
        while (! $file->eof()) {
            $line++;
            $values = $file->fgetcsv();
            if ($values === [null] || $values === false) {
                continue;
            } try {
                $row = array_combine($headers, array_pad($values, count($headers), null));
                foreach ($mapping as $field => $header) {
                    if ($header !== null && $header !== '') {
                        $row[$field] = $row[$header] ?? null;
                    }
                }
                $status = $handler($row);
                $result[$status]++;
            } catch (\Throwable $e) {
                $result['errors'][$line] = $e->getMessage();
            }
        }

        return $result;
    }

    private function date(?string $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }
}

<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Pet;
use Carbon\Carbon;

class CsvImportService
{
    public function __construct(private PhoneNormalizer $phones, private QuotaService $quota) {}

    /** @return array{created:int,updated:int,errors:array<int,string>} */
    public function customers(Company $company, string $path, array $mapping = []): array
    {
        return $this->rows($path, function (array $row) use ($company) {
            $name = trim((string) ($row['responsible_name'] ?? $row['name'] ?? $row['customer_name'] ?? ''));
            if ($name === '') {
                throw new \InvalidArgumentException('Informe o nome do responsável.');
            }
            $phone = $this->phones->normalize($row['responsible_phone'] ?? $row['phone'] ?? '');
            $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->where('phone', $phone)->first();
            $payload = ['name' => $name, 'phone' => $phone, 'last_activity_at' => $this->date($row['last_activity_at'] ?? null), 'next_return_at' => $this->date($row['next_return_at'] ?? null), 'opted_out_at' => filter_var($row['opted_out'] ?? false, FILTER_VALIDATE_BOOLEAN) ? now() : null];
            if ($customer) {
                $customer->update($payload);
                $result = 'updated';
            } else {
                $this->quota->consumeContact($company);
                $customer = Customer::withoutGlobalScopes()->create(['company_id' => $company->id, ...$payload]);
                $result = 'created';
            }

            $petName = trim((string) ($row['pet_name'] ?? ''));
            if ($petName !== '') {
                Pet::withoutGlobalScopes()->firstOrCreate(['company_id' => $company->id, 'customer_id' => $customer->id, 'name' => $petName]);
            }

            return $result;
        }, $mapping);
    }

    private function rows(string $path, callable $handler, array $mapping = []): array
    {
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $headers = array_map(fn (mixed $header): string => $this->normalizeHeader((string) $header), $file->fgetcsv());
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

    private function normalizeHeader(string $header): string
    {
        $header = trim($header);

        return preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
    }
}

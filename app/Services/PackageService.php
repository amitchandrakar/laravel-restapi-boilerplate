<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SyncPackageCandidatePermissionsJob;
use App\Models\Package;
use App\Models\User;
use App\Support\QuerySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageService
{
    public const SORT_OPTIONS = ['sort_order', 'name', 'latest'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPackage(array $data, User $actor): Package
    {
        return DB::transaction(function () use ($data, $actor): Package {
            $isDefault = (bool) ($data['is_default_registration'] ?? false);

            if ($isDefault) {
                Package::query()
                    ->where('is_default_registration', true)
                    ->update(['is_default_registration' => false]);
            }

            $monthlyPrice = (float) $data['monthly_price'];
            $yearlyPrice = (float) $data['yearly_price'];
            $payload = [
                'name' => (string) $data['name'],
                'code' => strtoupper((string) $data['code']),
                'description' => $data['description'] ?? null,
                'duration_unit' => strtolower((string) $data['duration_unit']),
                'monthly_price' => $monthlyPrice,
                'yearly_price' => $yearlyPrice,
                'price' => $yearlyPrice,
                'discounted_price' => null,
                'currency' => strtoupper((string) ($data['currency'] ?? 'INR')),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default_registration' => $isDefault,
                'is_popular' => (bool) ($data['is_popular'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            /** @var Package $package */
            $package = Package::query()->create($payload);

            if (array_key_exists('permission_ids', $data) && is_array($data['permission_ids'])) {
                $package->permissions()->sync(array_values(array_unique(array_map('intval', $data['permission_ids']))));
                SyncPackageCandidatePermissionsJob::dispatch((int) $package->id);
            }

            Log::info('PackageService: package created', ['package_id' => $package->id]);

            return $package->refresh()->load('permissions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePackage(Package $package, array $data, User $actor): Package
    {
        return DB::transaction(function () use ($package, $data, $actor): Package {
            $payload = [];

            if (array_key_exists('name', $data)) {
                $payload['name'] = (string) $data['name'];
            }

            if (array_key_exists('code', $data)) {
                $payload['code'] = strtoupper((string) $data['code']);
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }

            if (array_key_exists('duration_unit', $data)) {
                $payload['duration_unit'] = strtolower((string) $data['duration_unit']);
            }

            if (array_key_exists('monthly_price', $data)) {
                $payload['monthly_price'] = (float) $data['monthly_price'];
            }

            if (array_key_exists('yearly_price', $data)) {
                $payload['yearly_price'] = (float) $data['yearly_price'];
                $payload['price'] = (float) $data['yearly_price'];
            }

            if (array_key_exists('currency', $data)) {
                $payload['currency'] = strtoupper((string) $data['currency']);
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('is_popular', $data)) {
                $payload['is_popular'] = (bool) $data['is_popular'];
            }

            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }

            if (array_key_exists('is_default_registration', $data)) {
                $isDefault = (bool) $data['is_default_registration'];

                if ($isDefault) {
                    Package::query()
                        ->where('id', '!=', $package->id)
                        ->where('is_default_registration', true)
                        ->update(['is_default_registration' => false]);
                }
                $payload['is_default_registration'] = $isDefault;
            }

            $payload['updated_by'] = $actor->id;

            $package->update($payload);

            if (array_key_exists('permission_ids', $data) && is_array($data['permission_ids'])) {
                $package->permissions()->sync(array_values(array_unique(array_map('intval', $data['permission_ids']))));
                SyncPackageCandidatePermissionsJob::dispatch((int) $package->id);
            }

            Log::info('PackageService: package updated', ['package_id' => $package->id]);

            return $package->refresh()->load('permissions');
        });
    }

    public function deletePackage(Package $package, User $actor): void
    {
        DB::transaction(function () use ($package, $actor): void {
            $package->update(['updated_by' => $actor->id]);
            $package->delete();
            Log::info('PackageService: package deleted', ['package_id' => $package->id]);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return LengthAwarePaginator<int, Package>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['perPage'] ?? 15)));

        return $this->buildListQuery($filters)->with('permissions')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @return Builder<Package>
     */
    public function buildListQuery(array $filters = []): Builder
    {
        $query = Package::query();

        if (!empty($filters['search'])) {
            QuerySearch::whereContainsAny($query, ['name', 'code'], (string) $filters['search']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['duration_unit'])) {
            $query->where('duration_unit', (string) $filters['duration_unit']);
        }

        if (array_key_exists('is_popular', $filters)) {
            $query->where('is_popular', filter_var($filters['is_popular'], FILTER_VALIDATE_BOOLEAN));
        }

        $sort = (string) ($filters['sort'] ?? 'sort_order');

        match ($sort) {
            'name' => $query->orderBy('name'),
            'latest' => $query->orderByDesc('id'),
            default => $query->orderBy('sort_order')->orderByDesc('id'),
        };

        return $query;
    }
}

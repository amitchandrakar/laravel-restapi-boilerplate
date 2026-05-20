<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Role;
use App\Support\ScoutConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

trait SearchableCandidateProfile
{
    use Searchable;

    public function searchableAs(): string
    {
        return ScoutConfig::candidateIndexName();
    }

    public function getScoutKey(): mixed
    {
        return $this->uuid;
    }

    public function getScoutKeyName(): string
    {
        return 'uuid';
    }

    public function shouldBeSearchable(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        if ((string) ($this->profile_status ?? '') !== 'published' || $this->published_at === null) {
            return false;
        }

        return $this->isCandidateRole();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $age = null;
        if ($this->date_of_birth !== null) {
            $age = Carbon::parse($this->date_of_birth)->age;
        }

        $educationDegreeIds = DB::table('user_education_details')
            ->where('user_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('degree_id')
            ->map(static fn($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $displayName = trim(implode(' ', array_filter([
            (string) ($this->first_name ?? ''),
            (string) ($this->last_name ?? ''),
        ])));

        return [
            'uuid' => (string) $this->uuid,
            'first_name' => (string) ($this->first_name ?? ''),
            'last_name' => mb_strtolower(trim((string) ($this->last_name ?? ''))),
            'display_name' => $displayName,
            'gender' => (string) ($this->gender ?? ''),
            'age' => $age,
            'body_type' => (string) ($this->body_type ?? ''),
            'complexion' => (string) ($this->complexion ?? ''),
            'height' => (string) ($this->height ?? ''),
            'weight' => (string) ($this->weight ?? ''),
            'current_city' => mb_strtolower(trim((string) ($this->current_city ?? ''))),
            'current_state' => mb_strtolower(trim((string) ($this->current_state ?? ''))),
            'current_country' => mb_strtolower(trim((string) ($this->current_country ?? ''))),
            'occupation' => mb_strtolower(trim((string) ($this->occupation ?? ''))),
            'diet' => (string) ($this->diet ?? ''),
            'smoking' => (string) ($this->smoking ?? ''),
            'drinking' => (string) ($this->drinking ?? ''),
            'education_degree_ids' => $educationDegreeIds,
            'profile_status' => (string) ($this->profile_status ?? ''),
            'is_searchable' => 1,
            'is_featured' => $this->is_featured ? 1 : 0,
            'published_at' => $this->publishedAtTimestamp(),
            'tier' => $this->resolveSearchTier(),
        ];
    }

    public function isCandidateRole(): bool
    {
        if ($this->relationLoaded('primaryRole')) {
            $role = $this->primaryRole;

            return $role instanceof Role && $role->name === 'candidate';
        }

        $roleName = DB::table('roles')->where('id', $this->role_id)->value('name');

        return $roleName === 'candidate';
    }

    private function publishedAtTimestamp(): ?int
    {
        if ($this->published_at === null) {
            return null;
        }

        return Carbon::parse($this->published_at)->getTimestamp();
    }

    private function resolveSearchTier(): string
    {
        if ($this->is_featured) {
            return 'featured';
        }

        return 'standard';
    }

    public function wasSearchable(): bool
    {
        return (string) ($this->profile_status ?? '') === 'published' && $this->published_at !== null;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Candidate;

use App\Http\Requests\Api\ApiFormRequest;

class SaveCandidateLifestyleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return self::rulesWithPrefix('');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rulesWithPrefix(string $prefix): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.') . '.';

        return [
            $p . 'sleep_pattern' => ['nullable', 'string', 'max:64'],
            $p . 'working_hours' => ['nullable', 'string', 'max:64'],
            $p . 'social_personality' => ['nullable', 'string', 'max:32'],
            $p . 'dietary_preferences' => ['nullable', 'string', 'max:64'],
            $p . 'drinking_habits' => ['nullable', 'string', 'max:32'],
            $p . 'smoking_habits' => ['nullable', 'string', 'max:32'],
            $p . 'fitness_level' => ['nullable', 'string', 'max:64'],
            $p . 'travel_style' => ['nullable', 'string', 'max:64'],
            $p . 'communication_style' => ['nullable', 'string', 'max:64'],
            $p . 'relationship_with_family' => ['nullable', 'string', 'max:64'],
            $p . 'weekend_preference' => ['nullable', 'string', 'max:64'],
            $p . 'diet' => ['nullable', 'string', 'max:64'],
            $p . 'smoking' => ['nullable', 'string', 'max:64'],
            $p . 'drinking' => ['nullable', 'string', 'max:64'],
            $p . 'interests' => ['nullable', 'array', 'max:100'],
            $p . 'interests.*' => ['string', 'max:128'],
            $p . 'movie_genres' => ['nullable', 'array', 'max:100'],
            $p . 'movie_genres.*' => ['string', 'max:128'],
            $p . 'hobbies' => ['nullable', 'array', 'max:100'],
            $p . 'hobbies.*' => ['string', 'max:128'],
            $p . 'likes' => ['nullable', 'array', 'max:100'],
            $p . 'likes.*' => ['string', 'max:128'],
            $p . 'dislikes' => ['nullable', 'array', 'max:100'],
            $p . 'dislikes.*' => ['string', 'max:128'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Static option lists for candidate profile editors (heights, body types, etc.).
 */
final class CandidateProfileOptionSets
{
    /**
     * Heights from 4'0" through 8'0" in one-inch steps (49 entries).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function heights(): array
    {
        $out = [];
        for ($totalInches = 48; $totalInches <= 96; $totalInches++) {
            $feet = intdiv($totalInches, 12);
            $inches = $totalInches % 12;
            $label = sprintf("%d'%d\"", $feet, $inches);
            $out[] = [
                'value' => $feet . '-' . $inches,
                'label' => $label,
            ];
        }

        return $out;
    }

    /**
     * @return array{male: list<array{value: string, label: string}>, female: list<array{value: string, label: string}>}
     */
    public static function bodyTypesByGender(): array
    {
        return [
            'male' => [
                ['value' => 'slim', 'label' => 'Slim'],
                ['value' => 'average_medium', 'label' => 'Average / Medium'],
                ['value' => 'athletic_fit', 'label' => 'Athletic / Fit'],
                ['value' => 'heavy_broad_plus_size', 'label' => 'Heavy / Broad / Plus-Size'],
            ],
            'female' => [
                ['value' => 'slim', 'label' => 'Slim'],
                ['value' => 'average_medium', 'label' => 'Average / Medium'],
                ['value' => 'athletic_fit', 'label' => 'Athletic / Fit'],
                ['value' => 'curvy', 'label' => 'Curvy'],
                ['value' => 'heavy_plus_size', 'label' => 'Heavy / Plus-Size'],
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function complexions(): array
    {
        return [
            ['value' => 'fair', 'label' => 'Fair'],
            ['value' => 'light', 'label' => 'Light'],
            ['value' => 'wheatish', 'label' => 'Wheatish'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'dusky', 'label' => 'Dusky'],
            ['value' => 'dark', 'label' => 'Dark'],
            ['value' => 'deep', 'label' => 'Deep'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function bloodGroups(): array
    {
        return [
            ['value' => 'A+', 'label' => 'A+'],
            ['value' => 'A-', 'label' => 'A-'],
            ['value' => 'B+', 'label' => 'B+'],
            ['value' => 'B-', 'label' => 'B-'],
            ['value' => 'AB+', 'label' => 'AB+'],
            ['value' => 'AB-', 'label' => 'AB-'],
            ['value' => 'O+', 'label' => 'O+'],
            ['value' => 'O-', 'label' => 'O-'],
            ['value' => 'not_sure', 'label' => 'Not Sure'],
        ];
    }

    /**
     * Zodiac slug matches filenames under public/images/zodiac/{slug}.svg.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function zodiacSigns(): array
    {
        return [
            ['value' => 'aries', 'label' => 'Aries'],
            ['value' => 'taurus', 'label' => 'Taurus'],
            ['value' => 'gemini', 'label' => 'Gemini'],
            ['value' => 'cancer', 'label' => 'Cancer'],
            ['value' => 'leo', 'label' => 'Leo'],
            ['value' => 'virgo', 'label' => 'Virgo'],
            ['value' => 'libra', 'label' => 'Libra'],
            ['value' => 'scorpio', 'label' => 'Scorpio'],
            ['value' => 'sagittarius', 'label' => 'Sagittarius'],
            ['value' => 'capricorn', 'label' => 'Capricorn'],
            ['value' => 'aquarius', 'label' => 'Aquarius'],
            ['value' => 'pisces', 'label' => 'Pisces'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function diets(): array
    {
        return [
            ['value' => 'vegetarian', 'label' => 'Vegetarian'],
            ['value' => 'non_vegetarian', 'label' => 'Non-Vegetarian'],
            ['value' => 'vegan', 'label' => 'Vegan'],
            ['value' => 'eggitarian', 'label' => 'Eggitarian'],
            ['value' => 'keto', 'label' => 'Keto'],
        ];
    }
}

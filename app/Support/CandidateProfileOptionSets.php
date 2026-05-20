<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Static option lists for candidate profile editors (heights, body types, etc.).
 */
final class CandidateProfileOptionSets
{
    /**
     * @param  list<string>  $values
     *
     * @return list<array{value: string, label: string}>
     */
    private static function asValueLabel(array $values): array
    {
        return array_map(static fn(string $v): array => ['value' => $v, 'label' => $v], $values);
    }

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

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function sleepPatterns(): array
    {
        return self::asValueLabel([
            'Early Bird (Morning Person)',
            'Night Owl',
            'Flexible',
            'Heavy Sleeper',
            'Light Sleeper',
            'Sleeps Late & Wakes Late',
            'Sleeps Early & Wakes Early',
            'Weekend Catch-up Sleeper',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function workingHours(): array
    {
        return self::asValueLabel([
            'Standard 9-to-5',
            'Flexible Hours',
            'Remote Worker',
            'Hybrid Worker',
            'Shift Work',
            'Night Shifts',
            'Rotational Shifts',
            'Freelancer',
            'Business Owner',
            'Startup Lifestyle',
            'Part-time',
            'Student Schedule',
            'Workaholic',
            'Always On (Freelance/Business)',
            'Seasonal Work',
            'Retired',
            'Homemaker',
            'Unemployed / Between Jobs',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function socialPersonalities(): array
    {
        return self::asValueLabel(['Introvert', 'Extrovert', 'Ambivert', 'Talkative']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function interests(): array
    {
        return self::asValueLabel([
            'Photography',
            'Gaming',
            'Gardening',
            'Music',
            'DIY/Crafts',
            'Cooking/Culinary',
            'Tech & Gadgets',
            'Reading/Literature',
            'Fashion & Styling',
            'Fitness & Gym',
            'Yoga & Meditation',
            'Astrology',
            'Volunteering',
            'Pets/Animal Welfare',
            'Fine Arts (Painting/Sketching)',
            'Writing/Blogging',
            'Travel',
            'Entrepreneurship',
            'Investing & Finance',
            'Movies & Cinema',
            'Spirituality',
            'Nature & Wildlife',
            'Cars & Bikes',
            'Interior Design',
            'History & Culture',
            'Science & Innovation',
            'Podcasts',
            'Language Learning',
            'Social Media Content Creation',
            'Dance & Performing Arts',
            'Stand-up Comedy',
            'Architecture',
            'Food Exploration',
            'Self-development',
            'Politics & Current Affairs',
            'Minimalism',
            'Luxury Lifestyle',
            'Anime & Manga',
            'Esports',
            'Public Speaking',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function movieGenres(): array
    {
        return self::asValueLabel([
            'Action/Adventure',
            'Comedy',
            'Romantic',
            'Rom-Com',
            'Drama',
            'Thriller',
            'Horror',
            'Mystery',
            'Sci-Fi/Fantasy',
            'Documentary',
            'Anime',
            'Historical/Period Drama',
            'Reality TV',
            'Biopic',
            'Crime',
            'Psychological Thriller',
            'Family',
            'Animation',
            'Musical',
            'War',
            'Sports',
            'Superhero',
            'Suspense',
            'Dark Comedy',
            'Indie Films',
            'Classic Cinema',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function hobbies(): array
    {
        return self::asValueLabel([
            'Trekking/Hiking',
            'Playing an Instrument',
            'Dancing',
            'Singing',
            'Cycling',
            'Swimming',
            'Traveling/Backpacking',
            'Solving Puzzles/Board Games',
            'Bird Watching',
            'Coding/Open Source',
            'Stargazing',
            'Wine/Tea Tasting',
            'Fishing',
            'Camping',
            'Motorcycling',
            'Running/Jogging',
            'Gym Workouts',
            'Martial Arts',
            'Calligraphy',
            'Pottery',
            'Collecting Items',
            'Blogging/Vlogging',
            'Baking',
            'Chess',
            'Meditation',
            'Volunteering',
            'Stock Trading',
            'Watching Web Series',
            'Learning New Skills',
            'DIY Home Projects',
            'Listening to Podcasts',
            'Stand-up Comedy',
            'Attending Workshops',
            'Creative Writing',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function likes(): array
    {
        return self::asValueLabel([
            'Sunsets & Beaches',
            'Home-cooked meals',
            'Meaningful Conversations',
            'Road Trips',
            'Clean & Organized Spaces',
            'Live Music/Concerts',
            'Rain/Petrichor',
            'Early Morning Walks',
            'Spontaneous Plans',
            'Art Galleries',
            'Late-night Talks',
            'Peace & Quiet',
            'Festivals & Celebrations',
            'Coffee Dates',
            'Tea Time',
            'Pets',
            'Nature Getaways',
            'Fitness Lifestyle',
            'Family Time',
            'Luxury Experiences',
            'Minimalist Lifestyle',
            'Adventure Activities',
            'Books & Libraries',
            'Street Food',
            'Learning New Things',
            'Deep Emotional Connections',
            'Humor & Sarcasm',
            'Long Drives',
            'Watching Sunrises',
            'Small Gatherings',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function dislikes(): array
    {
        return self::asValueLabel([
            'Smoking/Alcohol Smell',
            'Rudeness/Lack of Manners',
            'Lying/Dishonesty',
            'Unpunctuality',
            'Crowded Places',
            'Animal Cruelty',
            'Constant Social Media Use',
            'Loud Environments',
            'Wastage of Food',
            'Gossiping',
            'Negativity',
            'Drama & Toxicity',
            'Poor Hygiene',
            'Disorganization',
            'Arrogance',
            'Fake Personalities',
            'Overworking',
            'Lack of Communication',
            'Judgmental People',
            'Self-centered Behavior',
            'Messy Spaces',
            'Noise Pollution',
            'Canceling Plans Last Minute',
            'Overdependence',
            'Lack of Ambition',
            'Irresponsibility',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function dietaryPreferences(): array
    {
        return self::asValueLabel(['Vegetarian', 'Non-Vegetarian', 'Eggitarian', 'No Dietary Restrictions']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function drinkingHabits(): array
    {
        return self::asValueLabel(['Never', 'Occasionally', 'Regularly']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function smokingHabits(): array
    {
        return self::asValueLabel(['Non-smoker', 'Occasionally', 'Regularly']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function fitnessLevels(): array
    {
        return self::asValueLabel([
            'Very Active',
            'Moderately Active',
            'Fitness Enthusiast',
            'Gym Regular',
            'Yoga Practitioner',
            'Sports Player',
            'Occasionally Active',
            'Sedentary Lifestyle',
            'Health Conscious',
            'Training for Goals',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function travelStyles(): array
    {
        return self::asValueLabel([
            'Luxury Traveler',
            'Budget Traveler',
            'Backpacker',
            'Road Trip Lover',
            'Adventure Traveler',
            'Solo Traveler',
            'Family Traveler',
            'Weekend Explorer',
            'International Traveler',
            'Nature Traveler',
            'Beach Lover',
            'Mountain Lover',
            'City Explorer',
            'Cultural Traveler',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function communicationStyles(): array
    {
        return self::asValueLabel([
            'Soft-spoken',
            'Humorous',
            'Emotional',
            'Logical',
            'Expressive',
            'Listener First',
            'Talkative',
            'Reserved',
            'Straightforward',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function relationshipsWithFamily(): array
    {
        return self::asValueLabel([
            'Very Close',
            'Close-knit Family',
            'Independent but Connected',
            'Moderately Close',
            'Traditional Family Values',
            'Modern Family Values',
            'Lives with Family',
            'Prefers Nuclear Family',
            'Prefers Joint Family',
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function weekendPreferences(): array
    {
        return self::asValueLabel([
            'Staying Home',
            'Traveling',
            'Partying',
            'Family Time',
            'Outdoor Activities',
            'Watching Movies/Series',
            'Social Gatherings',
            'Learning & Self-growth',
            'Sleeping & Relaxing',
            'Fitness & Sports',
        ]);
    }
}

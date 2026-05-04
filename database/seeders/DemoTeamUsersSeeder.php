<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTeamUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::withTrashed()
            ->where('email', 'like', 'team%@example.com')
            ->get()
            ->each(static function (User $user): void {
                $user->forceDelete();
            });
    }
}

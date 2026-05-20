<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ScoutConfig;
use Illuminate\Console\Command;

class SyncPublishedCandidatesToAlgolia extends Command
{
    protected $signature = 'candidates:sync-algolia {--chunk=100 : Records per chunk}';

    protected $description = 'Import all published candidate profiles into the Algolia index';

    public function handle(): int
    {
        if (!ScoutConfig::usesAlgolia()) {
            $this->error('Algolia is not configured. Set SCOUT_DRIVER=algolia and ALGOLIA_* in .env');

            return self::FAILURE;
        }

        $chunk = max(10, (int) $this->option('chunk'));

        $query = User::query()
            ->candidates()
            ->where('profile_status', 'published')
            ->whereNotNull('published_at')
            ->whereNull('deleted_at');

        $total = (int) $query->count();
        $this->info("Syncing {$total} published candidates to Algolia index: " . ScoutConfig::candidateIndexName());

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($users) use ($bar): void {
            /** @var \Illuminate\Support\Collection<int, User> $users */
            $searchable = $users->filter(static fn(User $user): bool => $user->shouldBeSearchable());
            if ($searchable->isNotEmpty()) {
                $searchable->searchable();
            }
            $bar->advance($users->count());
        });

        $bar->finish();
        $this->newLine();
        $this->info('Algolia sync jobs dispatched (ensure queue workers are running).');

        return self::SUCCESS;
    }
}

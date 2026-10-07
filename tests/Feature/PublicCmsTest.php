<?php

declare(strict_types=1);

use App\Support\CacheKeys;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns public site settings without auth', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);

    $this->getJson('/api/v1/app/public/site-settings')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'siteName',
                'logoUrl',
                'faviconUrl',
                'contactEmail',
                'contactPhone',
                'contactAddress',
                'allowedCommunitySurnames',
                'successStoriesCount',
                'maintenanceMode',
            ],
        ])
        ->assertJsonMissingPath('data.requireProfileApproval');
});

it('returns published legal page by slug', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);

    DB::table('legal_pages')
        ->where('slug', 'terms')
        ->update([
            'is_published' => true,
            'title' => 'Terms of Service',
            'body' => '<p>Public terms.</p>',
        ]);

    $this->getJson('/api/v1/app/public/legal-pages/terms')
        ->assertStatus(200)
        ->assertJsonPath('data.slug', 'terms')
        ->assertJsonPath('data.title', 'Terms of Service')
        ->assertJsonPath('data.body', '<p>Public terms.</p>')
        ->assertJsonMissingPath('data.isPublished');
});

it('returns 404 for unpublished legal page', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);

    DB::table('legal_pages')
        ->where('slug', 'privacy-policy')
        ->update(['is_published' => false]);

    $this->getJson('/api/v1/app/public/legal-pages/privacy-policy')->assertStatus(404);
});

it('caches published legal pages on repeat requests', function () {
    $this->seed(RbacSeeder::class);
    $this->seed(DemoMasterDataSeeder::class);

    DB::table('legal_pages')
        ->where('slug', 'terms')
        ->update([
            'is_published' => true,
            'title' => 'Cached Terms',
            'body' => '<p>Cached body.</p>',
        ]);

    Cache::forget(CacheKeys::publicLegalPage('terms'));

    $this->getJson('/api/v1/app/public/legal-pages/terms')
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Cached Terms');

    expect(Cache::has(CacheKeys::publicLegalPage('terms')))->toBeTrue();

    DB::table('legal_pages')
        ->where('slug', 'terms')
        ->update(['title' => 'Mutated Title']);

    $this->getJson('/api/v1/app/public/legal-pages/terms')
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Cached Terms');
});

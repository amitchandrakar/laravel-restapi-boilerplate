<?php

declare(strict_types=1);

use Database\Seeders\RbacSeeder;

it('allows admins to update and fetch SEO settings', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-seo@example.com');

    $payload = [
        'siteTitle' => 'Kurmi Samaj Matrimonial',
        'defaultDescription' => 'Find your perfect life partner.',
        'defaultKeywords' => 'Kurmi matrimony, Kurmi shaadi',
        'canonicalBaseUrl' => 'https://example.com',
        'googleAnalyticsEnabled' => true,
        'googleAnalyticsSnippet' => '<script>window.test=true;</script>',
        'robotsEnabled' => true,
        'robotsTxt' => "User-agent: *\nDisallow:",
        'sitemapEnabled' => true,
        'sitemapUrls' => "/\n/browse",
        'ogImage' => '/og-image.jpg',
        'ogType' => 'website',
        'twitterCard' => 'summary_large_image',
        'twitterTitle' => 'Twitter SEO title',
        'twitterDescription' => 'Twitter SEO description',
        'twitterImage' => '/twitter-image.jpg',
    ];

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/seo', $payload)
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siteTitle', 'Kurmi Samaj Matrimonial')
        ->assertJsonPath('data.googleAnalyticsEnabled', true)
        ->assertJsonPath('data.sitemapUrls', "/\n/browse");

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/settings/seo')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siteTitle', 'Kurmi Samaj Matrimonial')
        ->assertJsonPath('data.twitterCard', 'summary_large_image');
});

it('returns forbidden when a candidate updates SEO settings', function () {
    $this->seed(RbacSeeder::class);
    $candidate = $this->createUserWithRole('candidate', 'candidate-seo@example.com');

    $this->actingAs($candidate, 'sanctum')
        ->putJson('/api/v1/admin/settings/seo', ['siteTitle' => 'Nope'])
        ->assertStatus(403);
});

it('rejects SEO settings payloads that fail validation', function () {
    $this->seed(RbacSeeder::class);
    $admin = $this->createUserWithRole('admin', 'admin-seo-validation@example.com');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/admin/settings/seo', [
            'canonicalBaseUrl' => 'invalid-url',
        ])
        ->assertStatus(422);
});

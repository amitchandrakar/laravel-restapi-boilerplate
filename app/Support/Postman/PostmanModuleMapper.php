<?php

declare(strict_types=1);

namespace App\Support\Postman;

final class PostmanModuleMapper
{
    /**
     * @return array{realm: string, module: string, folder: string}
     */
    public function map(PostmanRouteRecord $record): array
    {
        $uri = $record->uri;

        if ($uri === 'api' || str_starts_with($uri, 'api/health')) {
            return [
                'realm' => 'infrastructure',
                'module' => 'infrastructure',
                'folder' => 'Infrastructure',
            ];
        }

        if (str_starts_with($uri, 'api/v1/auth/')) {
            return [
                'realm' => 'shared',
                'module' => 'auth',
                'folder' => 'Shared/Auth',
            ];
        }

        if (str_starts_with($uri, 'api/v1/admin/')) {
            $suffix = substr($uri, strlen('api/v1/admin/'));

            return [
                'realm' => 'admin',
                'module' => $this->adminModule($suffix),
                'folder' => 'Admin/' . $this->moduleLabel($this->adminModule($suffix)),
            ];
        }

        if (str_starts_with($uri, 'api/v1/app/')) {
            $suffix = substr($uri, strlen('api/v1/app/'));

            return [
                'realm' => 'app',
                'module' => $this->appModule($suffix),
                'folder' => 'App/' . $this->moduleLabel($this->appModule($suffix)),
            ];
        }

        return [
            'realm' => 'infrastructure',
            'module' => 'infrastructure',
            'folder' => 'Infrastructure',
        ];
    }

    private function adminModule(string $suffix): string
    {
        if (str_starts_with($suffix, 'auth/')) {
            return 'auth';
        }

        if (str_starts_with($suffix, 'candidates')) {
            return 'candidates';
        }

        if (str_starts_with($suffix, 'packages')) {
            return 'packages';
        }

        if (str_starts_with($suffix, 'subscriptions')) {
            return 'subscriptions';
        }

        if (str_starts_with($suffix, 'payments')) {
            return 'payments';
        }

        if (str_starts_with($suffix, 'reports')) {
            return 'reports';
        }

        if (str_starts_with($suffix, 'dashboard') || str_starts_with($suffix, 'system-health')) {
            return 'dashboard';
        }

        if (str_starts_with($suffix, 'settings')) {
            return 'settings';
        }

        if (str_starts_with($suffix, 'team-users')) {
            return 'team-users';
        }

        if (str_starts_with($suffix, 'users')) {
            return 'users';
        }

        return 'misc';
    }

    private function appModule(string $suffix): string
    {
        if (str_starts_with($suffix, 'public/')) {
            return 'public';
        }

        if (str_contains($suffix, 'webhook') || str_starts_with($suffix, 'payment/razorpay')) {
            return 'webhooks';
        }

        if (str_starts_with($suffix, 'me/')) {
            return 'me';
        }

        if (str_starts_with($suffix, 'auth/candidate/profile')) {
            return 'candidate-profile';
        }

        if (str_starts_with($suffix, 'auth/candidate/contact-requests')) {
            return 'contact-requests';
        }

        if (
            str_starts_with($suffix, 'auth/candidate/search') ||
            str_starts_with($suffix, 'auth/candidate/favorites') ||
            str_starts_with($suffix, 'auth/candidate/matches')
        ) {
            return 'discovery';
        }

        if (
            str_contains($suffix, 'auth/candidate/') &&
            (str_contains($suffix, '/photos') || str_contains($suffix, 'profile-details'))
        ) {
            return 'candidate-profile';
        }

        if (str_starts_with($suffix, 'auth/candidate/kyc')) {
            return 'candidate-kyc';
        }

        if (str_starts_with($suffix, 'auth/')) {
            return 'auth';
        }

        return 'misc';
    }

    private function moduleLabel(string $module): string
    {
        return str_replace('-', ' ', ucwords(str_replace('-', ' ', $module)));
    }
}

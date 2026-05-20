<?php

declare(strict_types=1);
use App\Support\UserImageStorageUrl;

it('detects relative storage keys in image URLs', function () {
    expect(UserImageStorageUrl::isRelativeStorageKey('12/id_verification/x.webp'))->toBeTrue();
    expect(UserImageStorageUrl::isRelativeStorageKey('https://example.com/a.webp'))->toBeFalse();
    expect(UserImageStorageUrl::isRelativeStorageKey(''))->toBeFalse();
});

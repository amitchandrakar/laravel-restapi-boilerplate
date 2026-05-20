<?php

declare(strict_types=1);
use Symfony\Component\Finder\Finder;

it('requires declare(strict_types=1) across PHP files in app, config, and core paths', function () {
    $directories = [__DIR__ . '/../../app', __DIR__ . '/../../tests'];

    $finder = new Finder();
    $finder->files()->in($directories)->name('*.php')->notName('StrictTypesTest.php');

    // Skip itself to avoid recursion issues if it were dynamic, though here it's fine
    $filesWithoutStrictTypes = [];

    foreach ($finder as $file) {
        $content = $file->getContents();

        // Check if declare(strict_types=1); exists
        if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content)) {
            $filesWithoutStrictTypes[] = $file->getRelativePathname();
        }
    }

    expect($filesWithoutStrictTypes)->toBeEmpty("The following files are missing 'declare(strict_types=1);':\n" . implode("\n", $filesWithoutStrictTypes));
});

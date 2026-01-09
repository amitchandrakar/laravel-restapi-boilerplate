<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class StrictTypesTest extends TestCase
{
    /** @test */
    public function all_php_files_must_declare_strict_types(): void
    {
        $directories = [__DIR__ . '/../../app', __DIR__ . '/../../tests'];

        $finder = new Finder();
        $finder->files()->in($directories)->name('*.php')->notName('StrictTypesTest.php'); // Skip itself to avoid recursion issues if it were dynamic, though here it's fine

        $filesWithoutStrictTypes = [];

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Check if declare(strict_types=1); exists
            if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content)) {
                $filesWithoutStrictTypes[] = $file->getRelativePathname();
            }
        }

        $this->assertEmpty(
            $filesWithoutStrictTypes,
            "The following files are missing 'declare(strict_types=1);':\n" . implode("\n", $filesWithoutStrictTypes)
        );
    }
}

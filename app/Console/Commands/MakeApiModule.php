<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeApiModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-module {name}
    {--api-version=V1 : The API version (V1, V2, etc.)}
    {--no-observer : Skip observer generation}
    {--no-event : Skip event and listener generation}
    {--no-notification : Skip notification generation}
    {--skip-optional : Skip observer, event, listener, and notification generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a standardized API module including model (with migration), API controller, request, resource, service, feature test, and documentation.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = Str::studly(trim($this->argument('name')));
        $version = strtoupper($this->option('api-version'));

        if ($name === '') {
            $this->error('Module name is required and must be a valid class name.');

            return self::FAILURE;
        }

        // Resolve flags
        $skipOptional = $this->option('skip-optional');
        $skipObserver = $skipOptional || $this->option('no-observer');
        $skipEvent = $skipOptional || $this->option('no-event');
        $skipNotification = $skipOptional || $this->option('no-notification');

        // Architectural guardrails
        if ($skipObserver && !$skipEvent) {
            $this->error('Invalid configuration: Events require an observer. Use --no-event or remove --no-observer.');

            return self::FAILURE;
        }

        $this->info("Scaffolding API module: {$name} ({$version})");
        $this->newLine();

        /*
        |------------------------------------------------------------------
        | Core artifacts (always generated)
        |------------------------------------------------------------------
        */
        $this->call('make:model', [
            'name' => $name,
            '--migration' => true,
        ]);

        $this->call('make:controller', [
            'name' => "Api/{$version}/{$name}Controller",
            '--api' => true,
        ]);

        $this->call('make:request', [
            'name' => "Api/{$version}/{$name}Request",
        ]);

        $this->call('make:resource', [
            'name' => "Api/{$version}/{$name}Resource",
        ]);

        $this->makeService($name);

        /*
        |------------------------------------------------------------------
        | Mandatory quality gates
        |------------------------------------------------------------------
        */
        $this->makeTest($name, $version);
        $this->makeDocs($name);

        /*
        |------------------------------------------------------------------
        | Optional architecture components
        |------------------------------------------------------------------
        */
        if (!$skipObserver) {
            $this->call('make:observer', [
                'name' => "{$name}Observer",
                '--model' => $name,
            ]);
        }

        if (!$skipEvent) {
            $eventName = "{$name}Created";

            $this->call('make:event', [
                'name' => $eventName,
            ]);

            $this->call('make:listener', [
                'name' => "Send{$name}Notification",
                '--event' => $eventName,
            ]);
        }

        if (!$skipNotification) {
            $this->call('make:notification', [
                'name' => "{$name}Notification",
            ]);
        }

        /*
        |------------------------------------------------------------------
        | Completion + enforced checklist
        |------------------------------------------------------------------
        */
        $this->newLine();
        $this->info('API module scaffolded successfully.');

        $this->line('Next steps (mandatory):');

        if (!$skipObserver) {
            $this->line("1) Register {$name}Observer in AppServiceProvider.");
        }

        $this->line('2) Register API routes in routes/api.php:');
        $this->info(
            "   Route::apiResource('" .
                Str::kebab(Str::plural($name)) .
                "', \App\Http\Controllers\Api\\{$version}\\{$name}Controller::class);"
        );

        if (!$skipObserver && !$skipEvent) {
            $this->line("3) Dispatch {$name}Created event from {$name}Observer.");
        }

        $this->line("4) Implement API logic and write tests in tests/Feature/Api/{$version}/{$name}Test.php.");
        $this->line("5) Update documentation in docs/api-modules/{$name}.md.");

        return self::SUCCESS;
    }

    protected function makeService(string $name): void
    {
        $path = app_path("Services/{$name}Service.php");

        if (File::exists($path)) {
            $this->warn('Service already exists');

            return;
        }

        if (!is_dir(app_path('Services'))) {
            mkdir(app_path('Services'), 0755, true);
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Services;

class {$name}Service
{
    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        //
    }
}
PHP;

        File::put($path, $stub);
    }

    protected function makeTest(string $name, string $version): void
    {
        $path = base_path("tests/Feature/Api/{$version}/{$name}Test.php");
        $directory = dirname($path);

        if (File::exists($path)) {
            $this->warn('Test already exists');

            return;
        }

        File::ensureDirectoryExists($directory);

        $endpoint = '/api/' . strtolower($version) . '/' . Str::kebab(Str::plural($name));

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\\{$version};

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class {$name}Test extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User \$user;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->user = User::factory()->create();
    }

    public function test_example(): void
    {
        \$response = \$this->actingAs(\$this->user)->getJson('{$endpoint}');

        // \$response->assertStatus(200);
    }
}
PHP;

        File::put($path, $stub);
    }

    protected function makeDocs(string $name): void
    {
        $basePath = base_path('docs/api-modules');
        $path = "{$basePath}/{$name}.md";

        if (File::exists($path)) {
            $this->warn('Docs file already exists');

            return;
        }

        File::ensureDirectoryExists($basePath);

        $content = <<<MD
# {$name} API Module

## Overview
Describe the purpose of the {$name} API module.

## API Endpoints
List all endpoints exposed by this module.

## Request Flow
Model → Observer → Event → Listener → Notification

Explain how data flows through this module.

## Events
- {$name}Created

Describe when and why events are triggered.

## Notes
Add edge cases, assumptions, and business rules here.

> [!IMPORTANT]
> This document must be updated once module implementation is complete.
MD;

        File::put($path, $content);
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Postman\PostmanCollectionWriter;
use App\Support\Postman\PostmanExampleResponses;
use App\Support\Postman\PostmanFormRequestParser;
use App\Support\Postman\PostmanModuleMapper;
use App\Support\Postman\PostmanRequestBuilder;
use App\Support\Postman\PostmanRouteCollector;
use Illuminate\Console\Command;

class GeneratePostmanCollection extends Command
{
    protected $signature = 'postman:generate {--output=docs/postman : Output directory for Postman files}';

    protected $description = 'Generate Postman collections and environment from registered API routes';

    public function handle(
        PostmanRouteCollector $collector,
        PostmanModuleMapper $moduleMapper,
        PostmanFormRequestParser $formRequestParser,
        PostmanExampleResponses $exampleResponses,
        PostmanCollectionWriter $writer
    ): int {
        $output = base_path((string) $this->option('output'));

        $builder = new PostmanRequestBuilder($moduleMapper, $formRequestParser, $exampleResponses);
        $records = $collector->collect();

        /** @var array<string, array{realm: string, module: string, folder: string, items: list<array<string, mixed>>}> $groups */
        $groups = [];

        foreach ($records as $record) {
            $mapping = $moduleMapper->map($record);
            $key = $mapping['realm'] . '/' . $mapping['module'];

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'realm' => $mapping['realm'],
                    'module' => $mapping['module'],
                    'folder' => $mapping['folder'],
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = $builder->build($record);
        }

        $writer->writeAll($output, $groups);

        $requestCount = array_sum(array_map(static fn(array $g): int => count($g['items']), $groups));
        $moduleCount = count($groups);

        $this->info("Generated {$requestCount} requests across {$moduleCount} modules in {$output}");

        return self::SUCCESS;
    }
}

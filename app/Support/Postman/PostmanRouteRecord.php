<?php

declare(strict_types=1);

namespace App\Support\Postman;

final readonly class PostmanRouteRecord
{
    /**
     * @param  list<string>  $methods
     * @param  list<string>  $middleware
     * @param  list<string>  $pathParameters
     */
    public function __construct(
        public string $method,
        public string $uri,
        public string $action,
        public ?string $controllerClass,
        public ?string $controllerMethod,
        public array $middleware,
        public array $pathParameters,
        public ?string $formRequestClass
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Support\Postman;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

final class PostmanRouteCollector
{
    public function __construct(private readonly Router $router) {}

    /**
     * @return list<PostmanRouteRecord>
     */
    public function collect(): array
    {
        $records = [];

        foreach ($this->router->getRoutes() as $route) {
            if (!($route instanceof Route)) {
                continue;
            }

            $uri = $route->uri();

            if (!str_starts_with($uri, 'api')) {
                continue;
            }

            $action = $route->getActionName();

            if ($action === 'Closure' || str_contains($action, 'Closure')) {
                if ($uri === 'api') {
                    $records[] = $this->buildRecord($route, 'GET', $uri, null, null, null);
                }

                continue;
            }

            [$controllerClass, $controllerMethod] = $this->parseControllerAction($action);
            $formRequestClass =
                $controllerClass !== null && $controllerMethod !== null
                    ? $this->resolveFormRequestClass($controllerClass, $controllerMethod)
                    : null;

            foreach ($this->httpMethodsForRoute($route) as $method) {
                $records[] = $this->buildRecord(
                    $route,
                    $method,
                    $uri,
                    $controllerClass,
                    $controllerMethod,
                    $formRequestClass
                );
            }
        }

        usort(
            $records,
            static fn(PostmanRouteRecord $a, PostmanRouteRecord $b): int => [$a->uri, $a->method] <=> [
                $b->uri,
                $b->method,
            ]
        );

        return $records;
    }

    /**
     * @return list<string>
     */
    private function httpMethodsForRoute(Route $route): array
    {
        $methods = array_values(array_diff($route->methods(), ['HEAD']));

        if (count($methods) === 0) {
            return [];
        }

        if (count($methods) === 1) {
            return [$methods[0]];
        }

        if (in_array('PATCH', $methods, true) && in_array('PUT', $methods, true)) {
            return ['PATCH'];
        }

        return [reset($methods)];
    }

    private function buildRecord(
        Route $route,
        string $method,
        string $uri,
        ?string $controllerClass,
        ?string $controllerMethod,
        ?string $formRequestClass
    ): PostmanRouteRecord {
        $middleware = collect($route->gatherMiddleware())
            ->map(static fn(string $m): string => class_basename($m))
            ->values()
            ->all();

        $pathParameters = array_map(
            static fn(string $name): string => Str::before($name, ':'),
            array_keys($route->parameterNames())
        );

        return new PostmanRouteRecord(
            method: $method,
            uri: $uri,
            action: $route->getActionName(),
            controllerClass: $controllerClass,
            controllerMethod: $controllerMethod,
            middleware: $middleware,
            pathParameters: $pathParameters,
            formRequestClass: $formRequestClass
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function parseControllerAction(string $action): array
    {
        if (!str_contains($action, '@')) {
            return [null, null];
        }

        [$class, $method] = explode('@', $action, 2);

        return [$class, $method];
    }

    private function resolveFormRequestClass(string $controllerClass, string $method): ?string
    {
        if (!class_exists($controllerClass)) {
            return null;
        }

        try {
            $reflection = new ReflectionMethod($controllerClass, $method);
        } catch (\ReflectionException) {
            return null;
        }

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type === null || $type->isBuiltin()) {
                continue;
            }

            $className = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($className === null || !class_exists($className)) {
                continue;
            }

            if ((new ReflectionClass($className))->isSubclassOf(FormRequest::class)) {
                return $className;
            }
        }

        return null;
    }
}

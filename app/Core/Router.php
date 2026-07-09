<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable|array{0:class-string,1:string}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalize($path);
        $method = strtoupper($method);

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            foreach ($this->routes[$method] ?? [] as $route => $routeHandler) {
                $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route);
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->invoke($routeHandler, $params);
                    return;
                }
            }

            http_response_code(404);
            View::render('errors/404', ['title' => 'Səhifə tapılmadı']);
            return;
        }

        $this->invoke($handler, []);
    }

    /** @param array<string, string> $params */
    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method(...array_values($params));
            return;
        }

        $handler(...array_values($params));
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

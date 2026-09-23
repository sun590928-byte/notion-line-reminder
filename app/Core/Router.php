<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{0:string,1:string,2:callable|array}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['GET', $this->compile($pattern), $handler];
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['POST', $this->compile($pattern), $handler];
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback('/\{([a-z_]+)(\*)?\}/', static function ($m) {
            return '(?P<' . $m[1] . '>' . (isset($m[2]) && $m[2] === '*' ? '.+' : '[^/]+') . ')';
        }, $pattern);
        return '#^' . $regex . '$#u';
    }

    public function dispatch(string $method, string $path): void
    {
        $allowed = false;
        foreach ($this->routes as [$m, $regex, $handler]) {
            if (!preg_match($regex, $path, $match)) {
                continue;
            }
            if ($m !== $method) {
                $allowed = true;
                continue;
            }
            $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            if (is_array($handler)) {
                [$class, $action] = $handler;
                (new $class())->{$action}($params);
            } else {
                $handler($params);
            }
            return;
        }
        abort($allowed ? 405 : 404);
    }
}

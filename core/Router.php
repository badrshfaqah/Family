<?php

namespace Core;

use Core\Support\Request;
use Core\Support\Response;

final class Router
{
    private array $routes = [];
    private string $notFoundHandler;

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
        $this->add('POST', $pattern, $handler);
    }

    public function setNotFound(callable $handler): void
    {
        $this->routes['__404__'] = $handler;
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $regex = $this->compile($pattern);
        $this->routes[$method][] = ['regex' => $regex, 'handler' => $handler, 'pattern' => $pattern];
    }

    private function compile(string $pattern): string
    {
        $pattern = trim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^/' . $regex . '$#u';
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();

        $candidates = $this->routes[$method] ?? [];

        // تُرتَّب المسارات حسب مستوى التحديد (Specificity) لا حسب ترتيب التسجيل،
        // بحيث لا يبتلع مسار عام مثل /{slug} (الصفحات الثابتة) مسارات محددة
        // كـ /calendar أو /news تسجّلها إضافات أخرى بغض النظر عن ترتيب تركيبها.
        usort($candidates, fn($a, $b) => $this->specificity($b['pattern']) <=> $this->specificity($a['pattern']));

        foreach ($candidates as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                $this->call($route['handler'], $params);
                return;
            }
        }

        if (isset($this->routes['__404__'])) {
            http_response_code(404);
            call_user_func($this->routes['__404__']);
            return;
        }

        http_response_code(404);
        echo 'الصفحة غير موجودة.';
    }

    /** يحسب درجة تحديد المسار: الأجزاء الحرفية (غير المتغيرة) أهم من أجزاء {param} */
    private function specificity(string $pattern): int
    {
        $segments = array_filter(explode('/', trim($pattern, '/')), fn($s) => $s !== '');
        $score = count($segments);
        foreach ($segments as $segment) {
            $score += str_starts_with($segment, '{') ? 1 : 100;
        }
        return $score;
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler) && is_string($handler[0])) {
            $class = $handler[0];
            $method = $handler[1];
            $instance = new $class();
            call_user_func_array([$instance, $method], [$params]);
            return;
        }
        call_user_func($handler, $params);
    }
}

<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupMiddlewares = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function group(array $middlewares, callable $callback): void
    {
        $prev = $this->groupMiddlewares;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        $callback($this);
        $this->groupMiddlewares = $prev;
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): void
    {
        $allMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $allMiddlewares,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                // Execute middlewares
                foreach ($route['middlewares'] as $mw) {
                    $middlewareInstance = is_string($mw) ? new $mw() : $mw;
                    $response = $middlewareInstance->handle($request);
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    $res = call_user_func_array($handler, array_merge([$request], $params));
                } elseif (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $res = call_user_func_array([$controller, $action], array_merge([$request], $params));
                } else {
                    return Response::error("Invalid route handler configuration", "ROUTE_ERROR", null, 500);
                }

                if ($res instanceof Response) {
                    return $res;
                }
                if (is_array($res)) {
                    return Response::json($res);
                }
                return Response::html((string)$res);
            }
        }

        if ($request->isAjax()) {
            return Response::error("Endpoint not found", "RESOURCE_NOT_FOUND", null, 404);
        }

        return View::render('errors.404', ['title' => 'Page Not Found', 'path' => $path], 'auth');
    }
}

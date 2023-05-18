<?php

declare(strict_types=1);

namespace PeibinLaravel\Guzzle;

use GuzzleHttp\HandlerStack;
use Illuminate\Container\Container;
use PeibinLaravel\Coroutine\Coroutine;
use PeibinLaravel\Pool\SimplePool\PoolFactory;

class HandlerStackFactory
{
    protected array $option = [
        'min_connections' => 1,
        'max_connections' => 30,
        'wait_timeout'    => 3.0,
        'max_idle_time'   => 60,
    ];

    protected array $middlewares = [
        'retry' => [RetryMiddleware::class, [1, 10]],
    ];

    protected bool $usePoolHandler = false;

    public function __construct()
    {
        $this->usePoolHandler = class_exists(PoolFactory::class);
    }

    public function create(array $option = [], array $middlewares = []): HandlerStack
    {
        $handler = null;
        $option = array_merge($this->option, $option);
        $middlewares = array_merge($this->middlewares, $middlewares);

        if (Coroutine::inCoroutine()) {
            $handler = $this->getHandler($option);
        }

        $stack = HandlerStack::create($handler);

        foreach ($middlewares as $key => $middleware) {
            if (is_array($middleware)) {
                [$class, $arguments] = $middleware;
                $middleware = new $class(...$arguments);
            }

            if ($middleware instanceof MiddlewareInterface) {
                $stack->push($middleware->getMiddleware(), $key);
            }
        }

        return $stack;
    }

    protected function getHandler(array $option)
    {
        if ($this->usePoolHandler) {
            return Container::getInstance()->make(PoolHandler::class, ['option' => $option]);
        }

        return Container::getInstance()->make(CoroutineHandler::class);
    }
}

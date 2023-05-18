<?php

declare(strict_types=1);

namespace PeibinLaravel\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Coroutine\Coroutine;
use Swoole\Runtime;

class ClientFactory
{
    protected bool $runInSwoole = false;

    protected int $nativeCurlHook = 0;

    public function __construct(protected Container $container)
    {
        $this->runInSwoole = extension_loaded('swoole');
        if (defined('SWOOLE_HOOK_NATIVE_CURL')) {
            $this->nativeCurlHook = SWOOLE_HOOK_NATIVE_CURL;
        }
    }

    public function create(array $options = []): Client
    {
        $stack = null;

        if (
            $this->runInSwoole
            && Coroutine::inCoroutine()
            && (Runtime::getHookFlags() & $this->nativeCurlHook) == 0
        ) {
            $stack = HandlerStack::create(new CoroutineHandler());
        }

        $config = array_replace(['handler' => $stack], $options);

        return new Client($config);
    }
}

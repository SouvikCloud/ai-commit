<?php

declare(strict_types=1);

/**
 * This file is part of the guanguans/ai-commit.
 *
 * (c) guanguans <ityaozm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Support;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\VarDumper;

abstract class FoundationSDK
{
    use Tappable;
    use Macroable;
    use Conditionable;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $pendingRequest;

    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->pendingRequest = $this->buildPendingRequest($this->config);
    }

    /**
     * @psalm-suppress UnusedClosureParam
     */
    public function ddRequestData()
    {
        return $this->tapPendingRequest(static function (PendingRequest $pendingRequest): void {
            $pendingRequest->beforeSending(static function (Request $request, array $options): void {
                VarDumper::dump($options['laravel_data']);
                exit(1);
            });
        });
    }

    /**
     * @psalm-suppress UnusedClosureParam
     */
    public function dumpRequestData()
    {
        return $this->tapPendingRequest(static function (PendingRequest $pendingRequest): void {
            $pendingRequest->beforeSending(static function (Request $request, array $options): void {
                VarDumper::dump($options['laravel_data']);
            });
        });
    }

    public function dd()
    {
        return $this->tapPendingRequest(static function (PendingRequest $pendingRequest): void {
            $pendingRequest->dd();
        });
    }

    public function dump()
    {
        return $this->tapPendingRequest(static function (PendingRequest $pendingRequest): void {
            $pendingRequest->dump();
        });
    }

    public function withLogMiddleware(?LoggerInterface $logger = null, ?MessageFormatter $formatter = null, string $logLevel = 'info')
    {
        return $this->tapPendingRequest(static function (PendingRequest $pendingRequest) use ($logLevel, $formatter, $logger): void {
            $logger = $logger ?: Log::channel('daily');
            $formatter = $formatter ?: new MessageFormatter(MessageFormatter::DEBUG);
            $pendingRequest->withMiddleware(Middleware::log($logger, $formatter, $logLevel));
        });
    }

    public function tapPendingRequest(callable $callback)
    {
        $this->pendingRequest = tap($this->pendingRequest, $callback);

        return $this;
    }

    public function clonePendingRequest()
    {
        return clone $this->pendingRequest;
    }

    /**
     * ```php
     * protected function validateConfig(array $config): array
     * {
     *     return validate($config, [
     *         'http_options' => 'array',
     *     ]);
     * }
     * ```.
     *
     * @return array The merged and validated options
     *
     * @throws \Illuminate\Validation\ValidationException laravel validation rules
     */
    abstract protected function validateConfig(array $config): array;

    /**
     * ```php
     * protected function buildPendingRequest(array $config): PendingRequest
     * {
     *     return Http::withOptions($config['http_options'])
     *         ->baseUrl($config['baseUrl'])
     *         ->asJson()
     *         ->withMiddleware($this->buildLoggerMiddleware());
     * }
     * ```.
     */
    abstract protected function buildPendingRequest(array $config): PendingRequest;
}

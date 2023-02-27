<?php

declare(strict_types=1);

/**
 * This file is part of the guanguans/ai-commit.
 *
 * (c) guanguans <ityaozm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

use App\Support\OpenAI;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * @psalm-suppress UnusedClosureParam
 */
beforeEach(function () {
    setup_http_fake();

    $this->openAI = new OpenAI(Arr::only(
        config('ai-commit.generators.openai'),
        ['http_options', 'retry', 'base_url', 'api_key']
    ));
});

/**
 * @psalm-suppress UndefinedPropertyFetch
 */
it('can hydrate data', function () {
    $data = 'data: {"id": "cmpl-6n1mYrlWTmE9184S4pajlIx6JITEu", "object": "text_completion", "created": 1677142942, "choices": [{"text": "", "index": 0, "logprobs": null, "finish_reason": "stop"}], "model": "text-davinci-003"}';
    expect($data)->not->toBeJson()
        ->and(OpenAI::hydrateData($data))->toBeJson();
})->group(__DIR__, __FILE__);

it('can completions', function () {
    $parameters = config('ai-commit.generators.openai.completion_parameters');
    $parameters['prompt'] = 'OK';
    $response = $this->openAI->completions($parameters, function () {});

    expect($response->json('choices.0.text'))->toBeString()->not->toBeEmpty();
    Http::assertSentCount(1);
})->group(__DIR__, __FILE__);

it('will throw RequestException when completions', function () {
    $parameters = config('ai-commit.generators.openai.completion_parameters');
    $parameters['prompt'] = 'Too Many Requests';
    $this->openAI->completions($parameters, function () {});
})->group(__DIR__, __FILE__)->throws(RequestException::class, 'HTTP request returned status code 429');

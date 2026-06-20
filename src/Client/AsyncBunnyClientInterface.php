<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Client;

use GuzzleHttp\Promise\PromiseInterface;

interface AsyncBunnyClientInterface extends BunnyClientInterface
{
    /** @param string|resource $content */
    public function uploadAsync(string $path, mixed $content): PromiseInterface;
}

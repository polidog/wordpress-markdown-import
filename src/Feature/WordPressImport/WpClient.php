<?php

namespace App\Feature\WordPressImport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WpClient implements ClientInterface
{
    private ClientInterface $client;

    /**
     * @param array{0: string, 1: string} $auth
     */
    public function __construct(string $baseUri, private readonly array $auth)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
        ]);
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->client->send($request, $options);
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->client->sendAsync($request, $options);
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        $options = array_merge($options, [
            'auth' => $this->auth,
        ]);

        return $this->client->request($method, $uri, $options);
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        $options = array_merge($options, [
            'auth' => $this->auth,
        ]);

        return $this->client->requestAsync($method, $uri, $options);
    }

    public function getConfig(?string $option = null)
    {
        return $this->client->getConfig($option);
    }
}

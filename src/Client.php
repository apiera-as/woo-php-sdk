<?php

declare(strict_types=1);

namespace Apiera\WooPhpSdk;

use Apiera\WooPhpSdk\Exception\Http\BadRequestException;
use Apiera\WooPhpSdk\Exception\Http\InternalServerErrorException;
use Apiera\WooPhpSdk\Exception\Http\NotFoundException;
use Apiera\WooPhpSdk\Exception\Http\RequestException;
use Apiera\WooPhpSdk\Exception\Http\UnauthorizedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final readonly class Client
{
    private GuzzleClient $client;

    public function __construct(
        private Configuration $config,
    ) {
        $stack = HandlerStack::create();

        // Add OAuth1 middleware
        $oauth = new Oauth1([
            'consumer_key'    => $this->config->getConsumerKey(),
            'consumer_secret' => $this->config->getConsumerSecret(),
        ]);
        $stack->push($oauth);

        $this->client = new GuzzleClient([
            'base_uri' => $this->buildBaseUri(),
            'handler' => $stack,
            'timeout' => $this->config->getTimeout(),
            'headers' => [
                'User-Agent' => $this->config->getUserAgent(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $data): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     *
     * @return array<string, mixed>
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    private function buildBaseUri(): string
    {
        return rtrim($this->config->getBaseUrl(), '/') . '/' . $this->config->getApiVersion()->value;
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);

            return $this->parseResponse($response);
        } catch (ClientException $e) {
            match ($e->getResponse()->getStatusCode()) {
                400 => throw new BadRequestException($e->getRequest(), $e->getResponse()),
                401 => throw new UnauthorizedException($e->getRequest(), $e->getResponse()),
                404 => throw new NotFoundException($e->getRequest(), $e->getResponse()),
                default => throw new RequestException($e->getRequest(), $e->getResponse(), $e->getMessage()),
            };
        } catch (ServerException $e) {
            throw new InternalServerErrorException($e->getRequest(), $e->getResponse());
        } catch (GuzzleException $e) {
            // Handle network issues, timeouts, etc.
            throw new RequestException(
                new Request($method, $endpoint),
                null,
                sprintf('Request failed: %s', $e->getMessage())
            );
        } catch (Throwable $e) {
            // Catch any other unexpected errors
            throw new RequestException(
                new Request($method, $endpoint),
                null,
                sprintf('Unexpected error: %s', $e->getMessage())
            );
        }
    }

    /**
     * @throws InternalServerErrorException
     *
     * @return array<string, mixed>
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InternalServerErrorException(
                new Request('GET', (string) $response->getBody()->getMetadata('uri')),
                $response,
                'Invalid JSON response'
            );
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use Apiera\WooPhpSdk\Client;
use Apiera\WooPhpSdk\Configuration;
use Apiera\WooPhpSdk\Enum\ApiVersion;
use Apiera\WooPhpSdk\Exception\Http\BadRequestException;
use Apiera\WooPhpSdk\Exception\Http\InternalServerErrorException;
use Apiera\WooPhpSdk\Exception\Http\NotFoundException;
use Apiera\WooPhpSdk\Exception\Http\RequestException;
use Apiera\WooPhpSdk\Exception\Http\UnauthorizedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    private Configuration $config;

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testSuccessfulGetRequest(): void
    {
        $expectedResponse = ['id' => 1, 'name' => 'Test Product'];
        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResponse)),
        ]);

        $client = $this->createClientWithMockHandler($mock);
        $response = $client->get('products/1');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testSuccessfulPostRequest(): void
    {
        $requestData = ['name' => 'New Product', 'price' => 99.99];
        $expectedResponse = ['id' => 1] + $requestData;

        $mock = new MockHandler([
            new Response(201, [], json_encode($expectedResponse)),
        ]);

        $client = $this->createClientWithMockHandler($mock);
        $response = $client->post('products', $requestData);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testSuccessfulPutRequest(): void
    {
        $requestData = ['name' => 'Updated Product'];
        $expectedResponse = ['id' => 1] + $requestData;

        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResponse)),
        ]);

        $client = $this->createClientWithMockHandler($mock);
        $response = $client->put('products/1', $requestData);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testSuccessfulDeleteRequest(): void
    {
        $expectedResponse = ['deleted' => true, 'id' => 1];
        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResponse)),
        ]);

        $client = $this->createClientWithMockHandler($mock);
        $response = $client->delete('products/1');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testBadRequestException(): void
    {
        $mock = new MockHandler([
            new ClientException(
                'Bad Request',
                new Request('GET', 'products'),
                new Response(400, [], json_encode([
                    'code' => 'invalid_product',
                    'message' => 'Invalid product data',
                    'data' => ['status' => 400],
                ]))
            ),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(BadRequestException::class);
        $client->get('products');
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testUnauthorizedException(): void
    {
        $mock = new MockHandler([
            new ClientException(
                'Unauthorized',
                new Request('GET', 'products'),
                new Response(401, [], json_encode([
                    'code' => 'unauthorized',
                    'message' => 'Invalid authentication credentials',
                    'data' => ['status' => 401],
                ]))
            ),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(UnauthorizedException::class);
        $client->get('products');
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testNotFoundException(): void
    {
        $mock = new MockHandler([
            new ClientException(
                'Not Found',
                new Request('GET', 'products/999'),
                new Response(404, [], json_encode([
                    'code' => 'not_found',
                    'message' => 'Product not found',
                    'data' => ['status' => 404],
                ]))
            ),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(NotFoundException::class);
        $client->get('products/999');
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testInternalServerErrorException(): void
    {
        $mock = new MockHandler([
            new ServerException(
                'Server Error',
                new Request('GET', 'products'),
                new Response(500, [], json_encode([
                    'code' => 'server_error',
                    'message' => 'Internal server error',
                    'data' => ['status' => 500],
                ]))
            ),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(InternalServerErrorException::class);
        $client->get('products');
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testInvalidJsonResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'invalid json'),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(RequestException::class);
        $client->get('products');
    }

    /**
     * @throws \Apiera\WooPhpSdk\Exception\Http\HttpException
     */
    public function testRequestException(): void
    {
        $mock = new MockHandler([
            new ClientException(
                'Custom Error',
                new Request('GET', 'products'),
                new Response(418, [], json_encode([
                    'code' => 'custom_error',
                    'message' => 'Custom error message',
                    'data' => ['status' => 418],
                ]))
            ),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(RequestException::class);
        $client->get('products');
    }

    protected function setUp(): void
    {
        $this->config = new Configuration(
            baseUrl: 'https://example.com',
            consumerKey: 'test_key',
            consumerSecret: 'test_secret',
            apiVersion: ApiVersion::V3,
            userAgent: 'WooCommerce PHP SDK',
            timeout: 30
        );
    }

    private function createClientWithMockHandler(MockHandler $mock): Client
    {
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        // Use reflection to set the private client property
        $client = new Client($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setValue($client, $guzzle);

        return $client;
    }
}

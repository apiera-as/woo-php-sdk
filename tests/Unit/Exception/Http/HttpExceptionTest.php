<?php

declare(strict_types=1);

namespace Tests\Unit\Exception\Http;

use Apiera\WooPhpSdk\Exception\Http\BadRequestException;
use Apiera\WooPhpSdk\Exception\Http\HttpException;
use Apiera\WooPhpSdk\Exception\Http\InternalServerErrorException;
use Apiera\WooPhpSdk\Exception\Http\NotFoundException;
use Apiera\WooPhpSdk\Exception\Http\RequestException;
use Apiera\WooPhpSdk\Exception\Http\UnauthorizedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(HttpException::class)]
#[CoversClass(BadRequestException::class)]
#[CoversClass(InternalServerErrorException::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(UnauthorizedException::class)]
#[CoversClass(RequestException::class)]
final class HttpExceptionTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExceptionWithoutResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($this->createConfiguredMock(UriInterface::class, [
            '__toString' => 'https://example.com/api',
        ]));
        $request->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);

        $exception = new BadRequestException($request, null, 'Test message');

        $this->assertSame($request, $exception->getRequest());
        $this->assertNull($exception->getResponse());
        $this->assertFalse($exception->hasResponse());
        $this->assertNull($exception->getResponseStatusCode());
        $this->assertSame([], $exception->getResponseHeaders());
        $this->assertSame('GET', $exception->getRequestMethod());
        $this->assertSame('https://example.com/api', $exception->getRequestUri());
        $this->assertSame(['Content-Type' => ['application/json']], $exception->getRequestHeaders());

        $errorMessage = $exception->getErrorMessage();
        $this->assertSame('unknown_error', $errorMessage->getCode());
        $this->assertSame('Test message', $errorMessage->getMessage());
        $this->assertSame(['status' => 0], $errorMessage->getData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExceptionWithValidResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $responseBody = json_encode([
            'code' => 'not_found',
            'message' => 'Resource not found',
            'data' => ['status' => 404],
        ]);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);

        $exception = new NotFoundException($request, $response);

        $this->assertTrue($exception->hasResponse());
        $this->assertSame(404, $exception->getResponseStatusCode());
        $this->assertSame(['Content-Type' => ['application/json']], $exception->getResponseHeaders());

        $errorMessage = $exception->getErrorMessage();
        $this->assertSame('not_found', $errorMessage->getCode());
        $this->assertSame('Resource not found', $errorMessage->getMessage());
        $this->assertSame(['status' => 404], $errorMessage->getData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExceptionWithInvalidResponseBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('invalid json');
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(500);

        $exception = new InternalServerErrorException($request, $response);

        $errorMessage = $exception->getErrorMessage();
        $this->assertSame('invalid_response', $errorMessage->getCode());
        $this->assertSame('Invalid response received from the API', $errorMessage->getMessage());
        $this->assertSame(['status' => 500], $errorMessage->getData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExceptionWithIncompleteResponseData(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        // Response missing required fields
        $responseBody = json_encode(['some' => 'data']);

        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(400);

        $exception = new BadRequestException($request, $response);

        $errorMessage = $exception->getErrorMessage();
        $this->assertSame('invalid_response', $errorMessage->getCode());
        $this->assertSame('Invalid response received from the API', $errorMessage->getMessage());
        $this->assertSame(['status' => 400], $errorMessage->getData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testRequestDataHandling(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $requestBody = json_encode(['test' => 'data']);
        $stream->method('__toString')->willReturn($requestBody);
        $request->method('getBody')->willReturn($stream);

        $exception = new UnauthorizedException($request);

        $this->assertSame(['test' => 'data'], $exception->getRequestData());
        // Test caching
        $this->assertSame(['test' => 'data'], $exception->getRequestData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testEmptyRequestBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('');
        $request->method('getBody')->willReturn($stream);

        $exception = new UnauthorizedException($request);

        $this->assertNull($exception->getRequestData());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInvalidRequestBodyJson(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('invalid json');
        $request->method('getBody')->willReturn($stream);

        $exception = new UnauthorizedException($request);

        $this->assertNull($exception->getRequestData());
    }
}

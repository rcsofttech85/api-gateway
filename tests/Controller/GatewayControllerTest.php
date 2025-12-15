<?php

namespace App\Tests\Controller;

use App\Controller\GatewayController;
use App\Service\ServiceDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GatewayControllerTest extends TestCase
{
    private function createRequest(string $uri, string $method = 'GET'): Request
    {
        $request = Request::create($uri, $method);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('Authorization', 'Bearer test-token'); // in case controller uses auth header
        return $request;
    }

    private function createLimiter(): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'api', 'policy' => 'fixed_window', 'limit' => 10, 'interval' => '1 minute'],
            new InMemoryStorage()
        );
    }

    public function testHandleForwardsRequestToService(): void
    {
        // Mocks
        $serviceDiscovery = $this->createStub(ServiceDiscovery::class);
        $serviceDiscovery->method('getServiceUrl')->with('users')->willReturn('http://10.0.0.1:8080');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"status":"ok"}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);

        // Ensure request() is called with exact expected arguments
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://10.0.0.1:8080/1', $this->anything())
            ->willReturn($response);

        $limiterFactory = $this->createLimiter();

        $controller = new GatewayController($httpClient, $serviceDiscovery, $limiterFactory, $logger);

        $request = $this->createRequest('/api/users/1', 'GET');
        $gatewayResponse = $controller->handle($request, 'users', '1');

        $this->assertEquals(200, $gatewayResponse->getStatusCode());
        $this->assertEquals('{"status":"ok"}', $gatewayResponse->getContent());
    }

    public function testHandleThrowsNotFoundIfServiceUnknown(): void
    {
        $serviceDiscovery = $this->createStub(ServiceDiscovery::class);
        $serviceDiscovery->method('getServiceUrl')->willReturn(null);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $logger = $this->createStub(LoggerInterface::class);
        $limiterFactory = $this->createLimiter();

        $controller = new GatewayController($httpClient, $serviceDiscovery, $limiterFactory, $logger);

        $this->expectException(NotFoundHttpException::class);

        $request = $this->createRequest('/api/unknown/1', 'GET');
        $controller->handle($request, 'unknown', '1');
    }
}

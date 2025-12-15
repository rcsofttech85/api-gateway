<?php

namespace App\Tests\Service;

use App\Service\ServiceDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ServiceDiscoveryTest extends TestCase
{
    public function testGetServiceUrlReturnsUrlForHealthyService(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            [
                'Service' => ['Address' => '10.0.0.1', 'Port' => 8080],
                'Node' => ['Address' => '127.0.0.1'],
            ]
        ]);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $consulUrl =  'http://consul:8500';

        $serviceDiscovery = new ServiceDiscovery($client, $consulUrl);
        $url = $serviceDiscovery->getServiceUrl('my-service');

        $this->assertEquals('http://10.0.0.1:8080', $url);
    }

    public function testGetServiceUrlReturnsNullForEmptyService(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $consulUrl =  'http://consul:8500';
        $serviceDiscovery = new ServiceDiscovery($client, $consulUrl);
        $url = $serviceDiscovery->getServiceUrl('unknown-service');

        $this->assertNull($url);
    }
}

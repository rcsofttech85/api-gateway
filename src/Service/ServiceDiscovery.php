<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ServiceDiscovery
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'CONSUL_URL')]
        private string $consulUrl
    ) {
    }

    public function getServiceUrl(string $serviceName): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                sprintf('%s/v1/health/service/%s?passing=1', $this->consulUrl, $serviceName)
            );

            $instances = $response->toArray();

            if (empty($instances)) {
                return null;
            }

            // Simple round-robin or random selection could be implemented here.
            // For now, pick a random one.
            $instance = $instances[array_rand($instances)];

            $address = $instance['Service']['Address'];
            $port = $instance['Service']['Port'];

            // If address is empty, use node address
            if (empty($address)) {
                $address = $instance['Node']['Address'];
            }

            return sprintf('http://%s:%d', $address, $port);
        } catch (\Throwable $e) {
            // Log error
            return null;
        }
    }
}

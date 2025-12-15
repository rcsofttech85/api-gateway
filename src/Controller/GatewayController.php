<?php

namespace App\Controller;

use App\Service\ServiceDiscovery;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


final class GatewayController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ServiceDiscovery $serviceDiscovery,
        private readonly RateLimiterFactory $apiLimiter,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(
        '/api/{service}/{path}',
        name: 'api_gateway',
        requirements: ['path' => '.+'],
        methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
    )]
     public function handle(Request $request, string $service, string $path): Response
    {
        //  Rate limiting
        $limiter = $this->apiLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        //  Service discovery
        $baseUrl = $this->serviceDiscovery->getServiceUrl($service);
        if (!$baseUrl) {
            throw new NotFoundHttpException(sprintf('Service "%s" not found.', $service));
        }

        //  Build target URL
        $targetUrl = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        if ($request->getQueryString()) {
            $targetUrl .= '?' . $request->getQueryString();
        }

        //  Build forward headers
        $headers = $this->buildForwardHeaders($request);

        try {
            $response = $this->httpClient->request(
                $request->getMethod(),
                $targetUrl,
                [
                    'headers'       => $headers,
                    'body'          => $request->getContent(),
                    'timeout'       => 5,
                    'max_redirects' => 0,
                ]
            );

            return new Response(
                $response->getContent(false),
                $response->getStatusCode(),
                $response->getHeaders(false)
            );
        } catch (\Throwable $e) {
            $this->logger->error('Gateway forwarding failed', [
                'service' => $service,
                'url'     => $targetUrl,
                'error'   => $e->getMessage(),
            ]);

            return new Response('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }



    /**
     * Allow-listed headers + gateway identity 
     */
    private function buildForwardHeaders(Request $request): array
    {
        $headers = [
            'Accept'       => $request->headers->get('Accept'),
            'Content-Type' => $request->headers->get('Content-Type'),
            'User-Agent'   => $request->headers->get('User-Agent'),
            'X-Gateway'    => 'api-gateway',
            'X-Client-IP'  => $request->getClientIp(),
        ];

       

        return $headers;
    }

}


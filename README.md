# Lightweight API Gateway

An API Gateway built with Symfony 7 for microservices architectures. It handles authentication, rate limiting, service discovery, and circuit breaking, ensuring secure, resilient, and reliable communication between clients and backend services

##  Features

- **JWT Authentication**: Secure API access using `lexik/jwt-authentication-bundle` 
- **Rate Limiting**: Protects your services from abuse using Symfony's Rate Limiter and Redis.
- **Service Discovery**: Dynamic service resolution using HashiCorp Consul.
- **Circuit Breaker**: Fault tolerance implementation using `ackintosh/ganesha` to prevent cascading failures.
- **Distributed Caching**: High-performance caching with Redis.
- **Request/Response Logging**: Comprehensive logging for monitoring and debugging.


##  Tech Stack

- **Language**: PHP 8.3+
- **Framework**: Symfony 7.2
- **Database/Cache**: Redis
- **Service Registry**: Consul
- **Server**: Nginx

##  Prerequisites

Ensure you have the following installed on your system:

- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)
- [PHP](https://www.php.net/) 8.3 or higher (for local development)
- [Composer](https://getcomposer.org/)

##  Installation & Setup

1.  **Clone the repository**
    ```bash
    git clone <repository-url>
    cd api-gateway
    ```

2.  **Start the infrastructure**
    Use Docker Compose to spin up the API Gateway, Redis, Consul, and Nginx.
    ```bash
    docker-compose up -d --build
    ```

3.  **Install PHP dependencies**
    ```bash
    composer install
    ```

4.  **Generate JWT Keys**
    The gateway requires SSL keys for signing JWT tokens.
    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```

##  Configuration

### Environment Variables
Copy the example environment file and configure it as needed:
```bash
cp .env .env.local
```
Key variables to check:
- `REDIS_URL`: Connection string for Redis (default: `redis://redis:6379`).
- `CONSUL_URL`: URL for the Consul agent.

### Service Discovery
Services are registered and discovered via Consul. Ensure your microservices register themselves with Consul upon startup. The gateway queries Consul to route requests to the appropriate service instances.

### Rate Limiting
Rate limiting policies are defined in `config/packages/rate_limiter.yaml`. You can customize the limits per IP or authenticated user.

##  Running Tests

To run the test suite, execute:
```bash
php bin/phpunit
```

##  Usage

### Obtaining a Token
Send a POST request to the login endpoint (configure your security firewall paths accordingly, typically `/api/login_check`):

```bash
curl -X POST https://localhost/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username":"your_user", "password":"your_password"}'
```

### Making Authenticated Requests
Include the obtained token in the `Authorization` header:

```bash
curl -X GET https://localhost/api/resource \
  -H "Authorization: Bearer <your_token>"
```

##  Docker Services

- **php**: The Symfony application container.
- **nginx**: Web server acting as the entry point.
- **consul**: Service registry and key-value store.
- **redis**: In-memory data structure store for caching and rate limiting.



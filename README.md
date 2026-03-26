# AsCachedAttribute

Symfony attribute-based HTTP response caching. Mark controller actions with `#[AsCachedResponse]` to automatically cache responses.

## Installation

```sh
composer require devouted/symfony-as-cached-attribute
```

## Usage

### Service configuration

Add to your `services.yaml`:

```yaml
services:
  Devouted\AsCachedAttribute\Listener\CachedResponseListener:
    arguments:
      $cache: '@cache.app'
      $logger: '@monolog.logger'          # optional, set to ~ to disable
      $modifyResponseCacheHeaders: true   # optional, set to false to skip Cache-Control headers
    tags:
      - { name: kernel.event_listener, event: kernel.controller_arguments, priority: -1 }
      - { name: kernel.event_listener, event: kernel.response, priority: 1000 }
```

### Basic usage

```php
#[AsCachedResponse]
public function __invoke(): Response
{
    return new Response('cached content');
}
```

or on a regular controller action:

```php
#[AsCachedResponse]
public function someAction(Request $request): Response
{
    return new Response('cached content');
}
```

### Cache key parametrization

Use `#[AsCachedRequestParameter]` on method parameters or DTO properties to build unique cache keys per request:

On method parameters:

```php
#[AsCachedResponse]
public function show(
    #[AsCachedRequestParameter] int $id,
    #[AsCachedRequestParameter] string $slug
): Response {
    return new Response("id={$id}, slug={$slug}");
}
```

On DTO properties (e.g. with `#[MapQueryString]` or `#[MapRequestPayload]`):

```php
#[AsCachedResponse]
public function list(#[MapQueryString] ProductFilter $filter): Response
{
    return new Response("filtered list");
}

class ProductFilter
{
    public function __construct(
        #[AsCachedRequestParameter]
        public int $categoryId,
        #[AsCachedRequestParameter]
        public ?string $sort = null,
    ) {}
}
```

### AsCachedResponse options

```php
#[AsCachedResponse(
    ttl: 3600,           // cache lifetime in seconds (default: 3600)
    etag: 'my-etag',     // optional ETag header value
    expires: 'tomorrow', // optional Expires header (any strtotime-compatible string)
    isPublic: true,      // public (shared) or private cache (default: true)
)]
```

### Response headers

Cached responses include an `X-Cache` header:
- `Miss` — response was generated and stored in cache
- `Hit` — response was served from cache

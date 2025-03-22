# AsCachedAttribute

## Installation

```sh
composer require devouted/symfony-as-cached-attribute
```

## Usage

add to your services.yaml

```yaml
services:
  Devouted\AsCachedAttribute\Listener\CachedResponseListener:
    arguments: [ '@cache.app' ]
    tags:
      - { name: kernel.event_listener, event: kernel.controller_arguments }
      - { name: kernel.event_listener, event: kernel.response }
```
and in action that needs to be cached:

```php
    #[AsCachedResponse]
    public function __invoke(ServiceAppQuery $query): ServiceAppPresenter
    {
        
    }
```

or

```php
    #[AsCachedResponse]
    public function someAction(Request $request): Response
    {
        return Response();
    }
```

Extend your cache to be parametrized by request params, just add #[AsCachedRequestParameter]:

```php
    #[AsCachedResponse]
    public function someAction(#[MapRequestPayload] SomeDTO $someDTO): Response
    {
        return Response();
    }

    class SomeDTO{
     public function __construct() {
        #[AsCachedRequestParameter]
        public int $someId,
        public string $someString
     }
    }
```

the #[AsCachedRequestParameter] is used to mark what should be used to build cache key for uniqueness for given request 

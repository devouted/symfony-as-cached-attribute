<?php

namespace Devouted\AsCachedAttribute\Listener;


use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Cache\CacheInterface;

class CachedResponseListener
{
    const CACHE_HEADER_STATE_NAME = 'X-Cache';
    const CACHE_HEADER_MISS = 'Miss';
    const CACHE_HEADER_HIT = 'Hit';

    public function __construct(private readonly CacheInterface   $cache,
                                private readonly ?LoggerInterface $logger,
                                private bool                      $modifyResponseCacheHeaders = true)
    {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();

        if (is_string($controller) || is_object($controller) || is_array($controller)) {

            $reflectionMethod = new ReflectionMethod(...$this->resolveCallableForReflection($controller));
            $attributes = $reflectionMethod->getAttributes(AsCachedResponse::class);
            if (empty($attributes)) {
                return;
            }
            /** @var AsCachedResponse $cacheAttribute */
            $cacheAttribute = $attributes[0]->newInstance();

            $event->getRequest()->attributes->set('_cache', $cacheAttribute);

            $request = $event->getRequest();
            $cacheKey = $this->generateCacheKey($request, $event, $cacheAttribute->cacheKeyParams);

            $request->attributes->set('_cache_key', $cacheKey);
            $state = "Miss";
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $expires = $cacheItem->getMetadata()['expiry'] ?? null;
                $cachedResponse = $cacheItem->get();
                if ($cachedResponse instanceof Response) {
                    $event->setController(fn() => $cachedResponse);
                    $event->stopPropagation();
                    if ($this->logger) {
                        $expires = $expires - time();
                    }
                    $state = "Hit, expires in: " . $expires . " seconds";
                } else {
                    $this->cache->deleteItem($cacheKey);
                }
            }
            $this->log("Cache " . $state);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        $cacheAttribute = $request->attributes->get('_cache');
        if (!$cacheAttribute instanceof AsCachedResponse) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() >= 400 || $response->headers->get(self::CACHE_HEADER_STATE_NAME) === self::CACHE_HEADER_HIT) {
            return;
        }
        if ($this->modifyResponseCacheHeaders) {
            $response->setCache([
                'max_age'  => $cacheAttribute->ttl,
                's_maxage' => $cacheAttribute->ttl,
                'public'   => $cacheAttribute->isPublic,
            ]);

            if ($cacheAttribute->etag) {
                $response->setEtag($cacheAttribute->etag);
            }

            if ($cacheAttribute->expires) {
                $response->setExpires(new \DateTime($cacheAttribute->expires));
            }
        }

        $cacheKey = $request->attributes->get('_cache_key');

        $response->headers->set(self::CACHE_HEADER_STATE_NAME, self::CACHE_HEADER_HIT);

        $this->cache->get($cacheKey, function ($item) use ($response, $cacheAttribute) {
            $item->expiresAfter($cacheAttribute->ttl);

            return $response;
        });

        $response->headers->set(self::CACHE_HEADER_STATE_NAME, self::CACHE_HEADER_MISS);

        $event->setResponse($response);
    }

    private function generateCacheKey(Request $request, ControllerArgumentsEvent $event, array $cacheKeyParams = []): string
    {
        $methodReflection = $this->resolveCallableForReflection($event->getController());
        foreach ($event->getNamedArguments() as $paramName => $arg) {
            //if param is type of DTO where we can choose parameters that need to be used for cache key
            if (is_object($arg)) {
                $reflectionClass = new \ReflectionClass($arg);
                foreach ($reflectionClass->getProperties() as $property) {
                    $attributes = $property->getAttributes(AsCachedRequestParameter::class);
                    if (!empty($attributes)) {
                        $property->setAccessible(true);
                        $name = $property->getName();
                        $cacheKeyParams[$name] = $property->getValue($arg);
                    }
                }
            }
        }
        foreach ($event->getNamedArguments() as $paramName => $arg) {
            $attributes = (new \ReflectionParameter($methodReflection, $paramName))->getAttributes(AsCachedRequestParameter::class);
            if (!empty($attributes)) {
                $cacheKeyParams[$paramName] = $arg;
            }
        }
        $this->log("Cache key parameters: " . json_encode($cacheKeyParams));
        return 'cached_response_' . md5($request->getPathInfo() . serialize($cacheKeyParams));
    }

    private function resolveCallableForReflection(array|string|object $controller): array
    {
        if (is_array($controller)) {
            return [$controller[0], $controller[1]];
        }

        return [$controller, "__invoke"];
    }

    private function log(string $msg): void
    {
        $this->logger?->info(sprintf('[AsCachedResponse] %s', $msg));
    }
}

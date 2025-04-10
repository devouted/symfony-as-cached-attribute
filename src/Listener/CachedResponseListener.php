<?php

namespace Devouted\AsCachedAttribute\Listener;


use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Tests\Integration\Controller\CachedQueryDTO;

class CachedResponseListener
{
    const CACHE_HEADER_STATE_NAME = 'X-Cache';
    const CACHE_HEADER_MISS = 'Miss';
    const CACHE_HEADER_HIT = 'Hit';

    public function __construct(private CacheInterface $cache)
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

            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $cachedResponse = $cacheItem->get();
                if ($cachedResponse instanceof Response) {
                    $cachedResponse->headers->set(self::CACHE_HEADER_STATE_NAME, self::CACHE_HEADER_HIT);
                    $event->setController(fn() => $cachedResponse);
                } else {
                    $this->cache->deleteItem($cacheKey);
                }
            }
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

        $response->setCache([
            'max_age' => $cacheAttribute->ttl,
            's_maxage' => $cacheAttribute->ttl,
            'public' => $cacheAttribute->isPublic,
        ]);

        if ($cacheAttribute->etag) {
            $response->setEtag($cacheAttribute->etag);
        }

        if ($cacheAttribute->expires) {
            $response->setExpires(new \DateTime($cacheAttribute->expires));
        }

        $cacheKey = $request->attributes->get('_cache_key');

        $response->headers->set(self::CACHE_HEADER_STATE_NAME, self::CACHE_HEADER_MISS);

        $this->cache->get($cacheKey, function ($item) use ($response, $cacheAttribute) {
            $item->expiresAfter($cacheAttribute->ttl);

            return $response;
        });

        $event->setResponse($response);
    }

    private function generateCacheKey(Request $request, ControllerArgumentsEvent $event, array $cacheKeyParams = []): string
    {
        $methodReflection = $this->resolveCallableForReflection($event->getController());
        foreach ($event->getArguments() as $paramName => $arg) {
            //if param is type of DTO where we can choose parameters that need to be used for cache key
            if (is_object($arg)) {
                $reflectionClass = new \ReflectionClass($arg);
                $constructor = $reflectionClass->getConstructor();
                if ($constructor) {
                    foreach ($constructor->getParameters() as $param) {
                        $attributes = $param->getAttributes(AsCachedRequestParameter::class);
                        if (!empty($attributes)) {
                            $name = $param->getName();
                            $cacheKeyParams[$name] = $arg->{$name};
                        }
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
        return 'cached_response_' . md5($request->getPathInfo() . serialize($cacheKeyParams));
    }

    private function resolveCallableForReflection(array|string|object $controller): array
    {
        if (is_array($controller)) {
            return [$controller[0], $controller[1]];
        }

        return [$controller, "__invoke"];
    }
}

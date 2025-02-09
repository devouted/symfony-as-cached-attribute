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

class CachedResponseListener
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();

        if (is_string($controller) || is_object($controller)) {
            $reflectionMethod = new ReflectionMethod($controller, "__invoke");
            $attributes = $reflectionMethod->getAttributes(AsCachedResponse::class);
            if (empty($attributes)) {
                return;
            }
            /** @var AsCachedResponse $cacheAttribute */
            $cacheAttribute = $attributes[0]->newInstance();
            $event->getRequest()->attributes->set('_cache', $cacheAttribute);

            $request = $event->getRequest();
            $cacheKey = $this->generateCacheKey($request, $event);
            $request->attributes->set('_cache_key', $cacheKey);

            $cachedResponse = $this->cache->get($cacheKey, fn() => null);
            if ($cachedResponse instanceof Response) {
                //Jeśli znaleziono w cache, zwracamy gotową odpowiedź i pomijamy kontroler
                $event->setController(fn() => $cachedResponse);
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

        if ($response->getStatusCode() >= 400) {
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

        $this->cache->get($cacheKey, function ($item) use ($response, $cacheAttribute) {
            $item->expiresAfter($cacheAttribute->ttl);
            return $response;
        });

        $event->setResponse($response);
    }

    private function generateCacheKey(Request $request, ControllerArgumentsEvent $event): string
    {
        $cachedParams = [];
        foreach ($event->getNamedArguments() as $paramName => $arg) {
            if (is_object($arg)) {
                $reflectionClass = new \ReflectionClass($arg);
                foreach ($reflectionClass->getProperties() as $property) {
                    $attributes = $property->getAttributes(AsCachedRequestParameter::class);
                    if (!empty($attributes)) {
                        $cachedParams[$property->getName()] = $property->getValue($arg);
                    }
                }
            } else {
                $attributes = (new \ReflectionParameter([$event->getController(), "__invoke"], $paramName))->getAttributes(AsCachedRequestParameter::class);
                if (!empty($attributes)) {
                    $cachedParams[$paramName] = $arg;
                }
            }
        }
        return 'cached_response_' . md5($request->getPathInfo() . serialize($cachedParams));
    }
}

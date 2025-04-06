<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;

class CachedController
{
    #[Route('/cached-endpoint', name: 'cached_endpoint')]
    #[AsCachedResponse]
    public function cached(): Response
    {
        return new Response('expected cached response');
    }

    #[Route('/cached-with-param/{id}', name: 'cached_with_param')]
    #[AsCachedResponse]
    public function cachedWithParam(
        #[AsCachedRequestParameter] int $id
    ): Response
    {
        return new Response("cached with param id={$id}");
    }

    #[Route('/cached-multi-param/{id}/{slug}', name: 'cached_multi_param')]
    #[AsCachedResponse]
    public function cachedWithMultipleParams(
        #[AsCachedRequestParameter] int    $id,
        #[AsCachedRequestParameter] string $slug
    ): Response
    {
        return new Response("cached with id={$id}, slug={$slug}");
    }

    #[Route('/cached-with-query', name: 'cached_with_query')]
    #[AsCachedResponse]
    public function cachedWithQueryParam(
        #[AsCachedRequestParameter]
        #[MapQueryParameter]
        string $q
    ): Response
    {
        return new Response("cached with query q={$q}");
    }

    #[Route('/cached-combined', name: 'cached_combined')]
    #[AsCachedResponse]
    public function cachedWithDto(
        #[MapQueryString] CachedQueryDTO $query
    ): Response
    {
        return new Response("cached with id={$query->id}, filter={$query->filter}");
    }
}

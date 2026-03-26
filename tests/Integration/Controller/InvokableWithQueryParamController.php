<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class InvokableWithQueryParamController
{
    #[Route('/inv-with-query', name: 'inv_with_query')]
    #[AsCachedResponse]
    public function __invoke(
        #[AsCachedRequestParameter]
        #[MapQueryParameter]
        string $q
    ): Response
    {
        return new Response("invokable q={$q}");
    }
}

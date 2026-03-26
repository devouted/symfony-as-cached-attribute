<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;

class InvokableWithDtoController
{
    #[Route('/inv-combined', name: 'inv_combined')]
    #[AsCachedResponse]
    public function __invoke(
        #[MapQueryString] CachedQueryDTO $query
    ): Response
    {
        return new Response("invokable id={$query->id}, filter={$query->filter}");
    }
}

<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvokableEndpointController
{
    #[Route('/inv-endpoint', name: 'inv_endpoint')]
    #[AsCachedResponse]
    public function __invoke(): Response
    {
        return new Response('invokable cached response');
    }
}

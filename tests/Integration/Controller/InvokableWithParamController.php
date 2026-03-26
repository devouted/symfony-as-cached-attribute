<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvokableWithParamController
{
    #[Route('/inv-with-param/{id}', name: 'inv_with_param')]
    #[AsCachedResponse]
    public function __invoke(
        #[AsCachedRequestParameter] int $id
    ): Response
    {
        return new Response("invokable param id={$id}");
    }
}

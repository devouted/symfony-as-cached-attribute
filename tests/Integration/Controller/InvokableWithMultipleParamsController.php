<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;
use Devouted\AsCachedAttribute\Attribute\AsCachedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvokableWithMultipleParamsController
{
    #[Route('/inv-multi-param/{id}/{slug}', name: 'inv_multi_param')]
    #[AsCachedResponse]
    public function __invoke(
        #[AsCachedRequestParameter] int    $id,
        #[AsCachedRequestParameter] string $slug
    ): Response
    {
        return new Response("invokable id={$id}, slug={$slug}");
    }
}

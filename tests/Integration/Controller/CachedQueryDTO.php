<?php

namespace Tests\Integration\Controller;

use Devouted\AsCachedAttribute\Attribute\AsCachedRequestParameter;

class CachedQueryDTO
{
    public function __construct(
        #[AsCachedRequestParameter]
        public int     $id,
        #[AsCachedRequestParameter]
        public ?string $filter = null,
    )
    {
    }
}
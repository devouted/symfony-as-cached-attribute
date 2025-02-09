<?php

namespace Devouted\Symfony\AsCachedAttribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AsCachedResponse
{
    public function __construct(
        public int     $ttl = 3600,
        public ?string $etag = null,
        public ?string $expires = null,
        public bool    $isPublic = true
    )
    {
    }
}

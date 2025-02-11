<?php

namespace Devouted\AsCachedAttribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AsCachedResponse
{
    /**
     * Attribute defining caching behavior for a controller response.
     *
     * @param int         $ttl             Time-to-live for cache (in seconds, default: 3600)
     * @param string|null $etag            ETag value for the response (optional)
     * @param string|null $expires         Expiration date for the cache (e.g., 'tomorrow')
     * @param bool        $isPublic        Whether the cache is public (shared) or private, public will tell browser to get reponse from local cache (default: public)
     * @param array       $cacheKeyParams  List of request parameters to include in the cache key generation
     */
    public function __construct(
        public int     $ttl = 3600,
        public ?string $etag = null,
        public ?string $expires = null,
        public bool    $isPublic = true,
        public array   $cacheKeyParams = []
    ) {}
}

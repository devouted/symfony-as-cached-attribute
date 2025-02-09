<?php

namespace Devouted\Symfony\AsCachedAttribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AsCachedRequestParameter
{
}

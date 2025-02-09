<?php

namespace Devouted\AsCachedAttribute\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AsCachedRequestParameter
{
}

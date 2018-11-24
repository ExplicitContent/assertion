<?php

namespace ExplicitContent\Boost\Behaviors;

/**
 * It could be useful to drop duplicates. array_unique(), for instance, doesn't work with objects.
 */
interface OffersUniqueHash
{
    public function toHash(): string;
}

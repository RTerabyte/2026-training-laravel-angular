<?php

namespace App\Family\Domain\Exception;

final class FamilyNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Family with id "%s" not found.', $id));
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Exceptions;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Exception;
use Illuminate\Database\Eloquent\Model;

final class FilamentConverseException extends Exception
{
    public static function validateConversableUser(Model $user): void
    {
        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            throw new self(
                'The user model [' . $user::class . "] must use the [Dvarilek\FilamentConverse\Models\Concerns\Conversable] trait.",
            );
        }
    }
}

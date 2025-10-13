<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Exceptions;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class FilamentConverseException extends Exception
{
    public static function validateConversableUser(Model $user): void
    {
        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            throw new stati(
                "The user model [" . $user::class . "] must use the [Dvarilek\FilamentConverse\Models\Concerns\Conversable] trait.",
            );
        }
    }
}

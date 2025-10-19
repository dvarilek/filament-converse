<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Exceptions;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class FilamentConverseException extends Exception
{
    /**
     * @param  class-string<Model & Authenticatable>|Model  $user
     */
    public static function validateConversableUser(string | Model $user): void
    {
        if (! is_string($user)) {
            $user = get_class($user);
        }

        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            throw new self(
                'The user model [' . $user . "] must use the [Dvarilek\FilamentConverse\Models\Concerns\Conversable] trait.",
            );
        }
    }
}

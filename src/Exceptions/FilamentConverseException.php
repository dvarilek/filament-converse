<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Exceptions;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Exception;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class FilamentConverseException extends Exception
{
    /**
     * @param  class-string<Model & Authenticatable>|Model  $user
     */
    public static function throwInvalidConversableUserException(string | Model $user): never
    {
        if (! is_string($user)) {
            $user = get_class($user);
        }

        throw new self(
            "The user model [{$user}] must use the [" . Conversable::class . '] trait.',
        );
    }

    /**
     * @param  class-string<Component>  $componentClass
     * @param  class-string<Component>  $parentComponentClass
     */
    public static function throwInvalidParentComponentException(string $componentClass, string $parentComponentClass): never
    {
        throw new self(
            "Component [{$componentClass}] must be nested within [" . ConversationSchema::class . "], but found [{$parentComponentClass}] instead."
        );
    }
}

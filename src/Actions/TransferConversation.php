<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class TransferConversation
{
    public function handle(Conversation $conversation, Authenticatable & Model $newOwner): bool
    {
        if (! in_array(Conversable::class, class_uses_recursive($newOwner))) {
            FilamentConverseException::throwInvalidConversableUserException($newOwner);
        }

        return DB::transaction(static function () use ($conversation, $newOwner): bool {
            $newOwnerParticipation = $conversation
                ->participations()
                ->active()
                ->firstWhere('participant_id', $newOwner->getKey());

            if (! $newOwnerParticipation) {
                throw new Exception("The user [$newOwner] does not participate in the conversation.");
            }

            return $conversation->owner()->associate($newOwnerParticipation)->save();
        });
    }
}

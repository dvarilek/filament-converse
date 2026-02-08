<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveConversation
{
    public function handle(Conversation $conversation, Authenticatable & Model $participant): bool
    {
        if (! in_array(Conversable::class, class_uses_recursive($participant))) {
            FilamentConverseException::throwInvalidConversableUserException($participant);
        }

        return DB::transaction(static function () use ($conversation, $participant): bool {
            /* @var ConversationParticipation $participation */
            $participation = $conversation
                ->participations()
                ->firstWhere('participant_id', $participant->getKey());

            if (! $participation) {
                throw new Exception("The user [$participant] does not participante in the conversation.");
            }

            if ($conversation->owner_id === $participation->getKey()) {
                throw new Exception("The user [$participant] cannot leave a conversation he owns.");
            }

            return $participation->update([
                'present_until' => now()
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;

class UpdateConversation
{
    /**
     * @param  Collection<int, Model&Authenticatable>|(Model&Authenticatable)  $participants
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Conversation $conversation, (Authenticatable & Model) | Collection $participants, array $attributes = [], (Authenticatable & Model) | null $creator = null): Conversation
    {
        if (! $participants instanceof Collection) {
            $participants = collect([$participants]);
        }

        return DB::transaction(function () use ($conversation, $participants, $attributes, $creator): Conversation {
            $conversation->update([
                'name' => $attributes['name'] ?? null,
                'description' => $attributes['description'] ?? null,
                'image' => $attributes['image'] ?? null,
            ]);

            $conversationKey = $conversation->getKey();

            if ($creator) {
                if (! in_array(Conversable::class, class_uses_recursive($creator))) {
                    FilamentConverseException::throwInvalidConversableUserException($creator);
                }

                $oldCreatorParticipation = $conversation->creator;

                /* @var ConversationParticipation $newCreatorParticipation */
                $newCreatorParticipation = $creator->conversationParticipations()->create([
                    'conversation_id' => $conversationKey,
                ]);

                $conversation->creator()->associate($newCreatorParticipation)->save();

                if ($oldCreatorParticipation !== null) {
                    $oldCreatorParticipation->delete();
                }
            }

            $creatorKey = $conversation->creator->participant_id;

            $conversation
                ->participations()
                ->whereNotIn('participant_id', [
                    ...$participants->map->getKey(),
                    $creatorKey
                ])
                ->delete();

            $existingParticipantIds = $conversation->participations()->pluck('participant_id');

            foreach ($participants as $participant) {
                if ($existingParticipantIds->contains($participant->getKey())) {
                    continue;
                }

                if (! in_array(Conversable::class, class_uses_recursive($participant))) {
                    FilamentConverseException::throwInvalidConversableUserException($participant);
                }

                $participant->conversationParticipations()->create([
                    'conversation_id' => $conversationKey,
                ]);
            }

            return $conversation;
        });
    }
}

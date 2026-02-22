<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateConversation
{
    /**
     * @param  Collection<int, Model&Authenticatable>|list<Model&Authenticatable>|(Model&Authenticatable)  $participants
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Conversation $conversation, (Authenticatable & Model) | array | Collection $participants, array $attributes = []): Conversation
    {
        if (! $participants instanceof Collection) {
            $participants = collect(is_array($participants) ? $participants : [$participants]);
        }

        return DB::transaction(function () use ($conversation, $participants, $attributes): Conversation {
            $conversation->update([
                'name' => $attributes['name'] ?? null,
                'description' => $attributes['description'] ?? null,
                'image' => $attributes['image'] ?? null,
            ]);

            $conversationOwner = $conversation->owner;

            if ($participants->doesntContain(fn (Authenticatable & Model $participant) => $participant->getKey() === $conversationOwner->participant_id)) {
                $participants = $participants->push($conversationOwner->participant);
            }

            $participantIds = $participants->map->getKey();

            $conversation
                ->participations()
                ->active()
                ->whereNotIn('participant_id', $participantIds)
                ->deactivateMany();

            $conversation
                ->participations()
                ->inactive()
                ->whereIn('participant_id', $participantIds)
                ->activateMany();

            $existingParticipantIds = $conversation
                ->fresh()
                ->participations()
                ->active()
                ->pluck('participant_id');

            $conversationKey = $conversation->getKey();

            foreach ($participants as $participant) {
                if ($existingParticipantIds->contains($participant->getKey())) {
                    continue;
                }

                if (! in_array(Conversable::class, class_uses_recursive($participant))) {
                    FilamentConverseException::throwInvalidConversableUserException($participant);
                }

                $participant->conversationParticipations()->create([
                    'conversation_id' => $conversationKey,
                    'joined_at' => now()
                ]);
            }

            return $conversation->fresh();
        });
    }
}

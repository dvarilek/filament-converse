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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateConversation
{
    /**
     * @param  Collection<int, Model&Authenticatable>|(Model&Authenticatable)  $participants
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Authenticatable & Model $creator, (Authenticatable & Model) | Collection $participants, array $attributes = []): Conversation
    {
        if (! $participants instanceof Collection) {
            $participants = collect([$participants]);
        }

        if (! in_array(Conversable::class, class_uses_recursive($creator))) {
            FilamentConverseException::throwInvalidConversableUserException($creator);
        }

        if ($participants->isEmpty()) {
            throw new Exception('A conversation cannot be created without participants.');
        }

        if ($participants->map->getKey()->contains($creator->getKey())) {
            throw new Exception('A conversation creator cannot be one of its other participants');
        }

        /* @var Conversation */
        return DB::transaction(static function () use ($creator, $participants, $attributes): Conversation {
            /* @var Conversation $conversation */
            $conversation = Conversation::query()->create([
                'name' => $attributes['name'] ?? null,
                'description' => $attributes['description'] ?? null,
                'image' => $attributes['image'] ?? null,
            ]);

            $conversationKey = $conversation->getKey();

            /* @var ConversationParticipation $creatorParticipant */
            $creatorParticipant = $creator->conversationParticipations()->create([
                'conversation_id' => $conversationKey,
            ]);

            $conversation->creator()->associate($creatorParticipant)->save();

            $participants->each(static function (Authenticatable & Model $participant) use ($conversationKey) {
                if (! in_array(Conversable::class, class_uses_recursive($participant))) {
                    FilamentConverseException::throwInvalidConversableUserException($participant);
                }

                $participant->conversationParticipations()->create([
                    'conversation_id' => $conversationKey,
                ]);
            });

            return $conversation;
        });
    }
}

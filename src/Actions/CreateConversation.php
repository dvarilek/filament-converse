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

        /* @var Conversation */
        return DB::transaction(function () use ($creator, $participants, $attributes) {
            if (! in_array(Conversable::class, class_uses_recursive($creator))) {
                FilamentConverseException::throwInvalidConversableUserException($creator);
            }

            /* @var Conversation $conversation */
            $conversation = Conversation::query()->create([
                'name' => $attributes['name'] ?? null,
                'description' => $attributes['description'] ?? null,
                'image' => $attributes['image'] ?? null,
            ]);

            if ($participants->isEmpty()) {
                throw new Exception('A conversation cannot be created without participants.');
            }

            $conversationKey = $conversation->getKey();

            /* @var ConversationParticipation $creatorParticipant */
            $creatorParticipant = $creator->conversationParticipations()->create([
                'conversation_id' => $conversationKey,
            ]);

            $conversation->creator()->associate($creatorParticipant)->save();

            $participants->each(function (Authenticatable & Model $participant) use ($conversationKey) {
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

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipant;
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
    public function handle(Authenticatable & Model $createdBy, (Authenticatable & Model) | Collection $participants, array $attributes): Conversation
    {
        if (! $participants instanceof Collection) {
            $participants = collect([$participants]);
        }

        /* @var Conversation */
        return DB::transaction(function () use ($createdBy, $participants, $attributes) {
            $this->validationCandidateParticipant($createdBy);

            $timestamp = now()->format('Y-m-d H:i:s');

            /* @var Conversation $conversation */
            $conversation = Conversation::query()->create([
                'type' => $attributes['type'],
                'name' => $attributes['name'] ?? null,
                'description' => $attributes['description'] ?? null,
                'color' => $attributes['color'] ?? null,
            ]);

            $this->validationConversationType($conversation, $participants);

            $conversationKey = $conversation->getKey();

            /* @var ConversationParticipant $createdByParticipant */
            $createdByParticipant = $createdBy->conversationParticipation()->create([
                'joined_at' => $timestamp,
                'invited_at' => $timestamp,
                'conversation_id' => $conversationKey,
            ]);

            $conversation->createdBy()->associate($createdByParticipant)->save();

            $participants->each(function (Authenticatable & Model $participant) use ($conversationKey, $timestamp) {
                $this->validationCandidateParticipant($participant);

                $participant->conversationParticipation()->create([
                    'joined_at' => $timestamp,
                    'invited_at' => $timestamp,
                    'conversation_id' => $conversationKey,
                ]);
            });

            return $conversation;
        });
    }

    /**
     * @param  Collection<int, Authenticatable&Model>  $participants
     */
    protected function validationConversationType(Conversation $conversation, Collection $participants): void
    {
        if ($participants->isEmpty()) {
            throw new Exception('A conversation cannot be created without participants.');
        }

        $count = $participants->count();

        if ($count > 1 && $conversation->isDirect()) {
            throw new Exception('A direct conversation cannot be created with more than one participant');
        }
    }

    protected function validationCandidateParticipant(Authenticatable & Model $participant): void
    {
        if (! in_array(Conversable::class, class_uses_recursive($participant))) {
            throw new Exception('The conversation participant must be a model that uses the `Dvarilek\FilamentConverse\Models\Concerns\Conversable` trait.');
        }
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models\Concerns;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Model
 *
 * @property Collection<int, ConversationParticipant> $conversationParticipants
 * @property Collection<int, Conversation> $conversations
 */
trait Conversable
{
    /**
     * @return MorphMany<ConversationParticipant, static>
     */
    public function conversationParticipants(): MorphMany
    {
        return $this->morphMany(ConversationParticipant::class, 'participant');
    }

    /**
     * @return MorphToMany<Conversation, static>
     */
    public function conversations(): MorphToMany
    {
        return $this->morphToMany(
            Conversation::class,
            'participant',
            'conversation_participants'
        )
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps()
            ->using(ConversationParticipant::class);
    }
}

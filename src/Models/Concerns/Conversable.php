<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models\Concerns;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Model
 *
 * @property Collection<int, ConversationParticipation> $conversationParticipation
 * @property Collection<int, Conversation> $conversations
 */
trait Conversable
{
    /**
     * @return MorphMany<ConversationParticipation, static>
     */
    public function conversationParticipation(): MorphMany
    {
        return $this->morphMany(ConversationParticipation::class, 'participant');
    }

    /**
     * @return MorphToMany<Conversation, static>
     */
    public function conversations(): MorphToMany
    {
        return $this->morphToMany(
            Conversation::class,
            'participant',
            'conversation_participations',
            'participant_id',
            'conversation_id'
        )
            ->withTimestamps()
            ->with([
                'createdBy.participant',
                'participations.participant'
            ]);
    }

    public static function getNameColumn(): string
    {
        return 'name';
    }

    public static function getAvatarColumn(): ?string
    {
        return null;
    }
}

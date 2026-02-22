<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models\Concerns;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

/**
 * @mixin Model
 *
 * @property-read Collection<int, Conversation> $conversations
 * @property-read Collection<int, Conversation> $activeConversations
 * @property-read Collection<int, ConversationParticipation> $conversationParticipations
 * @property-read Collection<int, Conversation> $activeConversationParticipations
 */
trait Conversable
{
    /**
     * @return HasManyThrough<Conversation, ConversationParticipation, static>
     */
    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            ConversationParticipation::class,
            'participant_id',
            'id',
            'id',
            'conversation_id'
        );
    }

    /**
     * @return HasManyThrough<Conversation, ConversationParticipation, static>
     */
    public function activeConversations(): HasManyThrough
    {
        return $this->conversations()->whereNull('conversation_participations.deactivated_at');
    }

    /**
     * @return HasMany<ConversationParticipation, static>
     */
    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipation::class, 'participant_id');
    }

    public function activeConversationParticipations(): HasMany
    {
        return $this->conversationParticipations()->whereNull('deactivated_at');
    }

    public static function getFilamentNameAttribute(): string
    {
        return 'name';
    }
}

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
 * @property Collection<int, Conversation> $conversations
 * @property Collection<int, ConversationParticipation> $conversationParticipations
 */
trait Conversable
{
    /**
     * @return HasMany<ConversationParticipation, static>
     */
    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipation::class, 'participant_id');
    }

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

    public function participatesInAnyConversation(): bool
    {
        return $this->conversations()->exists();
    }

    public static function getFilamentNameAttribute(): string
    {
        return 'name';
    }
}

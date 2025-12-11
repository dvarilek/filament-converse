<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models\Concerns;

use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

/**
 * @mixin Model
 *
 * @property-read Collection<int, Conversation> $conversations
 * @property-read Collection<int, ConversationParticipation> $conversationParticipations
 *
 * @method Builder excludeSharedDirectConversationsWith(Authenticatable $participant)
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

    /**
     * @param  Builder<static>  $query
     */
    public function scopeExcludeSharedDirectConversationsWith(Builder $query, Authenticatable & Model $participant): void
    {
        $query
            ->whereDoesntHave(
                'conversations',
                static fn (Builder $query) => $query
                    ->where('type', ConversationTypeEnum::DIRECT)
                    ->whereHas(
                        'participations',
                        static fn (Builder $subQuery) => $subQuery
                            ->where('participant_id', $participant->getKey())
                    )
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

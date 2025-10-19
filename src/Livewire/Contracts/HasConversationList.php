<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Contracts;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface HasConversationList
{
    /* @return Collection<int, Conversation> */
    public function conversations(): Collection;

    public function updateActiveConversation(string $conversationKey): void;

    public function getActiveConversation(): ?Conversation;

    /**
     * @param  Builder<Conversation>  $query
     */
    public function applyConversationListSearch(Builder $query): void;

    /**
     * @param  Builder<Conversation>  $query
     */
    public function applyConversationListFilters(Builder $query): void;

    public function resetCachedConversations(): void;
}

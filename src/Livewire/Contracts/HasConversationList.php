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

    public function getActiveConversation(): ?Conversation;

    public function applyConversationListSearch(Builder $query): void;

    public function applyConversationListFilters(Builder $query): void;

    public function resetCachedConversations(): void;
}

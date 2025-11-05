<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Contracts;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Support\Collection;

interface HasConversationSchema
{
    /* @return Collection<int, Conversation> */
    public function conversations(): Collection;

    public function updateActiveConversation(string $conversationKey): void;

    public function getActiveConversation(): ?Conversation;

    public function resetCachedConversations(): void;
}

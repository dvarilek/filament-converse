<?php

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * @property Collection<int, Conversation> $conversations
 */
trait HasConversations
{
    public ?string $activeConversationKey = null;

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): Collection
    {
        $user = auth()->user();

        FilamentConverseException::validateConversableUser($user);

        $conversations = $user->conversations()
            ->with([
                'createdBy.participant',
                'participations.participant',
            ]);

        $this->applyConversationListSearch($conversations);
        $this->applyConversationListFilters($conversations);

        return $conversations->get();
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $this->activeConversationKey = $conversationKey;
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationKey) {
            return null;
        }

        $conversation = $this->conversations->firstWhere((new Conversation())->getKeyName(), $this->activeConversationKey);

        if (! $conversation) {
            return null;
        }

        return $conversation;
    }

    public function resetCachedConversations(): void
    {
        unset($this->conversations);
    }
}

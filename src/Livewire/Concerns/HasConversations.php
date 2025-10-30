<?php

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * @property Collection<int, Conversation> $conversations
 */
trait HasConversations
{
    public ?string $activeConversationKey = null;

    public function mountHasConversations(): void
    {
        $this->conversationPanel = $this->makeConversationPanel();
        $this->resetCachedConversations();

        $conversationPanel = $this->getConversationPanel();

        $shouldPersistActiveConversationInSession = $conversationPanel->shouldPersistActiveConversationInSession();
        $activeConversationSessionKey = $this->getActiveConversationSessionKey();

        if (
            $this->activeConversationKey === null &&
            $shouldPersistActiveConversationInSession &&
            session()->has($activeConversationSessionKey)
        ) {
            $this->activeConversationKey = session()->get($activeConversationSessionKey);
        } else {
            $this->activeConversationKey = $conversationPanel->getDefaultActiveConversation()?->getKey();
        }
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): Collection
    {
        $query = $this->getConversationsBaseQuery();

        $this->applyConversationListSearch($query);
        $this->applyConversationListFilters($query);

        return $query->get();
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $this->activeConversationKey = $conversationKey;

        if ($this->getConversationPanel()->shouldPersistActiveConversationInSession()) {
            session()->put(
                $this->getActiveConversationSessionKey(),
                $this->activeConversationKey,
            );
        }
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationKey) {
            return null;
        }

        $conversation = $this->conversations
            ->firstWhere((new Conversation)->getQualifiedKeyName(), $this->activeConversationKey);

        if ($conversation) {
            return $conversation;
        }

        if (! $this->getConversationPanel()->getConversationList()->isSearchable()) {
            return null;
        }

        return $this->getConversationsBaseQuery()
            ->firstWhere((new Conversation)->getQualifiedKeyName(), $this->activeConversationKey);
    }

    public function resetCachedConversations(): void
    {
        unset($this->conversations);
    }

    public function getActiveConversationSessionKey(): string
    {
        $livewire = md5($this::class);

        return "{$livewire}_active_conversation";
    }

    /**
     * @return Builder<Conversation>
     */
    protected function getConversationsBaseQuery(): Builder
    {
        $user = auth()->user();

        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            FilamentConverseException::throwInvalidConversableUserException($user);
        }

        /* @var Builder<Conversation> */
        return $user->conversations()
            ->select('conversations.*')
            ->getQuery()
            ->with([
                'participations.participant',
                'participations.latestMessage',
            ]);
    }
}

<?php

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\Converse;
use Filament\Schemas\Components\Component;
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
        $this->resetCachedConversations();

        if ($this->activeConversationKey === null) {
            if ($converseComponent = $this->getConverseComponent()) {
                $shouldPersistActiveConversationInSession = $converseComponent->shouldPersistActiveConversationInSession();
                $activeConversationSessionKey = $this->getActiveConversationSessionKey();

                if (
                    $shouldPersistActiveConversationInSession &&
                    session()->has($activeConversationSessionKey)
                ) {
                    $this->activeConversationKey = session()->get($activeConversationSessionKey);
                } else {
                    $this->activeConversationKey = $converseComponent->getDefaultActiveConversation()?->getKey();
                }
            }
        }
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): Collection
    {
        $user = auth()->user();
        FilamentConverseException::validateConversableUser($user);

        /* @var Builder<Conversation> $conversations */
        $conversations = $user->conversations()
            ->select('conversations.*')
            ->getQuery();

        $this->applyConversationListSearch($conversations);
        $this->applyConversationListFilters($conversations);

        return $conversations
            ->with([
                'participations.participant',
                'participations.latestMessage',
            ])
            ->get();
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationKey) {
            return null;
        }

        return $this->conversations->firstWhere((new Conversation)->getKeyName(), $this->activeConversationKey);
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $this->activeConversationKey = $conversationKey;

        if (
            ($converseComponent = $this->getConverseComponent()) &&
            $converseComponent->shouldPersistActiveConversationInSession()
        ) {
            session()->put(
                $this->getActiveConversationSessionKey(),
                $this->activeConversationKey,
            );
        }
    }

    public function getActiveConversationSessionKey(): string
    {
        $livewire = md5($this::class);

        return "{$livewire}_active_conversation";
    }

    protected function getConverseComponent(): ?Converse
    {
        return collect($this->getSchema('content')->getFlatComponents())
            ->first(fn (Component $component) => $component instanceof Converse);
    }

    public function resetCachedConversations(): void
    {
        unset($this->conversations);
    }
}

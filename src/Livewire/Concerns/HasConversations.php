<?php

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
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
        $this->conversationSchema = $this->makeConversationSchema();
        $this->resetCachedConversations();

        $conversationSchema = $this->getConversationSchema();

        $shouldPersistActiveConversationInSession = $conversationSchema->shouldPersistActiveConversationInSession();
        $activeConversationSessionKey = $this->getActiveConversationSessionKey();

        if (
            $this->activeConversationKey === null &&
            $shouldPersistActiveConversationInSession &&
            session()->has($activeConversationSessionKey)
        ) {
            $this->activeConversationKey = session()->get($activeConversationSessionKey);
        } else {
            $this->activeConversationKey = $conversationSchema->getDefaultActiveConversation()?->getKey();
        }
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): Collection
    {
        $query = $this->getBaseConversationsQuery();

        $this->applyConversationListSearch($query);
        $this->applyConversationListFilters($query);

        return $query->get();
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $this->activeConversationKey = $conversationKey;

        if ($this->getConversationSchema()->shouldPersistActiveConversationInSession()) {
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

        $qualifiedConversationKeyName = (new Conversation)->getQualifiedKeyName();

        $conversation = $this->conversations
            ->firstWhere($qualifiedConversationKeyName, $this->activeConversationKey);

        if ($conversation) {
            return $conversation;
        }

        if (! $this->getConversationSchema()->getConversationList()->isSearchable()) {
            return null;
        }

        return $this->getBaseConversationsQuery()
            ->firstWhere($qualifiedConversationKeyName, $this->activeConversationKey);
    }

    public function resetCachedConversations(): void
    {
        unset($this->conversations);
    }

    public function getActiveConversationSessionKey(): string
    {
        $identifier = md5($this::class);

        if ($this->conversationSchemaConfiguration) {
            $identifier .= '_' . $this->conversationSchemaConfiguration;
        }

        return "{$identifier}_active_conversation";
    }

    /**
     * @return Builder<Conversation>
     */
    protected function getBaseConversationsQuery(): Builder
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

    public function getActiveConversationAuthenticatedUserParticipation(): ConversationParticipation
    {
        $user = auth()->user();

        if (! in_array(Conversable::class, class_uses_recursive($user))) {
            FilamentConverseException::throwInvalidConversableUserException($user);
        }

        /* @var ConversationParticipation|null $authenticatedUserParticipation */
        $authenticatedUserParticipation = $this->getActiveConversation()
            ->participations
            ->firstWhere('participant_id', $user->getKey());

        if (! $authenticatedUserParticipation) {
            throw new \Exception('The authenticated user is not participating in the active conversation.');
        }

        return $authenticatedUserParticipation;
    }
}

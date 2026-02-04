<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * @property Collection<int, Conversation> $conversations
 */
trait InteractsWithConversationManager
{
    public ?string $activeConversationKey = null;

    public string $conversationListSearch = '';

    public int $conversationListPage = 1;

    public int $activeConversationMessagesPage = 1;

    /**
     * This property and its exact structure is required for scrollToBottom and pagination to work as expected.
     *
     * @var array<string, array{exists: bool, createdByAuthenticatedUser: bool}>
     */
    public array $messagesCreatedDuringConversationSession = [];

    public ?string $oldestNewMessageKey = null;

    /**
     * @var array<string, mixed>
     */
    public array $cachedUnsendMessages = [];

    public function mountInteractsWithConversationManager(): void
    {
        $conversationSchema = $this->getConversationSchema();
        $activeConversationSessionKey = $this->getActiveConversationSessionKey();

        if (
            $this->activeConversationKey === null &&
            $conversationSchema->shouldPersistActiveConversationInSession() &&
            session()->has($activeConversationSessionKey)
        ) {
            $this->activeConversationKey = session()->get($activeConversationSessionKey);
        } else {
            $this->activeConversationKey = $conversationSchema->getDefaultActiveConversation()?->getKey();
        }
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $previousActiveConversationKey = $this->activeConversationKey;
        $this->activeConversationKey = $conversationKey;

        $this->activeConversationMessagesPage = 1;
        $this->oldestNewMessageKey = null;
        $this->messagesCreatedDuringConversationSession = [];

        $conversationSchema = $this->getConversationSchema();

        if ($conversationSchema->shouldPersistActiveConversationInSession()) {
            session()->put(
                $this->getActiveConversationSessionKey(),
                $this->activeConversationKey,
            );
        }

        $statePath = $conversationSchema->getConversationThread()->getStatePath();

        $this->cachedUnsendMessages[$previousActiveConversationKey] = data_get($this, $statePath);
        data_set($this, $statePath, $this->cachedUnsendMessages[$conversationKey] ?? null);

        $this->content->fill();
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        $conversationList = $this->getConversationSchema()->getConversationList();

        return $this->getBaseFilteredConversationsQuery()
            ->limit(
                $conversationList->getDefaultLoadedConversationsCount()
                + (($this->getConversationListPage() - 1)) * $conversationList->getConversationsLoadedPerPage()
            )
            ->get();
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationKey) {
            return null;
        }

        $instance = new Conversation;

        $conversation = $this->conversations
            ->firstWhere($instance->getKeyName(), $this->activeConversationKey);

        if ($conversation) {
            return $conversation;
        }

        if (! $this->getConversationSchema()->getConversationList()->isSearchable()) {
            return null;
        }

        return $this->getBaseConversationsQuery()
            ->firstWhere($instance->getQualifiedKeyName(), $this->activeConversationKey);
    }

    public function getConversationListPage(): int
    {
        return $this->conversationListPage;
    }

    public function getActiveConversationMessagesPage(): int
    {
        return $this->activeConversationMessagesPage;
    }

    public function incrementConversationListPage(): void
    {
        $this->conversationListPage++;
    }

    public function incrementActiveConversationMessagesPage(): void
    {
        $this->activeConversationMessagesPage++;
    }

    public function registerMessageCreatedDuringConversationSession(string $messageKey, mixed $messageAuthorKey, bool $exists = true): void
    {
        if ($exists === false && ! isset($this->messagesCreatedDuringConversationSession[$messageKey])) {
            return;
        }

        $this->messagesCreatedDuringConversationSession[$messageKey] = [
            'exists' => $exists,
            'createdByAuthenticatedUser' => $messageAuthorKey === auth()->id(),
        ];
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
    public function getBaseFilteredConversationsQuery(): Builder
    {
        $query = $this->getBaseConversationsQuery();

        $conversationList = $this->getConversationSchema()->getConversationList();

        if ($conversationList && $this->conversationListSearch) {
            $query = $conversationList->applyConversationSearch($query);
        }

        // TODO: Eventually add filters here

        return $query;
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
                'participations' => static fn (HasMany $relation) => $relation->unreadMessagesCount(),
                'participations.latestMessage',
                'participations.participant',
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

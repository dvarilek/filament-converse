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

    public int $activeConversationMessagesPage = 1;


    /**
     * It is structured this way mainly so the scrollToBottom functionality works as expected.
     *
     * @var array<string, array{exists: bool, createdByAuthenticatedUser: bool}>
     */
    public array $messagesCreatedDuringConversationSession = [];

    /**
     * @var array<string, mixed>
     */
    public array $cachedUnsendMessages = [];

    public function mountHasConversations(): void
    {
        $this->conversationSchema = $this->makeConversationSchema();

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

        $conversationThread = $conversationSchema->getConversationThread();
        $statePath = $conversationThread->getStatePath();
    }

    public function updateActiveConversation(string $conversationKey): void
    {
        $previousActiveConversationKey = $this->activeConversationKey;
        $this->activeConversationKey = $conversationKey;

        $this->activeConversationMessagesPage = 1;
        $this->messagesCreatedDuringConversationSession = [];

        $conversationSchema = $this->getConversationSchema();

        if ($conversationSchema->shouldPersistActiveConversationInSession()) {
            session()->put(
                $this->getActiveConversationSessionKey(),
                $this->activeConversationKey,
            );
        }

        $conversationThread = $conversationSchema->getConversationThread();
        $statePath = $conversationThread->getStatePath();

        $this->cachedUnsendMessages[$previousActiveConversationKey] = data_get($this, $statePath);
        data_set($this, $statePath, $this->cachedUnsendMessages[$conversationKey] ?? null);
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        $query = $this->getBaseConversationsQuery();

        $this->applyConversationListSearch($query);
        $this->applyConversationListFilters($query);

        return $query->get();
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationKey) {
            return null;
        }

        $helperInstance = new Conversation;

        $conversation = $this->conversations
            ->firstWhere($helperInstance->getKeyName(), $this->activeConversationKey);

        if ($conversation) {
            return $conversation;
        }

        if (! $this->getConversationSchema()->getConversationList()->isSearchable()) {
            return null;
        }

        return $this->getBaseConversationsQuery()
            ->firstWhere($helperInstance->getQualifiedKeyName(), $this->activeConversationKey);
    }

    public function getActiveConversationMessagesPage(): int
    {
        return $this->activeConversationMessagesPage;
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

    public function getMessagesSentDuringConversationSessionCount(): int
    {
        return count(array_filter($this->messagesCreatedDuringConversationSession, static fn (array $data) => $data['createdByAuthenticatedUser'] === true));
    }

    public function getForeignMessagesReceivedDuringConversationSessionCount(): int
    {
        return count(array_filter($this->messagesCreatedDuringConversationSession, static fn (array $data) => $data['createdByAuthenticatedUser'] === false));
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

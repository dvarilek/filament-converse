<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Collections\ConversationParticipationCollection;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Renderless;

trait HasReadReceipts
{
    protected bool | Closure $shouldShowReadReceipts = true;

    protected bool | Closure $shouldMarkConversationAsRead = true;

    protected string | Htmlable | Closure | null $shortenedReadReceiptMessage = null;

    protected bool | Closure $shouldShowFullReadReceiptMessage = false;

    protected string | Htmlable | Closure | null $fullReadReceiptMessage = null;

    public function showReadReceipts(bool | Closure $condition = true): static
    {
        $this->shouldShowReadReceipts = $condition;

        return $this;
    }

    public function markConversationAsRead(bool | Closure $condition = true): static
    {
        $this->shouldMarkConversationAsRead = $condition;

        return $this;
    }

    public function shortenedReadReceiptMessage(string | Htmlable | Closure | null $message): static
    {
        $this->shortenedReadReceiptMessage = $message;

        return $this;
    }

    public function showFullReadReceiptMessage(bool | Closure $condition = true): static
    {
        $this->shouldShowFullReadReceiptMessage = $condition;

        return $this;
    }

    public function fullReadReceiptMessage(string | Htmlable | Closure | null $message): static
    {
        $this->fullReadReceiptMessage = $message;

        return $this;
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function shouldShowReadReceipts(Message $message, ConversationParticipationCollection $readByParticipations, ConversationParticipationCollection $readByParticipationsAsLastMessage, Collection $messages): bool
    {
        return (bool) $this->evaluate($this->shouldShowReadReceipts, [
            'message' => $message,
            'messages' => $messages,
            'readByParticipations' => $readByParticipations,
            'readByParticipationsAsLastMessage' => $readByParticipationsAsLastMessage,
        ], [
            Message::class => $message,
            ConversationParticipationCollection::class => $readByParticipations,
        ]);
    }

    public function shouldMarkConversationAsRead(): bool
    {
        return (bool) $this->evaluate($this->shouldMarkConversationAsRead);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getShortenedReadReceiptMessage(Message $message, ConversationParticipationCollection $readByParticipations, ConversationParticipationCollection $readByParticipationsAsLastMessage, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->shortenedReadReceiptMessage, [
            'message' => $message,
            'messages' => $messages,
            'readByParticipations' => $readByParticipations,
            'readByParticipationsAsLastMessage' => $readByParticipationsAsLastMessage,
        ], [
            Message::class => $message,
            ConversationParticipationCollection::class => $readByParticipations,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function shouldShowFullReadReceiptMessage(Message $message, ConversationParticipationCollection $readByParticipations, ConversationParticipationCollection $readByParticipationsAsLastMessage, Collection $messages): bool
    {
        return (bool) $this->evaluate($this->shouldShowFullReadReceiptMessage, [
            'message' => $message,
            'messages' => $messages,
            'readByParticipations' => $readByParticipations,
            'readByParticipationsAsLastMessage' => $readByParticipationsAsLastMessage,
        ], [
            Message::class => $message,
            ConversationParticipationCollection::class => $readByParticipations,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getFullReadReceiptMessage(Message $message, ConversationParticipationCollection $readByParticipations, ConversationParticipationCollection $readByParticipationsAsLastMessage, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->fullReadReceiptMessage, [
            'message' => $message,
            'messages' => $messages,
            'readByParticipations' => $readByParticipations,
            'readByParticipationsAsLastMessage' => $readByParticipationsAsLastMessage,
        ], [
            Message::class => $message,
            ConversationParticipationCollection::class => $readByParticipations,
        ]);
    }

    #[Renderless]
    #[ExposedLivewireMethod]
    public function markCurrentConversationAsRead(): void
    {
        if (! $this->shouldMarkConversationAsRead()) {
            return;
        }

        /* @var ConversationManager $livewire */
        $livewire = $this->getLivewire();

        $livewire->getActiveConversationAuthenticatedUserParticipation()->readConversation(
            $livewire->getActiveConversation()
        );
    }

    /**
     * @param  Collection<int, Message>  $orderedMessages
     * @return array<string, array{readBy: list<ConversationParticipation>, readByAsLastMessage: list<ConversationParticipation>}>
     */
    public function getMessageReadsMap(Collection $orderedMessages): array
    {
        $messageReadsMap = [];

        foreach ($orderedMessages as $message) {
            $messageReadsMap[$message->getKey()] = [
                'readBy' => [],
                'readByAsLastMessage' => [],
            ];
        }

        foreach ($this->getActiveConversation()?->participations ?? [] as $participation) {
            if (! ($lastReadAt = $participation->last_read_at)) {
                continue;
            }

            $lastReadMessageKey = null;

            foreach ($orderedMessages as $message) {
                if ($message->created_at->gt($participation->last_read_at)) {
                    break;
                }

                $messageKey = $message->getKey();
                $messageReadsMap[$messageKey]['readBy'][] = $participation;
                $lastReadMessageKey = $messageKey;
            }

            if ($lastReadMessageKey !== null) {
                $messageReadsMap[$lastReadMessageKey]['readByAsLastMessage'][] = $participation;
            }
        }

        return $messageReadsMap;
    }
}

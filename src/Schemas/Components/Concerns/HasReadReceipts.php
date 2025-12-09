<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Actions\ReadConversation;
use Dvarilek\FilamentConverse\Events\ConversationRead;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Collection;
use Closure;
use Livewire\Attributes\Renderless;

trait HasReadReceipts
{
    protected bool | Closure $shouldShowReadReceipts = true;

    protected bool | Closure $shouldMarkConversationAsRead = true;

    protected bool | Closure | null $isMessageRead = null;

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

    public function messageRead(bool | Closure | null $condition = null): static
    {
        $this->isMessageRead = $condition;

        return $this;
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function shouldShowReadReceipts(Message $message, Collection $messages): bool
    {
        return (bool) $this->evaluate($this->shouldShowReadReceipts, [
            'message' => $message,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Collection::class => $messages,
        ]);
    }

    public function shouldMarkConversationAsRead(): bool
    {
        return (bool) $this->evaluate($this->shouldMarkConversationAsRead);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function isMessageRead(Message $message, Collection $messages): bool
    {
        return (bool) $this->evaluate($this->isMessageRead, [
            'message' => $message,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Collection::class => $messages,
        ]);
    }

    #[Renderless]
    #[ExposedLivewireMethod]
    public function markCurrentConversationAsRead(): void
    {
        if (! $this->shouldMarkConversationAsRead()) {
            return;
        }

        $livewire = $this->getLivewire();

        $livewire->getActiveConversationAuthenticatedUserParticipation()->readConversation(
            $livewire->getActiveConversation()
        );
    }

    /**
     * @param Collection<int, Message> $orderedMessages
     *
     * @return array<string, array<mixed>>
     */
    public function getReadReceiptsMap(Collection $orderedMessages): array
    {
        $messagesReadByParticipationsMap = [];

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
                $messagesReadByParticipationsMap[$messageKey]['readBy'][] = $participation;
                $lastReadMessageKey = $messageKey;
            }

            if ($lastReadMessageKey !== null) {
                $messagesReadByParticipationsMap[$lastReadMessageKey]['lastReadBy'][] = $participation;
            }
        }

        return dd(collect($messagesReadByParticipationsMap));
    }
}

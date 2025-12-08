<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Models\Message;
use Illuminate\Support\Collection;
use Closure;

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

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function shouldMarkConversationAsRead(Message $message, Collection $messages): bool
    {
        return (bool) $this->evaluate($this->shouldMarkConversationAsRead, [
            'message' => $message,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Collection::class => $messages,
        ]);
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
}

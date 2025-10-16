<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Closure;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

trait HasConversations
{
    protected bool | Closure $shouldShowConversationImage = true;

    protected ?Closure $getConversationNameUsing = null;

    protected ?Closure $getConversationImageUsing = null;

    protected ?Closure $isConversationUnread = null;

    public function showConversationImage(bool | Closure $condition = true): static
    {
        $this->shouldShowConversationImage = $condition;

        return $this;
    }

    public function getConversationNameUsing(Closure $callback): static
    {
        $this->getConversationNameUsing = $callback;

        return $this;
    }

    public function getConversationImageUsing(?Closure $callback = null): static
    {
        $this->getConversationImageUsing = $callback;

        return $this;
    }

    public function isConversationUnreadUsing(?Closure $callback = null): static
    {
        $this->isConversationUnread = $callback;

        return $this;
    }

    public function shouldShowConversationImage(): bool
    {
        return (bool) $this->evaluate($this->shouldShowConversationImage);
    }

    public function getConversationName(Conversation $conversation): string | Htmlable
    {
        return $this->evaluate($this->getConversationNameUsing, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getConversationImage(Conversation $conversation): ?string
    {
        return $this->evaluate($this->getConversationImageUsing, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function isConversationUnread(Conversation $conversation): bool
    {
        return (bool) $this->evaluate($this->isConversationUnread, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function hasConversationImageClosure(): bool
    {
        return $this->getConversationImageUsing !== null;
    }

    public function hasIsConversationUnreadClosure(): bool
    {
        return $this->isConversationUnread !== null;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->getLivewire()->conversations;
    }

    public function getActiveConversation(): ?Conversation
    {
        return $this->getLivewire()->getActiveConversation();
    }
}

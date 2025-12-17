<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Closure;
use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

trait BelongsToConversationSchema
{
    protected bool | Closure | null $shouldShowConversationImage = true;

    protected ?Closure $formatConversationNameUsing = null;

    protected ?Closure $getUnreadMessagesCountUsing = null;

    public function showConversationImage(bool | Closure | null $condition = true): static
    {
        $this->shouldShowConversationImage = $condition;

        return $this;
    }

    public function formatConversationNameUsing(?Closure $callback = null): static
    {
        $this->formatConversationNameUsing = $callback;

        return $this;
    }

    public function getUnreadMessagesCountUsing(?Closure $callback = null): static
    {
        $this->getUnreadMessagesCountUsing = $callback;

        return $this;
    }

    public function shouldShowConversationImage(Conversation $conversation): bool
    {
        return (bool) $this->evaluate($this->shouldShowConversationImage, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getUnreadMessagesCount(Conversation $conversation): int
    {
        if ($this->getUnreadMessagesCountUsing) {
            return $this->evaluate($this->getUnreadMessagesCountUsing, [
                'conversation' => $conversation,
            ], [
                Conversation::class => $conversation,
            ]);
        }

        return $conversation
            ->participations
            ->firstWhere('participant_id', auth()->id())
            ->unread_messages_count;
    }

    public function getConversationName(Conversation $conversation): string | Htmlable | null
    {
        $name = $this->getConversationSchema()->getConversationName($conversation);

        if ($this->formatConversationNameUsing) {
            $name = $this->evaluate($this->formatConversationNameUsing, [
                'name' => $name,
                'conversationName' => $name,
                'value' => $name,
                'conversation' => $conversation,
            ], [
                Conversation::class => $conversation,
            ]);
        }

        return $name;
    }

    public function getConversationImageUrl(Conversation $conversation): ?string
    {
        return $this->getConversationSchema()->getConversationImageUrl($conversation);
    }

    /**
     * @return array<int, array{source: string, alt: string}>
     */
    public function getDefaultConversationImageData(Conversation $conversation): array
    {
        return $this->getConversationSchema()->getDefaultConversationImageData($conversation);
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->getConversationSchema()->getConversations();
    }

    public function getActiveConversation(): ?Conversation
    {
        return $this->getConversationSchema()->getActiveConversation();
    }

    protected function getConversationSchema(): ConversationSchema
    {
        $component = $this->getContainer()->getParentComponent();

        if (! $component instanceof ConversationSchema) {
            FilamentConverseException::throwInvalidParentComponentException(static::class, $component::class);
        }

        return $component;
    }
}

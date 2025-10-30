<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Schemas\Components\ConversationPanel;

trait InteractsWithConversationManager
{
    use CanFilterConversations;
    use CanSearchConversations;
    use HasConversations;

    protected ConversationPanel $conversationPanel;

    public function bootedInteractsWithConversationManager(): void
    {
        $this->conversationPanel = $this->makeConversationPanel();
    }

    public function conversationPanel(ConversationPanel $conversationPanel): ConversationPanel
    {
        return $conversationPanel;
    }

    public function getConversationPanel(): ConversationPanel
    {
        return $this->conversationPanel;
    }

    protected function makeConversationPanel(): ConversationPanel
    {
        return ConversationPanel::make($this);
    }

    public function resetCachedConversations(): void
    {
        unset($this->conversations);
    }
}

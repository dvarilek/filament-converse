<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;

trait InteractsWithConversationManager
{
    use CanFilterConversations;
    use CanSearchConversations;
    use HasConversations;

    /**
     * @var class-string|null
     */
    protected ConversationSchema $conversationSchema;

    public function bootInteractsWithConversationManager(): void
    {
        $this->conversationSchema = $this->makeConversationSchema();
    }

    public function getConversationSchema(): ConversationSchema
    {
        return $this->conversationSchema;
    }

    protected function makeConversationSchema(): ConversationSchema
    {
        $conversationSchema = ConversationSchema::make($this);

        if ($this->conversationSchemaConfiguration && method_exists($this->conversationSchemaConfiguration, 'configure')) {
            return $this->conversationSchemaConfiguration::configure($conversationSchema) ?? $conversationSchema;
        }

        return $conversationSchema;
    }
}

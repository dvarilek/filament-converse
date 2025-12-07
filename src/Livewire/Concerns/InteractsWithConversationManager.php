<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Filament\Schemas\Components\Component;
use Livewire\WithPagination;

trait InteractsWithConversationManager
{
    use CanFilterConversations;
    use CanSearchConversations;
    use HasConversations;

    protected function makeConversationSchema(): ConversationSchema
    {
        $conversationSchema = ConversationSchema::make($this);

        if ($this->conversationSchemaConfiguration && method_exists($this->conversationSchemaConfiguration, 'configure')) {
            return $this->conversationSchemaConfiguration::configure($conversationSchema) ?? $conversationSchema;
        }

        return $conversationSchema;
    }

    public function getConversationSchema(): ConversationSchema
    {
        return $this->content->getComponent(fn (Component $component) => $component instanceof ConversationSchema) ?? throw new \RuntimeException('The conversation schema component is missing.');
    }
}

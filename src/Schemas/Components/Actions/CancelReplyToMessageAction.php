<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CancelReplyToMessageAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'cancelReplyToMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-input.actions.cancel-reply'));

        $this->icon(Heroicon::OutlinedXMark);

        $this->iconButton();

        $this->action(static function (ConversationManager $livewire): void {
            $livewire->cancelReplyToMessage();
            $livewire->content->fill();
        });
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getLivewire()->getActiveConversation()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Conversation::class => [$this->getLivewire()->getActiveConversation()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}

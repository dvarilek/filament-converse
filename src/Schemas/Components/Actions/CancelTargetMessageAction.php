<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\MessageActionType;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CancelTargetMessageAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'cancelTargetMessageAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(static fn (?MessageActionType $targetMessageActionType = null): ?string => match (true) {
            $targetMessageActionType === MessageActionType::REPLY => __('filament-converse::conversation-thread.message-input.actions.cancel-reply'),
            $targetMessageActionType === MessageActionType::EDIT => __('filament-converse::conversation-thread.message-input.actions.cancel-edit'),
            default => null
        });

        $this->icon(Heroicon::OutlinedXMark);

        $this->iconButton();

        $this->action(static function (ConversationManager $livewire): void {
            $livewire->cancelTargetMessageAction();
            $livewire->content->fill();
        });
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        $livewire = $this->getLivewire();

        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$livewire->getActiveConversation()],
            'targetMessageActionType' => [$livewire->targetMessageActionType],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        $livewire = $this->getLivewire();

        return match ($parameterType) {
            Conversation::class => [$livewire->getActiveConversation()],
            MessageActionType::class => [$livewire->targetMessageActionType],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}

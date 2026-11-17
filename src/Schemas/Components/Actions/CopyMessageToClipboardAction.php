<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class CopyMessageToClipboardAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'copyMessageToClipboard';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.copy-to-clipboard.label'));

        $this->color('gray');

        $this->icon(Heroicon::OutlinedClipboard);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        $this->visible(static fn (?Message $message): bool => filled($message?->content));

        $this->alpineClickHandler(
            static fn (?Message $message): string => 'navigator.clipboard.writeText(' . Js::from($message?->content) . '); close()'
        );
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getLivewire()->getActiveConversation()],
            'message' => [$this->getRecord()],
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
            Message::class => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}

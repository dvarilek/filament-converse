<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\MessageActionType;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class EditMessageAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'editMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.edit.label'));

        $this->color('primary');

        $this->icon(Heroicon::OutlinedPencil);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        $this->action(function (ConversationManager $livewire, Message $message): void {
            $livewire->targetMessageKey = $message->getKey();
            $livewire->targetMessageActionType = MessageActionType::EDIT;

            // TODO: Change
            $livewire->content->fill(['conversation_schema' => [
                'messageContent' => $message->content,
                'attachments' => array_combine($message->attachments, $message->attachment_file_names),
            ]]);

            // The dropdown doesn't close automatically after the action.
            $livewire->dispatch('fi-converse-close-actions-dropdown');
        });

        $this->extraAttributes([
            "x-on:fi-converse-close-actions-dropdown.window" => 'close()'
        ]);

        $this->visible(
            static fn (ConversationManager $livewire, ?Message $message): bool => $message?->author_id === $livewire->getActiveConversationAuthenticatedUserParticipation()->getKey()
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

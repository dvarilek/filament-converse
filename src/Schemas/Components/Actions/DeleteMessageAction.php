<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DeleteMessageAction extends Action
{
    protected ?Closure $deleteMessageUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'deleteMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.delete.label'));

        $this->modalHeading(__('filament-converse::conversation-thread.message-actions.delete.modal-heading'));

        $this->successNotificationTitle(__('filament-converse::conversation-thread.message-actions.delete.success'));

        $this->color('danger');

        $this->requiresConfirmation();

        $this->icon(Heroicon::OutlinedTrash);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        $this->visible(
            static fn (ConversationManager $livewire, ?Message $message): bool => filled($message) &&
                $message->author_id === $livewire->getActiveConversationAuthenticatedUserParticipation()->getKey()
        );

        $this->deleteMessageUsing(static fn (Message $message): bool => $message->delete());

        $this->action(static function (DeleteMessageAction $action): void {
            if (! $action->deleteMessageUsing) {
                return;
            }

            $result = $action->evaluate($action->deleteMessageUsing);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });
    }

    public function deleteMessageUsing(?Closure $callback = null): static
    {
        $this->deleteMessageUsing = $callback;

        return $this;
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

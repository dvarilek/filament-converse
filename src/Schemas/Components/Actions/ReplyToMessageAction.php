<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Actions\Concerns\CanSendMessages;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\FusedGroup;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ReplyToMessageAction extends Action
{
    use CanSendMessages;

    protected ?Closure $replyToMessageUsing = null;

    public static function getDefaultName(): string
    {
        return 'replyToMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.reply.label'));

        $this->modalHeading(__('filament-converse::conversation-thread.message-actions.reply.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-thread.message-actions.reply.submit-label'));

        $this->color('primary');

        $this->icon(Heroicon::OutlinedArrowTurnDownLeft);

        $this->modalWidth(Width::Large);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        // TODO: add to EditMessageAction
        $this->schema(static fn (ReplyToMessageAction $action): array => [
            FusedGroup::make([
                $action->getAttachmentAreaComponent(),
                $action->getTextareaComponent(),
                Actions::make([
                    $action->getUploadAttachmentAction(),
                    $action->getSendMessageAction()
                        ->action($action->getLivewireCallMountedActionName()),
                ])
                    ->alignBetween(),
            ])
                ->extraAttributes([
                    'class' => 'fi-converse-conversation-thread-message-input'
                ]),
        ]);

        $this->action(static function (array $data, Message $message, ReplyToMessageAction $action, ConversationManager $livewire): void {
            if (! $action->replyToMessageUsing) {
                return;
            }

            /* @var ?Message $message */
            $message = $action->evaluate($action->replyToMessageUsing, [
                'data' => [
                    ...$data,
                    'reply_to_message_id' => $message->getKey(),
                ],
            ]);

            if (! $message) {
                $action->failure();

                return;
            }

            $livewire->handleMessageChangeDuringConversationSession($message->getKey());
            $action->success();
        });

        $this->modalFooterActions([]);
    }

    public function replyToMessageUsing(?Closure $callback = null): static
    {
        $this->replyToMessageUsing = $callback;

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

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\AttachmentArea;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ReplyToMessageAction extends Action
{
    protected ?Closure $modifyAttachmentAreaComponentUsing = null;

    protected ?Closure $modifyTextareaComponentUsing = null;

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

        $this->schema(static fn (ReplyToMessageAction $action): array => [
            // TODO: Add upload modal and actions from main thread
            $action->getAttachmentAreaComponent(),
            $action->getTextareaComponent(),
        ]);

        $this->action(static function (array $data, Message $message, ReplyToMessageAction $action, ConversationManager $livewire): void {
            if (! $action->replyToMessageUsing) {
                return;
            }

            /* @var ?Message $message */
            $message = $action->evaluate($action->replyToMessageUsing, [
                'data' => [
                    ...$data,
                    'reply_to_message_id' => $message->getKey()
                ]
            ]);

            if (! $message) {
                $action->failure();

                return;
            }

            $statePath = $livewire->getConversationSchema()->getConversationThread()->getStatePath();
            $activeConversation = $livewire->getActiveConversation();

            data_set($livewire->cachedUnsendMessages, $statePath . ".{$activeConversation->getKey()}", null);
            $livewire->registerMessageCreatedDuringConversationSession($message->getKey(), auth()->id());
            // The cached conversations need to be reset so that the latest message of the current conversation
            // gets properly updated for the authenticated user.
            unset($livewire->conversations);

            $action->success();
        });
    }

    public function modifyAttachmentAreaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyAttachmentAreaComponentUsing = $callback;

        return $this;
    }

    public function modifyTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyTextareaComponentUsing = $callback;

        return $this;
    }

    public function replyToMessageUsing(?Closure $callback = null): static
    {
        $this->replyToMessageUsing = $callback;

        return $this;
    }

    public function getAttachmentAreaComponent(): Field
    {
        $component = AttachmentArea::make('attachments');

        if ($this->modifyAttachmentAreaComponentUsing) {
            $component = $this->evaluate($this->modifyAttachmentAreaComponentUsing, [
                'component' => $component,
            ], [
                AttachmentArea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getTextareaComponent(): Field
    {
        $component = Textarea::make('messageContent')
            ->extraAlpineAttributes([
                'x-on:keydown' => ''
            ]);

        if ($this->modifyTextareaComponentUsing) {
            $component = $this->evaluate($this->modifyTextareaComponentUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? $component;
        }

        return $component;
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

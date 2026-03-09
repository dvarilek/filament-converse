<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditMessageAction extends Action
{
    protected ?Closure $modifyTextareaComponentUsing = null;

    protected ?Closure $updateMessageUsing = null;

    public static function getDefaultName(): string
    {
        return 'editMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-thread.message-actions.edit.label'));

        $this->modalHeading(__('filament-converse::conversation-thread.message-actions.edit.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-thread.message-actions.edit.submit-label'));

        $this->successNotificationTitle(__('filament-converse::conversation-thread.message-actions.edit.success'));

        $this->color('primary');

        $this->icon(Heroicon::OutlinedPencil);

        $this->modalWidth(Width::Large);

        $this->model(Message::class);

        $this->record(
            static fn (Conversation $conversation, array $arguments): ?Message => $conversation
                ->messages()
                ->find($arguments['recordKey'] ?? null)
        );

        $this->visible(
            static fn (ConversationManager $livewire, ?Message $message): bool => filled($message?->content) &&
                $message->author_id === $livewire->getActiveConversationAuthenticatedUserParticipation()->getKey()
        );

        $this->schema(static fn (EditMessageAction $action): array => [
            $action->getTextareaComponent(),
        ]);

        $this->updateMessageUsing(static fn (array $data, Message $message): bool => $message->update([
            'content' => $data['messageContent'],
        ]));

        $this->action(static function (array $data, EditMessageAction $action): void {
            if (! $action->updateMessageUsing) {
                return;
            }

            $result = $action->evaluate($action->updateMessageUsing);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });
    }

    public function modifyTextareaComponentUsing(?Closure $callback = null): static
    {
        $this->modifyTextareaComponentUsing = $callback;

        return $this;
    }

    public function updateMessageUsing(?Closure $callback = null): static
    {
        $this->updateMessageUsing = $callback;

        return $this;
    }

    public function getTextareaComponent(): Field
    {
        $component = Textarea::make('messageContent')
            ->hiddenLabel()
            ->placeholder(__('filament-converse::conversation-thread.placeholder'))
            ->required(static fn (Message $message) => blank($message->attachments))
            ->autosize()
            ->autofocus()
            ->rows(4)
            ->maxLength(65535)
            ->extraAttributes([
                'style' => 'max-height: 8rem; overflow: auto',
            ]);

        if ($this->modifyTextareaComponentUsing) {
            $component = $this->evaluate($this->modifyTextareaComponentUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? null;
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

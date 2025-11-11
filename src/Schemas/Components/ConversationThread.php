<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasFileAttachments;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

class ConversationThread extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use HasFileAttachments;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    const MESSAGE_INPUT_FIELD_KEY = 'message_input_field';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected ?Closure $modifyMessageInputFieldUsing = null;

    public static function make()
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key('conversation-thread');

        $this->emptyStateHeading(static function () {
            return __('filament-converse::conversation-thread.empty-state.heading');
        });

        $this->childComponents(fn () => [
            $this->getEditConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getEditMessageAction(),
            $this->getDeleteMessageAction(),
        ], static::MESSAGE_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getMessageInputField(),
        ], static::MESSAGE_INPUT_FIELD_KEY);
    }

    public function editConversationAction(?Closure $callback): static
    {
        $this->modifyEditConversationActionUsing = $callback;

        return $this;
    }

    public function editMessageAction(?Closure $callback): static
    {
        $this->modifyEditMessageActionUsing = $callback;

        return $this;
    }

    public function deleteMessageAction(?Closure $callback): static
    {
        $this->modifyDeleteMessageActionUsing = $callback;

        return $this;
    }

    public function messageInputField(?Closure $callback): static
    {
        $this->modifyMessageInputFieldUsing = $callback;

        return $this;
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd($this->getLivewire()->content->getState()));

        if ($this->modifyEditConversationActionUsing) {
            $action = $this->evaluate($this->modifyEditConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getEditMessageAction(): Action
    {
        $action = EditMessageAction::make();

        if ($this->modifyEditMessageActionUsing) {
            $action = $this->evaluate($this->modifyEditMessageActionUsing, [
                'action' => $action,
            ], [
                EditMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getDeleteMessageAction(): Action
    {
        $action = DeleteMessageAction::make();

        if ($this->modifyDeleteMessageActionUsing) {
            $action = $this->evaluate($this->modifyDeleteMessageActionUsing, [
                'action' => $action,
            ], [
                DeleteMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getMessageInputField(): MessageInput
    {
        $component = MessageInput::make('message_content');

        if ($this->modifyMessageInputFieldUsing) {
            $component = $this->evaluate($this->modifyMessageInputFieldUsing, [
                'component' => $component,
            ], [
                MessageInput::class => $component,
                Component::class => $component,
            ]) ?? $component;
        }

        return $component;
    }
}

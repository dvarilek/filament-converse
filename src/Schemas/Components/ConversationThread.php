<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ConversationThread extends Component
{
    use Concerns\HasConversations;
    use Concerns\HasEmptyState;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected string | Htmlable | Closure | null $heading = null;

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    /**
     * @param  array<string, mixed> | Closure  $data
     */
    public function __construct(string | Htmlable | Closure | null $heading)
    {
        $this->heading($heading);
    }

    public static function make(string | Htmlable | Closure | null $heading = null)
    {
        $static = app(static::class, ['heading' => $heading]);
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

        $this->childCOmponents(fn () => [
            $this->getEditMessageAction(),
            $this->getDeleteMessageAction(),
        ], static::MESSAGE_ACTIONS_KEY);
    }

    public function heading(string | Htmlable | Closure | null $heading): static
    {
        $this->heading = $heading;

        return $this;
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

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading) ?? __('filament-converse::conversation-thread.heading');
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd('editConversation'));

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
        $action = Action::make('editMessage')
            ->iconButton()
            ->icon(Heroicon::OutlinedPencil)
            ->action(fn () => dd('editMessage'));

        if ($this->modifyEditMessageActionUsing) {
            $action = $this->evaluate($this->modifyEditMessageActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getDeleteMessageAction(): Action
    {
        $action = Action::make('deleteMessage')
            ->iconButton()
            ->color('danger')
            ->icon(Heroicon::OutlinedTrash)
            ->action(fn () => dd('deleteMessage'));

        if ($this->modifyDeleteMessageActionUsing) {
            $action = $this->evaluate($this->modifyDeleteMessageActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }
}

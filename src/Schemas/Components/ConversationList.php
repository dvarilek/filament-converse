<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component as LivewireComponent;

class ConversationList extends Component
{
    use Concerns\HasConversations;
    use Concerns\HasEmptyState;
    use Concerns\HasSearch;

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-list';

    protected string | Htmlable | Closure | null $heading = null;

    protected string | Htmlable | Closure | null $description = null;

    protected bool | Closure $shouldConversationListOverflow = false;

    public function __construct(string | Closure | null $heading)
    {
        $this->heading($heading);
    }

    public static function make(string | Closure | null $heading = null)
    {
        $static = app(static::class, ['heading' => $heading]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getConversationNameUsing(static function (Conversation $conversation) {
            return $conversation->getName();
        });

        $this->getConversationImageUsing(static function (Conversation $conversation) {
            return $conversation->image;
        });
    }

    public function heading(string | Htmlable | Closure | null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(string | Htmlable | Closure | null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function conversationListOverflow(bool | Closure $condition = true): static
    {
        $this->shouldConversationListOverflow = $condition;

        return $this;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading) ?? "temp"; // TODO:
    }

    public function getDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->description);
    }

    public function shouldConversationListOverflow(): bool
    {
        return (bool) $this->evaluate($this->shouldConversationListOverflow);
    }

    public function getLivewire(): LivewireComponent & HasSchemas & HasActions & HasConversationList
    {
        return parent::getLivewire();
    }
}

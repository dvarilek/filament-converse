<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Illuminate\Contracts\Support\Htmlable;

class ConversationThread extends Component
{
    use HasKey;

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected string | Htmlable | Closure | null $heading = null;

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
    }

    public function heading(string | Htmlable | Closure | null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading) ?? __('filament-converse::conversation-thread.heading');
    }
}

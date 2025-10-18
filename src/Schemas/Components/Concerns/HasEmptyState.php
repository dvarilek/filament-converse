<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Closure;

trait HasEmptyState
{
    protected View | Htmlable | Closure | null $emptyState = null;

    protected string | Htmlable | Closure | null $emptyStateHeading = null;

    protected string | Htmlable | Closure | null $emptyStateDescription = null;

    protected string | BackedEnum | Closure | null $emptyStateIcon = null;

    protected string | Closure | null $emptyStateIconColor = null;

    public function emptyState(View | Htmlable | Closure | null $emptyState): static
    {
        $this->emptyState = $emptyState;

        return $this;
    }

    public function emptyStateHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->emptyStateHeading = $heading;

        return $this;
    }

    public function emptyStateDescription(string | Htmlable | Closure | null $description): static
    {
        $this->emptyStateDescription = $description;

        return $this;
    }

    public function emptyStateIcon(string | BackedEnum | Closure | null $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function emptyStateIconColor(string | Closure | null $color): static
    {
        $this->emptyStateIconColor = $color;

        return $this;
    }

    public function getEmptyState(): View | Htmlable | null
    {
        return $this->evaluate($this->emptyState);
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return $this->evaluate($this->emptyStateHeading) ?? __('filament-converse::conversation-list.empty-state.heading');
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->emptyStateDescription);
    }

    public function getEmptyStateIcon(): string | BackedEnum
    {
        return $this->evaluate($this->emptyStateIcon) ?? Heroicon::OutlinedXMark;
    }

    public function getEmptyStateIconColor(): string
    {
        return $this->evaluate($this->emptyStateIconColor) ?? 'primary';
    }
}

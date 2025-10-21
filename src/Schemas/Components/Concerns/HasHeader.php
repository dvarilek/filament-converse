<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasIcon;
use Filament\Support\View\Components\BadgeComponent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\HtmlString;
use Closure;

trait HasHeader
{
    protected string | Htmlable | Closure | null $heading = null;

    protected string | Htmlable | Closure | null $description = null;

    protected bool | Closure $hasHeadingBadge = true;

    protected int | string | Closure | null $headingBadgeState = null;

    protected string | array | Closure | null $headingBadgeColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $headingBadgeIcon = null;

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

    public function headingBadge(bool | Closure $condition = true): static
    {
        $this->hasHeadingBadge = $hasBadge;

        return $this;
    }

    public function headingBadgeState(int | string | Closure | null $state): static
    {
        $this->headingBadgeState = $state;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function headingBadgeColor(string | array | Closure | null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function headingBadgeIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->icon = filled($icon) ? $icon : false;

        return $this;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading) ?? __('filament-converse::conversation-list.heading');
    }

    public function getDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->description);
    }

    public function hasHeadingBadge(): bool
    {
        return (bool) $this->evaluate($this->hasHeadingBadge);
    }

    public function getHeadingBadgeState(): int | string | null
    {
        return $this->evaluate($this->headingBadgeState);
    }

    /**
     * @return string | array<string> | null
     */
    public function getHeadingBadgeColor(): string | array | null
    {
        return $this->evaluate($this->headingBadgeColor);
    }

    public function getHeadingBadgeIcon(): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->headingBadgeIcon);

        // https://github.com/filamentphp/filament/pull/13512
        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if ($icon === false) {
            return null;
        }

        return $icon;
    }
}

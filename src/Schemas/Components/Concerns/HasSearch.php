<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Closure;

trait HasSearch
{
    protected bool | Closure $isSearchable = true;

    protected string | Closure | null $searchPlaceholder = null;

    protected string | Closure | null $searchDebounce = null;

    protected bool | Closure $isSearchOnBlur = false;

    public function searchable(bool | Closure $isSearchable = true): static
    {
        $this->isSearchable = $isSearchable;

        return $this;
    }

    public function searchPlaceholder(string | Closure | null $searchPlaceholder): static
    {
        $this->searchPlaceholder = $searchPlaceholder;

        return $this;
    }

    public function searchDebounce(string | Closure | null $debounce): static
    {
        $this->searchDebounce = $debounce;

        return $this;
    }

    public function searchOnBlur(bool | Closure $condition = true): static
    {
        $this->isSearchOnBlur = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }

    public function getSearchPlaceholder(): ?string
    {
        return $this->evaluate($this->searchPlaceholder);
    }

    public function getSearchDebounce(): string
    {
        return $this->evaluate($this->searchDebounce) ?? '500ms';
    }

    public function isSearchOnBlur(): bool
    {
        return (bool) $this->evaluate($this->isSearchOnBlur);
    }
}

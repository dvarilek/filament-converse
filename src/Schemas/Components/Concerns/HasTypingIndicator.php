<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

trait HasTypingIndicator
{
    protected bool | Closure $shouldDispatchUserTypingEvent = true;

    protected ?Closure $formatTypingUserNameUsing = null;

    protected int | Closure $userTypingIndicatorTimeout = 3500;

    protected int | Closure | null $userTypingEventDispatchThreshold = 3000;

    protected bool | Closure $shouldShowTypingIndicator = true;

    /**
     * @var array{single: string, double: string, multiple: string, other: string, others: string}|Closure
     */
    protected array | Closure $userTypingTranslations = [];

    public function dispatchUserTypingEvent(bool | Closure $condition): static
    {
        $this->shouldDispatchUserTypingEvent = $condition;

        return $this;
    }

    public function formatTypingUserNameUsing(?Closure $callback = null): static
    {
        $this->formatTypingUserNameUsing = $callback;

        return $this;
    }

    public function userTypingIndicatorTimeout(int | Closure | null $milliseconds): static
    {
        $this->userTypingIndicatorTimeout = $milliseconds;

        return $this;
    }

    public function userTypingEventDispatchThreshold(int | Closure | null $millisecond): static
    {
        $this->userTypingEventDispatchThreshold = $millisecond;

        return $this;
    }

    public function showTypingIndicator(bool | Closure $condition): static
    {
        $this->shouldShowTypingIndicator = $condition;

        return $this;
    }

    /**
     * @param  array{single: string, double: string, multiple: string, other: string, others: string}|Closure  $translations
     */
    public function userTypingTranslations(array | Closure $translations): static
    {
        $this->userTypingTranslations = $translations;

        return $this;
    }

    public function shouldDispatchUserTypingEvent(): bool
    {
        return (bool) $this->evaluate($this->shouldDispatchUserTypingEvent);
    }

    public function getUserTypingIndicatorTimeout(): int
    {
        return $this->evaluate($this->userTypingIndicatorTimeout) ?? 3500;
    }

    public function getUserTypingEventDispatchThreshold(): ?int
    {
        return $this->evaluate($this->userTypingEventDispatchThreshold);
    }

    public function shouldShowTypingIndicator(): bool
    {
        return (bool) $this->evaluate($this->shouldShowTypingIndicator);
    }

    /**
     * @return array{single: string, double: string, multiple: string, other: string, others: string}
     */
    public function getUserTypingTranslations(): array
    {
        return $this->evaluate($this->userTypingTranslations) ?? [];
    }
}
<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Illuminate\View\ComponentAttributeBag;

trait HasExtraConversationAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraConversationAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraConversationAttributes(array | Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraConversationAttributes[] = $attributes;
        } else {
            $this->extraConversationAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraConversationAttributes(): array
    {
        $temporaryAttributeBag = new ComponentAttributeBag;

        foreach ($this->extraConversationAttributes as $extraAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraConversationAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraConversationAttributes());
    }
}

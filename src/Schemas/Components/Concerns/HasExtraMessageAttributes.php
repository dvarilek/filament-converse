<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Illuminate\View\ComponentAttributeBag;

trait HasExtraMessageAttributes
{
    /**
     * @var array<array<mixed> | Closure>
     */
    protected array $extraMessageAttributes = [];

    /**
     * @param  array<mixed> | Closure  $attributes
     */
    public function extraMessageAttributes(array | Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraMessageAttributes[] = $attributes;
        } else {
            $this->extraMessageAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraMessageAttributes(): array
    {
        $temporaryAttributeBag = new ComponentAttributeBag;

        foreach ($this->extraMessageAttributes as $extraMessageAttributes) {
            $temporaryAttributeBag = $temporaryAttributeBag->merge($this->evaluate($extraMessageAttributes), escape: false);
        }

        return $temporaryAttributeBag->getAttributes();
    }

    public function getExtraMessageAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraMessageAttributes());
    }
}
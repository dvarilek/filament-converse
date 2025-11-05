<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

trait BelongsToLivewire
{
    protected Component & HasSchemas & HasConversationSchema $livewire;

    public function livewire(Component & HasSchemas & HasConversationSchema $livewire): static
    {
        $this->livewire = $livewire;

        return $this;
    }

    public function getLivewire(): Component & HasSchemas & HasConversationSchema
    {
        return $this->evaluate($this->livewire) ?? parent::getLivewire();
    }
}

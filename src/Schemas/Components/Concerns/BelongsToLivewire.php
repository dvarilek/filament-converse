<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

trait BelongsToLivewire
{
    protected Component & HasSchemas & HasConversationList $livewire;

    public function livewire(Component & HasSchemas & HasConversationList $livewire): static
    {
        $this->livewire = $livewire;

        return $this;
    }

    public function getLivewire(): Component & HasSchemas & HasConversationList
    {
        return $this->evaluate($this->livewire) ?? parent::getLivewire();
    }
}

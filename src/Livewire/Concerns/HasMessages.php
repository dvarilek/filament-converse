<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

trait HasMessages
{

    public function sendMessage(): void
    {
        $state = $this->content->getState();

        dd($state);
    }
}

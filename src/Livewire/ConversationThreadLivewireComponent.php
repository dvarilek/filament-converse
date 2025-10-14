<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ConversationThreadLivewireComponent extends Component
{
    public function render(): View
    {
        return view('filament-converse::conversation-thread');
    }
}

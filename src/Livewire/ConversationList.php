<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConversationList extends Component
{
    /**
     * @return list<Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): array
    {
        $user = auth()->user();

        return $user ? $user->conversations() : [];
    }

    public function render(): View
    {
        return view('filament-converse::livewire.conversation-list');
    }
}

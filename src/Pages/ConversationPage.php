<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Pages;

use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Filament\Pages\Page;

class ConversationPage extends Page implements HasConversationList
{
    use CanManageConversations;

    public function mount(): void
    {
        $this->resetCachedConversations();
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Livewire\Concerns\InteractsWithConversationManager;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationThread;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\View\View;
use Livewire\Component;

class ConversationManager extends Component implements HasActions, HasConversationList, HasConversationThread, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithConversationManager;
    use InteractsWithSchemas;

    public function content(Schema $schema): Schema
    {

        return $schema
            ->components([
                $this->getConversationPanel(),
            ]);
    }

    public function render(): View
    {
        return view('filament-converse::livewire.conversation-manager');
    }
}

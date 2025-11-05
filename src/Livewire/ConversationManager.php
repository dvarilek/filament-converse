<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Livewire\Concerns\InteractsWithConversationManager;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConversationManager extends Component implements HasActions, HasConversationSchema, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithConversationManager;
    use InteractsWithSchemas;

    public ?array $data = [];

    #[Locked]
    public ?string $conversationSchemaConfiguration = null;

    public function mount(?string $conversationSchemaConfiguration = null): void
    {
        $this->conversationSchemaConfiguration = $conversationSchemaConfiguration;

        $this->content->fill();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getConversationSchema(),
            ])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('filament-converse::livewire.conversation-manager');
    }
}

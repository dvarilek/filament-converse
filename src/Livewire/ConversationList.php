<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Contracts\View\View;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use App\Models\User;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
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

        if (! $user) {
            return [];
        }

        FilamentConverseException::validateConversableUser($user);

        return $user->conversations()->get()->toArray();
    }

    public function render(): View
    {
        return view('filament-converse::conversation-list');
    }
}

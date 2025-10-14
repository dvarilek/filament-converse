<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use App\Models\User;
use Filament\Actions\Action;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConversationListLivewireComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed(persist: true, key: 'filament-converse::conversations-list-computed-property')]
    public function conversations(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        FilamentConverseException::validateConversableUser($user);

        return $user->conversations()->get();
    }

    public function createConversation(): Action
    {
        // TODO: Move to its own class probably
        return Action::make('createConversation')
            ->label('Create')
            ->icon(Heroicon::Plus)
            ->size(Size::ExtraSmall);
    }

    public function render(): View
    {
        return view('filament-converse::conversation-list');
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;

trait CanSearchConversations
{
    public string $conversationListSearch = '';

    /**
     * @param  Builder<Conversation>  $query
     */
    public function applyConversationListSearch(Builder $query): void
    {
        if (! $this->conversationListSearch) {
            return;
        }

        $nameAttribute = FilamentConverseServiceProvider::getFilamentConverseUserModel()::getFilamentNameAttribute();

        $query
            ->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$this->conversationListSearch}%")
                    ->orWhere('description', 'like', "%{$this->conversationListSearch}%")
                    ->orWhereHas(
                        'participations',
                        fn (Builder $subQuery) => $subQuery
                            ->whereHas(
                                'participant',
                                fn (Builder $q) => $q
                                    ->where($nameAttribute, 'like', "%{$this->conversationListSearch}%")
                            )
                    )
            );
    }

    public function updatedConversationListSearch(): void
    {
        $this->resetCachedConversations();
    }
}

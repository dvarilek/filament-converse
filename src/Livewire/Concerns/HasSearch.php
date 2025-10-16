<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    public string $conversationListSearch = '';

    public function applyConversationListSearch(Builder $query): void
    {
        if (! $this->conversationListSearch) {
            return;
        }

        $query
            ->where('name', 'like', "%{$this->conversationListSearch}%")
            ->where(function (Builder $query) {
                $query
                    ->orWhere('description', 'like', "%{$this->conversationListSearch}%")
                    ->orWhereHas('participations', function (Builder $subQuery) {
                        $subQuery->where('participant_name', 'like', "%{$this->conversationListSearch}%");
                    });
            });
    }

    public function updatedConversationListSearch(): void
    {
        $this->resetCachedConversations();
    }
}

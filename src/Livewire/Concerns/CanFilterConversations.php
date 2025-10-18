<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Livewire\Concerns;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;

trait CanFilterConversations
{
    /**
     * @param Builder<Conversation> $query
     */
    public function applyConversationListFilters(Builder $query): void
    {
        // TODO: Do later, + maybe add more traitsr
    }
}

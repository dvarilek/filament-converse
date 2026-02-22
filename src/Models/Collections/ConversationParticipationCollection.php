<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models\Collections;

use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TModel of ConversationParticipation
 *
 * @extends \Illuminate\Support\Collection<TKey, TModel>
 */
class ConversationParticipationCollection extends Collection
{
    public function other(): static
    {
        $authenticatedUserId = auth()->id();

        return $this->reject(static fn (ConversationParticipation $participation) => $participation->participant_id === $authenticatedUserId);
    }

    public function active(): static
    {
        return $this->filter(static fn (ConversationParticipation $participation) => $participation->deactivated_at === null);
    }

    public function inactive(): static
    {
        return $this->reject(static fn (ConversationParticipation $participation) => $participation->deactivated_at === null);
    }
}

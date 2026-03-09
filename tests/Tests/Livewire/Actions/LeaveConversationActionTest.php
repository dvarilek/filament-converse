<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\LeaveConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

test('is hidden for conversation owner', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    app(CreateConversation::class)->handle($owner, $participant);

    $livewire = livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->assertActionHidden(LeaveConversationAction::getDefaultName());

    $this->actingAs($participant);

    $livewire->assertActionVisible(LeaveConversationAction::getDefaultName());
});

it('can leave conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($participant);

    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->callAction(
            TestAction::make(LeaveConversationAction::getDefaultName()),
        );

    $participations = $conversation->fresh()->participations;

    expect($participations->pluck('participant_id')->sort()->values()->toArray())
        ->toHaveCount(2)
        ->toBe(collect([$owner->getKey(), $participant->getKey()])->sort()->values()->toArray())
        ->and($participations->active()->pluck('participant_id')->sort()->values()->toArray())
        ->toHaveCount(1)
        ->not->toContain($participant->getKey());
});

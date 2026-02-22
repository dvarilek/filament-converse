<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\TransferConversationAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

test('is hidden for regular participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($participant);

    app(CreateConversation::class)->handle($owner, $participant);

    $livewire = livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->assertActionHidden(TransferConversationAction::getDefaultName());

    $this->actingAs($owner);

    $livewire->assertActionVisible(TransferConversationAction::getDefaultName());
});

it('can transfer conversation in a direct conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->callAction(TransferConversationAction::getDefaultName());

    $conversation = $conversation->fresh();

    $userParticipation = $conversation
        ->participations()
        ->firstWhere('participant_id', $participant->getKey());

    expect($conversation->owner_id)->toBe($userParticipation->getKey());
});

it('can transfer conversation in a group conversation', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, [
        $firstParticipant,
        $secondParticipant,
    ]);

    $firstParticipantParticipation = $conversation->participations()->firstWhere('participant_id', $secondParticipant->getKey());
    $secondParticipantParticipation = $conversation->participations()->firstWhere('participant_id', $firstParticipant->getKey());

    $livewire = livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        );

    $livewire
        ->callAction(TransferConversationAction::getDefaultName(), [
            'participation' => $secondParticipantParticipation->getKey(),
        ])
        ->assertHasNoFormErrors();

    $conversation = $conversation->fresh();

    expect($conversation->owner_id)->toBe($secondParticipantParticipation->getKey());

    $this->actingAs($firstParticipant);

    $livewire
        ->callAction(TransferConversationAction::getDefaultName(), [
            'participation' => $firstParticipantParticipation->getKey(),
        ])
        ->assertHasNoFormErrors();

    $conversation = $conversation->fresh();

    expect($conversation->owner_id)->toBe($firstParticipantParticipation->getKey());
});

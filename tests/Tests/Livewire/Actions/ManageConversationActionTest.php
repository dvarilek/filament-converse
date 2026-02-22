<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

it('can update conversation', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();
    $thirdParticipant = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, [
        $firstParticipant,
        $secondParticipant,
        $thirdParticipant
    ]);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
            [
                'name' => 'updated name',
                'description' => 'updated description',
                'participants' => [
                    $firstParticipant->getKey(),
                    $thirdParticipant->getKey()
                ]
            ]
        )
        ->assertHasNoErrors();

    expect($conversation->fresh())
        ->name->toBe('updated name')
        ->description->toBe('updated description')
        ->participations->active()->other()->pluck('participant_id')->sort()->values()->toArray()
        ->toBe(collect([$firstParticipant->getKey(), $thirdParticipant->getKey()])->sort()->values()->toArray());
});

it('requires a selected participant to update conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    app(CreateConversation::class)->handle($owner, $participant);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
            [
                'participants' => []
            ]
        )
        ->assertHasFormErrors(['participants' => 'required']);
});




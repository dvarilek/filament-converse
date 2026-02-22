<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Filament\Actions\Testing\TestAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\DeleteConversationAction;

use function Pest\Livewire\livewire;

test('is hidden for regular participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    app(CreateConversation::class)->handle($owner, $participant);

    $livewire = livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->assertActionVisible(DeleteConversationAction::getDefaultName());;

    $this->actingAs($participant);

    $livewire->assertActionHidden(DeleteConversationAction::getDefaultName());
});

it('can delete conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    app(CreateConversation::class)->handle($owner, $participant);

    livewire(ConversationManager::class)
        ->mountAction(
            TestAction::make(ManageConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread'),
        )
        ->callAction(DeleteConversationAction::getDefaultName());

    expect(Conversation::query()->count())->toBe(0);
});

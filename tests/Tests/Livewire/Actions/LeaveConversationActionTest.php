<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\LeaveConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Filament\Actions\Testing\TestAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;

use function Pest\Livewire\livewire;

it('can leave conversation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle($owner, $otherUser);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(LeaveConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread.mountedActions.manageConversation'),
            [
                'conversation' => $conversation,
            ]
        )
        ->assertHasNoErrors();

    expect(Conversation::query()->count())->toBe(0);
})->only();

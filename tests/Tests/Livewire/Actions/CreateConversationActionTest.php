<?php

use Dvarilek\FilamentConverse\Schemas\Components\Actions\CreateConversationAction;
use Filament\Actions\Testing\TestAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;

use function Pest\Livewire\livewire;

it('can create a new direct conversation through action', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    $livewire = livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(CreateConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_list'), [
                'participants' => [
                    $participant->getKey()
                ],
            ]
        )
        ->assertHasNoErrors();

    /* @var Conversation $conversation */
    $conversation = Conversation::query()->first();

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->name->toBeNull()
        ->description->toBeNull()
        ->image->toBeNull()
        ->participations->toHaveCount(2)
        ->participations->pluck('participant_id')->toContain($owner->getKey(), $participant->getKey())
        ->owner->participant->getKey()->toBe($owner->getKey())
        ->and($livewire->instance())
        ->conversations->toHaveCount(1)
        ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
        ->getActiveConversation()->getKey()->toBe($conversation->getKey());
});

it('requires a selected participant to create a new direct conversation', function () {
    $this->actingAs(User::factory()->create());

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(CreateConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_list')
        )
        ->assertHasFormErrors(['participants' => 'required']);
});

it('can create a new group conversation through action', function () {
    $owner = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $this->actingAs($owner);

    $livewire = livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(CreateConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_list'), [
                'participants' => [
                    $firstUser->getKey(),
                    $secondUser->getKey()
                ],
            ]
        )
        ->assertHasNoErrors();

    /* @var Conversation $conversation */
    $conversation = Conversation::query()->first();

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->name->toBeNull()
        ->description->toBeNull()
        ->image->toBeNull()
        ->participations->toHaveCount(3)
        ->participations->pluck('participant_id')->toContain($owner->getKey(), $firstUser->getKey(), $secondUser->getKey())
        ->owner->participant->getKey()->toBe($owner->getKey())
        ->and($livewire->instance())
        ->conversations->toHaveCount(1)
        ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
        ->getActiveConversation()->getKey()->toBe($conversation->getKey());
});

it('can create a new group conversation through action with additional data', function () {
    $owner = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $this->actingAs($owner);

    $livewire = livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(CreateConversationAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_list'), [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
                'name' => 'Test conversation',
                'description' => 'Test description',
            ]
        )
        ->assertHasNoErrors();

    /* @var Conversation $conversation */
    $conversation = Conversation::query()->first();

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->name->toBe('Test conversation')
        ->description->toBe('Test description')
        ->image->toBeNull()
        ->participations->toHaveCount(3)
        ->participations->pluck('participant_id')->toContain($owner->getKey(), $firstUser->getKey(), $secondUser->getKey())
        ->owner->participant->getKey()->toBe($owner->getKey())
        ->and($livewire->instance())
        ->conversations->toHaveCount(1)
        ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
        ->getActiveConversation()->getKey()->toBe($conversation->getKey());
});

it('can create multiple group conversations with the same participants', function () {
    $owner = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $this->actingAs($owner);
    $action = TestAction::make(CreateConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation_list');

    $livewire = livewire(ConversationManager::class)
        ->callAction($action, [
            'participants' => [$firstUser->getKey(), $secondUser->getKey()],
            'name' => 'First group',
        ])
        ->assertHasNoErrors();

    expect(Conversation::query()->count())->toBe(1);

    $livewire
        ->callAction($action, [
            'participants' => [$firstUser->getKey(), $secondUser->getKey()],
            'name' => 'Second group',
        ])
        ->assertHasNoErrors();

    expect(Conversation::query()->count())->toBe(2);

    $livewire
        ->callAction($action, [
            'participants' => [$secondUser->getKey()],
            'name' => 'Third group',
        ])
        ->assertHasNoErrors();

    expect(Conversation::query()->count())->toBe(3);
});

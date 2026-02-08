<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

describe('render', function () {})->todo();

// TODO: Finish

describe('message input', function () {
    test('can send message', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        app(CreateConversation::class)->handle($owner, $otherUser);

        $livewire = livewire(ConversationManager::class)
            ->fillForm([
                'conversation_thread' => 'Test message',
            ], 'content')
            ->callAction(TestAction::make('sendMessage')->schemaComponent('conversation_thread', 'content'));

        dd($livewire->instance()->getActiveConversation());

        expect($livewire->instance()->getActiveConversation()->messages)
            ->toHaveCount(1)
            ->first()->content->toBe('Test message');
    });

    test('can send message with attachment', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        app(CreateConversation::class)->handle($owner, $otherUser);
    });
})->skip()->todo();

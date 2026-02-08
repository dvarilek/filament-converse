<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\LeaveConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Tests\Models\User;

use function Pest\Livewire\livewire;

describe('render', function () {
    it('can render conversations', function () {
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $thirdUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $secondUser);

        /* @var Conversation $firstGroupConversation */
        $firstGroupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser]));
        /* @var Conversation $thirdUserConversation */
        $secondGroupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser, $thirdUser]));

        $livewire = livewire(ConversationManager::class);

        expect($livewire->instance()->conversations)
            ->toHaveCount(4)
            ->pluck((new Conversation)->getKeyName())
            ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $firstGroupConversation->getKey(), $secondGroupConversation->getKey())
            ->and($livewire)
            ->assertSeeText($firstUser->name)
            ->assertSeeText($secondUser->name)
            ->assertSeeText($firstUser->name . " & " . $secondUser->name)
            ->assertSeeText($firstUser->name . ', ' . $secondUser->name . ' & ' . $thirdUser->name);
    });

    it('does not render conversations where the user is no longer present', function () {
        $owner = User::factory()->create();
        $firstUser = User::factory()->create();

        $this->actingAs($firstUser);

        $conversation = app(CreateConversation::class)->handle($owner, $firstUser);
        app(LeaveConversation::class)->handle($conversation, $firstUser);

        expect(livewire(ConversationManager::class)->instance()->conversations)
            ->toHaveCount(0);
    });

    it('can render conversation name', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        app(CreateConversation::class)->handle($owner, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText($otherUser->name);
    });

    it('can render conversation latest messages', function () {
        Carbon::setTestNow();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($owner, $otherUser);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.empty-state'));

        $firstMessage = app(SendMessage::class)->handle($conversation->owner, $conversation, [
            'content' => 'Test message',
        ]);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.current-user') . ': ' . $firstMessage->content);

        Carbon::setTestNow(now()->addMinutes(5));

        /* @var ConversationParticipation $otherParticipant */
        $otherParticipant = $conversation->otherParticipations()->first();

        $secondMessage = app(SendMessage::class)->handle($otherParticipant, $conversation, [
            'content' => 'Second test message',
        ]);

        livewire(ConversationManager::class)
            ->assertSeeText($otherUser->getAttributeValue($otherUser::getFilamentNameAttribute()) . ': ' . $secondMessage->content);
    });
});

describe('search', function () {
    it('can search conversations by participants', function () {
        $owner = User::factory()->state(['name' => 'owner'])->create();
        $firstUser = User::factory()->state(['name' => 'first'])->create();
        $secondUser = User::factory()->state(['name' => 'second'])->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $firstUser);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $secondUser);
        /* @var Conversation $groupConversation */
        $groupConversation = app(CreateConversation::class)->handle($owner, collect([$firstUser, $secondUser]));

        $primaryKey = (new Conversation)->getKeyName();
        $livewire = livewire(ConversationManager::class);

        $livewire->set('conversationListSearch', $firstUser->name);

        expect($livewire->instance()->conversations)
            ->toHaveCount(2)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey(), $groupConversation->getKey())
            ->not->toContain($secondUserConversation->getKey());

        $livewire->set('conversationListSearch', $secondUser->name);

        expect($livewire->instance()->conversations)
            ->toHaveCount(2)
            ->pluck($primaryKey)
            ->toContain($secondUserConversation->getKey(), $groupConversation->getKey())
            ->not->toContain($firstUserConversation->getKey());

        $livewire->set('conversationListSearch', '');

        expect($livewire->instance()->conversations)
            ->toHaveCount(3)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $groupConversation->getKey());
    });

    it('can search conversations by name or description', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($owner, $otherUser, [
            'name' => 'Group A',
            'description' => 'First group',
        ]);

        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($owner, $otherUser, [
            'name' => 'Group B',
            'description' => 'Second group',
        ]);

        $primaryKey = (new Conversation)->getKeyName();
        $livewire = livewire(ConversationManager::class);

        $livewire->set('conversationListSearch', 'Group A');

        expect($livewire->instance()->conversations)
            ->toHaveCount(1)
            ->pluck($primaryKey)
            ->toContain($firstUserConversation->getKey())
            ->not->toContain($secondUserConversation->getKey());

        $livewire->set('conversationListSearch', 'Second group');

        expect($livewire->instance()->conversations)
            ->toHaveCount(1)
            ->pluck($primaryKey)
            ->toContain($secondUserConversation->getKey())
            ->not->toContain($firstUserConversation->getKey());
    });
});



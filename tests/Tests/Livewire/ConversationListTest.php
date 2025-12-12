<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateDirectConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateGroupConversationAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

describe('render', function () {
    it('can render conversations', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $firstUser, ['type' => ConversationTypeEnum::DIRECT]);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $secondUser, ['type' => ConversationTypeEnum::DIRECT]);
        /* @var Conversation $groupConversation */
        $groupConversation = app(CreateConversation::class)->handle($creator, collect([$firstUser, $secondUser]), ['type' => ConversationTypeEnum::GROUP]);

        $livewire = livewire(ConversationManager::class);

        expect($livewire->instance()->conversations)
            ->toHaveCount(3)
            ->pluck((new Conversation)->getKeyName())
            ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $groupConversation->getKey())
            ->and($livewire)
            ->assertSeeText($firstUserConversation->getName())
            ->assertSeeText($secondUserConversation->getName())
            ->assertSeeText($groupConversation->getName());
    });

    it('can render conversation name', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($creator, $otherUser, ['type' => ConversationTypeEnum::DIRECT]);

        livewire(ConversationManager::class)
            ->assertSeeText($conversation->getName());
    });

    it('can render conversation latest messages', function () {
        Carbon::setTestNow();

        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $conversation */
        $conversation = app(CreateConversation::class)->handle($creator, $otherUser, ['type' => ConversationTypeEnum::DIRECT]);

        livewire(ConversationManager::class)
            ->assertSeeText(__('filament-converse::conversation-list.latest-message.empty-state'));

        $firstMessage = app(SendMessage::class)->handle($conversation->creator, $conversation, [
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
        $creator = User::factory()->state(['name' => 'creator'])->create();
        $firstUser = User::factory()->state(['name' => 'first'])->create();
        $secondUser = User::factory()->state(['name' => 'second'])->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $firstUser, ['type' => ConversationTypeEnum::DIRECT]);
        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $secondUser, ['type' => ConversationTypeEnum::DIRECT]);
        /* @var Conversation $groupConversation */
        $groupConversation = app(CreateConversation::class)->handle($creator, collect([$firstUser, $secondUser]), ['type' => ConversationTypeEnum::GROUP]);

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
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        /* @var Conversation $firstUserConversation */
        $firstUserConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::GROUP,
            'name' => 'Group A',
            'description' => 'First group',
        ]);

        /* @var Conversation $secondUserConversation */
        $secondUserConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::GROUP,
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

describe('actions', function () {
    it('can create a new direct conversation through action', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateDirectConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participant' => $otherUser->getKey(),
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->type->toBe(ConversationTypeEnum::DIRECT)
            ->name->toBeNull()
            ->description->toBeNull()
            ->image->toBeNull()
            ->participations->toHaveCount(2)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $otherUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('requires a selected participant to create a new direct conversation', function () {
        $this->actingAs(User::factory()->create());

        livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateDirectConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'))
            ->assertHasFormErrors(['participant' => 'required']);
    });

    it('can create a new group conversation through action', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateGroupConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->type->toBe(ConversationTypeEnum::GROUP)
            ->name->toBeNull()
            ->description->toBeNull()
            ->image->toBeNull()
            ->participations->toHaveCount(3)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('can create a new group conversation through action with additional data', function () {
        $creator = User::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($creator);

        $livewire = livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateGroupConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'), [
                'participants' => [$firstUser->getKey(), $secondUser->getKey()],
                'name' => 'Test conversation',
                'description' => 'Test description',
            ])
            ->assertHasNoErrors();

        /* @var Conversation $conversation */
        $conversation = Conversation::query()->first();

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->type->toBe(ConversationTypeEnum::GROUP)
            ->name->toBe('Test conversation')
            ->description->toBe('Test description')
            ->image->toBeNull()
            ->participations->toHaveCount(3)
            ->participations->pluck('participant_id')->toContain($creator->getKey(), $firstUser->getKey(), $secondUser->getKey())
            ->creator->participant->getKey()->toBe($creator->getKey())
            ->and($livewire->instance())
            ->conversations->toHaveCount(1)
            ->conversations->value((new Conversation)->getKeyName())->toBe($conversation->getKey())
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('requires selected participants to create a new group conversation', function () {
        $this->actingAs(User::factory()->create());

        livewire(ConversationManager::class)
            ->callAction(TestAction::make(CreateGroupConversationAction::getDefaultName())->schemaComponent('conversation_schema.conversation-list'))
            ->assertHasFormErrors(['participants' => 'required']);
    });
});

describe('filters', function () {});

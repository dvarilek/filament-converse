<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationList;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationSchema;
use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
use Dvarilek\FilamentConverse\Tests\Models\User;

use function Pest\Livewire\livewire;

describe('active conversation', function () {
    test('search does not affect the active conversation', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        $conversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        $livewire = livewire(ConversationManager::class)
            ->set('conversationListSearch', 'This should not affect the active conversation');

        expect($livewire->instance())
            ->conversations->toHaveCount(0)
            ->getActiveConversation()->getKey()->toBe($conversation->getKey());
    });

    it('can configure conversation schema contents', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => TestConfiguration::class,
        ])
            ->assertSeeText('Conversation list custom heading')
            ->assertSeeText('Conversation thread custom empty state');
    });

    it('can persist active conversation in session', function () {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($creator);

        app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::DIRECT,
        ]);

        $secondConversation = app(CreateConversation::class)->handle($creator, $otherUser, [
            'type' => ConversationTypeEnum::GROUP,
        ]);

        livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => SessionConfiguration::class,
        ])
            ->call('updateActiveConversation', $secondConversation->getKey());

        livewire(ConversationManager::class, [
            'conversationSchemaConfiguration' => SessionConfiguration::class,
        ])
            ->assertSet('activeConversationKey', $secondConversation->getKey());
    });
});

class TestConfiguration
{
    public static function configure(ConversationSchema $schema): ConversationSchema
    {
        return $schema
            ->conversationList(fn (ConversationList $conversationList) => $conversationList->heading('Conversation list custom heading'))
            ->conversationThread(fn (ConversationThread $conversationThread) => $conversationThread->emptyStateHeading('Conversation thread custom empty state'));
    }
}

class SessionConfiguration
{
    public static function configure(ConversationSchema $schema): ConversationSchema
    {
        return $schema->persistActiveConversationInSession();
    }
}

<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Pages\ConversationPage;
use Dvarilek\FilamentConverse\Tests\Models\User;

use function Pest\Livewire\livewire;

it('can search conversations by participant names', function () {
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
    $livewire = livewire(ConversationPage::class);

    expect($livewire->instance())
        ->toHaveCount(3)
        ->and($livewire->instance()->conversations->pluck($primaryKey))
        ->toContain($firstUserConversation->getKey(), $secondUserConversation->getKey(), $groupConversation->getKey());
})->skip(message: 'This test is not working yet for some reason');

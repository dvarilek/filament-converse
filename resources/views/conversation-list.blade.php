@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationList;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;

    $shouldShowConversationImage = $shouldShowConversationImage();
    $hasConversationImageClosure = $hasConversationImageClosure();
    $hasIsConversationUnreadClosure = $hasIsConversationUnreadClosure();

    /* @var Collection<int, Conversation> $conversations */
    $conversations = $getConversations();
    $activeConversation = $getActiveConversation();

    $headerActions = array_filter(
        $getChildComponents(ConversationList::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );
@endphp

<div
    class="fi-converse-conversation-list"
    x-show="!isBelowLg || showConversationListSidebar"
    x-on:click.away="isBelowLg && (showConversationListSidebar = false)"
>
    <div class="fi-converse-conversation-list-header">
        <div class="fi-converse-conversation-list-header-top">
            <div class="fi-converse-conversation-list-header-content">
                <div class="fi-converse-conversation-list-header-title">
                    <h2 class="fi-converse-conversation-list-header-heading">
                        {{ $getHeading() }}
                    </h2>
                    @if ($hasHeadingBadge() && filled($headingBadgeState = $getHeadingBadgeState()))
                        <x-filament::badge
                            :icon="$getHeadingBadgeIcon()"
                            :color="$getHeadingBadgeColor()"
                        >
                            {{ $headingBadgeState }}
                        </x-filament::badge>
                    @endif
                </div>
                @if (filled($description = $getDescription()))
                    <p class="fi-converse-conversation-list-header-description">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if (count($headerActions))
                <div class="fi-converse-conversation-list-header-actions">
                    @foreach ($headerActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        </div>

        <div class="fi-converse-conversation-list-header-bottom">
            @if ($isSearchable())
                @php
                    $searchPlaceholder = $getSearchPlaceholder();
                    $searchDebounce = $getSearchDebounce();
                    $searchOnBlur = $isSearchOnBlur();

                    $wireModelAttribute = $searchOnBlur ? 'wire:model.blur' : "wire:model.live.debounce.{$searchDebounce}";
                @endphp

                <div
                    x-id="['input']"
                    class="fi-converse-conversation-list-search-field"
                >
                    <label x-bind:for="$id('input')" class="fi-sr-only">
                        {{ __('filament-converse::conversation-list.search.label') }}
                    </label>

                    <x-filament::input.wrapper
                        inline-prefix
                        :prefix-icon="Heroicon::MagnifyingGlass"
                        wire:target="conversationListSearch"
                    >
                        <x-filament::input
                            :attributes="
                                (new ComponentAttributeBag)->merge([
                                    'autocomplete' => 'off',
                                    'inlinePrefix' => true,
                                    'maxlength' => 1000,
                                    'placeholder' => $searchPlaceholder,
                                    'type' => 'search',
                                    'wire:key' => $this->getId() . '.conversationListSearch.field.input',
                                    $wireModelAttribute => 'conversationListSearch',
                                    'x-bind:id' => '$id(\'input\')',
                                    'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                                ], escape: false)
                            "
                        />
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div class="fi-converse-conversation-list-header-bottom-actions">
                <x-filament::badge>All</x-filament::badge>
                <x-filament::badge>Direct</x-filament::badge>
                <x-filament::badge>Group</x-filament::badge>
            </div>
        </div>
    </div>

    <ul class="fi-converse-conversation-area">
        @forelse ($conversations as $conversation)
            @php
                $conversationName = $getConversationName($conversation);
                $conversationKey = $conversation->getKey();

                /* @var Message $latestMessage */
                $latestMessage = $conversation
                    ->participations
                    ->pluck('latestMessage')
                    ->filter()
                    ->sortByDesc('created_at')
                    ->first();
            @endphp

            <li
                wire:click="updateActiveConversation('{{ $conversationKey }}')"
                @class([
                    'fi-converse-conversation-list-item-active' => $conversationKey === $activeConversation?->getKey(),
                    'fi-converse-conversation-list-item-unread' => $hasIsConversationUnreadClosure && $isConversationUnread($conversation),
                    'fi-converse-conversation-list-item',
                ])
            >
                @if ($shouldShowConversationImage)
                    <x-filament-converse::conversation-image
                        :conversation="$conversation"
                        :conversation-name="$conversationName"
                        :conversation-image="$hasConversationImageClosure ? $getConversationImage($conversation) : null"
                    />
                @endif

                <div class="fi-converse-conversation-list-item-content">
                    <div class="fi-converse-conversation-list-item-title">
                        <h4 class="fi-converse-conversation-list-item-heading">
                            {{ $conversationName }}
                        </h4>

                        @if ($latestMessage)
                            <p
                                class="fi-converse-conversation-list-item-time-indicator"
                            >
                                {{ $getLatestMessageDateTime($conversation, $latestMessage) }}
                            </p>
                        @endif
                    </div>
                    <p
                        class="fi-converse-conversation-list-item-last-message-description"
                    >
                        @if ($latestMessage)
                            {{ $getLatestMessageContent($conversation, $latestMessage) }}
                        @else
                            {{ __('filament-converse::conversation-list.last-message.empty-state') }}
                        @endif
                    </p>
                </div>
            </li>
        @empty
            @if ($emptyState = $getEmptyState())
                {{ $emptyState }}
            @else
                <x-filament::empty-state
                    :icon="$getEmptyStateIcon()"
                    :icon-color="$getEmptyStateIconColor()"
                >
                    <x-slot name="heading">
                        {{ $getEmptyStateHeading() }}
                    </x-slot>
                    <x-slot name="description">
                        {{ $getEmptyStateDescription() }}
                    </x-slot>
                </x-filament::empty-state>
            @endif
        @endforelse
    </ul>
</div>

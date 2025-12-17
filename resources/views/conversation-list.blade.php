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

    /* @var Collection<int, Conversation> $conversations */
    $conversations = $getConversations();
    $totalConversationsCount = $this->getBaseFilteredConversationsQuery()->count();
    $activeConversation = $getActiveConversation();

    /* @var ComponentAttributeBag $extraConversationAttributeBag */
    $extraConversationAttributeBag = $getExtraConversationAttributeBag();

    $headerActions = array_filter(
        $getChildComponents(ConversationList::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );
@endphp

<div
    x-show="!isBelowLg || showConversationListSidebar"
    x-on:click.away="showConversationListSidebar = false"
    x-data="{
        isLoadingMoreConversations: false,

        async loadMoreConversations() {
            this.isLoadingMoreConversations = true

            try {
                await $wire.call('incrementConversationListPage')
            } finally {
                this.isLoadingMoreConversations = false
            }
        },
    }"
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-converse-conversation-list',
            ])
    }}
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

            <x-filament::icon-button
                color="gray"
                x-show="isBelowLg"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                icon-size="lg"
                :label="__('filament-converse::conversation-list.list-close-button-label')"
                tabindex="-1"
                x-on:click="showConversationListSidebar = false"
                class="fi-converse-conversation-list-header-close-button"
            />

            @if (count($headerActions))
                <div
                    class="fi-converse-conversation-list-header-actions"
                    x-bind:class="{ 'fi-converse-actions-full-width': isBelowLg }"
                >
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
        @if (count($conversations))
            @foreach ($conversations as $conversation)
                @php
                    /* @var ?Message $latestMessage */
                    $latestMessage = $getLatestMessage($conversation);
                    $conversationName = $getConversationName($conversation);
                    $showConversationImage = $shouldShowConversationImage($conversation);
                    $unreadMessagesCount = $getUnreadMessagesCount($conversation);
                    $conversationKey = $conversation->getKey();
                @endphp

                <li
                    wire:key="fi-converse-conversation-list-item-{{ $this->getId() }}-{{ $conversationKey }}-{{ $unreadMessagesCount }}"
                    wire:loading.attr="disabled"
                    x-data="{ unreadMessagesCount: {{ $unreadMessagesCount }} }"
                    x-on:click="
                        await $wire.call('updateActiveConversation', '{{ $conversationKey }}')
                        showConversationListSidebar = false
                    "
                    x-on:filament-converse-conversation-read.window="
                        if ($event.detail.conversationKey === '{{ $conversationKey }}') {
                            unreadMessagesCount = 0
                        }
                    "
                    {{
                        $extraConversationAttributeBag
                            ->class([
                                'fi-converse-conversation-list-item-active' => $conversationKey === $activeConversation?->getKey(),
                                'fi-converse-conversation-list-item',
                            ])
                            ->merge([
                                'x-bind:class' => "{ 'fi-converse-conversation-list-item-unread': unreadMessagesCount > 0 }",
                            ])
                    }}
                >
                    @if ($showConversationImage)
                        <x-filament-converse::conversation-image
                            :conversation="$conversation"
                            :conversation-name="$conversationName"
                            :conversation-image-url="$getConversationImageUrl($conversation)"
                            :get-default-conversation-image-data="$getDefaultConversationImageData"
                        />
                    @endif

                    <div class="fi-converse-conversation-list-item-content">
                        <div class="fi-converse-conversation-list-item-header">
                            <h4
                                class="fi-converse-conversation-list-item-heading"
                            >
                                {{ $conversationName }}
                            </h4>

                            @if ($latestMessage)
                                <p
                                    class="fi-converse-conversation-list-item-time-indicator"
                                >
                                    {{ $getLatestMessageDateTime($latestMessage, $conversation) }}
                                </p>
                            @endif
                        </div>

                        <div class="fi-converse-conversation-list-item-footer">
                            <p
                                class="fi-converse-conversation-list-item-last-message-description"
                            >
                                @if ($latestMessage)
                                    {{ $getLatestMessageContent($latestMessage, $conversation) }}
                                @else
                                    {{ $getLatestMessageEmptyContent($conversation) }}
                                @endif
                            </p>

                            @if ($unreadMessagesCount)
                                <x-filament::badge
                                    x-show="unreadMessagesCount > 0"
                                    :icon="$getUnreadMessagesBadgeIcon($latestMessage, $conversation)"
                                    :color="$getUnreadMessagesBadgeColor($latestMessage, $conversation)"
                                    size="sm"
                                >
                                    {{ $unreadMessagesCount > 100 ? '99+' : $unreadMessagesCount }}
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach

            @if ($totalConversationsCount && $totalConversationsCount > count($conversations))
                <div
                    class="fi-converse-conversation-list-load-more-messages-indicator-container"
                >
                    <div
                        x-intersect="loadMoreConversations()"
                        aria-hidden="true"
                    ></div>
                    <div
                        x-cloak
                        x-show="isLoadingMoreConversations"
                        class="fi-converse-conversation-list-load-more-messages-indicator"
                    >
                        {{ \Filament\Support\generate_loading_indicator_html() }}
                    </div>
                </div>
            @endif
        @else
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
        @endif
    </ul>
</div>

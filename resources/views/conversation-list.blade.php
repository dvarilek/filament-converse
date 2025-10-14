@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Illuminate\Contracts\Auth\Authenticatable;
    use Illuminate\Database\Eloquent\Model;

    $filament = filament();
    // TODO:
    $searchDebounce = '500ms';
    $searchOnBlur = true;
    $searchPlaceholder = '';

    $activeConversationKey = '';
    $conversationModelPrimaryKey = (new Conversation)->getKeyName();
@endphp

<div class="fi-converse-conversation-list">
    <div class="fi-converse-conversation-list-header">
        <div class="fi-converse-conversation-list-header-top">
            <div class="fi-converse-conversation-list-header-content">
                <div class="fi-converse-conversation-list-header-title">
                    <h2 class="fi-converse-conversation-list-header-heading">
                        Conversations
                    </h2>
                    <x-filament::badge>
                        12
                    </x-filament::badge>
                </div>
                <p class="fi-converse-conversation-list-header-description">
                    Some description
                </p>
            </div>

            {{ $this->createConversation }}
        </div>

        <div class="fi-converse-conversation-list-header-bottom">
            <x-filament-tables::search-field
                :debounce="$searchDebounce"
                :on-blur="$searchOnBlur"
                :placeholder="$searchPlaceholder"
            />
            <div class="fi-converse-conversation-list-header-bottom-actions">
                <x-filament::badge>
                    Action1
                </x-filament::badge>
                <x-filament::badge>
                    Action2
                </x-filament::badge>
                <x-filament::badge>
                    Action3
                </x-filament::badge>
            </div>
        </div>
    </div>

    <ul class="fi-converse-conversation-area">
        @foreach($this->conversations as $conversation)
            <li
                @class([
                    'fi-converse-conversation-list-item-active' => $conversation[$conversationModelPrimaryKey] === $activeConversationKey,
                    'fi-converse-conversation-list-item-unread' => false,
                    'fi-converse-conversation-list-overflow' => false,
                    'fi-converse-conversation-list-item'
                ])
            >
                @php
                    /* @var Authenticatable & Model $conversationAuthor */
                    $conversationAuthor = $conversation->createdBy->participant;
                    $conversationName = $conversation->name ?? "Conversation name";
                @endphp

                <x-filament::avatar
                    class="fi-ta-converse-conversation-list-item-avatar"
                    :src="$filament->getUserAvatarUrl($conversationAuthor)"
                    :alt="$conversationName"
                    size="lg"
                />

                <div class="fi-ta-converse-conversation-list-item-content">
                    <div class="fi-ta-converse-conversation-list-item-title">
                        <h4 class="fi-ta-converse-conversation-list-item-heading">
                            {{ $conversationName }}
                        </h4>
                        <div class="fi-ta-converse-conversation-list-item-indicator">
                            <p class="fi-ta-converse-conversation-list-item-last-message-time-indicator">
                                Time
                            </p>
                            <x-filament::badge>
                                +2
                            </x-filament::badge>
                        </div>
                    </div>
                    <p class="fi-ta-converse-conversation-list-item-last-message-description">
                        Someone: Long message that is very very very very very long long long
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
</div>

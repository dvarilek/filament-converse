@php
    use Illuminate\Support\Collection;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Models\Conversation;

    $conversation = Conversation::query()->first();

    /* @var Collection<int, Message> */
    $messages = $conversation->messages->load('author.participant');
@endphp

<div class="fi-converse-conversation-thread">
    <div class="fi-converse-conversation-thread-header">
        <div class="fi-converse-conversation-thread-header-avatar">
            TODO
        </div>

        <h2 class="fi-converse-conversation-thread-header-heading">
            {{ $conversation->getName() }}
        </h2>
    </div>
    <div class="fi-converse-conversation-message-area">
        <div class="fi-converse-conversation-thread-message-box">
            @forelse($messages as $index => $message)
                @php
                    $messageContent = $message->content;
                    $messageAuthor = $message->author->participant;
                    $messageAuthorName = $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute());

                    $isAuthoredByAuthenticatedUser = $messageAuthor->getKey() === auth()->id();

                    // NOTE: Actually might not be needed
                    $isNextMessageFromCurrentAuthor = ($messages[$index + 1] ?? null)?->author?->getKey() === $messageAuthor->getKey();
                @endphp

                <div
                    @class([
                        "fi-converse-conversation-thread-message-container-reversed" => $isAuthoredByAuthenticatedUser,
                        "fi-converse-conversation-thread-message-container"
                    ])>
                    <x-filament::avatar
                        class="fi-converse-conversation-thread-message-avatar"
                        :src="filament()->getUserAvatarUrl($messageAuthor)"
                        :alt="$messageAuthorName"
                        size="md"
                    />

                    <div class="fi-converse-conversation-thread-message-content">
                        <div class="fi-converse-conversation-thread-message-details">
                            @if (!$isAuthoredByAuthenticatedUser)
                                <div class="fi-converse-conversation-thread-message-author">
                                    {{ $messageAuthorName }}
                                </div>
                            @endif

                            <div class="fi-converse-conversation-thread-message-time">
                                {{ $message->created_at }}
                            </div>
                        </div>

                        <div class="fi-converse-conversation-thread-message-body">
                            <div class="fi-converse-conversation-thread-message">
                                {{ $messageContent }}
                            </div>

                            Actions
                        </div>
                    </div>
                </div>
            @empty

            @endforelse
        </div>
    </div>
    <div class="fi-converse-conversation-thread-message-input">
        Send message
    </div>
</div>

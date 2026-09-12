@props([
    'conversation',
    'conversationName',
    'conversationImageUrl' => null,
    'getDefaultConversationImageData',
])

@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\ConversationParticipation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Illuminate\Support\Collection;

    /* @var Conversation $conversation */
@endphp

@if ($conversationImageUrl)
    <div class="fi-converse-conversation-image-container">
        <x-filament::avatar
            class="fi-converse-conversation-image-image"
            :src="$conversationImageUrl"
            :alt="$conversationName"
            size="lg"
        />
    </div>
@else
    @php
        $conversationImageData = $getDefaultConversationImageData($conversation);
        $hasMultipleAvatarsInConversationImage = count($conversationImageData) === 2;
    @endphp

    <div
        @class([
            'fi-converse-conversation-image-multiple-avatars' => $hasMultipleAvatarsInConversationImage,
            'fi-converse-conversation-image-container',
        ])
    >
        @if ($hasMultipleAvatarsInConversationImage)
            @php
                $topAvatarParticipant = $conversationImageData[0] ?? [];
                $bottomAvatarParticipant = $conversationImageData[1] ?? [];
            @endphp

            <x-filament::avatar
                class="fi-converse-conversation-image-image fi-converse-conversation-image-top-avatar"
                :src="$topAvatarParticipant['source'] ?? null"
                :alt="$topAvatarParticipant['alt'] ?? null"
                size="md"
            />
            <x-filament::avatar
                color="primary"
                class="fi-converse-conversation-image-image fi-converse-conversation-image-bottom-avatar"
                :src="$bottomAvatarParticipant['source'] ?? null"
                :alt="$bottomAvatarParticipant['alt'] ?? null"
                size="md"
            />
        @else
            @php
                $otherParticipant = $conversationImageData[0] ?? [];
            @endphp

            <x-filament::avatar
                class="fi-converse-conversation-image-image"
                :src="$otherParticipant['source'] ?? null"
                :alt="$otherParticipant['alt'] ?? null"
                size="lg"
            />
        @endif
    </div>
@endif

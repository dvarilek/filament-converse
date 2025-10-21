@props([
    'conversation',
    'conversationName',
    'conversationImage' => null,
])

@php


@endphp

@if ($conversationImage)
    <div
        class="fi-converse-conversation-list-item-image-wrapper"
    >
        <x-filament::avatar
            class="fi-converse-conversation-list-item-image"
            :src="$conversationImage"
            :alt="$conversationName"
            size="lg"
        />
    </div>
@else
    @php
        $otherParticipations = $conversation->otherParticipations()->;
        $hasMultipleAvatarsInConversationImage = $conversation->isGroup() || $otherParticipations->count() >= 2;
    @endphp

    <divh
        @class([
            'fi-converse-conversation-list-item-multiple-avatars' => $hasMultipleAvatarsInConversationImage,
            'fi-converse-conversation-list-item-image-wrapper',
        ])
    >
        @if ($hasMultipleAvatarsInConversationImage)
            @php
                $latestParticipant = null;
                $penultimateParticipant = null;
            @endphp

            <x-filament::avatar
                class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-penultimate-avatar"
                :src="filament()->getUserAvatarUrl($penultimateParticipant)"
                :alt="$penultimateParticipant->getAttribute($penultimateParticipant::getFilamentNameAttribute())"
                size="md"
            />
            <x-filament::avatar
                color="primary"
                class="fi-converse-conversation-list-item-image fi-converse-conversation-list-item-last-avatar"
                :src="filament()->getUserAvatarUrl($latestParticipant)"
                :alt="$latestParticipant->getAttribute($latestParticipant::getFilamentNameAttribute())"
                size="md"
            />
        @else
            <x-filament::avatar
                class="fi-converse-conversation-list-item-image"
                :src="filament()->getUserAvatarUrl($otherParticipations->first()->participant)"
                :alt="$conversationName"
                size="lg"
            />
        @endif
    </divh>
@endif

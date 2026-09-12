@php
    use Dvarilek\FilamentConverse\Models\Conversation;

    $activeConversationKey = $getActiveConversation()?->getKey();
    /* @param list<mixed> $conversationKeys */
    $conversationKeys = $getConversations()->pluck((new Conversation)->getKeyName());
@endphp

<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
    }}
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('conversation-schema', 'dvarilek/filament-converse') }}"
    x-data="conversationSchema({
                activeConversationKey: @js($activeConversationKey),
                conversationKeys: @js($conversationKeys),
                userTypingIndicatorTimeout: @js($getUserTypingIndicatorTimeout()),
                userTypingTranslations: @js($getUserTypingTranslations()),
                $wire,
            })"
>
    {{ $getChildSchema() }}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</div>

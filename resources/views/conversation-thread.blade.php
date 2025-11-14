@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Illuminate\Support\Collection;
    use Illuminate\View\ComponentAttributeBag;
    use Filament\Support\View\Components\ModalComponent\IconComponent;
    use Filament\Support\Icons\Heroicon;

    $key = $getKey();
    $statePath = $getStatePath();
    $hasFileAttachments = $hasFileAttachments();

    /* @var Conversation | null $conversation */
    $conversation = $getActiveConversation();

    /* @var Collection<int, Message> */
    $messages = $conversation?->participations?->flatMap?->messages?->sortBy('created_at') ?? []; // temp

    $headerActions = array_filter(
        $getChildComponents(ConversationThread::HEADER_ACTIONS_KEY),
        static fn (Action | ActionGroup $action) => $action->isVisible()
    );

    $messageActions = $getChildComponents(ConversationThread::MESSAGE_ACTIONS_KEY);
@endphp

<div
    class="fi-converse-conversation-thread"

    @if ($hasFileAttachments)
        @php
            $attachmentModalIconColor = $getAttachmentModalIconColor();
            $attachmentModalIcon = $getAttachmentModalIcon();
            $attachmentModalHeading = $getAttachmentModalHeading();
            $attachmentModalDescription = $getAttachmentModalDescription();

            $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
            $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
        @endphp
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('conversation-thread', 'dvarilek/filament-converse') }}"
        x-data="conversationThread({
            statePath: @js($statePath),
            componentKey: @js($key),
            fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
            fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),
            fileAttachmentsAcceptedFileTypesMessage: @js($getAttachmentsAcceptedFileTypesErrorMessage($fileAttachmentsAcceptedFileTypes)),
            fileAttachmentsMaxSizeMessage: @js($getAttachmentsMaxFileSizeErrorMessage($fileAttachmentsMaxSize)),
            $wire
        })"
        x-bind:class="{'fi-converse-conversation-thread-attachment-dragging-active': isDraggingOver}"
    @endif
>

    @if ($hasFileAttachments)
        <div
            x-cloak
            x-show="isDraggingOver"
            class="fi-converse-attachment-modal-overlay"
        >
            <div class="fi-converse-attachment-modal-backdrop"></div>

            <div class="fi-converse-attachment-modal">
                <div class="fi-converse-attachment-modal-header">
                    <div
                        {{ (new ComponentAttributeBag)->color(IconComponent::class, $attachmentModalIconColor, 'primary')->class(['fi-converse-attachment-modal-icon-bg']) }}
                    >
                        {{ \Filament\Support\generate_icon_html($attachmentModalIcon, size: \Filament\Support\Enums\IconSize::Large) }}
                    </div>
                </div>
                <div class="fi-converse-attachment-modal-content">
                    <h2 class="fi-converse-attachment-modal-heading">
                        {{ $attachmentModalHeading }}
                    </h2>
                    @if (filled($attachmentModalDescription))
                        <p class="fi-converse-attachment-modal-description">
                            {{ $attachmentModalDescription }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="fi-converse-conversation-thread-header">
        @if ($conversation)
            @php
                $conversationName = $getConversationName($conversation);
            @endphp

            <div class="fi-converse-conversation-thread-header-content">
                <x-filament::icon-button
                    x-cloak
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                    class="fi-converse-conversation-thread-header-expand-list-button"
                    icon-size="lg"
                    x-on:click="showConversationListSidebar = !showConversationListSidebar"
                />

                <x-filament-converse::conversation-image
                    :conversation="$conversation"
                    :conversation-name="$conversationName"
                    :conversation-image-url="$getConversationImageUrl($conversation)"
                    :get-default-conversation-image-data="$getDefaultConversationImageData"
                />

                <h2 class="fi-converse-conversation-thread-header-heading">
                    {{ $conversationName }}
                </h2>
            </div>

            @if (count($headerActions))
                <div class="fi-converse-conversation-thread-header-actions">
                    @foreach ($headerActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @if($hasFileAttachments)
        <div
            x-cloak
            x-show="fileAttachmentUploadFailureMessage"
            class="fi-converse-conversation-thread-upload-failute-message-container"
        >
            <p x-text="fileAttachmentUploadFailureMessage" class="fi-converse-conversation-thread-upload-failute-message">

            </p>
        </div>
    @endif

    <div class="fi-converse-conversation-thread-message-box">
        @forelse ($messages as $message)
            @php
                $messageAuthor = $message->author->participant;
                $messageAuthorName = $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute());

                $isAuthoredByAuthenticatedUser = $messageAuthor->getKey() === auth()->id();
            @endphp

            <div
                @class([
                    'fi-converse-conversation-thread-message-container-reversed' => $isAuthoredByAuthenticatedUser,
                    'fi-converse-conversation-thread-message-container group',
                ])
            >
                @if (! $isAuthoredByAuthenticatedUser)
                    <x-filament::avatar
                        class="fi-converse-conversation-thread-message-avatar"
                        :src="filament()->getUserAvatarUrl($messageAuthor)"
                        :alt="$messageAuthorName"
                        size="md"
                    />
                @endif

                <div class="fi-converse-conversation-thread-message-content">
                    <div
                        class="fi-converse-conversation-thread-message-details"
                    >
                        @if (! $isAuthoredByAuthenticatedUser)
                            <div
                                class="fi-converse-conversation-thread-message-author"
                            >
                                {{ $messageAuthorName }}
                            </div>
                        @endif

                        <div
                            class="fi-converse-conversation-thread-message-time"
                        >
                            {{ $message->created_at }}
                        </div>
                    </div>

                    <div class="fi-converse-conversation-thread-message-body">
                        <div class="fi-converse-conversation-thread-message">
                            {{ $message->content }}
                        </div>

                        @php
                            $filteredMessageActions = array_filter(
                                $messageActions,
                                static function (Action | ActionGroup $action) use ($message) {
                                    $action->record($message)->arguments(['record' => $message->getKey()]);

                                    return $action->isVisible();
                                }
                            )
                        @endphp

                        @if (count($filteredMessageActions))
                            <div
                                class="fi-converse-conversation-thread-message-actions"
                            >
                                @foreach ($filteredMessageActions as $action)
                                    {{ $action }}
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            @if ($emptyState = $getEmptyState())
                {{ $emptyState }}
            @else
                <x-filament::empty-state
                    :icon="$getEmptyStateIcon()"
                    :icon-color="$getEmptyStateIconColor()"
                    class="fi-converse-conversation-thread-empty-state"
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
    </div>
    @if ($conversation)
        @php
            $messageInputField = $getChildComponents(ConversationThread::MESSAGE_INPUT_FIELD_KEY)[0] ?? null;
        @endphp

        @if ($messageInputField && $messageInputField->isVisible())
            <div class="fi-converse-conversation-thread-message-input-container">
                {{ $messageInputField }}
            </div>
        @endif
    @endif
</div>

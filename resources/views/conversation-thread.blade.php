@php
    use Dvarilek\FilamentConverse\Models\Conversation;
    use Dvarilek\FilamentConverse\Models\Message;
    use Dvarilek\FilamentConverse\Schemas\Components\ConversationThread;
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Illuminate\Support\Collection;

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
            $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
            $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
            $fileAttachmentsAcceptedFileTypesMessage = __('filament-forms::components.markdown_editor.file_attachments_accepted_file_types_message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]);
            $fileAttachmentsMaxSizeMessage = trans_choice('filament-forms::components.markdown_editor.file_attachments_max_size_message', $fileAttachmentsMaxSize, ['max' => $fileAttachmentsMaxSize]);

            // TODO: Add methods for this, handle upload, loading, failure, add modal content, + configuration methods, refactor into overlay
        @endphp
        x-bind:class="{'fi-converse-conversation-thread-attachment-dragging-active': isDraggingOver}"
        x-data="{
            isDraggingOver: false,

            statePath: @js($statePath),

            key: @js($key),

            fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),

            fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),

            fileAttachmentsAcceptedFileTypesMessage: @js($fileAttachmentsAcceptedFileTypesMessage),

            fileAttachmentsMaxSizeMessage: @js($fileAttachmentsMaxSizeMessage),

            init() {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName =>
                    this.$el.addEventListener(eventName, eventName => {
                        event.preventDefault();
                        event.stopPropagation();
                    }, false)
                );

                ['dragenter', 'dragover'].forEach(eventName => {
                    this.$el.addEventListener(eventName, () => {
                        this.isDraggingOver = true;
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    this.$el.addEventListener(eventName, event => {
                        if (!this.$el.contains(event.relatedTarget)) {
                            this.isDraggingOver = false;
                        }
                    }, false);
                });

                this.$el.addEventListener('drop', event => {
                    this.isDraggingOver = false;

                    const files = Array.from((event.dataTransfer && event.dataTransfer.files) || []);
                    if (files.length === 0) return;

                    files.forEach(this.handleUpload);
                }, false);
            },

            handleUpload(file) {
                if (this.fileAttachmentAcceptedFileTypes && !this.fileAttachmentAcceptedFileTypes.includes(file.type)) {
                    return this.onError(this.fileAttachmentAcceptedFileTypes ? this.fileAttachmentsAcceptedFileTypesMessage : null);
                }

                if (this.fileAttachmentMaxSize && file.size > +this.fileAttachmentMaxSize * 1024) {
                    return this.onError(this.fileAttachmentMaxSize ? this.fileAttachmentsMaxSizeMessage : null);
                }

                $wire.upload('componentFileAttachments.' + this.statePath, file, () => {
                    $wire
                        .callSchemaComponentMethod(
                            this.key,
                            'saveUploadedFileAttachmentAndGetUrl',
                        )
                        .then((url) => {
                            if (!url) {
                                return this.onError();
                            }

                            this.onSuccess(url);
                        });
                });
            },

            onError() {
                console.error('Upload error');
            },

            onSuccess(url) {
                console.log('Upload success:', url);
            }
        }"
    @endif
>

    @if ($hasFileAttachments)
        <div
            x-cloak
            x-show="isDraggingOver"
            class="fi-converse-conversation-thread-attachment-modal"
        >
            <div>
                ICON
            </div>
            <div>
                <h4>
                    HEADING
                </h4>
                <p>
                    DESCRIPTION
                </p>
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

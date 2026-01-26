@php
    use Filament\Schemas\Components\Icon;
    use Illuminate\View\ComponentAttributeBag;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
    use Filament\Support\View\Components\ModalComponent\IconComponent;

    $id = $getId();
    $key = $getKey();
    $statePath = $getStatePath();


    /* @var list<TemporaryUploadedFile> $uploadedFileAttachments */
    $uploadedFileAttachments = $getUploadedFileAttachments();
    $activeConversationKey = $getActiveConversation()?->getKey();

    $fileAttachmentAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
    $fileAttachmentMaxSize = $getFileAttachmentsMaxSize();
    $maxFileAttachments = $getMaxFileAttachments();
    $acceptedFileTypesValidationMessage = $getAttachmentsAcceptedFileTypesValidationMessage($fileAttachmentAcceptedFileTypes);
    $maxFileSizeValidationMessage = $getAttachmentsMaxFileSizeValidationMessage($fileAttachmentMaxSize);
    $maxFileAttachmentsValidationMessage = $getMaxFileAttachmentsValidationMessage();

        /*
         *   fileAttachmentAcceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                    fileAttachmentMaxSize: @js($fileAttachmentsMaxSize),


                    maxFileAttachments: @js($maxFileAttachments),
                    acceptedFileTypesValidationMessage: @js($getAttachmentsAcceptedFileTypesValidationMessage($fileAttachmentsAcceptedFileTypes)),
                    maxFileSizeValidationMessage: @js($getAttachmentsMaxFileSizeValidationMessage($fileAttachmentsMaxSize)),
                    maxFileAttachmentsValidationMessage: @js($getMaxFileAttachmentsValidationMessage($maxFileAttachments)),
         */
@endphp
<div
    wire:key="fi-converse-conversation-thread-attachment-area-{{ $id }}-{{ $key }}-{{ count($uploadedFileAttachments) }}"
    class="fi-converse-attachment-area"
    x-data="{
        attachmentDropZoneRef: @js($getAttachmentDropZoneRef()),

        isDraggingFileAttachment: false,

        uploadingFileAttachments: [],

        fileAttachmentUploadValidationMessage: null,

        init() {
            this.registerFileAttachmentUploadEventListeners()
        },

        registerFileAttachmentUploadEventListeners() {
            const element = this.$refs[this.attachmentDropZoneRef]

            if (! element) {
                console.warn('Dropzone ref ' + this.attachmentDropZoneRef + ' not found.')
                return
            }

            new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (eventName) =>
                    element.addEventListener(
                        eventName,
                        (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                        },
                        false,
                    ),
            )

            new Set(['dragenter', 'dragover']).forEach((eventName) => {
                element.addEventListener(
                    eventName,
                    () => {
                        this.isDraggingFileAttachment = true
                    },
                    false,
                )
            })

            element.addEventListener('dragleave', (event) => {
                if (!element.contains(event.relatedTarget)) {
                    this.isDraggingFileAttachment = false
                }
            })

            element.addEventListener('drop', async (event) => {
                this.isDraggingFileAttachment = false

                await this.handleAttachmentUpload(
                    (event.dataTransfer && event.dataTransfer.files) || [],
                )
            })
        },

        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
        },

        async handleAttachmentUpload(files) {
            if (!files.length) {
                return
            }

            this.fileAttachmentUploadValidationMessage = null
            let fileAttachmentUploadValidationMessage = null

            if (maxFileAttachments) {
                const uploadedFileAttachmentsCount = Array.from(
                    (await $wire.get(
                        'componentFileAttachments.' + statePath,
                    )) || [],
                ).length

                if (
                    uploadedFileAttachmentsCount + files.length >
                    maxFileAttachments
                ) {
                    this.fileAttachmentUploadValidationMessage =
                        maxFileAttachmentsValidationMessage

                    return
                }
            }

            const validFiles = Array.from(files).filter((file) => {
                if (
                    fileAttachmentAcceptedFileTypes &&
                    !fileAttachmentAcceptedFileTypes.includes(file.type)
                ) {
                    fileAttachmentUploadValidationMessage =
                        acceptedFileTypesValidationMessage

                    return false
                }

                if (
                    fileAttachmentMaxSize &&
                    file.size > +fileAttachmentMaxSize * 1024
                ) {
                    fileAttachmentUploadValidationMessage =
                        maxFileSizeValidationMessage

                    return false
                }

                return true
            })

            if (fileAttachmentUploadValidationMessage) {
                this.fileAttachmentUploadValidationMessage =
                    fileAttachmentUploadValidationMessage
            }

            if (validFiles.length === 0) {
                return
            }

            this.uploadingFileAttachments.push(...validFiles)

            await $wire.uploadMultiple(
                'componentFileAttachments.' + statePath + '.' + conversationKey,
                validFiles,
                () => (this.uploadingFileAttachments = []),
                () => (this.uploadingFileAttachments = []),
            )
        },
    }"
    x-bind:class="{
        'fi-converse-attachment-area-has-content': isUploadingFileAttachment() || @js(count($uploadedFileAttachments) > 0),
        'fi-converse-is-dragging-attachment': isDraggingFileAttachment
    }"
    x-on:filament-converse-trigger-file-input.window="$refs.fileInput.click()"
>
    <input
        type="file"
        @if (count($uploadedFileAttachments) !== 1)
            multiple
        @endif
        x-ref="fileInput"
        class="hidden"
        x-on:change="handleAttachmentUpload($event.target.files)"
    />

    <div
        x-cloak
        x-show="isDraggingFileAttachment"
        class="fi-converse-attachment-modal-overlay"
    >
        <div class="fi-converse-attachment-modal-backdrop"></div>

        <div
            class="fi-converse-attachment-modal"
        >
            <div class="fi-converse-attachment-modal-header">
                <div
                    {{ (new ComponentAttributeBag)->color(IconComponent::class, $getAttachmentModalIconColor(), 'primary')->class(['fi-converse-attachment-modal-icon-bg']) }}
                >
                    {{ \Filament\Support\generate_icon_html($getAttachmentModalIcon(), size: \Filament\Support\Enums\IconSize::Large) }}
                </div>
            </div>
            <div class="fi-converse-attachment-modal-content">
                <h2 class="fi-converse-attachment-modal-heading">
                    {{ $getAttachmentModalHeading() }}
                </h2>
                @if (filled($attachmentModalDescription = $getAttachmentModalDescription()))
                    <p class="fi-converse-attachment-modal-description">
                        {{ $attachmentModalDescription }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <template
        x-for="file in uploadingFileAttachments.reverse()"
    >
        <div
            x-bind:class="
                file.type.startsWith('image/')
                    ? 'fi-converse-image-attachment-container'
                    : 'fi-converse-generic-attachment-container fi-converse-attachment-adaptable-width'
            "
        >
            <div
                x-cloak
                x-show="file.type.startsWith('image/')"
                class="fi-converse-image-attachment-skeleton"
            >
                {{ Icon::make(\Filament\Support\Icons\Heroicon::OutlinedPhoto)->color('gray')->extraAttributes(['class' => 'fi-size-2xl']) }}
            </div>

            <div
                x-cloak
                x-show="! file.type.startsWith('image/')"
                class="fi-converse-generic-attachment-skeleton"
            ></div>

            <div
                x-cloak
                x-show="! file.type.startsWith('image/')"
                class="fi-converse-attachment-information-container-skeleton"
            >
                <div
                    class="fi-converse-attachment-name-skeleton"
                ></div>
                <div
                    class="fi-converse-attachment-mime-type-badge-skeleton"
                ></div>
            </div>
        </div>
    </template>

    @foreach ($uploadedFileAttachments as $fileAttachment)
        @php
            $hasImageMimeType = $isImageMimeType($fileAttachment->getMimeType());

            $attachmentPath = $fileAttachment->getPath();
            $attachmentOriginalName = $fileAttachment->getClientOriginalName();
            $attachmentMimeType = $fileAttachment->getMimeType();
            $data = ['fileAttachment' => $fileAttachment];

        @endphp

        <x-filament-converse::conversation-attachment
            :has-image-mime-type="$hasImageMimeType"
            :file-attachment-name="$getFileAttachmentName($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :file-attachment-toolbar="$getFileAttachmentToolbar($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :should-show-only-image-attachment="$shouldShowOnlyImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :file-attachment-url="$hasImageMimeType ? $fileAttachment->temporaryUrl() : null"
            :should-preview-image-attachment="$shouldPreviewImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :file-attachment-icon="$getFileAttachmentIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :mime-type-badge-label="$getFileAttachmentMimeTypeBadgeLabel($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :mime-type-badge-icon="$getFileAttachmentMimeTypeBadgeIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :mime-type-badge-color="$getFileAttachmentMimeTypeBadgeColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $data)"
            :is-removable="true"
            file-attachment-remove-handler="$wire.removeUpload('componentFileAttachments.{{ $statePath }}.{{ $activeConversationKey }}', '{{ $fileAttachment->getFilename() }}')"
            :generic-attachment-container-extra-attributes-bag="
                (new ComponentAttributeBag)
                    ->class(['fi-converse-attachment-adaptable-width'])
            "
        />
    @endforeach
</div>

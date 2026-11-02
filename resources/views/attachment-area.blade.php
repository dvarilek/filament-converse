@php
    use Filament\Schemas\Components\Icon;
    use Filament\Support\View\Components\ModalComponent\IconComponent;
    use Illuminate\Contracts\Filesystem\Filesystem;
    use Illuminate\Support\Js;
    use Illuminate\View\ComponentAttributeBag;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $id = $getId();
    $componentKey = $getKey();
    $uploadDropZoneRef = $getUploadDropZoneRef();
    $statePath = $getStatePath();
    $isMultiple = $isMultiple();

    /* @var Filesystem $fileAttachmentsDisk */
    $fileAttachmentsDisk = $getDisk();
    /* @var array<int|string, TemporaryUploadedFile|string>  $fileAttachments */
    $fileAttachments = array_reverse($getRawState() ?? []);
    $fileAttachmentsCount = count($fileAttachments);
@endphp

<div
    wire:key="fi-converse-conversation-thread-attachment-area-{{ $id }}-{{ $componentKey }}-{{ $fileAttachmentsCount }}"
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('attachment-area', 'dvarilek/filament-converse') }}"
    x-data="attachmentArea({
                uploadDropZoneRef: @js($uploadDropZoneRef),
                statePath: @js($statePath),
                isMultiple: @js($isMultiple),
                fileAttachmentsCount: @js($fileAttachmentsCount),
                $wire,
            })"
    x-on:filament-converse-trigger-file-input.window="
        if ($event.detail.statePath === @js($statePath)) $refs.fileInput.click()
    "
>
    <input
        type="file"
        @if ($isMultiple)
            multiple
        @endif
        x-ref="fileInput"
        class="hidden"
        x-on:change="handleAttachmentUpload($event.target.files)"
    />

    @if ($uploadDropZoneRef && ($isMultiple || $fileAttachmentsCount < 2))
        <div
            x-cloak
            x-show="isDraggingFileAttachment"
            class="fi-converse-upload-modal-overlay"
        >
            <div class="fi-converse-upload-modal-backdrop"></div>

            <div class="fi-converse-upload-modal">
                <div class="fi-converse-upload-modal-header">
                    <div
                        {{ (new ComponentAttributeBag)->color(IconComponent::class, $getUploadModalIconColor(), 'primary')->class(['fi-converse-upload-modal-icon-bg']) }}
                    >
                        {{ \Filament\Support\generate_icon_html($getUploadModalIcon(), size: \Filament\Support\Enums\IconSize::Large) }}
                    </div>
                </div>
                <div class="fi-converse-upload-modal-content">
                    <h2 class="fi-converse-upload-modal-heading">
                        {{ $getUploadModalHeading() }}
                    </h2>
                    @if (filled($uploadModalDescription = $getUploadModalDescription()))
                        <p class="fi-converse-upload-modal-description">
                            {{ $uploadModalDescription }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div
        wire:key="fi-converse-conversation-thread-attachment-area-{{ $id }}-{{ $componentKey }}-{{ count($fileAttachments) }}"
        class="fi-converse-attachment-area"
        x-bind:class="{
            'fi-converse-is-dragging-attachment': isDraggingFileAttachment,
            'fi-converse-attachment-area-has-content':
                isUploadingFileAttachment() || @js(count($fileAttachments) > 0),
        }"
    >
        <template x-for="file in uploadingFileAttachments.reverse()">
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
                    <div class="fi-converse-attachment-name-skeleton"></div>
                    <div
                        class="fi-converse-attachment-mime-type-badge-skeleton"
                    ></div>
                </div>
            </div>
        </template>

        @foreach ($fileAttachments as $key => $fileAttachment)
            @php
                if ($fileAttachment instanceof TemporaryUploadedFile) {
                    $attachmentName = $fileAttachment->getFilename();
                    $attachmentOriginalName = $fileAttachment->getClientOriginalName();
                    $attachmentMimeType = $fileAttachment->getMimeType();
                    $messageContext = ['fileAttachment' => $fileAttachment];

                    $hasImageMimeType = $isImageMimeType($attachmentMimeType);
                    $imageFileAttachmentUrl = $hasImageMimeType ? $fileAttachment->temporaryUrl() : null;
                } else {
                    $attachmentName = $key;
                    $attachmentOriginalName = $fileAttachment;
                    $attachmentMimeType = $fileAttachmentsDisk->mimeType($attachmentName);
                    $messageContext = [];

                    $hasImageMimeType = $isImageMimeType($attachmentMimeType);
                    $imageFileAttachmentUrl = $hasImageMimeType ? $getSavedFileAttachmentUrl($attachmentName) : null;
                }
            @endphp

            <x-filament-converse::conversation-attachment
                :has-image-mime-type="$hasImageMimeType"
                :file-attachment-name="$getFileAttachmentName($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :file-attachment-toolbar="$getFileAttachmentToolbar($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :should-show-only-image-attachment="$shouldShowOnlyImageAttachment($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :file-attachment-url="$imageFileAttachmentUrl"
                :should-preview-image-attachment="$shouldPreviewImageAttachment($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :file-attachment-icon="$getFileAttachmentIcon($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :mime-type-badge-label="$getFileAttachmentMimeTypeBadgeLabel($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :mime-type-badge-icon="$getFileAttachmentMimeTypeBadgeIcon($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :mime-type-badge-color="$getFileAttachmentMimeTypeBadgeColor($attachmentName, $attachmentOriginalName, $attachmentMimeType, $messageContext)"
                :is-removable="true"
                :file-attachment-remove-handler="
                    $isRemovable($attachmentName)
                    ? 'async () => {
                        await $wire.callSchemaComponentMethod(' . Js::from($componentKey) . ', \'removeUploadedFile\', [' . Js::from($attachmentName) . ']);
                        $wire.$refresh();
                    }'
                    : null
                "
                :generic-attachment-container-extra-attributes-bag="
                    (new ComponentAttributeBag)
                        ->class(['fi-converse-attachment-adaptable-width'])
                "
            />
        @endforeach
    </div>
</div>

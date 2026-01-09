@props([
    'conversationKey',
    'uploadedFileAttachments',
    'isImageMimeType',
    'getUploadedFileAttachmentName',
    'getUploadedFileAttachmentToolbar',
    'shouldShowOnlyUploadedImageAttachment',
    'shouldPreviewUploadedImageAttachment',
    'getUploadedFileAttachmentIcon',
    'getUploadedFileAttachmentMimeTypeBadgeLabel',
    'getUploadedFileAttachmentMimeTypeBadgeIcon',
    'getUploadedFileAttachmentMimeTypeBadgeColor',
])

@php
    use Filament\Schemas\Components\Icon;

    $id = $getId();
    $key = $getKey();
    $statePath = $getStatePath();
@endphp

<div
    wire:key="fi-converse-conversation-thread-attachment-area-{{ $id }}-{{ $key }}-{{ count($uploadedFileAttachments) }}"
    class="fi-converse-attachment-area"
    x-bind:class="{
        'fi-converse-attachment-area-has-content':
        isUploadingFileAttachment() || @js(count($uploadedFileAttachments) > 0),
    }"
>
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
        @endphp

        <x-filament-converse::conversation-attachment
            :has-image-mime-type="$hasImageMimeType"
            :file-attachment-name="$getUploadedFileAttachmentName($fileAttachment)"
            :file-attachment-toolbar="$getUploadedFileAttachmentToolbar($fileAttachment)"
            :should-show-only-image-attachment="$shouldShowOnlyUploadedImageAttachment($fileAttachment)"
            :file-attachment-url="$hasImageMimeType ? $fileAttachment->temporaryUrl() : null"
            :should-preview-image-attachment="$shouldPreviewUploadedImageAttachment($fileAttachment)"
            :file-attachment-icon="$getUploadedFileAttachmentIcon($fileAttachment)"
            :mime-type-badge-label="$getUploadedFileAttachmentMimeTypeBadgeLabel($fileAttachment)"
            :mime-type-badge-icon="$getUploadedFileAttachmentMimeTypeBadgeIcon($fileAttachment)"
            :mime-type-badge-color="$getUploadedFileAttachmentMimeTypeBadgeColor($fileAttachment)"
            :is-removable="true"
            file-attachment-remove-handler="$wire.removeUpload('componentFileAttachments.{{ $statePath }}.{{ $conversationKey }}', '{{ $fileAttachment->getFilename() }}')"
            :generic-attachment-container-extra-attributes-bag="
                (new ComponentAttributeBag)
                    ->class(['fi-converse-attachment-adaptable-width'])
            "
        />
    @endforeach
</div>

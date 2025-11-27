<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use BackedEnum;
use Closure;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Schemas\Components\Icon;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Concerns\HasFileAttachments as BaseHasFileAttachments;


trait HasFileAttachments
{
    use BaseHasFileAttachments;

    protected int | Closure | null $maxFileAttachments = null;

    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected string | Closure | null $attachmentsAcceptedFileTypesValidationMessage = null;

    protected string | Closure | null $attachmentsMaxFileSizeValidationMessage = null;

    protected string | Closure | null $maxFileAttachmentsValidationMessage = null;

    protected bool | Closure $shouldShowOnlyImageAttachmentByDefault = true;

    protected bool | Closure $shouldPreviewImageAttachmentByDefault = true;

    protected string | BackedEnum | Htmlable | Closure | null $defaultFileAttachmentIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $defaultFileAttachmentIconColor
     */
    protected string | array | Closure | null $defaultFileAttachmentIconColor = 'primary';

    protected ?Closure $defaultFileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $defaultFileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $defaultFileAttachmentMimeTypeBadgeColor
     */
    protected string | array | Closure | null $defaultFileAttachmentMimeTypeBadgeColor = 'gray';

    protected bool | Closure | null $shouldShowOnlyUploadedImageAttachment = null;

    protected bool | Closure | null $shouldPreviewUploadedImageAttachment = null;

    protected ?Closure $uploadedFileAttachmentName = null;

    protected ?Closure $uploadedFileAttachmentToolbar = null;

    protected string | BackedEnum | Htmlable | Closure | null $uploadedFileAttachmentIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $uploadedFileAttachmentIconColor
     */
    protected string | array | Closure | null $uploadedFileAttachmentIconColor = 'primary';

    protected ?Closure $uploadedFileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $uploadedFileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $uploadedFileAttachmentMimeTypeBadgeColor
     */
    protected string | array | Closure | null $uploadedFileAttachmentMimeTypeBadgeColor = 'gray';

    protected bool | Closure | null $shouldShowOnlyMessageImageAttachment = null;

    protected bool | Closure | null $shouldPreviewMessageImageAttachment = null;

    protected ?Closure $formatMessageFileAttachmentName = null;

    protected ?Closure $messageFileAttachmentToolbar = null;

    protected string | BackedEnum | Htmlable | Closure | null $messageFileAttachmentIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $messageFileAttachmentIconColor
     */
    protected string | array | Closure | null $messageFileAttachmentIconColor = 'primary';

    protected ?Closure $messageFileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $messageFileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $messageFileAttachmentMimeTypeBadgeColor
     */
    protected string | array | Closure | null $messageFileAttachmentMimeTypeBadgeColor = 'gray';

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    public function maxFileAttachments(int | Closure | null $maxFileAttachments): static
    {
        $this->maxFileAttachments = $maxFileAttachments;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function attachmentModalIconColor(string | array | Closure | null $color): static
    {
        $this->attachmentModalIconColor = $color;

        return $this;
    }

    public function attachmentModalIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->attachmentModalIcon = filled($icon) ? $icon : false;

        return $this;
    }

    public function attachmentModalHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->attachmentModalHeading = $heading;

        return $this;
    }

    public function attachmentModalDescription(string | Htmlable | Closure | null $description): static
    {
        $this->attachmentModalDescription = $description;

        return $this;
    }

    public function attachmentsAcceptedFileTypesValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->attachmentsAcceptedFileTypesValidationMessage = $validationMessage;

        return $this;
    }

    public function attachmentsMaxFileSizeValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->attachmentsMaxFileSizeValidationMessage = $validationMessage;

        return $this;
    }

    public function maxFileAttachmentsValidationMessage(string | Closure | null $validationMessage): static
    {
        $this->maxFileAttachmentsValidationMessage = $validationMessage;

        return $this;
    }

    public function showOnlyImageAttachmentByDefault(bool | Closure $condition = true): static
    {
        $this->shouldShowOnlyImageAttachmentByDefault = $condition;

        return $this;
    }

    public function previewImageAttachmentByDefault(bool | Closure $condition = true): static
    {
        $this->shouldPreviewImageAttachmentByDefault = $condition;

        return $this;
    }

    public function defaultFileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $icon = null): static
    {
        $this->defaultFileAttachmentIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function defaultFileAttachmentIconColor(string | array | Closure | null $color = null): static
    {
        $this->defaultFileAttachmentIconColor = $color;

        return $this;
    }

    public function defaultFileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->defaultFileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function defaultFileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $icon = null): static
    {
        $this->defaultFileAttachmentMimeTypeBadgeIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function defaultFileAttachmentMimeTypeBadgeColor(string | array | Closure | null $color = null): static
    {
        $this->defaultFileAttachmentMimeTypeBadgeColor = $color;

        return $this;
    }

    public function showOnlyUploadedImageAttachment(bool | Closure | null $condition = true): static
    {
        $this->shouldShowOnlyUploadedImageAttachment = $condition;

        return $this;
    }

    public function previewUploadedImageAttachment(bool | Closure | null $condition = true): static
    {
        $this->shouldPreviewUploadedImageAttachment = $condition;

        return $this;
    }

    public function uploadedFileAttachmentName(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentName = $callback;

        return $this;
    }

    public function uploadedFileAttachmentToolbar(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentToolbar = $callback;

        return $this;
    }

    public function uploadedFileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $icon = null): static
    {
        $this->uploadedFileAttachmentIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function uploadedFileAttachmentIconColor(string | array | Closure | null $color = null): static
    {
        $this->uploadedFileAttachmentIconColor = $color;

        return $this;
    }

    public function uploadedFileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->uploadedFileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function uploadedFileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $icon = null): static
    {
        $this->uploadedFileAttachmentMimeTypeBadgeIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function uploadedFileAttachmentMimeTypeBadgeColor(string | array | Closure | null $color = null): static
    {
        $this->uploadedFileAttachmentMimeTypeBadgeColor = $color;

        return $this;
    }

    public function showOnlyMessageImageAttachment(bool | Closure | null $condition = true): static
    {
        $this->shouldShowOnlyMessageImageAttachment = $condition;

        return $this;
    }

    public function previewMessageImageAttachment(bool | Closure | null $condition = true): static
    {
        $this->shouldPreviewMessageImageAttachment = $condition;

        return $this;
    }

    public function formatMessageFileAttachmentName(?Closure $callback = null): static
    {
        $this->formatMessageFileAttachmentName = $callback;

        return $this;
    }

    public function messageFileAttachmentToolbar(?Closure $callback = null): static
    {
        $this->messageFileAttachmentToolbar = $callback;

        return $this;
    }

    public function messageFileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $icon = null): static
    {
        $this->messageFileAttachmentIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function messageFileAttachmentIconColor(string | array | Closure | null $color = null): static
    {
        $this->messageFileAttachmentIconColor = $color;

        return $this;
    }

    public function messageFileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->messageFileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function messageFileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $icon = null): static
    {
        $this->messageFileAttachmentMimeTypeBadgeIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function messageFileAttachmentMimeTypeBadgeColor(string | array | Closure | null $color = null): static
    {
        $this->messageFileAttachmentMimeTypeBadgeColor = $color;

        return $this;
    }

    public function uploadAttachmentAction(?Closure $callback): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

        return $this;
    }

    public function getMaxFileAttachments(): ?int
    {
        return $this->evaluate($this->maxFileAttachments);
    }

    /**
     * @return string | array<string>
     */
    public function getAttachmentModalIconColor(): string | array
    {
        return $this->evaluate($this->attachmentModalIconColor) ?? 'primary';
    }

    public function getAttachmentModalIcon(): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->attachmentModalIcon) ?? Heroicon::PaperClip;

        // https://github.com/filamentphp/filament/pull/13512
        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if ($icon === false) {
            return null;
        }

        return $icon;
    }

    public function getAttachmentModalHeading(): string | Htmlable
    {
        return $this->evaluate($this->attachmentModalHeading) ?? __('filament-converse::conversation-thread.attachment-modal.heading');
    }

    public function getAttachmentModalDescription(): string | Htmlable
    {
        return $this->evaluate($this->attachmentModalDescription);
    }

    public function getAttachmentsAcceptedFileTypesValidationMessage(array $fileAttachmentsAcceptedFileTypes): string
    {
        return $this->evaluate($this->attachmentsAcceptedFileTypesValidationMessage, [
            'fileAttachmentsAcceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
            'acceptedFileTypes' => $fileAttachmentsAcceptedFileTypes,
        ]) ?? __('filament-converse::conversation-thread.attachment-modal.file-attachments-accepted-file-types-validation-message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]);
    }

    public function getAttachmentsMaxFileSizeValidationMessage(string $fileAttachmentsMaxSize): string
    {
        return $this->evaluate($this->attachmentsMaxFileSizeValidationMessage, [
            'fileAttachmentMaxSize' => $fileAttachmentsMaxSize,
            'maxSize' => $fileAttachmentsMaxSize,
        ]) ?? __('filament-converse::conversation-thread.attachment-modal.file-attachments-max-size-validation-message', ['max' => $fileAttachmentsMaxSize]);
    }

    public function getMaxFileAttachmentsValidationMessage(?int $maxFileAttachments = null): ?string
    {
        if ($maxFileAttachments === null) {
            return null;
        }

        return $this->evaluate($this->maxFileAttachmentsValidationMessage, [
            'maxFileAttachments' => $maxFileAttachments,
            'maxFiles' => $maxFileAttachments,
        ]) ?? trans_choice(
            'filament-converse::conversation-thread.attachment-modal.max-file-attachments-validation-message',
            $maxFileAttachments,
            ['count' => $maxFileAttachments],
        );
    }

    public function shouldShowOnlyImageAttachmentByDefault(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): bool
    {
        return (bool) $this->evaluate($this->shouldShowOnlyImageAttachmentByDefault, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]);
    }

    public function shouldPreviewImageAttachmentByDefault(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): bool
    {
        return (bool) $this->evaluate($this->shouldPreviewImageAttachmentByDefault, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]);
    }

    public function getDefaultFileAttachmentIcon(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->defaultFileAttachmentIcon, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]);

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if (is_string($icon) || $icon instanceof BackedEnum) {
            $icon = Icon::make($icon);
        }

        if ($icon instanceof Icon) {
            $icon->color($this->getDefaultFileAttachmentIconColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType));
        }

        return $icon;
    }

    public function getDefaultFileAttachmentIconColor(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): string | array
    {
        return $this->evaluate($this->defaultFileAttachmentIconColor, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]) ?? 'primary';
    }

    public function getDefaultFileAttachmentMimeTypeBadgeLabel(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): string | Htmlable | null
    {
        return $this->evaluate($this->defaultFileAttachmentMimeTypeBadgeLabel, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]);
    }

    public function getDefaultFileAttachmentMimeTypeBadgeIcon(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): string | BackedEnum | null
    {
        return $this->evaluate($this->defaultFileAttachmentMimeTypeBadgeIcon, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]);
    }

    public function getDefaultFileAttachmentMimeTypeBadgeColor(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType): string | array
    {
        return $this->evaluate($this->defaultFileAttachmentMimeTypeBadgeColor, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
        ]) ?? 'gray';
    }

    public function shouldShowOnlyUploadedImageAttachment(TemporaryUploadedFile $attachment): bool
    {
        $result = (bool) $this->evaluate($this->shouldShowOnlyUploadedImageAttachment, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);

        if ($result !== null) {
            return (bool) $result;
        }

        return $this->shouldShowOnlyImageAttachmentByDefault($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function shouldPreviewUploadedImageAttachment(TemporaryUploadedFile $attachment): bool
    {
        $result = $this->evaluate($this->shouldPreviewUploadedImageAttachment, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);

        if ($result !== null) {
            return (bool) $result;
        }

        return $this->shouldPreviewImageAttachmentByDefault($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());;
    }

    public function getUploadedFileAttachmentName(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentName, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentToolbar(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentToolbar, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getUploadedFileAttachmentIcon(TemporaryUploadedFile $attachment): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->uploadedFileAttachmentIcon, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if (is_string($icon) || $icon instanceof BackedEnum) {
            $icon = Icon::make($icon);
        }

        if ($icon instanceof Icon) {
            $icon->color($this->getUploadedFileAttachmentIconColor($attachment));
        }

        return $icon ?? $this->getDefaultFileAttachmentIcon($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function getUploadedFileAttachmentIconColor(TemporaryUploadedFile $attachment): string | array
    {
        return $this->evaluate($this->uploadedFileAttachmentIconColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? $this->getDefaultFileAttachmentIconColor($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function getUploadedFileAttachmentMimeTypeBadgeLabel(TemporaryUploadedFile $attachment): string | Htmlable | null
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeLabel, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeLabel($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function getUploadedFileAttachmentMimeTypeBadgeIcon(TemporaryUploadedFile $attachment): string | BackedEnum | null
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeIcon, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeIcon($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function getUploadedFileAttachmentMimeTypeBadgeColor(TemporaryUploadedFile $attachment): string | array
    {
        return $this->evaluate($this->uploadedFileAttachmentMimeTypeBadgeColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeColor($attachment->getPath(), $attachment->getClientOriginalName(), $attachment->getMimeType());
    }

    public function shouldShowOnlyMessageImageAttachment(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): bool
    {
        $result =  $this->evaluate($this->shouldShowOnlyMessageImageAttachment, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]);

        if ($result !== null) {
            return (bool) $result;
        }

        return $this->shouldShowOnlyImageAttachmentByDefault($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function shouldPreviewMessageImageAttachment(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): bool
    {
        $result = $this->evaluate($this->shouldPreviewMessageImageAttachment, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]);

        if ($result !== null) {
            return (bool) $result;
        }

        return $this->shouldPreviewImageAttachmentByDefault($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function getMessageFileAttachmentName(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | Htmlable
    {
        return $this->evaluate($this->formatMessageFileAttachmentName, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? $attachmentOriginalName;
    }

    public function getMessageFileAttachmentToolbar(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | Htmlable | null
    {
        return $this->evaluate($this->messageFileAttachmentToolbar, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]);
    }

    public function getMessageFileAttachmentIcon(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->messageFileAttachmentIcon, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]);

        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if (is_string($icon) || $icon instanceof BackedEnum) {
            $icon = Icon::make($icon);
        }

        if ($icon instanceof Icon) {
            $icon->color($this->getMessageFileAttachmentIconColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message));
        }

        return $icon ?? $this->getDefaultFileAttachmentIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function getMessageFileAttachmentIconColor(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | array
    {
        return $this->evaluate($this->messageFileAttachmentIconColor, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? $this->getDefaultFileAttachmentIconColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function getMessageFileAttachmentMimeTypeBadgeLabel(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | Htmlable | null
    {
        return $this->evaluate($this->messageFileAttachmentMimeTypeBadgeLabel, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeLabel($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function getMessageFileAttachmentMimeTypeBadgeIcon(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | BackedEnum | null
    {
        return $this->evaluate($this->messageFileAttachmentMimeTypeBadgeIcon, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeIcon($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function getMessageFileAttachmentMimeTypeBadgeColor(string $attachmentPath, string $attachmentOriginalName, string $attachmentMimeType, Message $message): string | array
    {
        return $this->evaluate($this->messageFileAttachmentMimeTypeBadgeColor, [
            'attachmentPath' => $attachmentPath,
            'attachmentOriginalName' => $attachmentOriginalName,
            'attachmentMimeType' => $attachmentMimeType,
            'message' => $message,
        ], [
            Message::class => $message,
        ]) ?? $this->getDefaultFileAttachmentMimeTypeBadgeColor($attachmentPath, $attachmentOriginalName, $attachmentMimeType);
    }

    public function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    protected function getUploadAttachmentAction(): Action
    {
        $action = Action::make('uploadAttachment')
            ->label(__('filament-converse::conversation-thread.footer-actions.upload-attachment-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperClip)
            ->alpineClickHandler('$refs.fileInput.click()');

        if ($this->modifyUploadAttachmentActionUsing) {
            $action = $this->evaluate($this->modifyUploadAttachmentActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getUploadedFileAttachments(): array
    {
        return Arr::wrap(data_get($this->getLivewire(), 'componentFileAttachments.' . $this->getStatePath())) ?? [];
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    public function getValidUploadedFileAttachments(): array
    {
        $attachments = $this->getUploadedFileAttachments();

        if (count($attachments) === 0) {
            return [];
        }

        $maxFileAttachments = $this->getMaxFileAttachments();

        if ($maxFileAttachments && count($attachments) > $maxFileAttachments) {
            return [];
        }

        $maxSize = $this->getFileAttachmentsMaxSize();
        $acceptedFileTypes = $this->getFileAttachmentsAcceptedFileTypes();

        try {
            foreach ($attachments as $attachment) {
                Validator::validate(
                    ['file' => $attachment],
                    rules: [
                        'file' => [
                            'file',
                            ...($maxSize ? ["max:{$maxSize}"] : []),
                            ...($acceptedFileTypes ? ['mimetypes:' . implode(',', $acceptedFileTypes)] : []),
                        ],
                    ],
                );
            }
        } catch (ValidationException $exception) {
            return [];
        }

        return $attachments;
    }
}

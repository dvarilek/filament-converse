<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Concerns\HasFileAttachments;
use Filament\Forms\Components\Concerns\HasMaxHeight;
use Filament\Forms\Components\Concerns\HasMinHeight;
use Filament\Forms\Components\Concerns\InteractsWithToolbarButtons;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Icon;
use Filament\Support\Concerns\CanConfigureCommonMark;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Support\Concerns\HasPlaceholder;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationThread extends Field implements CanBeLengthConstrainedContract
{
    use CanBeLengthConstrained;
    use CanConfigureCommonMark;
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use HasExtraAlpineAttributes;
    use HasFileAttachments;
    use HasMaxHeight;
    use HasMinHeight;
    use HasPlaceholder;
    use InteractsWithToolbarButtons;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected int | Closure | null $defaultLoadedMessagesCount = 15;

    protected int | Closure | null $messagesPerPageLoad = 15;

    protected int | Closure | null $messageTimestampGroupingInterval = 420;

    protected ?Closure $modifyMessagesQueryUsing = null;

    protected int | Closure | null $maxFileAttachments = null;

    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected string | Closure | null $attachmentsAcceptedFileTypesValidationMessage = null;

    protected string | Closure | null $attachmentsMaxFileSizeValidationMessage = null;

    protected string | Closure | null $maxFileAttachmentsValidationMessage = null;

    protected bool | Closure $hideAttachmentDetailsForImage = true;

    protected bool | Closure $previewImageAttachment = true;

    protected ?Closure $fileAttachmentName = null;

    protected ?Closure $fileAttachmentToolbar = null;

    protected string | BackedEnum | Htmlable | Closure | null $fileAttachmentIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $fileAttachmentIconColor
     */
    protected string | array | Closure | null $fileAttachmentIconColor = 'primary';

    protected ?Closure $fileAttachmentMimeTypeBadgeLabel = null;

    protected string | BackedEnum | Closure | null $fileAttachmentMimeTypeBadgeIcon = null;

    /**
     * @param  string | array<string> | Closure | null  $fileAttachmentMimeTypeBadgeColor
     */
    protected string | array | Closure | null $fileAttachmentMimeTypeBadgeColor = 'gray';

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'conversation_thread';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();

        $this->autofocus();

        $this->maxLength(65535);

        $this->minHeight('2rem');

        $this->live();

        $this->disableToolbarButtons([
            'codeBlock',
        ]);

        $this->attachmentModalDescription(__('filament-converse::conversation-thread.attachment-modal.description'));

        $this->emptyStateHeading(__('filament-converse::conversation-thread.empty-state.heading'));

        $this->fileAttachmentsAcceptedFileTypes([
            'image/png',
            'image/jpeg',
            'audio/mpeg',
            'video/mp4',
            'video/mpeg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $this->fileAttachmentName(static function (?TemporaryUploadedFile $attachment = null) {
            return $attachment?->getClientOriginalName();
        });

        $this->fileAttachmentIcon(static function (?TemporaryUploadedFile $attachment = null) {
            return match ($attachment?->getMimeType()) {
                'image/png',
                'image/jpeg' => Heroicon::OutlinedPhoto,
                'audio/mpeg' => Heroicon::OutlinedSpeakerWave,
                'video/mp4',
                'video/mpeg' => Heroicon::OutlinedVideoCamera,
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => Heroicon::OutlinedDocumentText,
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => Heroicon::OutlinedDocumentCurrencyEuro,
                default => Heroicon::OutlinedDocumentText,
            };
        });

        $this->fileAttachmentIconColor(static function (?TemporaryUploadedFile $attachment = null) {
            return match ($attachment?->getMimeType()) {
                'application/pdf', => 'danger',
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                default => 'primary',
            };
        });

        $this->fileAttachmentMimeTypeBadgeLabel(static function (?TemporaryUploadedFile $attachment = null) {
            return match ($attachment?->getMimeType()) {
                'image/png',
                'image/jpeg' => __('filament-converse::conversation-thread.attachment-area.mime-type.image'),
                'audio/mpeg' => __('filament-converse::conversation-thread.attachment-area.mime-type.audio'),
                'video/mp4',
                'video/mpeg' => __('filament-converse::conversation-thread.attachment-area.mime-type.video'),
                'application/pdf' => __('filament-converse::conversation-thread.attachment-area.mime-type.pdf'),
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('filament-converse::conversation-thread.attachment-area.mime-type.document'),
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('filament-converse::conversation-thread.attachment-area.mime-type.spreadsheet'),
                default => null,
            };
        });

        $this->childComponents(static fn (ConversationThread $component) => [
            $component->getEditConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(static fn (ConversationThread $component) => [
            $component->getEditMessageAction(),
            $component->getDeleteMessageAction(),
        ], static::MESSAGE_ACTIONS_KEY);

        $this->registerActions([
            static fn (ConversationThread $component) => $component->getSendMessageAction(),
            static fn (ConversationThread $component) => $component->getUploadAttachmentAction(),
        ]);
    }

    public function defaultLoadedMessagesCount(int | Closure | null $count): static
    {
        $this->defaultLoadedMessagesCount = $count;

        return $this;
    }

    public function messagesPerPageLoad(int | Closure | null $count): static
    {
        $this->messagesPerPageLoad = $count;

        return $this;
    }

    public function messageTimestampGroupingInterval(string | Closure | null $seconds): static
    {
        $this->messageTimestampGroupingInterval = $seconds;

        return $this;
    }

    public function modifyMessagesQueryUsing(?Closure $callback): static
    {
        $this->modifyMessagesQueryUsing = $callback;

        return $this;
    }

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

    public function hideAttachmentDetailsForImage(bool | Closure $condition = true): static
    {
        $this->hideAttachmentDetailsForImage = $condition;

        return $this;
    }

    public function previewImageAttachment(bool | Closure $condition = true): static
    {
        $this->previewImageAttachment = $condition;

        return $this;
    }

    public function fileAttachmentName(?Closure $callback = null): static
    {
        $this->fileAttachmentName = $callback;

        return $this;
    }

    public function fileAttachmentToolbar(?Closure $callback = null): static
    {
        $this->fileAttachmentToolbar = $callback;

        return $this;
    }

    public function fileAttachmentIcon(string | BackedEnum | Htmlable | Closure | null $fileAttachmentIcon = null): static
    {
        $this->fileAttachmentIcon = $fileAttachmentIcon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $fileAttachmentIconColor
     */
    public function fileAttachmentIconColor(string | array | Closure | null $fileAttachmentIconColor = null): static
    {
        $this->fileAttachmentIconColor = $fileAttachmentIconColor;

        return $this;
    }

    public function fileAttachmentMimeTypeBadgeLabel(?Closure $callback = null): static
    {
        $this->fileAttachmentMimeTypeBadgeLabel = $callback;

        return $this;
    }

    public function fileAttachmentMimeTypeBadgeIcon(string | BackedEnum | Closure | null $fileAttachmentMimeTypeBadgeIcon = null): static
    {
        $this->fileAttachmentMimeTypeBadgeIcon = $fileAttachmentMimeTypeBadgeIcon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $fileAttachmentMimeTypeBadgeColor
     */
    public function fileAttachmentMimeTypeBadgeColor(string | array | Closure | null $fileAttachmentMimeTypeBadgeColor = null): static
    {
        $this->fileAttachmentMimeTypeBadgeColor = $fileAttachmentMimeTypeBadgeColor;

        return $this;
    }

    public function editConversationAction(?Closure $callback): static
    {
        $this->modifyEditConversationActionUsing = $callback;

        return $this;
    }

    public function editMessageAction(?Closure $callback): static
    {
        $this->modifyEditMessageActionUsing = $callback;

        return $this;
    }

    public function deleteMessageAction(?Closure $callback): static
    {
        $this->modifyDeleteMessageActionUsing = $callback;

        return $this;
    }

    public function sendMessageAction(?Closure $callback): static
    {
        $this->modifySendMessageActionUsing = $callback;

        return $this;
    }

    public function uploadAttachmentAction(?Closure $callback): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

        return $this;
    }

    /**
     * @return array<string | array<string>>
     */
    public function getDefaultToolbarButtons(): array
    {
        return [
            ['bold', 'italic', 'strike', 'link'],
            ['heading'],
            ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['table'],
            ['undo', 'redo'],
        ];
    }

    public function getDefaultLoadedMessagesCount(): int
    {
        return $this->evaluate($this->defaultLoadedMessagesCount) ?? 15;
    }

    public function getMessagesPerPageLoad(): int
    {
        return $this->evaluate($this->messagesPerPageLoad) ?? 15;
    }

    public function getMessageTimestampGroupingInterval(): int
    {
        return $this->evaluate($this->messageTimestampGroupingInterval) ?? 420;
    }

    /**
     * @return Builder<Message>|null
     */
    public function getMessagesQuery(bool $shouldPaginate = true): ?Builder
    {
        /* @var ConversationManager $livewire */
        $livewire = $this->getLivewire();
        $conversation = $livewire->getActiveConversation();

        if (! $conversation) {
            return null;
        }

        /* @var Builder<Message> $query */
        $query = $conversation->messages()
            ->getQuery()
            ->orderBy('created_at', 'desc');

        if ($shouldPaginate) {
            $limit = $this->getDefaultLoadedMessagesCount()
                + (($livewire->getActiveConversationMessagesPage() - 1) * $this->getMessagesPerPageLoad());

            $query->limit($limit);
        }

        if ($this->modifyMessagesQueryUsing) {
            $query = $this->evaluate($this->modifyMessagesQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        return $query;
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

    public function shouldHideAttachmentDetailsForImage(?TemporaryUploadedFile $attachment = null): bool
    {
        return (bool) $this->evaluate($this->hideAttachmentDetailsForImage, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function shouldPreviewImageAttachment(?TemporaryUploadedFile $attachment = null): bool
    {
        return (bool) $this->evaluate($this->previewImageAttachment, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getFileAttachmentName(?TemporaryUploadedFile $attachment = null): string | Htmlable | null
    {
        return $this->evaluate($this->fileAttachmentName, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getFileAttachmentToolbar(?TemporaryUploadedFile $attachment = null): string | Htmlable | null
    {
        return $this->evaluate($this->fileAttachmentToolbar, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getFileAttachmentIcon(?TemporaryUploadedFile $attachment = null): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->fileAttachmentIcon, [
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
            $icon->color($this->getFileAttachmentIconColor($attachment));
        }

        return $icon;
    }

    public function getFileAttachmentIconColor(?TemporaryUploadedFile $attachment = null): string | array
    {
        return $this->evaluate($this->fileAttachmentIconColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? 'primary';
    }

    public function getFileAttachmentMimeTypeBadgeLabel(?TemporaryUploadedFile $attachment = null): string | Htmlable | null
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeLabel, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getFileAttachmentMimeTypeBadgeIcon(?TemporaryUploadedFile $attachment = null): string | BackedEnum | null
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeIcon, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]);
    }

    public function getFileAttachmentMimeTypeBadgeColor(?TemporaryUploadedFile $attachment = null): string | array
    {
        return $this->evaluate($this->fileAttachmentMimeTypeBadgeColor, [
            'attachment' => $attachment,
        ], [
            TemporaryUploadedFile::class => $attachment,
        ]) ?? 'gray';
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd('editConversation'));

        if ($this->modifyEditConversationActionUsing) {
            $action = $this->evaluate($this->modifyEditConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getEditMessageAction(): Action
    {
        $action = EditMessageAction::make();

        if ($this->modifyEditMessageActionUsing) {
            $action = $this->evaluate($this->modifyEditMessageActionUsing, [
                'action' => $action,
            ], [
                EditMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getDeleteMessageAction(): Action
    {
        $action = DeleteMessageAction::make();

        if ($this->modifyDeleteMessageActionUsing) {
            $action = $this->evaluate($this->modifyDeleteMessageActionUsing, [
                'action' => $action,
            ], [
                DeleteMessageAction::class => $action,
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
        return Arr::wrap(data_get($this->getLivewire(), 'componentFileAttachments.' . $this->getStatepath())) ?? [];
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

    protected function getSendMessageAction(): Action
    {
        $action = Action::make('sendMessage')
            ->label(__('filament-converse::conversation-thread.footer-actions.send-message-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperAirplane)
            ->action(static function (ConversationThread $component, ConversationManager $livewire) {
                $state = $livewire->content->getState();

                $message = $state[$component->getName()];
                $uploadedFileAttachments = $component->getValidUploadedFileAttachments();

                if (blank($message) && blank($uploadedFileAttachments)) {
                    return;
                }

                $attachments = $attachmentFileNames = [];

                foreach ($uploadedFileAttachments as $attachment) {
                    $attachments[] = $component->saveUploadedFileAttachment($attachment);
                    $attachmentFileNames[] = $attachment->getClientOriginalName();
                }

                $livewire->getActiveConversationAuthenticatedUserParticipation()->sendMessage([
                    'content' => $message,
                    'attachments' => $attachments,
                    'attachment_file_names' => $attachmentFileNames,
                ]);

                $livewire->content->fill();
                $livewire->componentFileAttachments = [];
            });

        if ($this->modifySendMessageActionUsing) {
            $action = $this->evaluate($this->modifySendMessageActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
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
}

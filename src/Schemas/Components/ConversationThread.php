<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
use Closure;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasFileAttachments;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Schemas\Components\Icon;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationThread extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use HasFileAttachments;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    const MESSAGE_INPUT_FIELD_KEY = 'message_input_field';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    // ------------------------------------------------------
    protected int | Closure | null $maxFileAttachments = null;

    protected string | array | Closure | null $attachmentModalIconColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $attachmentModalIcon = null;

    protected string | Htmlable | Closure | null $attachmentModalHeading = null;

    protected string | Htmlable | Closure | null $attachmentModalDescription = null;

    protected string | Closure | null $attachmentsAcceptedFileTypesValidationMessage = null;

    protected string | Closure | null $attachmentsMaxFileSizeValidationMessage = null;

    protected string | Closure | null $maxFileAttachmentsValidationMessage = null;

    protected ?Closure $formatFileAttachmentNameUsing = null;

    protected ?Closure $getAttachmentIconUsing = null;

    protected ?Closure $getAttachmentFormattedMimeTypeUsing = null;

    protected ?Closure $modifyMessageInputFieldUsing = null;

    // protected bool | Closure $shouldOnlyShowAttachmentIcon = false;

    public static function make()
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key('conversation-thread');

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

        $this->getAttachmentIconUsing(static function (string $mimeType) {
            return match ($mimeType) {
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

        $this->getAttachmentFormattedMimeTypeUsing(static function (string $mimeType) {
            return match ($mimeType) {
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

        $this->childComponents(static fn (ConversationThread $component) => [
            $component->getMessageInputField(),
        ], static::MESSAGE_INPUT_FIELD_KEY);
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

    public function formatFileAttachmentNameUsing(?Closure $callback = null): static
    {
        $this->formatFileAttachmentNameUsing = $callback;

        return $this;
    }

    public function getAttachmentIconUsing(?Closure $callback = null): static
    {
        $this->getAttachmentIconUsing = $callback;

        return $this;
    }

    public function getAttachmentFormattedMimeTypeUsing(?Closure $callback = null): static
    {
        $this->getAttachmentFormattedMimeTypeUsing = $callback;

        return $this;
    }

    public function messageInputField(?Closure $callback): static
    {
        $this->modifyMessageInputFieldUsing = $callback;

        return $this;
    }

    public function getUploadedFileAttachments(): array
    {
        return Arr::wrap(data_get($this->getLivewire(), 'componentFileAttachments.' . $this->getStatepath())) ?? [];
    }

    public function getUploadedFileAttachment(TemporaryUploadedFile | string | null $attachment = null): ?TemporaryUploadedFile
    {
        if (is_string($attachment)) {
            $attachment = data_get($this->getLivewire(), "componentFileAttachments.{$this->getStatePath()}.{$attachment}");
        } elseif (! $attachment) {
            $attachment = data_get($this->getLivewire(), "componentFileAttachments.{$this->getStatePath()}");
        }

        if ($attachment instanceof TemporaryUploadedFile) {
            $maxSize = $this->getFileAttachmentsMaxSize();
            $acceptedFileTypes = $this->getFileAttachmentsAcceptedFileTypes();
            $maxFileAttachments = $this->getMaxFileAttachments(); // TODO: Actually test later

            try {
                Validator::validate(
                    ['file' => $attachment],
                    rules: [
                        'file' => [
                            'file' => [
                                'array',
                                ...($maxFileAttachments ? ["max:{$maxFileAttachments}"] : []),
                            ],
                            'file.*',
                            ...($maxSize ? ["max:{$maxSize}"] : []),
                            ...($acceptedFileTypes ? ['mimetypes:' . implode(',', $acceptedFileTypes)] : []),
                        ],
                    ],
                );
            } catch (ValidationException $exception) {
                return null;
            }
        }

        return $attachment;
    }

    protected function getEditConversationAction(): Action
    {
        $action = Action::make('editConversation')
            ->iconButton()
            ->color('gray')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->size(Size::ExtraLarge)
            ->action(fn () => dd($this->getLivewire()->content->getState(), $this->getLivewire()->componentFileAttachments));

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

    public function formatFileAttachmentName(?string $fileAttachmentName): string | Htmlable | null
    {
        if ($this->formatFileAttachmentNameUsing) {
            $fileAttachmentName = $this->evaluate($this->formatFileAttachmentNameUsing, [
                'name' => $fileAttachmentName,
                'fileAttachmentName' => $fileAttachmentName,
            ]);
        }

        return $fileAttachmentName;
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
        $icon = $this->evaluate($this->attachmentModalIcon) ?? Heroicon::OutlinedPaperClip;

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

    public function getAttachmentIcon(string $mimeType): Htmlable | Icon | null
    {
        $icon = $this->evaluate($this->getAttachmentIconUsing, [
            'mimeType' => $mimeType,
        ]);

        if (is_string($icon) || $icon instanceof BackedEnum) {
            return Icon::make($icon);
        }

        return $icon;
    }

    public function getAttachmentFormattedMimeType(string $mimeType): string | Htmlable | null
    {
        return $this->evaluate($this->getAttachmentFormattedMimeTypeUsing, [
            'mimeType' => $mimeType,
        ]);
    }

    protected function getMessageInputField(): MessageInput
    {
        $component = MessageInput::make('message_content');

        if ($this->modifyMessageInputFieldUsing) {
            $component = $this->evaluate($this->modifyMessageInputFieldUsing, [
                'component' => $component,
            ], [
                MessageInput::class => $component,
                Component::class => $component,
            ]) ?? $component;
        }

        return $component;
    }
}

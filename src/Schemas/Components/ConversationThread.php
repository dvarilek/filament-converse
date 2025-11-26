<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Carbon\Carbon;
use Closure;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Concerns\HasFileAttachments as HasBaseFileAttachments;
use Filament\Forms\Components\Concerns\HasMaxHeight;
use Filament\Forms\Components\Concerns\HasMinHeight;
use Filament\Forms\Components\Concerns\InteractsWithToolbarButtons;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\CanConfigureCommonMark;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Support\Concerns\HasPlaceholder;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationThread extends Field implements CanBeLengthConstrainedContract
{
    use CanBeLengthConstrained;
    use CanConfigureCommonMark;
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasFileAttachments;
    use HasBaseFileAttachments;
    use HasExtraAlpineAttributes;
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

    protected ?Closure $formatMessageTimestampUsing = null;

    protected ?Closure $modifyMessagesQueryUsing = null;

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;

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

        $this->formatMessageTimestampUsing(static function (Carbon $timestamp, Message $message): string {
            $now = Carbon::now();

            return match (true) {
                $now->year !== $timestamp->year => $timestamp->isoFormat('L LT'),
                $now->month !== $timestamp->month => $timestamp->isoFormat('MMM D LT'),
                $now->isSameWeek($timestamp) && $now->day !== $timestamp->day => $timestamp->isoFormat('ddd LT'),
                $now->day !== $timestamp->day => $timestamp->isoFormat('D LT'),
                default => $timestamp->isoFormat('LT'),
            };
        });

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

        $this->uploadedFileAttachmentName(static function (TemporaryUploadedFile $attachment): ?string {
            return $attachment->getClientOriginalName();
        });

        $this->defaultFileAttachmentIcon(function (string $attachmentName, string $attachmentPath): Heroicon {
            return match (Storage::mimeType($attachmentPath)) {
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

        $this->defaultFileAttachmentIconColor(static function (string $attachmentPath): string {
            return match (Storage::mimeType($attachmentPath)) {
                'application/pdf', => 'danger',
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                default => 'primary',
            };
        });

        // TODO: Integrate default fallback methods for `shouldShowOnlyImageAttachmentByDefault` and `shouldPreviewImageAttachmentByDefault`
        // TODO: defaultIcon doesn't work well - when there is no concrete icon, but there is concrete icon color

        $this->defaultFileAttachmentMimeTypeBadgeLabel(static function (string $attachmentPath): ?string {
            return match (Storage::mimeType($attachmentPath)) {
                'image/png',
                'image/jpeg' => __('filament-converse::conversation-thread.attachments.mime-type.image'),
                'audio/mpeg' => __('filament-converse::conversation-thread.attachments.mime-type.audio'),
                'video/mp4',
                'video/mpeg' => __('filament-converse::conversation-thread.attachments.mime-type.video'),
                'application/pdf' => __('filament-converse::conversation-thread.attachments.mime-type.pdf'),
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('filament-converse::conversation-thread.attachments.mime-type.document'),
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('filament-converse::conversation-thread.attachments.mime-type.spreadsheet'),
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

    public function formatMessageTimestampUsing(?Closure $callback): static
    {
        $this->formatMessageTimestampUsing = $callback;

        return $this;
    }

    public function modifyMessagesQueryUsing(?Closure $callback): static
    {
        $this->modifyMessagesQueryUsing = $callback;

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

    public function formatMessageTimestamp(Carbon $timestamp, Message $message): ?string
    {
        return $this->evaluate($this->formatMessageTimestampUsing, [
            'timestamp' => $timestamp,
            'message' => $message,
        ], [
            Carbon::class => $timestamp,
            Message::class => $message,
        ]);
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
}

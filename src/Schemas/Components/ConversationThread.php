<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread\EditMessageAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConversationThread extends Textarea
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasExtraMessageAttributes;
    use Concerns\HasFileAttachments;
    use Concerns\HasReadReceipts;
    use Concerns\HasTypingIndicator;
    use HasExtraAttributes;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

    protected int | Closure | null $maxHeight = 8;

    protected int | Closure | null $defaultLoadedMessagesCount = 15;

    protected int | Closure | null $messagesLoadedPerPage = 15;

    protected ?Closure $getMessageAuthorNameUsing = null;

    protected ?Closure $getMessageAuthorAvatarUsing = null;

    protected ?Closure $getMessageTimestampUsing = null;

    protected ?Closure $getMessageContentUsing = null;

    protected string | Htmlable | Closure | null $messageDividerContent = null;

    protected bool | Closure | null $shouldShowNewMessagesDivider = null;

    protected ?Closure $getNewMessagesDividerContentUsing = null;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $NewMessagesDividerColor = 'primary';

    /**
     * @var string | array<string> | Closure | null
     */
    protected string | array | Closure | null $messageColor = null;

    protected int | Closure | null $autoScrollOnForeignMessagesThreshold = 300;

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

        $this->autosize();

        $this->autofocus();

        $this->maxLength(65535);

        $this->placeholder(__('filament-converse::conversation-thread.placeholder'));

        $this->attachmentModalDescription(__('filament-converse::conversation-thread.attachment-modal.description'));

        $this->emptyStateHeading(__('filament-converse::conversation-thread.empty-state.heading'));

        $this->getMessageContentUsing(static function (Message $message): ?string {
            return $message->content;
        });

        $this->getMessageAttachmentDataUsing(static function (ConversationThread $component, Message $message, Authenticatable $messageAuthor, Collection $messages): array {
            $fileAttachmentsDisk = $component->getFileAttachmentsDisk();

            return collect(array_combine($message->attachments, $message->attachment_file_names))
                ->filter(static fn (string $attachmentOriginalName, string $attachmentPath) => $fileAttachmentsDisk->exists($attachmentPath))
                ->mapWithKeys(function (string $attachmentOriginalName, string $attachmentPath) use ($component, $fileAttachmentsDisk, $message, $messageAuthor, $messages) {
                    $attachmentMimeType = $fileAttachmentsDisk->mimeType($attachmentPath);
                    $hasImageMimeType = $component->isImageMimeType($attachmentMimeType);

                    return [
                        $attachmentPath => [
                            'attachmentOriginalName' => $attachmentOriginalName,
                            'attachmentMimeType' => $attachmentMimeType,
                            'hasImageMimeType' => $hasImageMimeType,
                            'shouldShowOnlyMessageImageAttachment' => $component->shouldShowOnlyMessageImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, $message, $messageAuthor, $messages),
                        ],
                    ];
                })
                ->toArray();
        });

        $this->messenger();

        $this->showNewMessagesDivider(static function (Message $message, Collection $unreadMessages, Collection $messages, ConversationManager $livewire): bool {
            if (collect($livewire->messagesCreatedDuringConversationSession)->contains('createdByAuthenticatedUser', true)) {
                return false;
            }

            if (! $livewire->oldestNewMessageKey) {
                $authenticatedUserKey = $livewire->getActiveConversationAuthenticatedUserParticipation()->getKey();
                $livewire->oldestNewMessageKey = $unreadMessages->first(static fn (Message $message) => $authenticatedUserKey !== $message->author_id)?->getKey();
            }

            return $livewire->oldestNewMessageKey === $message->getKey();
        });

        $this->getNewMessagesDividerContentUsing(static function (Message $message, Collection $messages, ConversationManager $livewire): ?string {
            if ($livewire->oldestNewMessageKey !== $message->getKey()) {
                return null;
            }

            $newMessagesCount = $messages
                ->filter(static fn (Message $msg) => $message->created_at->lte($msg->created_at))
                ->count();

            return trans_choice('filament-converse::conversation-thread.new-messages-divider-content.label', $newMessagesCount, [
                'count' => $newMessagesCount,
            ]);
        });

        $this->messageColor(static function (Authenticatable $messageAuthor): string {
            return $messageAuthor->getKey() === auth()->id() ? 'primary' : 'gray';
        });

        $this->shortenedReadReceiptMessage(static function (Conversation $conversation, Message $message, Collection $readByParticipationsAsLastMessage): ?string {
            $user = auth()->user();

            if (! in_array(Conversable::class, class_uses_recursive($user))) {
                FilamentConverseException::throwInvalidConversableUserException($user);
            }

            $userNameAttribute = $user::getFilamentNameAttribute();

            $otherParticipantNames = $readByParticipationsAsLastMessage
                ->reject(
                    static fn (ConversationParticipation $participation) => $participation->getKey() === $message->author_id ||
                    $participation->participant_id === $user->getKey()
                )
                ->pluck('participant.' . $userNameAttribute);
            $readByOtherParticipationsCount = $otherParticipantNames->count();

            return match (true) {
                $readByOtherParticipationsCount === 0 => null,
                $readByOtherParticipationsCount === 1 => $conversation->isDirect() || ($conversation->participations->count() === 2)
                    ? __('filament-converse::conversation-thread.read-receipt.seen')
                    : __('filament-converse::conversation-thread.read-receipt.seen-by-one', [
                        'name' => $otherParticipantNames->first(),
                    ]),
                $readByOtherParticipationsCount === 2 => __('filament-converse::conversation-thread.read-receipt.seen-by-two', [
                    'firstName' => $otherParticipantNames->get(0),
                    'secondName' => $otherParticipantNames->get(1),
                ]),
                $readByOtherParticipationsCount === 3 => __('filament-converse::conversation-thread.read-receipt.seen-by-three', [
                    'firstName' => $otherParticipantNames->get(0),
                    'secondName' => $otherParticipantNames->get(1),
                    'thirdName' => $otherParticipantNames->get(2),
                ]),
                $readByParticipationsAsLastMessage->count() === $conversation->participations->count() => __('filament-converse::conversation-thread.read-receipt.seen-by-everyone'),
                default => __('filament-converse::conversation-thread.read-receipt.seen-by-many-shortened', [
                    'firstName' => $otherParticipantNames->get(0),
                    'secondName' => $otherParticipantNames->get(1),
                    'thirdName' => $otherParticipantNames->get(2),
                    'othersCount' => $readByOtherParticipationsCount - 3,
                ])
            };
        });

        $this->showFullReadReceiptMessage(static function (Conversation $conversation, Message $message, Collection $readByParticipationsAsLastMessage): bool {
            $user = auth()->user();

            if (! in_array(Conversable::class, class_uses_recursive($user))) {
                FilamentConverseException::throwInvalidConversableUserException($user);
            }

            $otherReadByParticipations = $readByParticipationsAsLastMessage->reject(
                static fn (ConversationParticipation $participation) => $participation->getKey() === $message->author_id ||
                $participation->participant_id === $user->getKey()
            );

            return $readByParticipationsAsLastMessage->count() !== $conversation->participations->count() && $otherReadByParticipations->count() > 4;
        });

        $this->fullReadReceiptMessage(static function (Conversation $conversation, Message $message, Collection $readByParticipationsAsLastMessage): ?string {
            $user = auth()->user();

            if (! in_array(Conversable::class, class_uses_recursive($user))) {
                FilamentConverseException::throwInvalidConversableUserException($user);
            }

            $userNameAttribute = $user::getFilamentNameAttribute();

            $otherParticipantNames = $readByParticipationsAsLastMessage
                ->reject(
                    static fn (ConversationParticipation $participation) => $participation->getKey() === $message->author_id ||
                    $participation->participant_id === $user->getKey()
                )
                ->pluck('participant.' . $userNameAttribute);
            $readByOtherParticipationsCount = $otherParticipantNames->count();

            return match (true) {
                $readByOtherParticipationsCount === 0 => null,
                $readByOtherParticipationsCount === 1 => $conversation->isDirect() || ($conversation->participations->count() === 2)
                    ? __('filament-converse::conversation-thread.read-receipt.seen')
                    : __('filament-converse::conversation-thread.read-receipt.seen-by-one', [
                        'name' => $otherParticipantNames->first(),
                    ]),
                $readByParticipationsAsLastMessage->count() === $conversation->participations->count() => __('filament-converse::conversation-thread.read-receipt.seen-by-everyone'),
                default => __('filament-converse::conversation-thread.read-receipt.seen-by-many-full', [
                    'names' => $otherParticipantNames->slice(0, -1)->join(', '),
                    'lastName' => $otherParticipantNames->last(),
                ]),
            };
        });

        $this->userTypingTranslations([
            'single' => __('filament-converse::conversation-thread.typing-indicator.single'),
            'double' => __('filament-converse::conversation-thread.typing-indicator.double'),
            'multiple' => __('filament-converse::conversation-thread.typing-indicator.multiple'),
            'other' => __('filament-converse::conversation-thread.typing-indicator.other'),
            'others' => __('filament-converse::conversation-thread.typing-indicator.others'),
        ]);

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

        $this->defaultFileAttachmentIcon(function (string $attachmentMimeType): Heroicon {
            return match ($attachmentMimeType) {
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

        $this->defaultFileAttachmentIconColor(static function (string $attachmentMimeType): string {
            return match ($attachmentMimeType) {
                'application/pdf', => 'danger',
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                default => 'primary',
            };
        });

        $this->defaultFileAttachmentMimeTypeBadgeLabel(static function (string $attachmentMimeType): ?string {
            return match ($attachmentMimeType) {
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

    public function classic(): static
    {
        $this->getMessageAuthorNameUsing(static function (Authenticatable $messageAuthor): ?string {
            return $messageAuthor->getKey() !== auth()->id() ? $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute()) : null;
        });

        $this->getMessageAuthorAvatarUsing(static function (Authenticatable $messageAuthor): ?string {
            return $messageAuthor->getKey() !== auth()->id() ? filament()->getUserAvatarUrl($messageAuthor) : null;
        });

        $this->getMessageTimestampUsing(static function (Message $message): string {
            $timestamp = $message->created_at;

            return match (true) {
                ! $timestamp->isCurrentYear() => $timestamp->isoFormat('L LT'),
                $timestamp->isToday() => $timestamp->isoFormat('LT'),
                default => $timestamp->isoFormat('D.M LT'),
            };
        });

        $this->messageDividerContent(static function (Message $message, Collection $messages): ?string {
            $currentMessageIndex = $messages->search(static fn (Message $msg) => $msg->getKey() === $message->getKey());

            if ($currentMessageIndex === false) {
                return null;
            }
            /* @var ?Message $nextMessage */
            $nextMessage = $messages->get($currentMessageIndex + 1);
            $currentMessageTimestamp = $message->created_at;

            if ($nextMessage && $currentMessageTimestamp->isSameDay($nextMessage->created_at)) {
                return null;
            }

            return match (true) {
                $currentMessageTimestamp->isToday() => __('filament-converse::conversation-thread.message-divider-content.today'),
                $currentMessageTimestamp->isYesterday() => __('filament-converse::conversation-thread.message-divider-content.yesterday'),
                $currentMessageTimestamp->isCurrentWeek() => $currentMessageTimestamp->isoFormat('dddd'),
                $currentMessageTimestamp->isCurrentYear() => $currentMessageTimestamp->isoFormat('D MMMM'),
                default => $currentMessageTimestamp->isoFormat('L'),
            };
        });

        return $this;
    }

    public function messenger(): static
    {
        $this->getMessageAuthorNameUsing(static function (Conversation $conversation, Message $message, Authenticatable $messageAuthor, Collection $messages): ?string {
            if ($messageAuthor->getKey() === auth()->id()) {
                return null;
            }

            $currentMessageIndex = $messages->search(static fn (Message $msg) => $msg->getKey() === $message->getKey());

            if ($currentMessageIndex === false) {
                return null;
            }

            /* @var ?Message $nextMessage */
            $nextMessage = $messages->get($currentMessageIndex + 1);

            if ($nextMessage && $conversation->participations->firstWhere((new ConversationParticipation)->getKeyName(), $nextMessage->author_id)->participant_id === $messageAuthor->getKey()) {
                return null;
            }

            return $messageAuthor->getAttributeValue($messageAuthor::getFilamentNameAttribute());
        });

        $this->getMessageAuthorAvatarUsing(static function (Conversation $conversation, Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null {
            if ($messageAuthor->getKey() === auth()->id()) {
                return null;
            }

            $currentMessageIndex = $messages->search(static fn (Message $msg) => $msg->getKey() === $message->getKey());

            if ($currentMessageIndex === false) {
                return null;
            }

            /* @var ?Message $previousMessage */
            $previousMessage = $messages->get($currentMessageIndex - 1);

            if ($previousMessage && $conversation->participations->firstWhere((new ConversationParticipation)->getKeyName(), $previousMessage->author_id)->participant_id === $messageAuthor->getKey()) {
                return new HtmlString("<div style='width: 32px' content: ''></div>");
            }

            return filament()->getUserAvatarUrl($messageAuthor);
        });

        $this->getMessageTimestampUsing();

        $this->messageDividerContent(static function (Message $message, Collection $messages): ?string {
            $currentMessageIndex = $messages->search(static fn (Message $msg) => $msg->getKey() === $message->getKey());

            if ($currentMessageIndex === false) {
                return null;
            }
            /* @var ?Message $nextMessage */
            $nextMessage = $messages->get($currentMessageIndex + 1);
            $currentMessageTimestamp = $message->created_at;

            if ($nextMessage && $nextMessage->created_at->diffInMinutes($currentMessageTimestamp) <= 7) {
                return null;
            }

            return match (true) {
                ! $currentMessageTimestamp->isCurrentYear() => $currentMessageTimestamp->isoFormat('L LT'),
                $currentMessageTimestamp->isCurrentWeek() && ! $currentMessageTimestamp->isToday() => $currentMessageTimestamp->isoFormat('ddd LT'),
                ! $currentMessageTimestamp->isToday() => $currentMessageTimestamp->isoFormat('D MMMM LT'),
                default => $currentMessageTimestamp->isoFormat('LT'),
            };
        });

        return $this;
    }

    public function maxHeight(int | Closure | null $maxHeight): static
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    public function defaultLoadedMessagesCount(int | Closure | null $count): static
    {
        $this->defaultLoadedMessagesCount = $count;

        return $this;
    }

    public function messagesLoadedPerPage(int | Closure | null $count): static
    {
        $this->messagesLoadedPerPage = $count;

        return $this;
    }

    public function getMessageAuthorNameUsing(?Closure $callback = null): static
    {
        $this->getMessageAuthorNameUsing = $callback;

        return $this;
    }

    public function getMessageAuthorAvatarUsing(?Closure $callback = null): static
    {
        $this->getMessageAuthorAvatarUsing = $callback;

        return $this;
    }

    public function getMessageTimestampUsing(?Closure $callback = null): static
    {
        $this->getMessageTimestampUsing = $callback;

        return $this;
    }

    public function getMessageContentUsing(?Closure $callback = null): static
    {
        $this->getMessageContentUsing = $callback;

        return $this;
    }

    public function messageDividerContent(string | Htmlable | Closure | null $content): static
    {
        $this->messageDividerContent = $content;

        return $this;
    }

    public function showNewMessagesDivider(bool | Closure | null $condition): static
    {
        $this->shouldShowNewMessagesDivider = $condition;

        return $this;
    }

    public function getNewMessagesDividerContentUsing(?Closure $callback = null): static
    {
        $this->getNewMessagesDividerContentUsing = $callback;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function newMessagesDividerColor(string | array | Closure | null $color): static
    {
        $this->NewMessagesDividerColor = $color;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function messageColor(string | array | Closure | null $color = null): static
    {
        $this->messageColor = $color;

        return $this;
    }

    public function autoScrollOnForeignMessagesThreshold(int | Closure | null $pixels): static
    {
        $this->autoScrollOnForeignMessagesThreshold = $pixels;

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

    public function getMaxHeight(): ?int
    {
        return $this->evaluate($this->maxHeight);
    }

    public function getDefaultLoadedMessagesCount(): int
    {
        return $this->evaluate($this->defaultLoadedMessagesCount) ?? 15;
    }

    public function getMessagesLoadedPerPage(): int
    {
        return $this->evaluate($this->messagesLoadedPerPage) ?? 15;
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageAuthorName(Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getMessageAuthorNameUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageAuthorAvatar(Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getMessageAuthorAvatarUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageTimestamp(Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getMessageTimestampUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageContent(Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getMessageContentUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageDividerContent(Message $message, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->messageDividerContent, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @param  Collection<int, Message>  $unreadMessages
     */
    public function shouldShowNewMessagesDivider(Message $message, Authenticatable $messageAuthor, Collection $messages, Collection $unreadMessages): bool
    {
        return (bool) $this->evaluate($this->shouldShowNewMessagesDivider, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
            'unreadMessages' => $unreadMessages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @param  Collection<int, Message>  $unreadMessages
     */
    public function getNewMessagesDividerContent(Message $message, Authenticatable $messageAuthor, Collection $messages, Collection $unreadMessages): string | Htmlable | null
    {
        return $this->evaluate($this->getNewMessagesDividerContentUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
            'unreadMessages' => $unreadMessages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @param  Collection<int, Message>  $unreadMessages
     */
    public function getNewMessagesDividerColor(Message $message, Authenticatable $messageAuthor, Collection $messages, Collection $unreadMessages): string | array
    {
        return $this->evaluate($this->NewMessagesDividerColor, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
            'unreadMessages' => $unreadMessages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
        ]) ?? 'primary';
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getMessageColor(Message $message, Authenticatable $messageAuthor, Collection $messages): string | array
    {
        return $this->evaluate($this->messageColor, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]) ?? 'gray';
    }

    public function getAutoScrollOnForeignMessagesThreshold(): int
    {
        return $this->evaluate($this->autoScrollOnForeignMessagesThreshold) ?? 300;
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
                + (($livewire->getActiveConversationMessagesPage() - 1) * $this->getMessagesLoadedPerPage());

            $extra = count(array_filter($livewire->messagesCreatedDuringConversationSession, static fn (array $data) => $data['exists'] === true));

            $query->limit($limit + $extra);
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
            ->keyBindings(['enter'])
            ->action(static function (ConversationThread $component, ConversationManager $livewire) {
                $state = $livewire->content->getState();
                $statePath = $component->getStatePath();

                $message = data_get([$livewire->content->getStatePath() => $state], $statePath);
                $uploadedFileAttachments = $component->getValidUploadedFileAttachments();

                if (blank($message) && blank($uploadedFileAttachments)) {
                    return;
                }

                $attachments = $attachmentFileNames = [];

                foreach ($uploadedFileAttachments as $attachment) {
                    $attachments[] = $component->saveUploadedFileAttachment($attachment);
                    $attachmentFileNames[] = $attachment->getClientOriginalName();
                }

                $activeConversation = $livewire->getActiveConversation();
                $message = $livewire->getActiveConversationAuthenticatedUserParticipation()->sendMessage($activeConversation, [
                    'content' => $message,
                    'attachments' => $attachments,
                    'attachment_file_names' => $attachmentFileNames,
                ]);

                $livewire->content->fill();
                data_set($livewire->componentFileAttachments, $statePath . ".{$activeConversation->getKey()}", []);
                data_set($livewire->cachedUnsendMessages, $statePath . ".{$activeConversation->getKey()}", null);
                $livewire->registerMessageCreatedDuringConversationSession($message->getKey(), auth()->id());
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

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'conversation',
            'activeConversation' => [$this->getActiveConversation()],
            'messages' => [$this->getMessagesQuery(false)?->get()],
            'livewire' => [$this->getLivewire()],
            default => []
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Conversation::class => [$this->getActiveConversation()],
            Collection::class => [$this->getMessagesQuery(false)?->get()],
            ConversationManager::class => [$this->getLivewire()],
            default => []
        };
    }
}

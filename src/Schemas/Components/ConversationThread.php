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
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use League\Flysystem\UnableToCheckFileExistence;

class ConversationThread extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasExtraMessageAttributes;
    use Concerns\HasReadReceipts;
    use Concerns\HasTypingIndicator;
    use Concerns\HasFileAttachments;
    use HasKey;
    use HasExtraAttributes;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const MESSAGE_ACTIONS_KEY = 'message_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-thread';

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

    protected string | Closure | null $fileAttachmentsDiskName = null;

    protected string | Closure | null $fileAttachmentsVisibility = null;

    protected ?Closure $getFileAttachmentUrlUsing = null;

    protected ?Closure $modifyMessagesQueryUsing = null;

    protected ?Closure $getMessageAttachmentDataUsing = null;

    protected ?Closure $modifyUploadAttachmentActionUsing = null;

    protected ?Closure $modifyEditConversationActionUsing = null;

    protected ?Closure $modifyEditMessageActionUsing = null;

    protected ?Closure $modifyDeleteMessageActionUsing = null;

    protected ?Closure $modifyAttachmentAreaUsing = null;

    protected ?Closure $modifyTextareaUsing = null;

    protected ?Closure $sendMessageUsing = null;

    protected ?Closure $modifySendMessageActionUsing = null;

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key('conversation_thread');

        $this->emptyStateHeading(__('filament-converse::conversation-thread.empty-state.heading'));

        $this->schema(static fn (ConversationThread $component) => [
            FusedGroup::make([
                $component->getAttachmentAreaComponent(),
                $component->getTextareaComponent(),
                Actions::make([
                    $component->getUploadAttachmentAction(),
                    $component->getSendMessageAction(),
                ])
                    ->alignBetween()
            ])
        ]);

        $this->sendMessageUsing(static function (Conversationmanager $livewire, array $data): ?Message {
            $messageContent = $data['messageContent'] ?? null;
            $uploadedAttachments = $data['attachments'] ?? [];

            if (blank($messageContent) && blank($uploadedAttachments)) {
                return null;
            }

            $attachments = $attachmentFileNames = [];

            foreach ($uploadedAttachments as $storedFileName => $attachment) {
                $attachments[] = $storedFileName;
                $attachmentFileNames[] = $attachment->getClientOriginalName();
            }

            return $livewire->getActiveConversationAuthenticatedUserParticipation()->sendMessage($livewire->getActiveConversation(), [
                'content' => $messageContent,
                'attachments' => $attachments,
                'attachment_file_names' => $attachmentFileNames,
            ]);
        });

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
                            'shouldShowOnlyMessageImageAttachment' => $component->shouldShowOnlyImageAttachment($attachmentPath, $attachmentOriginalName, $attachmentMimeType, [
                                'message' => $message,
                                'messageAuthor' => $messageAuthor,
                                'messages' => $messages
                            ]),
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
                $readByOtherParticipationsCount === 1 => $conversation->participations->count() === 2
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
                $readByOtherParticipationsCount === 1 => $conversation->participations->count() === 2
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

        $this->fileAttachmentIcon(function (string $attachmentMimeType): Heroicon {
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

        $this->fileAttachmentIconColor(static function (string $attachmentMimeType): string {
            return match ($attachmentMimeType) {
                'application/pdf', => 'danger',
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                default => 'primary',
            };
        });

        $this->fileAttachmentMimeTypeBadgeLabel(static function (string $attachmentMimeType): ?string {
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

    public function fileAttachmentsDisk(string | Closure | null $name): static
    {
        $this->fileAttachmentsDiskName = $name;

        return $this;
    }

    public function fileAttachmentsVisibility(string | Closure | null $visibility): static
    {
        $this->fileAttachmentsVisibility = $visibility;

        return $this;
    }

    public function getFileAttachmentUrlUsing(?Closure $callback): static
    {
        $this->getFileAttachmentUrlUsing = $callback;

        return $this;
    }

    public function modifyMessagesQueryUsing(?Closure $callback): static
    {
        $this->modifyMessagesQueryUsing = $callback;

        return $this;
    }

    public function getMessageAttachmentDataUsing(?Closure $callback): static
    {
        $this->getMessageAttachmentDataUsing = $callback;

        return $this;
    }

    public function editConversationAction(?Closure $callback): static
    {
        $this->modifyEditConversationActionUsing = $callback;

        return $this;
    }

    public function uploadAttachmentAction(?Closure $callback): static
    {
        $this->modifyUploadAttachmentActionUsing = $callback;

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

    public function sendMessageUsing(?Closure $callback): static
    {
        $this->sendMessageUsing = $callback;

        return $this;
    }

    public function modifyTextareaUsing(?Closure $callback): static
    {
        $this->modifyTextareaUsing = $callback;

        return $this;
    }

    public function modifyAttachmentAreaUsing(?Closure $callback): static
    {
        $this->modifyAttachmentAreaUsing = $callback;

        return $this;
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

    public function getFileAttachmentsDisk(): Filesystem
    {
        return Storage::disk($this->getFileAttachmentsDiskName());
    }

    public function getFileAttachmentsDiskName(): string
    {
        $name = $this->evaluate($this->fileAttachmentsDiskName);

        if (filled($name)) {
            return $name;
        }

        $name = config('filament.default_filesystem_disk');

        if ($name !== 'local') {
            return $name;
        }

        if ($this->getFileAttachmentsVisibility() !== 'public') {
            return $name;
        }

        return 'public';
    }

    public function getFileAttachmentsVisibility(): string
    {
        return $this->evaluate($this->fileAttachmentsVisibility) ?? 'public';
    }

    public function getFileAttachmentUrl(mixed $file): ?string
    {
        if ($this->getFileAttachmentUrlUsing) {
            return $this->evaluate($this->getFileAttachmentUrlUsing, [
                'file' => $file,
            ]);
        }

        /** @var FilesystemAdapter $storage */
        $storage = $this->getFileAttachmentsDisk();

        try {
            if (! $storage->exists($file)) {
                return null;
            }
        } catch (UnableToCheckFileExistence $exception) {
            return null;
        }

        if ($this->getFileAttachmentsVisibility() === 'private') {
            try {
                return $storage->temporaryUrl(
                    $file,
                    now()->addMinutes(30)->endOfHour(),
                );
            } catch (Throwable $exception) {
                // This driver does not support creating temporary URLs.
            }
        }

        return $storage->url($file);
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

    /**
     * @param  Collection<int, Message>  $messages
     * @return array{attachmentOriginalName: string, attachmentMimeType: string, hasImageMimeType: bool, shouldShowOnlyMessageImageAttachment: bool}
     */
    public function getMessageAttachmentData(Message $message, Authenticatable $messageAuthor, Collection $messages): array
    {
        return $this->evaluate($this->getMessageAttachmentDataUsing, [
            'message' => $message,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Message::class => $message,
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    public function getUploadAttachmentAction(): Action
    {
        $action = Action::make('uploadAttachment')
            ->label(__('filament-converse::conversation-thread.footer-actions.upload-attachment-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperClip)
            ->alpineClickHandler("\$dispatch('filament-converse-trigger-file-input')");

        if ($this->modifyUploadAttachmentActionUsing) {
            $action = $this->evaluate($this->modifyUploadAttachmentActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getEditConversationAction(): Action
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

    public function getEditMessageAction(): Action
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

    public function getDeleteMessageAction(): Action
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

    public function getAttachmentAreaComponent(): Field
    {
        $component = AttachmentArea::make('attachments')
            ->getFileAttachmentNameUsing($this->getFileAttachmentName(...))
            ->getFileAttachmentToolbarUsing($this->getFileAttachmentToolbar(...))
            ->showOnlyImageAttachment($this->shouldShowOnlyImageAttachment(...))
            ->previewImageAttachment($this->shouldPreviewImageAttachment(...))
            ->fileAttachmentIcon($this->getFileAttachmentIcon(...))
            ->fileAttachmentIconColor($this->getFileAttachmentIconColor(...))
            ->fileAttachmentMimeTypeBadgeLabel($this->getFileAttachmentMimeTypeBadgeLabel(...))
            ->fileAttachmentMimeTypeBadgeIcon($this->getFileAttachmentMimeTypeBadgeIcon(...))
            ->fileAttachmentMimeTypeBadgeColor($this->getFileAttachmentMimeTypeBadgeColor(...))
            ->requiredWithout('messageContent')
            ->validationMessages([
                'required_without' => __('filament-converse::conversation-thread.validation.message-required') . 'a',
            ]);

        if ($this->modifyAttachmentAreaUsing) {
            $component = $this->evaluate($this->modifyAttachmentAreaUsing, [
                'component' => $component,
            ], [
                AttachmentArea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getTextareaComponent(): Field
    {
        $component = Textarea::make('messageContent')
            ->hiddenLabel()
            ->autosize()
            ->autofocus()
            ->maxLength(65535)
            ->placeholder(__('filament-converse::conversation-thread.placeholder'))
            ->requiredWithout('attachments')
            ->validationMessages([
                'required_without' => __('filament-converse::conversation-thread.validation.message-required'). 'b',
            ])
            ->extraAttributes([
                'style' => 'max-height: 8rem; overflow: auto'
            ])
            ->extraAlpineAttributes([
                'x-on:keydown' => '$nextTick(() => fireUserTypingEvent($event))'
            ]);

        if ($this->modifyTextareaUsing) {
            $component = $this->evaluate($this->modifyTextareaUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getSendMessageAction(): Action
    {
        $action = Action::make('sendMessage')
            ->label(__('filament-converse::conversation-thread.footer-actions.send-message-label'))
            ->iconButton()
            ->iconSize(IconSize::Large)
            ->icon(Heroicon::PaperAirplane)
            ->keyBindings(['enter'])
            ->action(function (ConversationManager $livewire) {
                $data = $livewire->content->getState();

                if (! $this->sendMessageUsing) {
                    return;
                }

                /* @var ?Message $message */
                $message = $this->evaluate($this->sendMessageUsing, [
                    'data' => $data['conversation_schema'] ?? []
                ]);

                if (! $message) {
                    return;
                }

                $statePath = $livewire->getConversationSchema()->getConversationThread()->getStatePath();
                $activeConversation = $livewire->getActiveConversation();

                data_set($livewire->cachedUnsendMessages, $statePath . ".{$activeConversation->getKey()}", null);
                $livewire->registerMessageCreatedDuringConversationSession($message->getKey(), auth()->id());

                $livewire->content->fill();
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

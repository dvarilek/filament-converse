<?php

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Schemas\Components\Concerns\BelongsToLivewire;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Component as LivewireComponent;

class ConversationSchema extends Component
{
    use BelongsToLivewire;
    use HasKey;

    protected ?Closure $modifyConversationListUsing = null;

    protected ?Closure $modifyConversationThreadUsing = null;

    protected ?Closure $sortConversationsUsing = null;

    protected bool | Closure | null $persistsActiveConversationInSession = false;

    protected ?Closure $getDefaultActiveConversationUsing = null;

    protected ?Closure $conversationImageName = null;

    protected ?Closure $conversationImageUrl = null;

    protected ?Closure $defaultConversationImageData = null;

    protected string | Closure | null $conversationImageDiskName = null;

    protected string | Closure | null $conversationImageVisibility = null;

    protected bool | Closure $shouldCheckConversationImageExistence = true;

    protected string $view = 'filament-converse::conversation-schema';

    final public function __construct(LivewireComponent & HasSchemas & HasConversationSchema $livewire)
    {
        $this->livewire($livewire);
    }

    public static function make(LivewireComponent & HasSchemas & HasConversationSchema $livewire): static
    {
        $static = app(static::class, ['livewire' => $livewire]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columns(3);

        $this->schema(fn () => [
            $this->getConversationList(),
            $this->getConversationThread(),
        ]);

        $this->sortConversationsUsing(static function (Collection $conversations) {
            return $conversations->sortByDesc([
                static fn (Conversation $conversation) => $conversation
                    ->participations
                    ->pluck('latestMessage')
                    ->filter()
                    ->max('created_at')?->timestamp ?? 0,
                static fn (Conversation $conversation) => $conversation
                    ->created_at
                    ->timestamp,
            ]);
        });

        $this->getDefaultActiveConversationUsing(static function (Collection $conversations) {
            return $conversations->first();
        });

        $this->getConversationNameUsing(static function (Conversation $conversation) {
            return $conversation->getName();
        });

        $this->getConversationImageUrlUsing(static function (ConversationSchema $component, Conversation $conversation) {
            $imageUrl = $conversation->image;

            if (! $imageUrl) {
                return null;
            }

            /** @var FilesystemAdapter $storage */
            $storage = $component->getConversationImageDisk();

            if ($component->shouldCheckConversationImageExistence()) {
                try {
                    if (! $storage->exists($imageUrl)) {
                        return null;
                    }
                } catch (UnableToCheckFileExistence $exception) {
                    return null;
                }
            }

            if ($component->getConversationImageVisibility() === 'private') {
                try {
                    return $storage->temporaryUrl(
                        $imageUrl,
                        now()->addMinutes(30)->endOfHour(),
                    );
                } catch (Throwable $exception) {
                    // This driver does not support creating temporary URLs.
                }
            }

            return $storage->url($imageUrl);
        });

        $this->getDefaultConversationImageDataUsing(static function (ConversationSchema $component, Conversation $conversation) {
            $otherConversationParticipations = $conversation->participations->where('participant_id', '!=', auth()->id());

            if (! $conversation->isGroup() || $conversation->participations->count() <= 2) {
                $participant = $otherConversationParticipations->first()->participant;

                return [
                    [
                        'source' => filament()->getUserAvatarUrl($participant),
                        'alt' => $participant->getAttributeValue($participant::getFilamentNameAttribute()),
                    ],
                ];
            }

            /* @var Collection<int, Message> $latestMessages */
            $latestMessages = $otherConversationParticipations
                ->pluck('latestMessage')
                ->filter()
                ->sortByDesc('created_at');

            if ($latestMessages->isEmpty()) {
                [$bottomAvatarParticipant, $topAvatarParticipant] = $otherConversationParticipations->pluck('participant');
            } else {
                $conversationParticipationPrimaryKey = (new ConversationParticipation)->getKeyName();

                $firstLatestMessage = $latestMessages->first();
                $secondLatestMessage = $latestMessages->firstWhere('author_id', '!=', $firstLatestMessage->author_id);

                $firstParticipationWithLatestMessage = $otherConversationParticipations
                    ->firstWhere($conversationParticipationPrimaryKey, $firstLatestMessage->author_id);

                $secondParticipationWithLatestMessage = $otherConversationParticipations
                    ->firstWhere($conversationParticipationPrimaryKey, $secondLatestMessage?->author_id ?? $firstParticipationWithLatestMessage->getKey());

                $bottomAvatarParticipant = $firstParticipationWithLatestMessage->participant;
                $topAvatarParticipant = $secondParticipationWithLatestMessage->participant;
            }

            return [
                [
                    'source' => filament()->getUserAvatarUrl($topAvatarParticipant),
                    'alt' => $topAvatarParticipant->getAttributeValue($topAvatarParticipant::getFilamentNameAttribute()),
                ],
                [
                    'source' => filament()->getUserAvatarUrl($bottomAvatarParticipant),
                    'alt' => $bottomAvatarParticipant->getAttributeValue($bottomAvatarParticipant::getFilamentNameAttribute()),
                ],
            ];
        });
    }

    public function conversationList(?Closure $callback): static
    {
        $this->modifyConversationListUsing = $callback;

        return $this;
    }

    public function conversationThread(?Closure $callback): static
    {
        $this->modifyConversationThreadUsing = $callback;

        return $this;
    }

    protected function sortConversationsUsing(Closure $callback): static
    {
        $this->sortConversationsUsing = $callback;

        return $this;
    }

    public function persistActiveConversationInSession(bool | Closure | null $condition = true): static
    {
        $this->persistsActiveConversationInSession = $condition;

        return $this;
    }

    public function getDefaultActiveConversationUsing(?Closure $callback = null): static
    {
        $this->getDefaultActiveConversationUsing = $callback;

        return $this;
    }

    public function getConversationNameUsing(Closure $callback): static
    {
        $this->conversationImageName = $callback;

        return $this;
    }

    public function getConversationImageUrlUsing(?Closure $callback = null): static
    {
        $this->conversationImageUrl = $callback;

        return $this;
    }

    public function getDefaultConversationImageDataUsing(?Closure $callback = null): static
    {
        $this->defaultConversationImageData = $callback;

        return $this;
    }

    public function conversationImageVisibility(string | Closure | null $visibility): static
    {
        $this->conversationImageVisibility = $visibility;

        return $this;
    }

    public function conversationImageDisk(string | Closure | null $name): static
    {
        $this->conversationImageDiskName = $name;

        return $this;
    }

    public function checkConversationImageExistence(bool | Closure $condition = true): static
    {
        $this->shouldCheckConversationImageExistence = $condition;

        return $this;
    }

    public function getConversationList(): ConversationList
    {
        $component = ConversationList::make()
            ->columnSpan(1);

        if ($this->modifyConversationListUsing) {
            $component = $this->evaluate($this->modifyConversationListUsing, [
                'component' => $component,
            ], [
                ConversationList::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getConversationThread(): ConversationThread
    {
        $component = ConversationThread::make()
            ->columnSpan(2);

        if ($this->modifyConversationThreadUsing) {
            $component = $this->evaluate($this->modifyConversationThreadUsing, [
                'component' => $component,
            ], [
                ConversationThread::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function shouldPersistActiveConversationInSession(): bool
    {
        return (bool) $this->evaluate($this->persistsActiveConversationInSession);
    }

    public function getConversationName(Conversation $conversation): string | Htmlable | null
    {
        return $this->evaluate($this->conversationImageName, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getConversationImageUrl(Conversation $conversation): ?string
    {
        return $this->evaluate($this->conversationImageUrl, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    /**
     * @return array<int, array{source: string, alt: string}>
     */
    public function getDefaultConversationImageData(Conversation $conversation): array
    {
        return $this->evaluate($this->defaultConversationImageData, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]) ?? [];
    }

    public function getConversationImageVisibility(): string
    {
        $visibility = $this->getCustomConversationImageVisibility();

        if (filled($visibility)) {
            return $visibility;
        }

        return ($this->getConversationImageDiskName() === 'public') ? 'public' : 'private';
    }

    public function getCustomConversationImageVisibility(): ?string
    {
        return $this->evaluate($this->conversationImageVisibility);
    }

    public function getConversationImageDisk(): Filesystem
    {
        return Storage::disk($this->getConversationImageDiskName());
    }

    public function getConversationImageDiskName(): string
    {
        $name = $this->evaluate($this->conversationImageDiskName);

        if (filled($name)) {
            return $name;
        }

        $defaultName = config('filament.default_filesystem_disk');

        if (
            ($defaultName === 'public')
            && ($this->getCustomConversationImageVisibility() === 'private')
        ) {
            return 'local';
        }

        return $defaultName;
    }

    public function shouldCheckConversationImageExistence(): bool
    {
        return (bool) $this->evaluate($this->shouldCheckConversationImageExistence);
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        $conversations = $this->getLivewire()->conversations;

        if ($this->sortConversationsUsing) {
            $conversations = $this->evaluate($this->sortConversationsUsing, [
                'conversations' => $conversations,
            ], [
                Collection::class => $conversations,
            ]) ?? $conversations;
        }

        return $conversations;
    }

    public function getActiveConversation(): ?Conversation
    {
        return $this->getLivewire()->getActiveConversation();
    }

    public function getDefaultActiveConversation(): ?Conversation
    {
        $conversations = $this->getConversations();

        return $this->evaluate($this->getDefaultActiveConversationUsing, [
            'conversations' => $conversations,
        ], [
            Collection::class => $conversations,
        ]);
    }
}

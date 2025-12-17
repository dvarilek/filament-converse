<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Actions\ReadConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property-read Carbon|null $last_read_at
 * @property-read int|string $participant_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection<int, Message> $messages
 * @property-read Message|null $latestMessage
 * @property-read Collection<int, Conversation> $createdConversations
 * @property-read Authenticatable&Model $participant
 *
 * @method void unreadMessagesCount()
 */
class ConversationParticipation extends Model
{
    use HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'last_read_at',
        'conversation_id',
        'participant_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'id' => 'string',
        'last_read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Conversation, static>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * @return HasMany<Conversation, static>
     */
    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'creator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'author_id');
    }

    /**
     * @return HasOne<Message, static>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'author_id')
            ->latestOfMany('created_at');
    }

    /**
     * @return BelongsTo<Authenticatable & Model, static>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(FilamentConverseServiceProvider::getFilamentConverseUserModel(), 'participant_id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function sendMessage(Conversation $conversation, array $attributes): Message
    {
        return app(SendMessage::class)->handle($this, $conversation, $attributes);
    }

    public function readConversation(Conversation $conversation): void
    {
        app(ReadConversation::class)->handle($this, $conversation);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeUnreadMessagesCount(Builder $query): void
    {
        $query->addSelect([
            'conversation_participations.*',
            'unread_messages_count' => Message::selectRaw('count(*)')
                ->join('conversation_participations as author_cp', 'messages.author_id', '=', 'author_cp.id')
                ->whereColumn('author_cp.conversation_id', 'conversation_participations.conversation_id')
                ->where(function (Builder $subQuery) {
                    $subQuery->whereColumn('messages.created_at', '>', 'conversation_participations.last_read_at')
                        ->orWhereNull('conversation_participations.last_read_at');
                }),
        ]);
    }
}

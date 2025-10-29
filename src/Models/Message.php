<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string|null $content
 * @property list<string> $attachments
 * @property string $conversation_id
 * @property int|string $author_id
 * @property string|null $reply_to_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Conversation $conversation
 * @property ConversationParticipation $author
 * @property Message|null $reply
 * @property Collection<int, Message> $replies
 */
class Message extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content',
        'attachments',
        'reply_to_message_id',
        'author_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'id' => 'string',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Message, static>
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    /**
     * @return BelongsTo<ConversationParticipation, static>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipation::class, 'author_id');
    }

    /**
     * @return HasMany<Message, static>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function isUpdated(): bool
    {
        return $this->updated_at->isAfter($this->created_at);
    }
}

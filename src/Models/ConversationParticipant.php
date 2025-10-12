<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Carbon|null $joined_at
 * @property Carbon|null $invited_at
 * @property Carbon|null $last_read_at
 * @property string $conversation_id
 * @property string $participant_type
 * @property int|string $participant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Conversation $conversation
 * @property Collection<int, Conversation> $createdConversations
 * @property Authenticatable&Model $participant
 */
class ConversationParticipant extends Model
{
    use HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'joined_at',
        'invited_at',
        'last_read_at',
        'conversation_id',
        'participant_type',
        'participant_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'id' => 'string',
        'joined_at' => 'datetime',
        'invited_at' => 'datetime',
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
        return $this->hasMany(Conversation::class, 'created_by');
    }

    /**
     * @return MorphTo<Model, static>
     */
    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->joined_at === null;
    }
}

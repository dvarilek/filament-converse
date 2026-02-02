<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property-read string|null $image
 * @property-read string|null $name
 * @property-read string|null $description
 * @property-read string $creator_id
 * @property-read int|string|null $subject_id
 * @property-read string|null $subject_type
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection<int, ConversationParticipation> $participations
 * @property-read Collection<int, ConversationParticipation> $otherParticipations
 * @property-read Collection<int, Message> $messages
 * @property-read ConversationParticipation|null $creator
 */
class Conversation extends Model
{
    use HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'image',
        'name',
        'description',
        'creator_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return HasMany<ConversationParticipation, static>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(ConversationParticipation::class, 'conversation_id');
    }

    /**
     * @return HasMany<ConversationParticipation, static>
     */
    public function otherParticipations(): HasMany
    {
        return $this->participations()->whereNot('participant_id', auth()->id());
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Message::class,
            ConversationParticipation::class,
            'conversation_id',
            'author_id',
            'id',
            'id'
        )->select('messages.*');
    }

    /**
     * @return BelongsTo<ConversationParticipation, static>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipation::class, 'creator_id');
    }
}

<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property string|null $image
 * @property string|null $name
 * @property string|null $description
 * @property string $owner_id
 * @property int|string|null $subject_id
 * @property string|null $subject_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ConversationParticipation> $participations
 * @property-read Collection<int, ConversationParticipation> $otherParticipations
 * @property-read Collection<int, Message> $messages
 * @property-read ConversationParticipation|null $owner
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
        'owner_id',
        'subject_id',
        'subject_type',
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
    public function owner(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipation::class, 'owner_id');
    }
}

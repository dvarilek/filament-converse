<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Exceptions\FilamentConverseException;
use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ConversationTypeEnum $type
 * @property string|null $image
 * @property string|null $name
 * @property string|null $description
 * @property string $creator_id
 * @property int|string|null $subject_id
 * @property string|null $subject_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, ConversationParticipation> $participations
 * @property Collection<int, ConversationParticipation> $otherParticipations
 * @property ConversationParticipation|null $creator
 */
class Conversation extends Model
{
    use HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'type',
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
        'type' => ConversationTypeEnum::class,
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

    /**
     * @return BelongsTo<ConversationParticipation, static>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipation::class, 'creator_id');
    }

    public function isDirect(): bool
    {
        return $this->type === ConversationTypeEnum::DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === ConversationTypeEnum::GROUP;
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $authenticatedParticipant = auth()->user();

        if (! in_array(Conversable::class, class_uses_recursive($authenticatedParticipant))) {
            FilamentConverseException::throwInvalidConversableUserException($authenticatedParticipant);
        }

        $participantNameAttribute = $authenticatedParticipant::getFilamentNameAttribute();

        if ($this->relationLoaded('participations')) {
            $participantNames = $this->participations
                ->where('participant_id', '!=', $authenticatedParticipant->getKey())
                ->pluck('participant.' . $participantNameAttribute);
        } else {
            $participantNames = $this->otherParticipations()
                ->with('participant')
                ->get()
                ->pluck('participant.' . $participantNameAttribute);
        }

        return match ($participantNames->count()) {
            0 => '',
            1 => $participantNames->first(),
            2 => $participantNames->join(' & '),
            default => $participantNames->slice(0, -1)->join(', ') . ' & ' . $participantNames->last()
        };
    }
}

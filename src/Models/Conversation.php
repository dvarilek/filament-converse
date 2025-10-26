<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property ConversationTypeEnum $type
 * @property string|null $image
 * @property string|null $name
 * @property string|null $description
 * @property string $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, ConversationParticipation> $participations
 * @property Collection<int, ConversationParticipation> $otherParticipations
 * @property ConversationParticipation|null $createdBy
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
        'created_by',
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipation::class, 'created_by');
    }

    public function isDirect(): bool
    {
        return $this->type === ConversationTypeEnum::DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === ConversationTypeEnum::GROUP;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function sendMessage(ConversationParticipation $author, array $attributes): Message
    {
        return app(SendMessage::class)->handle($author, $this, $attributes);
    }

    public function getLatestMessages(): Collection
    {
        
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $nameAttribute = FilamentConverseServiceProvider::getFilamentConverseUserModel()::getFilamentNameAttribute();

        $participantNames = $this->otherParticipations()
            ->with([
                'participant:id,' . $nameAttribute,
            ])
            ->get()
            ->pluck('participant.' . $nameAttribute);

        return match ($participantNames->count()) {
            0 => '',
            1 => $participantNames->first(),
            2 => $participantNames->join(' & '),
            default => $participantNames->slice(0, -1)->join(', ') . ' & ' . $participantNames->last()
        };
    }
}

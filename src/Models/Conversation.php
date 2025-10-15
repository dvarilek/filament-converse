<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $color
 * @property string $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Message> $messages
 * @property Collection<int, ConversationParticipation> $participations
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
        'color',
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
     * @return HasMany<Message, static>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<ConversationParticipation, static>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(ConversationParticipation::class);
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
    public function sendMessage(ConversationParticipation $sender, array $attributes): Message
    {
        return app(SendMessage::class)->handle($sender, $this, $attributes);
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $participantNames = $this->participations()
            ->whereNot(static function (Builder $query) {
                $user = auth()->user();

                return $query
                    ->where('participant_type', $user::class)
                    ->where('participant_id', $user->getKey());
            })
            ->pluck('participant_name')
            ->filter();

        return match ($participantNames->count()) {
            0 => '',
            1 => $participantNames->first(),
            2 => $participantNames->join(' & '),
            default => $participantNames->slice(0, -1)->join(', ') . ' & ' . $participantNames->last()
        };
    }
}

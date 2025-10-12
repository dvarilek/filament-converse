<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Models;

use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ConversationTypeEnum $type
 * @property string|null $name
 * @property string|null $description
 * @property string|null $color
 * @property string $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Message> $messages
 * @property Collection<int, ConversationParticipant> $participants
 * @property ConversationParticipant|null $createdBy
 */
class Conversation extends Model
{
    use HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'type',
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
     * @return HasMany<ConversationParticipant, static>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return BelongsTo<ConversationParticipant, static>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipant::class, 'created_by');
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
    public function sendMessage(ConversationParticipant $sender, array $attributes): Message
    {
        return app(SendMessage::class)->handle($sender, $this, $attributes);
    }
}

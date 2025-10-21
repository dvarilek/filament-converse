<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('content')->nullable()->default(null);
            $table->json('attachments')->nullable()->default(null);
            $table->foreignIdFor(Conversation::class, 'conversation_id')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Message::class, 'reply_to_message_id')
                ->nullable()->default(null)
                ->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(ConversationParticipation::class, 'author_id')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

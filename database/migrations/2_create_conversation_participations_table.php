<?php

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Conversation::class, 'conversation_id')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('participant_name')->nullable()->default(null);
            $table->string('participant_avatar_source')->nullable()->default(null);
            $table->morphs('participant');
            $table->timestamp('joined_at')->nullable()->default(null);
            $table->timestamp('invited_at')->nullable()->default(null);
            $table->timestamp('last_read_at')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_id', 'participant_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participations');
    }
};

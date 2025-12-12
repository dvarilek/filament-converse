<?php

use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
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
            $table->foreignIdFor(FilamentConverseServiceProvider::getFilamentConverseUserModel(), 'participant_id')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('last_read_at')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participations');
    }
};

<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('image')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->foreignIdFor(ConversationParticipation::class, 'owner_id')
                ->nullable()->default(null)
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->nullableMorphs('subject');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

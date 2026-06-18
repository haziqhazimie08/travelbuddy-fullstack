<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('shared_at')->useCurrent();
            $table->string('permissions')->default('view');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_itineraries');
    }
};

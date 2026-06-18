<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('eco_friendly')->default(false)->after('location');
            $table->boolean('family_friendly')->default(false)->after('eco_friendly');
            $table->string('travel_preferences')->nullable()->after('family_friendly');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['eco_friendly', 'family_friendly', 'travel_preferences']);
        });
    }
};

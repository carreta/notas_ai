<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Store the selected model key (from config/models.php) and provider at save time
            // so the analysis phase can use the user's selection instead of config defaults.
            $table->string('model')->nullable()->after('meeting_time');
            $table->string('provider')->nullable()->after('model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['model', 'provider']);
        });
    }
};

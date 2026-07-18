<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone')->default('UTC');
            $table->string('language')->default('en');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i');
            $table->string('theme')->default('system');
            $table->string('default_view')->default('list');
            $table->string('start_page')->default('dashboard');
            $table->boolean('notification_email')->default(true);
            $table->boolean('notification_browser')->default(true);
            $table->boolean('notification_in_app')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};

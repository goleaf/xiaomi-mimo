<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('reminded_at');
            $table->boolean('is_sent')->default(false);
            $table->string('type')->default('in_app');
            $table->timestamps();

            $table->index('todo_id');
            $table->index('user_id');
            $table->index(['is_sent', 'reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};

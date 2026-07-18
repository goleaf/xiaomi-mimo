<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->string('event');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('workspace_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#6366f1');
            $table->string('icon')->default('folder');
            $table->boolean('is_archived')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('workspace_id');
            $table->index(['workspace_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

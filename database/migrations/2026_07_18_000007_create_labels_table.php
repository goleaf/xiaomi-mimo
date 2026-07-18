<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->timestamps();

            $table->index('workspace_id');
            $table->unique(['workspace_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};

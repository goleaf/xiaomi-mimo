<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('checklist_id');
            $table->string('content');
            $table->boolean('is_checked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('checklist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};

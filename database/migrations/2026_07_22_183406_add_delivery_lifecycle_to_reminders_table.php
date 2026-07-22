<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('type');
            $table->uuid('claim_token')->nullable()->after('status');
            $table->unsignedTinyInteger('attempts')->default(0)->after('claim_token');
            $table->timestamp('claimed_at')->nullable()->after('attempts');
            $table->timestamp('next_attempt_at')->nullable()->after('claimed_at');
            $table->timestamp('delivered_at')->nullable()->after('next_attempt_at');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
            $table->text('last_error')->nullable()->after('cancelled_at');
        });

        DB::table('reminders')->where('is_sent', true)->update([
            'status' => 'delivered',
            'delivered_at' => DB::raw('COALESCE(updated_at, reminded_at)'),
        ]);

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_is_sent_reminded_at_index');
            $table->index(
                ['status', 'reminded_at', 'next_attempt_at', 'id'],
                'reminders_delivery_scan_index',
            );
            $table->index(['status', 'claimed_at'], 'reminders_claim_lease_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_claim_lease_index');
            $table->dropIndex('reminders_delivery_scan_index');
            $table->index(['is_sent', 'reminded_at']);
            $table->dropColumn([
                'status',
                'claim_token',
                'attempts',
                'claimed_at',
                'next_attempt_at',
                'delivered_at',
                'failed_at',
                'cancelled_at',
                'last_error',
            ]);
        });
    }
};

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
        Schema::table('orders', function (Blueprint $table) {
            // Drop booking flow columns
            $table->dropColumn([
                'shipping_cost',
                'payment_requested_at',
                'payment_due_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 2)->nullable()->after('total_price');
            $table->timestamp('payment_requested_at')->nullable()->after('paid_at');
            $table->timestamp('payment_due_at')->nullable()->after('payment_requested_at');
        });
    }
};

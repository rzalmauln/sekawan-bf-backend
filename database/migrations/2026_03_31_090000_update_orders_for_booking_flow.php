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
            $table->decimal('shipping_cost', 15, 2)->nullable()->after('total_price');
            $table->timestamp('payment_requested_at')->nullable()->after('paid_at');
            $table->timestamp('payment_due_at')->nullable()->after('payment_requested_at');
        });

        DB::statement("UPDATE orders SET status = 'booking' WHERE status = 'pending'");
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(255) NOT NULL DEFAULT 'booking'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'booking'");
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(255) NOT NULL DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_cost',
                'payment_requested_at',
                'payment_due_at',
            ]);
        });
    }
};

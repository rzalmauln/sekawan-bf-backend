<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    protected $signature = 'orders:cancel-expired-bookings';

    protected $description = 'Cancel expired booking orders and restore stock';

    public function handle(CheckoutService $checkoutService): int
    {
        $count = $checkoutService->cancelExpiredBookings();

        $this->info("Cancelled {$count} expired booking order(s).");

        return self::SUCCESS;
    }
}

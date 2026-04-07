<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

class PrunePaymentProofs extends Command
{
    protected $signature = 'orders:prune-payment-proofs {--days= : Override payment proof retention days}';

    protected $description = 'Delete old payment proof files from completed and cancelled orders';

    public function handle(CheckoutService $checkoutService): int
    {
        $retentionDays = (int) ($this->option('days') ?: config('orders.payment_proof_retention_days', 90));

        if ($retentionDays < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $result = $checkoutService->prunePaymentProofs($retentionDays);

        $this->info(sprintf(
            'Cleared %d order(s) and deleted %d payment proof file(s).',
            $result['cleared_orders'],
            $result['deleted_files']
        ));

        return self::SUCCESS;
    }
}

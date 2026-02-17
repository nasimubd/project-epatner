<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use Carbon\Carbon;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Check and update expired subscriptions';

    public function handle()
    {
        $expiredBusinesses = Business::where('subscription_active', true)
            ->where('subscription_expires_at', '<', Carbon::now())
            ->get();

        foreach ($expiredBusinesses as $business) {
            $business->update([
                'subscription_active' => false,
                'subscription_status' => 'expired'
            ]);

            $this->info("Expired subscription for business: {$business->name}");
        }

        $this->info("Checked {$expiredBusinesses->count()} expired subscriptions");
    }
}

<?php

namespace Codeplugtech\DodoPayments\Console;

use Codeplugtech\DodoPayments\Enum\SubscriptionStatusEnum;
use Illuminate\Console\Command;
use Codeplugtech\DodoPayments\Subscription;
use Carbon\Carbon;

class UpdateCancelledSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:update-cancelled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subscription statuses to cancelled if the current date is past ends_at';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();

        $subscriptionsToCancel = Subscription::where('status', '!=', SubscriptionStatusEnum::CANCELLED->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->get();

        $count = $subscriptionsToCancel->count();

        Subscription::where('status', '!=', SubscriptionStatusEnum::CANCELLED->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update(['status' => SubscriptionStatusEnum::CANCELLED->value]);

        $this->info("$count subscription(s) have been updated to cancelled.");

        return Command::SUCCESS;
    }
}

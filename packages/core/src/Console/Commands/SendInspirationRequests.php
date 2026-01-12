<?php

namespace Lunar\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\InspirationRequest;
use Lunar\Models\Order;
use Lunar\Notifications\RequestInspirationNotification;

class SendInspirationRequests extends Command
{
    protected $signature = 'lunar:send-inspiration-requests {--days=14 : Days after delivery to send request}';

    protected $description = 'Send review request emails for delivered orders';

    public function handle(): int
    {
        if (! config('lunar.inspirations.auto_request.enabled', true)) {
            $this->info('Automatic inspiration requests are disabled.');

            return Command::SUCCESS;
        }

        $days = (int) $this->option('days');
        $expiryDays = config('lunar.inspirations.auto_request.token_expiry_days', 30);

        // Find orders that:
        // - Were placed X days ago
        // - Have no existing inspiration
        // - Have no pending inspiration request (that's not completed)
        $orders = Order::query()
            ->where('status', 'dispatched')
            ->whereNotNull('placed_at')
            ->whereDate('placed_at', '<=', now()->subDays($days))
            ->whereDoesntHave('inspirations')
            ->whereDoesntHave('inspirationRequests', function ($q) {
                $q->whereNull('completed_at');
            })
            ->whereHas('user')
            ->with(['user', 'shippingAddress', 'productLines'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found for inspiration requests.');

            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($orders as $order) {
            if (! $order->user?->email) {
                continue;
            }

            $request = InspirationRequest::create([
                'order_id' => $order->id,
                'token' => InspirationRequest::generateToken(),
                'expires_at' => now()->addDays($expiryDays),
            ]);

            try {
                $order->user->notify(new RequestInspirationNotification($order, $request));
                $request->markAsSent();
                $sent++;

                $this->line("Sent request for order {$order->reference}");
            } catch (\Exception $e) {
                $this->error("Failed to send request for order {$order->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} inspiration request emails.");

        return Command::SUCCESS;
    }
}

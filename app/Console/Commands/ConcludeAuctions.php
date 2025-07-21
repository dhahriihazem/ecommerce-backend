<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConcludeAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:conclude';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Conclude ended auctions, find winners, and create orders.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to process ended auctions...');

        $endedAuctions = Product::where('type', ProductType::Auction)
            ->where('auction_end_time', '<=', now())
            ->whereNull('auction_concluded_at')
            ->get();

        if ($endedAuctions->isEmpty()) {
            $this->info('No ended auctions to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$endedAuctions->count()} auctions to process.");

        foreach ($endedAuctions as $auction) {
            $this->processAuction($auction);
        }

        $this->info('Finished processing auctions.');
        return self::SUCCESS;
    }

    private function processAuction(Product $auction): void
    {
        $this->info("Processing auction for product #{$auction->id}: {$auction->name}");

        try {
            DB::transaction(function () use ($auction) {
                $winningBid = $auction->bids()
                    ->orderBy('bid_amount', 'desc')
                    ->orderBy('created_at', 'asc') // In case of a tie, first bid wins
                    ->first();

                if ($winningBid) {
                    $this->info("Winning bid of {$winningBid->bid_amount} found from user #{$winningBid->user_id}.");

                    // Create an order for the winner
                    $order = Order::create([
                        'user_id' => $winningBid->user_id,
                        'total_amount' => $winningBid->bid_amount,
                        'status' => OrderStatus::PendingPayment,
                    ]);

                    // Attach the product to the order
                    $order->products()->attach($auction->id, [
                        'quantity' => 1,
                        'price' => $winningBid->bid_amount,
                    ]);

                    $this->info("Order #{$order->id} created for user #{$winningBid->user_id}.");
                    Log::info("Auction concluded with a winner. Order created.", ['product_id' => $auction->id, 'order_id' => $order->id, 'winner_user_id' => $winningBid->user_id]);
                } else {
                    $this->info("No bids found for this auction.");
                    Log::info("Auction concluded with no bids.", ['product_id' => $auction->id]);
                }

                // Mark the auction as concluded to prevent re-processing
                $auction->update(['auction_concluded_at' => now()]);
            });
        } catch (\Throwable $e) {
            $this->error("Failed to process auction #{$auction->id}. Error: {$e->getMessage()}");
            Log::error('Auction conclusion failed', ['product_id' => $auction->id, 'error' => $e->getMessage()]);
        }
    }
}
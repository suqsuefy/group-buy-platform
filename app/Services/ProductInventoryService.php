<?php

namespace App\Services;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Stock is decremented on checkout, not on join. Count un-ordered commitments
 * across all active/open and fulfilled campaigns to avoid overbooking a product.
 *
 * Call this only after locking the product row inside the caller's transaction.
 * A placed order is excluded because checkout already decremented product.stock.
 */
class ProductInventoryService
{
    public function outstandingJoinedUnits(int $productId): int
    {
        return (int) DB::table('campaign_participants as participants')
            ->join('campaigns', 'campaigns.id', '=', 'participants.campaign_id')
            ->leftJoin('orders', function (JoinClause $join) {
                $join->on('orders.campaign_id', '=', 'participants.campaign_id')
                    ->on('orders.user_id', '=', 'participants.user_id');
            })
            ->where('campaigns.product_id', $productId)
            ->where('participants.status', 'joined')
            ->whereNull('orders.id')
            ->where(function ($query) {
                $query->where('campaigns.status', 'fulfilled')
                    ->orWhere(function ($active) {
                        $active->where('campaigns.status', 'active')
                            ->where('campaigns.start_at', '<=', now())
                            ->where('campaigns.end_at', '>', now());
                    });
            })
            ->sum('participants.quantity');
    }
}

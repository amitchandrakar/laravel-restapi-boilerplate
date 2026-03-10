<?php

declare(strict_types=1);

namespace App\Models;

class Reward extends BaseModel
{
    protected $table = 'rewards';

    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function userUnPaidRewardValue($userId)
    {
        return $this->where([
            'user_id' => $userId,
            'order_status' => 'Delivered',
        ])
            ->whereNotIn('paid_status', [2])
            ->get();
    }

    public function upcomingRewards($userId)
    {
        return $this->where([
            'user_id' => $userId,
        ])
            ->where(function ($query) {
                $query
                    ->whereIn('order_status', ['Confirmed', 'Ordered', 'Proposal', 'Received'])
                    ->orWhereNull('order_status');
            })
            ->sum('reward_value');
    }

    public function earnedRewards($userId)
    {
        return $this->where([
            'user_id' => $userId,
            'order_status' => 'Delivered',
        ])->sum('reward_value');
    }

    public function earnedAmazonRewardHistory($userId)
    {
        return $this->where([
            'user_id' => $userId,
            'order_status' => 'Delivered',
        ])->get();
    }

    public function userCashOutAmount($userId)
    {
        $rewardValues = $this->userUnPaidRewardValue($userId);
        // fetch total reward amount used of all orders delivered
        $totalRewardUsedAmount = Order::where([['alonti_user_id', $userId], ['status', '!=', 'Canceled']])->sum(
            'amazon_reward'
        );
        $cashoutAmount = 0;
        if ($rewardValues->count() > 0) {
            $rewardValues->each(function (Reward $reward) use (&$cashoutAmount) {
                $cashoutAmount += $reward->reward_value - $reward->paid_reward_value;
            });
        }
        // Subtract cashoutAmount - totalRewardsUsedAmount
        $cashoutAmount = $cashoutAmount - $totalRewardUsedAmount;

        return $cashoutAmount;
    }

    public function userRewardValue($userId)
    {
        return $this->where([
            'user_id' => $userId,
        ])->first();
    }

    public function orderRewardValue($orderId)
    {
        return $this->where([
            'order_id' => $orderId,
        ])->first();
    }

    public function cartRewardValue($cartId)
    {
        return $this->where([
            'cart_id' => $cartId,
        ])->first();
    }
}

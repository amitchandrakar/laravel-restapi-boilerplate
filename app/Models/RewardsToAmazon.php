<?php

declare(strict_types=1);


namespace App\Models;

class RewardsToAmazon extends BaseModel
{
    protected $table = 'rewards_to_amazon';

    protected static $unguarded = true;

    public function redeemedRewards($userId)
    {
        $cashOut = $this->where([
            'user_id' => $userId,
        ])->get();
        $cashoutAmount = 0;
        if ($cashOut->count() > 0) {
            $cashOut->each(function (RewardsToAmazon $reward) use (&$cashoutAmount) {
                $cashoutAmount += $reward->cash_out_amount;
            });
        }

        return $cashoutAmount;
    }
}

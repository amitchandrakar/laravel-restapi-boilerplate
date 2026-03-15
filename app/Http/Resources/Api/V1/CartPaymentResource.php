<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'cart_info' => $data['cart_info'] ?? null,
            'cafe_info' => $data['cafe_info'] ?? null,
            'company_payments' => $data['company_payments'] ?? [],
            'payment_profile_details' => $data['payment_profile_details'] ?? null,
            'states' => $data['states'] ?? [],
            'tip_options' => $data['tip_options'] ?? null,
            'cc_id' => $data['cc_id'] ?? null,
            'po_id' => $data['po_id'] ?? null,
            'cod_id' => $data['cod_id'] ?? null,
            'gift_to_display' => $data['gift_to_display'] ?? null,
            'default_tip_amount' => $data['default_tip_amount'] ?? null,
            'discount_options' => $data['discount_options'] ?? null,
            'customer_opt_alonti_rewards_ever' => $data['customer_opt_alonti_rewards_ever'] ?? null,
            'reward_calculate_value' => $data['reward_calculate_value'] ?? null,
            'amazon_reward_min_spend_amount' => $data['amazon_reward_min_spend_amount'] ?? null,
            'amazon_reward_balance' => $data['amazon_reward_balance'] ?? null,
            'amazon_reward_applied' => $data['amazon_reward_applied'] ?? null,
            'customer_opt_alonti_rewards_ever_email_exist' => $data['customer_opt_alonti_rewards_ever_email_exist'] ?? null,
            'anet_profile_exist' => $data['anet_profile_exist'] ?? null,
        ];
    }
}

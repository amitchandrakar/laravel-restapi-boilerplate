<?php

declare(strict_types=1);


namespace App\Alonti\Amazon;

use App\Models\AmazonApiLog;
use App\Models\RewardsToAmazon;
use Illuminate\Support\Facades\Auth;
use kamerk22\AmazonGiftCode\AmazonGiftCode;

class AmazonService
{
    protected $data;

    protected $user;

    protected $amazonGiftCode;

    public function __construct($data)
    {
        $this->data = collect($data);
        $this->user = Auth::user();
        $this->amazonGiftCode = new AmazonGiftCode();
    }

    public function createCard($flag = null)
    {
        $cashAmount = $this->data->get('cashout_amount');
        $data['status'] = false;
        $availableFund = $this->amazonGiftCode->getAvailableFunds();
        $availableAmount = $availableFund->getAmount();
        $response = '';

        $request = [
            'amount' => $cashAmount,
            'url' => 'buyGiftCard',
        ];

        $result = $this->amazonGiftCode->buyGiftCard($cashAmount);

        if ($result->getStatus()) {
            $data['status'] = true;
            $data['gcClaimCode'] = $result->getClaimCode();
            $request['creationRequestId'] = $result->getCreationRequestId();
            $request['gcid'] = $result->getId();
            $response = $result->getRawJson();
            $apiLog = $this->saveAmazonResponse($request, $response);
            $this->saveCashedOutAmount($cashAmount, $apiLog, 'create', $flag);
        } else {
            $apiLog = $this->saveAmazonResponse($request, $response);
            $data['message'] = 'Something went wrong, Please try again later or contact administrator';
        }

        return $data;
    }

    public function cancelCard($giftCardInfo, $flag = null)
    {
        $data['status'] = false;

        $request = [
            'amount' => $giftCardInfo->amount,
            'url' => 'cancelGiftCard',
        ];

        $response = '';

        $result = $this->amazonGiftCode->cancelGiftCard($giftCardInfo->creationRequestId, $giftCardInfo->gcid);

        if ($result->getStatus()) {
            $data['status'] = true;
            $request['creationRequestId'] = $result->getCreationRequestId();
            $request['gcid'] = $result->getId();
            $response = $result->getRawJson();
            $apiLog = $this->saveAmazonResponse($request, $response);
            $this->saveCashedOutAmount($giftCardInfo->amount, $apiLog, 'cancel', $flag);
        } else {
            $apiLog = $this->saveAmazonResponse($request, $response);
            $data['message'] = 'Something went wrong, Please try again later or contact administrator';
        }

        return $data;
    }

    public function saveAmazonResponse($request, $response)
    {
        $data = [
            'user_id' => $this->user->id,
            'creationRequestId' => $request['creationRequestId'],
            'gcid' => $request['gcid'],
            'amount' => $request['amount'],
            'url' => $request['url'],
            'request' => json_encode($request),
            'response' => $response,
        ];

        return AmazonApiLog::create($data);
    }

    public function saveCashedOutAmount($cashAmount, $apiLog, $status, $flag = null)
    {
        $data = [
            'user_id' => $this->user->id,
            'customer_amazon_email' => $this->data->get('amazon_email'),
            'cash_out_amount' => $cashAmount,
            'amazon_log_id' => $apiLog->id,
            'amazon_request' => $status,
            'is_referral_rewards' => $flag == 'referral' ? 1 : 0,
        ];

        return RewardsToAmazon::create($data);
    }
}

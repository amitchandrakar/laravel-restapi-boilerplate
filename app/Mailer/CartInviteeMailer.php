<?php

declare(strict_types=1);


namespace App\Mailer;

use App\Mail\InviteeDecline;
use App\Mail\InviteeOrderCompletion;
use App\Models\CartInvitee;
use Illuminate\Support\Facades\Mail;

class CartInviteeMailer
{
    public $cartInvitee;

    public function __construct(CartInvitee $cartInvitee)
    {
        $this->cartInvitee = $cartInvitee;
    }

    public function sendDeclineNotification()
    {
        $cartInvitee = $this->cartInvitee;
        $leaderInfo = $cartInvitee->group->leader;
        Mail::to($leaderInfo->email)->send(new InviteeDecline($cartInvitee));
    }

    public function sendOrderCompletionByInvitee()
    {
        $cartInvitee = $this->cartInvitee;
        $leaderInfo = $cartInvitee->group->leader;
        Mail::to($leaderInfo->email)->send(new InviteeOrderCompletion($cartInvitee));
    }
}

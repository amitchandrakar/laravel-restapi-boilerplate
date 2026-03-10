<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Mail\ProspectSubscribeEmail;
use App\Mail\ProspectUnsubscribeEmail;
use App\Models\MxProspect;
use Illuminate\Support\Facades\Mail;

class ProspectMailer
{
    public $prospect;

    public function __construct(MxProspect $prospect)
    {
        $this->prospect = $prospect;
    }

    public function sendProspectUnsubscribeEmail()
    {
        $to = ['Alonti@softwaysolutions.com'];
        Mail::to($to)->send(new ProspectUnsubscribeEmail($this->prospect, $to));
    }

    public function sendProspectSubscribeEmail()
    {
        $to = ['Alonti@softwaysolutions.com'];
        Mail::to($to)->send(new ProspectSubscribeEmail($this->prospect, $to));
    }
}

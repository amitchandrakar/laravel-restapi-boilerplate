<?php

declare(strict_types=1);


namespace App\Mailer;

use App\Mail\CsmWelcomeEmail;
use App\Mail\FirstOrderNotification;
use App\Mail\OrderCanceled;
use App\Mail\OrderConfirmation;
use App\Mail\OrderModified;
use App\Mail\VoidConfirmation;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderMailer
{
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function sendOrderConfirmation()
    {
        $order = $this->order;
        Mail::to($order->user->email)->send(new OrderConfirmation($order));
    }

    public function sendFirstOrderNotification()
    {
        $order = $this->order;
        $mails = [];
        if ($order->cafe->mgremail) {
            $mails[] = $order->cafe->mgremail;
        }

        // Send an order confirmation email to CSM
        if ($order && $order->cafe && $order->cafe->director) {
            $mails[] = $order->cafe->director->email;
        }

        // Send an order confirmation email to ACSM
        if ($order->cafe->directorTwo) {
            $mails[] = $order->cafe->directorTwo->email;
        }

        // Send an order confirmation email to district CSM
        if ($order->cafe->district && $order->cafe->district->market && $order->cafe->district->market->directors) {
            foreach ($order->cafe->district->market->directors as $director) {
                if ($order->cafe->district->catering_manager === $director->id) {
                    $mails[] = $director->email;
                }
            }
        }

        if (!empty($mails)) {
            if (!$order->user->cafe_id) {
                // Save the email log
                storeEmailSentLogs('alontimaps@alonti.com', $mails, '', 'New user sign-ups', 'mails.csm-welcome-email');

                Mail::to($mails)->send(new CsmWelcomeEmail($order->user));
            }

            // Save the email log
            $bcc = ['softwayalonti@gmail.com'];
            storeEmailSentLogs(
                'alontimaps@alonti.com',
                $mails,
                $bcc,
                'First Order Notification #' . $order->id,
                'mails.first-order-notification'
            );

            Mail::to($mails)->send(new FirstOrderNotification($order));
        }
    }

    public function sendVoidConfirmation()
    {
        $order = $this->order;
        $bcc = config('custom.orderconfirmation.bcc');

        // Save the email log
        storeEmailSentLogs(
            'alontimaps@alonti.com',
            $this->order->user->email,
            '',
            'Void confirmation email campaign',
            'Void confirmation email campaign'
        );

        Mail::to($order->cafe->mgremail)->bcc($bcc)->send(new VoidConfirmation($order));
    }

    public function sendOrderModified()
    {
        $order = $this->order;
        Mail::to($order->user->email)->send(new OrderModified($order));
    }

    public function sendOrderCanceled()
    {
        $bcc = [];
        $bcc = config('custom.orderconfirmation.bcc');
        $order = $this->order;

        // Send an order confirmation email to CSM and ACSM
        if ($this->order && isset($this->order->cafe)) {
            // Send an order confirmation email to CSM
            if (isset($this->order->cafe->director) && isset($this->order->cafe->director->email)) {
                array_push($bcc, $this->order->cafe->director->email);
            }

            // Send an order confirmation email to ACSM
            if (isset($this->order->cafe->directorTwo) && isset($this->order->cafe->directorTwo->email)) {
                array_push($bcc, $this->order->cafe->directorTwo->email);
            }

            // Send an order confirmation email to district CSM
            if (
                isset($this->order->cafe->district) &&
                isset($this->order->cafe->district->market) &&
                isset($this->order->cafe->district->market->directors)
            ) {
                // There are multiple directors for a market, so we need to loop through all directors and find the catering manager associated with the district
                foreach ($this->order->cafe->district->market->directors as $director) {
                    if ($this->order->cafe->district->catering_manager === $director->id) {
                        array_push($bcc, $director->email);
                    }
                }
            }
        }

        // Save the email log
        storeEmailSentLogs(
            'alontimaps@alonti.com',
            $this->order->user['email'],
            $bcc,
            'Order Canceled #' . $this->order->id,
            'Order Canceled #' . $this->order->id
        );

        Mail::to($order->cafe->mgremail)->bcc($bcc)->send(new OrderCanceled($order));
    }
}

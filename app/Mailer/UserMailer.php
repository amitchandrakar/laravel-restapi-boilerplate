<?php

declare(strict_types=1);


namespace App\Mailer;

use App\Mail\CsmWelcomeEmail;
use App\Mail\CustomerReferralEmailToCsm;
use App\Mail\PasswordResetEmail;
use App\Mail\TaxExemptEmail;
use App\Mail\UserAmazonGiftCardEmail;
use App\Mail\UserReferralEmail;
use App\Mail\UserSubscribeEmail;
use App\Mail\UserUnsubscribeEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserMailer
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function sendForgotPasswordEmail()
    {
        Mail::to($this->user->email)->send(new PasswordResetEmail($this->user));
    }

    public function sendWelcomeEmail()
    {
        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }

    public function sendCsmWelcomeEmail()
    {
        $cafe = $this->user->cafe;

        if ($cafe) {
            $cc = [];

            // If the cafe has a director, push the director email to the cc array
            if ($cafe->director) {
                $csmEmail = $cafe->director->email;
                $contains = Str::contains($csmEmail, '@alonti.com');

                if (!$contains) {
                    $csmEmail = $csmEmail . '@alonti.com';
                }

                // Push the director email to the cc array
                array_push($cc, $csmEmail);
            }

            // If the cafe has a directorTwo, push the directorTwo email to the cc array
            if ($cafe->directorTwo) {
                $directorTwoEmail = $cafe->directorTwo->email;
                $contains = Str::contains($directorTwoEmail, '@alonti.com');

                if (!$contains) {
                    $directorTwoEmail = $directorTwoEmail . '@alonti.com';
                }

                array_push($cc, $directorTwoEmail);
            }

            // Save the email log
            storeEmailSentLogs(
                'alontimaps@alonti.com',
                $this->user['email'],
                $cc,
                'New user sign-ups',
                'New user sign-ups'
            );

            Mail::to($cafe->mgremail)
                ->cc($cc)
                ->send(new CsmWelcomeEmail($this->user));
        }
    }

    public function sendTaxExemptionEmail()
    {
        Mail::to('acctsrec@alonti.com')->send(new TaxExemptEmail($this->user, 'acctsrec@alonti.com'));
    }

    public function sendCustomerUnsubscribeEmail()
    {
        $to = ['Alonti@softwaysolutions.com'];
        Mail::to($to)->send(new UserUnsubscribeEmail($this->user, $to));
    }

    public function sendCustomerSubscribeEmail()
    {
        $to = ['Alonti@softwaysolutions.com'];
        Mail::to($to)->send(new UserSubscribeEmail($this->user, $to));
    }

    public function sendAmazonGiftCardEmail($to, $amount, $giftcode, $flag = null)
    {
        Mail::to($to)->send(new UserAmazonGiftCardEmail($this->user, $amount, $giftcode, $flag, $to));
    }

    public function sendReferralEmail($to, $referralRewardAmount, $referralMinimumFoodPurchaseAmount)
    {
        Mail::to($to)->send(
            new UserReferralEmail($this->user, $to, $referralRewardAmount, $referralMinimumFoodPurchaseAmount)
        );
    }

    public function sendReferralEmailListToCsm($emailList)
    {
        $cafe = $this->user->cafe;

        if ($cafe) {
            $cc = [];

            // If the cafe has a director, push the director email to the cc array
            if ($cafe->director) {
                $csmEmail = $cafe->director->email;
                $contains = Str::contains($csmEmail, '@alonti.com');

                if (!$contains) {
                    $csmEmail = $csmEmail . '@alonti.com';
                }

                // Push the director email to the cc array
                array_push($cc, $csmEmail);
            }

            // If the cafe has a directorTwo, push the directorTwo email to the cc array
            if ($cafe->directorTwo) {
                $directorTwoEmail = $cafe->directorTwo->email;
                $contains = Str::contains($directorTwoEmail, '@alonti.com');

                if (!$contains) {
                    $directorTwoEmail = $directorTwoEmail . '@alonti.com';
                }

                array_push($cc, $directorTwoEmail);
            }

            // Save the email log
            storeEmailSentLogs(
                'alontimaps@alonti.com',
                $cafe->mgremail,
                $cc,
                $this->user->name . ' referred customers',
                ''
            );

            Mail::to($cafe->mgremail)
                ->cc($cc)
                ->send(new CustomerReferralEmailToCsm($this->user, $emailList));
        }
    }
}

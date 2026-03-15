<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\MxProspect;
use App\Models\User;

/**
 * Email Campaign Controller
 *
 * Handles email subscription management for:
 * - Customer unsubscribe/subscribe requests
 * - Prospect unsubscribe/subscribe requests
 * - Promotional email preference updates
 */
class EmailCampaignController extends Controller
{
    /**
     * Handle email unsubscribe requests
     *
     * Unsubscribes users or prospects from promotional emails
     * and sends confirmation email.
     *
     * @return void
     */
    public function unsubscribe()
    {
        // Get email from request and find user/prospect
        $email = request('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            // Unsubscribe registered user from promotional emails
            $user->update([
                'unsubscribe_promotion' => 'UNS',
            ]);

            $user->mailer()->sendCustomerUnsubscribeEmail();
        } else {
            // Check for prospect and unsubscribe them
            $prospect = MxProspect::where('email', $email)->first();
            if ($prospect) {
                $prospect->update([
                    'unsubscribe_promotion' => 'UNS',
                ]);

                $prospect->mailer()->sendProspectUnsubscribeEmail();
            }
        }

        exit();
    }

    /**
     * Handle email subscription requests
     *
     * Re-subscribes users or prospects to promotional emails
     * and sends confirmation email.
     *
     * @return void
     */
    public function subscribe()
    {
        // Get email from request and find user/prospect
        $email = request('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            // Re-subscribe registered user to promotional emails
            $user->update([
                'unsubscribe_promotion' => '',
            ]);

            $user->mailer()->sendCustomerSubscribeEmail();
        } else {
            // Check for prospect and re-subscribe them
            $prospect = MxProspect::where('email', $email)->first();

            if ($prospect) {
                $prospect->update([
                    'unsubscribe_promotion' => '',
                ]);

                $prospect->mailer()->sendProspectSubscribeEmail();
            }
        }
        exit();
    }
}

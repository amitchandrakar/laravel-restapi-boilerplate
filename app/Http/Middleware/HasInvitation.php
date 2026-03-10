<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Alonti\Invitation\InvitationManager;
use App\Alonti\ZipManager\ZipManager;
use Closure;

class HasInvitation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (session()->has('invitation.cart_invitee_id')) {
            config()->set('app.request-from-invitee', true);
            $invitationManager = app(InvitationManager::class);
            $code = $invitationManager->getCart()->zipcode;
            $zipManager = app(ZipManager::class);

            $zipManager->setDeliveryAreaByZip($code);

            // Pass to next middleware/request
            $response = $next($request);
            $invitationManager->clear();

            return $response;
        }

        return redirect('/')->with('notify-failure', 'You don\'t have any active group order invitation.');
    }
}

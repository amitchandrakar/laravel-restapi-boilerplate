<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invitation;

use App\Alonti\Cart\CartManager;
use App\Alonti\Invitation\InvitationManager;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartInvitee;
use App\Models\Category;
use App\Models\GroupOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class InviteeController extends Controller
{
    public function index()
    {
        $individualCart = false;

        if (Auth::user()) {
            $individualCart = Cart::individual()->mine()->pending()->first();
        }

        $cartInfo = app(CartManager::class)->getActiveCart();

        $requestFromInvitee = config()->get('app.request-from-invitee');
        $condition = ['parent_id' => null, 'display_status' => 1, 'status' => 1];
        $categories = Category::getCategories($requestFromInvitee);
        $cartId = session()->get('invitation.cart_id');
        $inviteeNameExist = session()->has('invitation.invitee_name')
            ? session()->get('invitation.invitee_name')
            : false;
        $invitee_total = 0;
        $budget = 0;
        $goConfigExist = false;
        $budgetActive = false;

        if ($requestFromInvitee) {
            if (request()->has('inviteeName') && !session()->has('invitation.invitee_name')) {
                $name = request()->has('inviteeName');
                session()->put('invitation.invitee_name', $name);
                $inviteeNameExist = true;
            }

            if ($cartInfo->groupOrderConfig) {
                $budget = $cartInfo->groupOrderConfig->invitee_budget;
                $budgetActive = $cartInfo->groupOrderConfig->invitee_budget > 0 ? true : false;
                $goConfigExist = $cartInfo->groupOrderConfig ? true : false;
            }

            $items = $cartInfo->items();
            $items = $items->where('invitee_id', session()->get('invitation.invitee_id'));
            $leader = app(InvitationManager::class)->getLeader();
            $group = GroupOrder::find(session()->get('invitation.group_order_id'));
            $invitee_total = round($cartInfo->totalForInvitee(session()->get('invitation.invitee_id')), 2);
        }

        return view(
            'invitation.invitee.index',
            compact(
                'categories',
                'budget',
                'invitee_total',
                'goConfigExist',
                'inviteeNameExist',
                'budgetActive',
                'individualCart',
                'cartInfo'
            )
        );
    }

    public function orderComplete()
    {
        $cartInvitee = CartInvitee::find(session()->get('invitation.cart_invitee_id'));
        $proceed = false;

        if (
            $cartInvitee->cart &&
            $cartInvitee->cart->order &&
            in_array($cartInvitee->cart->order->status, ['Delivered', 'Canceled'])
        ) {
            return redirect('/')->with(
                'notify-failure',
                'The order has been placed, for further information please contact your group leader.'
            );
        } elseif ($cartInvitee->cart->groupOrderConfig) {
            $responseTime = strtotime(
                $cartInvitee->cart->groupOrderConfig->response_date .
                    ' ' .
                    $cartInvitee->cart->groupOrderConfig->response_time
            );
            $timeZone = abs($cartInvitee->cart->cafe->market->timezone_difference);
            $timeZoneHours = strtotime('-' . $timeZone . ' hours');
            if ($timeZoneHours >= $responseTime) {
                return redirect('/')->with(
                    'notify-failure',
                    'You missed the order deadline. Please contact your group leader if you need lunch.'
                );
            } else {
                $proceed = true;
            }
        }

        if ($proceed) {
            if ($cartInvitee->hasAccepted()) {
                $inviteeItems = $cartInvitee->cartItems->where('cart_id', $cartInvitee->cart_id);
                $inviteeCartTotal = $inviteeItems->sum('total');
                $budget = session()->get('invitation.invitee_budget');
                $goConfigExist = false;
                $budgetActive = false;

                if ($cartInvitee->cart->groupOrderConfig) {
                    $budget = $cartInvitee->cart->groupOrderConfig->invitee_budget;
                    $budgetActive = $cartInvitee->cart->groupOrderConfig->invitee_budget > 0 ? true : false;
                    $goConfigExist = $cartInvitee->cart->groupOrderConfig ? true : false;
                }

                if ($goConfigExist && ($budgetActive && $budget < $inviteeCartTotal)) {
                    return redirect('/invitation/summary')->with(
                        'notify-failure',
                        'Your order value exceeds your allocated budget of $' .
                            round($budget, 2) .
                            '. Please adjust your order accordingly.'
                    );
                } else {
                    $itemAvailableValidation = app(CartManager::class)->storeItemValidation(
                        $cartInvitee->cart,
                        $inviteeItems
                    );

                    if (!$itemAvailableValidation['status']) {
                        return redirect('/invitation/summary')->with('notify-failure', $itemAvailableValidation['msg']);
                    } else {
                        if ($cartInvitee) {
                            $cartInvitee->response = CartInvitee::RESPONSE_COMPLETED;
                            $cartInvitee->save();
                        }

                        foreach ($inviteeItems as $item) {
                            $item->cart->calculateAndUpdate();
                        }

                        $invitationManager = app(InvitationManager::class);
                        $invitee = $invitationManager->getInvitee();
                        $user = User::where(['email' => $invitee->email]);
                        $leader = $invitationManager->getLeader();
                        // send email to leader that invitee completed the order
                        $cartInvitee->mailer()->sendOrderCompletionByInvitee();
                        $invitationManager->expireInvitation = true;
                        $invitee_total = 0;
                        $budget = 0;

                        return view(
                            'invitation.invitee.order-complete',
                            compact('leader', 'user', 'budget', 'invitee_total', 'goConfigExist', 'budgetActive')
                        );
                    }
                }
            } else {
                return redirect('/')->with(
                    'notify-failure',
                    'The invite is no longer valid as you have completed your order.'
                );
            }
        }
    }

    public function addName()
    {
        $data['status'] = false;
        $data['msg'] = 'Something went wrong, please try again ';
        $data['result'] = [];

        if (request()->has('name')) {
            $name = request()->get('name');
            session()->put('invitation.invitee_name', $name);
            $data['status'] = true;
            $data['msg'] = 'Success';
        }

        return response()->json($data);
    }
}

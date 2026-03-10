<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Mail\CsmGroupOrderNotificationEmail;
use App\Mail\GroupOrderInvitation;
use App\Mail\RemindInvitationInvitation;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductSelection;
use Illuminate\Support\Facades\Mail;

class CartMailer
{
    public $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function sendInvitationToInvitees($invitationIds = [], $editGroupOrder = null)
    {
        $cart = $this->cart;
        $config = $this->cart->groupOrderConfig ? $this->cart->groupOrderConfig->fresh() : null;
        $shipping = $this->cart->shipping ? $this->cart->shipping : [];
        $productName = '';

        if ($config && $config->default_meal) {
            $productName = 'If you do not add your meal on or before the response date and time then you will get the ';
            $product = Product::where('id', $config->product_id)
                ->with(['variant'])
                ->first();

            $productName .= $product->name;

            if (isset($product->variant[0]) && $product->name != $product->variant[0]->name) {
                $productName .= '(' . $product->variant[0]->name . ')';
            }

            if ($config->options_selection_id != '') {
                $options = json_decode($config->options_selection_id);
                $selectionIds = [];

                foreach ($options as $option) {
                    $selectionId = explode('-', $option);
                    $selectionIds[] = $selectionId[1];
                }

                $selection = ProductSelection::whereIn('id', $selectionIds)->get();
                $selNames = [];

                foreach ($selection as $sel) {
                    $selNames[] = $sel->name;
                }
                $productName .= ' with the sides';
                $productName .= !empty($selNames) ? ' ( ' . implode(', ', $selNames) . ' )' : '.';
            }
        }

        if (!$invitationIds) {
            $this->cart->invitees->each(function ($inviteeConnection) {
                $invitee = $inviteeConnection->invitee;
            });
        } else {
            $this->cart
                ->invitees()
                ->with(['invitee'])
                ->whereIn('id', $invitationIds)
                ->get()
                ->each(function ($inviteeConnection) use ($cart, $config, $shipping, $productName, $editGroupOrder) {
                    $invitee = $inviteeConnection->invitee;
                    if ($invitee && $inviteeConnection->response != 3) {
                        Mail::to($invitee->email)->send(
                            new GroupOrderInvitation(
                                $invitee,
                                $cart,
                                $inviteeConnection,
                                $config,
                                $shipping,
                                $productName,
                                $editGroupOrder
                            )
                        );
                    }
                });
        }
    }

    public function remindInvitationToInvitees($invitationIds)
    {
        $cart = $this->cart;
        $config = $this->cart->groupOrderConfig ? $this->cart->groupOrderConfig->fresh() : [];
        $shipping = $this->cart->shipping ? $this->cart->shipping : [];
        $productName = '';
        if ($config && $config->default_meal) {
            $productName = 'If you do not add your meal on or before the response date and time then you will get the ';
            $product = Product::where('id', $config->product_id)
                ->with(['variant'])
                ->first();
            $productName .= $product->name;
            if (isset($product->variant[0]) && $product->name != $product->variant[0]->name) {
                $productName .= '(' . $product->variant[0]->name . ')';
            }
            if ($config->options_selection_id != '') {
                $options = json_decode($config->options_selection_id);
                $selectionIds = [];
                foreach ($options as $option) {
                    $selectionId = explode('-', $option);
                    $selectionIds[] = $selectionId[1];
                }
                $selection = ProductSelection::whereIn('id', $selectionIds)->get();
                $selNames = [];
                foreach ($selection as $sel) {
                    $selNames[] = $sel->name;
                }
                $productName .= ' with the sides';
                $productName .= !empty($selNames) ? ' ( ' . implode(', ', $selNames) . ' )' : '.';
            }
        }
        $this->cart
            ->invitees()
            ->whereIn('id', $invitationIds)
            ->get()
            ->each(function ($inviteeConnection) use ($cart, $config, $shipping, $productName) {
                $invitee = $inviteeConnection->invitee;
                Mail::to($invitee->email)->send(
                    new RemindInvitationInvitation(
                        $invitee,
                        $cart,
                        $inviteeConnection,
                        $config,
                        $shipping,
                        $productName,
                        'reminder'
                    )
                );
            });
    }

    public function resendInvitationToInvitees($invitationIds)
    {
        $cart = $this->cart;
        $config = $this->cart->groupOrderConfig ? $this->cart->groupOrderConfig->fresh() : [];
        $shipping = $this->cart->shipping ? $this->cart->shipping : [];
        $productName = '';
        if ($config && $config->default_meal) {
            $productName = 'If you do not add your meal on or before the response date and time then you will get the ';
            $product = Product::where('id', $config->product_id)
                ->with(['variant'])
                ->first();
            $productName .= $product->name;
            if (isset($product->variant[0]) && $product->name != $product->variant[0]->name) {
                $productName .= '(' . $product->variant[0]->name . ')';
            }
            if ($config->options_selection_id != '') {
                $options = json_decode($config->options_selection_id);
                $selectionIds = [];
                foreach ($options as $option) {
                    $selectionId = explode('-', $option);
                    $selectionIds[] = $selectionId[1];
                }
                $selection = ProductSelection::whereIn('id', $selectionIds)->get();
                $selNames = [];
                foreach ($selection as $sel) {
                    $selNames[] = $sel->name;
                }
                $productName .= ' with the sides';
                $productName .= !empty($selNames) ? ' ( ' . implode(', ', $selNames) . ' )' : '.';
            }
        }
        $this->cart
            ->invitees()
            ->whereIn('id', $invitationIds)
            ->get()
            ->each(function ($inviteeConnection) use ($cart, $config, $shipping, $productName) {
                $invitee = $inviteeConnection->invitee;
                Mail::to($invitee->email)->send(
                    new GroupOrderInvitation(
                        $invitee,
                        $cart,
                        $inviteeConnection,
                        $config,
                        $shipping,
                        $productName,
                        'resend'
                    )
                );
            });
    }

    public function sendGroupOrderNotificationToCsm($flag = null)
    {
        $bcc = ['softwayalonti@gmail.com'];
        $cafe = $this->cart->cafe;

        if ($cafe) {
            // Send an order confirmation email to CSM and ACSM
            if ($this->cart && isset($this->cart->cafe)) {
                // Send an order confirmation email to CSM
                if (isset($this->cart->cafe->director) && isset($this->cart->cafe->director->email)) {
                    array_push($bcc, $this->cart->cafe->director->email);
                }

                // Send an order confirmation email to ACSM
                if (isset($this->cart->cafe->directorTwo) && isset($this->cart->cafe->directorTwo->email)) {
                    array_push($bcc, $this->cart->cafe->directorTwo->email);
                }

                // Send an order confirmation email to district CSM
                if (
                    $this->cart &&
                    isset($this->cart->cafe) &&
                    isset($this->cart->cafe->district) &&
                    isset($this->cart->cafe->district->market) &&
                    isset($this->cart->cafe->district->market->directors)
                ) {
                    // There are multiple directors for a market, so we need to loop through all directors and find the catering manager associated with the district
                    foreach ($this->cart->cafe->district->market->directors as $director) {
                        if ($this->cart->cafe->district->catering_manager === $director->id) {
                            array_push($bcc, $director->email);
                        }
                    }
                }
            }

            Mail::to($cafe->mgremail)
                ->bcc($bcc)
                ->send(new CsmGroupOrderNotificationEmail($this->cart, $flag, $bcc));
        }
    }
}

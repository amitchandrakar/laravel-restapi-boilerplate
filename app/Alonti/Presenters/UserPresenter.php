<?php

declare(strict_types=1);

namespace App\Alonti\Presenters;

use App\Models\GroupOrder;
use App\Models\User;

class UserPresenter
{
    /**
     * User instance for presentation
     *
     * @var User
     */
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function groupOrderListForSelectBox($placeholder = true)
    {
        $group_orders = $this->user->group_orders;
        $group_orders = $group_orders->map(function (GroupOrder $group) {
            return ['text' => $group->name, 'value' => $group->id];
        });

        if ($placeholder) {
            $group_orders->prepend(['text' => ' -- Select a group -- ', 'value' => 0], 0);
        }

        return $group_orders;
    }
}

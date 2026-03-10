<?php

declare(strict_types=1);


namespace App\Alonti\Presenters;

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
        $group_orders = $group_orders->map(function ($group) {
            return ['text' => $group->name, 'value' => $group->id];
        });

        if ($placeholder) {
            $group_orders->prepend(' -- Select a group -- ', '');
        }

        return $group_orders;
    }
}

<?php

declare(strict_types=1);

namespace App\Alonti\View;

class OrderGroupSelect
{
    /**
     * Whether to show placeholder option
     *
     * @var bool
     */
    public $placeholder;

    /**
     * Collection of items for the select dropdown
     *
     * @var \Illuminate\Support\Collection
     */
    public $items;

    public function __construct($items, $placeholder = true)
    {
        $this->placeholder = $placeholder;
        $this->items = $items->map(function ($group) {
            return ['text' => $group->name, 'value' => $group->id];
        });
    }

    public function build()
    {
        if ($this->items->count() == 0) {
            $this->placeholder = false;
        }
        if ($this->placeholder) {
            return $this->items->prepend(['text' => ' -- Select Group -- ', 'value' => '']);
        }

        return $this->items;
    }
}

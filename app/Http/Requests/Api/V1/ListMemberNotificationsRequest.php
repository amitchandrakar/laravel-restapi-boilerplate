<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class ListMemberNotificationsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'unreadOnly' => ['nullable', 'boolean'],
        ];
    }

    public function perPage(): int
    {
        $v = $this->integer('perPage', 15);

        return $v >= 1 && $v <= 50 ? $v : 15;
    }

    public function pageNumber(): int
    {
        $v = $this->integer('page', 1);

        return max(1, $v);
    }

    public function unreadOnly(): bool
    {
        return $this->boolean('unreadOnly');
    }
}

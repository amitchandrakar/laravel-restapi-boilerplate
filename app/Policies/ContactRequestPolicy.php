<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactRequest;
use App\Models\User;

class ContactRequestPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('candidate.send_contact_requests');
    }

    public function respond(User $actor, ContactRequest $contactRequest): bool
    {
        return (int) $actor->id === (int) $contactRequest->to_user_id;
    }
}

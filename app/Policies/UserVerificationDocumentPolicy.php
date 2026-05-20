<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserVerificationDocument;

class UserVerificationDocumentPolicy
{
    public function view(User $actor, UserVerificationDocument $document): bool
    {
        if ((int) $actor->id === (int) $document->user_id) {
            return true;
        }

        return $actor->can('admin.candidates.view');
    }

    public function upsert(User $actor, UserVerificationDocument $document): bool
    {
        return (int) $actor->id === (int) $document->user_id;
    }

    public function review(User $actor, UserVerificationDocument $document): bool
    {
        return $actor->can('admin.candidates.edit');
    }
}

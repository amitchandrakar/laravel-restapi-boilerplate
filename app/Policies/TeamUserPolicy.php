<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class TeamUserPolicy
{
    public function viewAnyTeamMember(User $actor): bool
    {
        return $actor->can('admin.teams.view');
    }

    public function viewTeamMember(User $actor, User $teamMember): bool
    {
        return $actor->can('admin.teams.view') && $this->isTeamMember($teamMember);
    }

    public function createTeamMember(User $actor): bool
    {
        return $actor->can('admin.teams.add');
    }

    public function updateTeamMember(User $actor, User $teamMember): bool
    {
        return $actor->can('admin.teams.edit') && $this->isTeamMember($teamMember);
    }

    public function deleteTeamMember(User $actor, User $teamMember): bool
    {
        return $actor->can('admin.teams.delete') && $this->isTeamMember($teamMember);
    }

    private function isTeamMember(User $user): bool
    {
        return User::query()->teamUsers()->where('id', $user->id)->exists();
    }
}

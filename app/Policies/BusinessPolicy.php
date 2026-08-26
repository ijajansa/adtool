<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function view(User $user, Business $business): bool
    {
        return $business->status && $user->hasBusinessRole($business, ['owner', 'admin', 'marketer', 'viewer']);
    }

    public function update(User $user, Business $business): bool
    {
        return $business->status && $user->hasBusinessRole($business, ['owner', 'admin']);
    }

    public function manageMembers(User $user, Business $business): bool
    {
        return $business->status && $user->hasBusinessRole($business, ['owner', 'admin']);
    }

    public function delete(User $user, Business $business): bool
    {
        return $business->status && $user->hasBusinessRole($business, 'owner');
    }
}

<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\MetaConnection;
use App\Models\User;

class MetaConnectionPolicy
{
    public function view(User $user, Business|MetaConnection $subject): bool
    {
        $business = $this->business($subject);

        return $business->status
            && $user->hasBusinessRole($business, ['owner', 'admin', 'marketer', 'viewer']);
    }

    public function connect(User $user, Business $business): bool
    {
        return $business->status && $user->hasBusinessRole($business, ['owner', 'admin']);
    }

    public function sync(User $user, MetaConnection $connection): bool
    {
        return $connection->access_token !== null
            && in_array($connection->status, [MetaConnection::STATUS_PENDING, MetaConnection::STATUS_CONNECTED, MetaConnection::STATUS_ERROR], true)
            && $connection->business->status
            && $user->hasBusinessRole($connection->business, ['owner', 'admin']);
    }

    public function selectAssets(User $user, MetaConnection $connection): bool
    {
        return $connection->status === MetaConnection::STATUS_CONNECTED
            && $connection->business->status
            && $user->hasBusinessRole($connection->business, ['owner', 'admin']);
    }

    public function disconnect(User $user, MetaConnection $connection): bool
    {
        return $connection->status !== MetaConnection::STATUS_REVOKED
            && $connection->business->status
            && $user->hasBusinessRole($connection->business, 'owner');
    }

    private function business(Business|MetaConnection $subject): Business
    {
        return $subject instanceof Business ? $subject : $subject->business;
    }
}

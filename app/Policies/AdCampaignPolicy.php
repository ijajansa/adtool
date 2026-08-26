<?php

namespace App\Policies;

use App\Models\AdCampaign;
use App\Models\User;

class AdCampaignPolicy
{
    private const READ_ROLES = ['owner', 'admin', 'marketer', 'viewer'];

    private const WRITE_ROLES = ['owner', 'admin', 'marketer'];

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, self::READ_ROLES);
    }

    public function view(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign) && $this->hasRole($user, self::READ_ROLES);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, self::WRITE_ROLES);
    }

    public function update(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign) && $campaign->isEditable() && $this->hasRole($user, self::WRITE_ROLES);
    }

    public function delete(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign)
            && ! $campaign->hasBeenPublished()
            && $campaign->isEditable()
            && $this->hasRole($user, ['owner', 'admin']);
    }

    public function publish(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign) && $this->hasRole($user, ['owner', 'admin']);
    }

    public function pause(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign) && $this->hasRole($user, self::WRITE_ROLES);
    }

    public function duplicate(User $user, AdCampaign $campaign): bool
    {
        return $this->ownsContext($user, $campaign) && $this->hasRole($user, self::WRITE_ROLES);
    }

    private function hasRole(User $user, array $roles): bool
    {
        return $user->currentBusiness !== null && $user->hasBusinessRole($user->currentBusiness, $roles);
    }

    private function ownsContext(User $user, AdCampaign $campaign): bool
    {
        return (int) $campaign->business_id === (int) $user->current_business_id;
    }
}

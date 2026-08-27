<?php

namespace App\Policies;

use App\Models\ServicePlan;
use App\Models\User;

class ServicePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServicePlan $servicePlan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServicePlan $servicePlan): bool
    {
        return true;
    }

    public function delete(User $user, ServicePlan $servicePlan): bool
    {
        return true;
    }
}

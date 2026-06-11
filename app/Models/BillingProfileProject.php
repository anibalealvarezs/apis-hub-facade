<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BillingProfileProject extends Pivot
{
    protected $table = 'billing_profile_project';
    public $incrementing = true;

    public function billingProfile()
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}

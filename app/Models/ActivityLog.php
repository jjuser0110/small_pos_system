<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of the activity log (superadmin only). Never edited after it is written. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'subject_label',
        'changes', 'description', 'branch_id', 'company_id', 'ip', 'method', 'url', 'user_agent',
    ];

    protected $casts = ['changes' => 'array'];

    public function user()    { return $this->belongsTo(User::class)->withTrashed(); }
    public function branch()  { return $this->belongsTo(Branch::class)->withTrashed(); }
    public function company() { return $this->belongsTo(Company::class)->withTrashed(); }
}

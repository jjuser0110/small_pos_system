<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffBorrow extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 
        'branch_id', 
        'company_id', 
        'amount', 
        'reason',
        'status', 
        'approved_by', 
        'status_at', 
        'reject_reason',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo('App\Models\User', 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch');
    }
}
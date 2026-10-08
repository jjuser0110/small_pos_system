<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 
        'branch_id', 
        'company_id', 
        'type', 
        'quantity',
        'target_company_id', 
        'target_branch_id', 
        'reason', 
        'status',
        'requested_by', 
        'approved_by', 
        'status_at', 
        'reject_reason',
    ];

    public function product()       { return $this->belongsTo('App\Models\Product'); }
    public function company()       { return $this->belongsTo('App\Models\Company'); }
    public function targetCompany() { return $this->belongsTo('App\Models\Company', 'target_company_id'); }
    public function requester()     { return $this->belongsTo('App\Models\User', 'requested_by'); }
    public function approver()      { return $this->belongsTo('App\Models\User', 'approved_by'); }

    public function typeLabel()
    {
        return [
            'adjust_in'  => 'Adjust In',
            'adjust_out' => 'Adjust Out',
            'transfer'   => 'Send to Other Company',
        ][$this->type] ?? $this->type;
    }
}
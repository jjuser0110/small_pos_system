<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'company_name',
        'company_code',
        'domain',
    ];

    /** Store domains as a bare lowercase host: "https://www.ShopA.com/" -> "shopa.com". */
    public static function normalizeDomain(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }
        $value = preg_replace('#^[a-z]+://#', '', $value);   // scheme
        $value = preg_replace('#[/?\#].*$#', '', $value);    // path, query
        $value = preg_replace('#:\d+$#', '', $value);        // port
        $value = preg_replace('#^www\.#', '', $value);

        return $value === '' ? null : $value;
    }

    public function setDomainAttribute($value): void
    {
        $this->attributes['domain'] = self::normalizeDomain($value);
    }

    /** Does a request host belong to this company? (www. and port are ignored) */
    public function matchesHost(?string $host): bool
    {
        return $this->domain !== null && self::normalizeDomain($host) === $this->domain;
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch');
    }
    
    public function categories()
    {
        return $this->hasMany('App\Models\Product');
    }
    
    public function users()
    {
        return $this->hasMany('App\Models\User');
    }
}

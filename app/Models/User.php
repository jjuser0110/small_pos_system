<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Support\ShiftWindow;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRolesAndAbilities,SoftDeletes;


    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'username',
        'role_id',
        'is_active',
        'branch_id',
        'company_id',
        'shift',
        'shift_start',
        'shift_end',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public const ROLE_STAFF = 5;

    public function isStaff(): bool
    {
        return (int) $this->role_id === self::ROLE_STAFF;
    }

    /** @var array<string, ?Company> working company per host, cached for the request */
    private array $workingCompanyCache = [];

    /**
     * The company this user is working for right now. Staff work for whichever
     * company's domain they logged in on (any company in their branch); their
     * own company_id is only their "home" company. Everyone else: own company.
     */
    public function workingCompany(?string $host = null): ?Company
    {
        if (!$this->isStaff()) {
            return $this->company;
        }

        $host = $host ?? request()->getHost();
        if (!array_key_exists($host, $this->workingCompanyCache)) {
            $this->workingCompanyCache[$host] = Company::where('branch_id', $this->branch_id)
                ->whereNotNull('domain')
                ->get()
                ->first(fn ($company) => $company->matchesHost($host));
        }

        return $this->workingCompanyCache[$host];
    }

    /** The shift occurrence this staff member may be logged in for right now. */
    public function currentShiftWindow(?Carbon $now = null): ?ShiftWindow
    {
        return ShiftWindow::current($this->shift, $now);
    }

    /** Staff may only be logged in during their shift (plus grace); others always. */
    public function isWithinShift(?Carbon $now = null): bool
    {
        return !$this->isStaff() || $this->currentShiftWindow($now) !== null;
    }

    /** Staff may only use their own company's domain; others may use any. */
    public function isOnAllowedDomain(?string $host): bool
    {
        if (!$this->isStaff()) {
            return true;
        }
        return $this->company !== null && $this->company->matchesHost($host);
    }

    /** Why a staff member may not be logged in here and now, or null if they may. */
    public function staffAccessProblem(?string $host, ?Carbon $now = null): ?string
    {
        if (!$this->isStaff()) {
            return null;
        }
        if (!$this->company || !$this->company->domain) {
            return 'Your company has no domain set yet. Please contact your manager.';
        }
        if (!$this->isOnAllowedDomain($host)) {
            return "Please log in at your company's address: " . $this->company->domain;
        }
        if (!in_array($this->shift, ShiftWindow::shifts(), true)) {
            return 'You have no shift (AM / PM) assigned. Please contact your manager.';
        }
        if (!$this->isWithinShift($now)) {
            $next = ShiftWindow::nextLoginFrom($this->shift, $now);
            return 'You are outside your ' . $this->shift . ' shift time.'
                . ($next ? ' You can log in from ' . $next->format('d M H:i') . '.' : '');
        }
        return null;
    }
}

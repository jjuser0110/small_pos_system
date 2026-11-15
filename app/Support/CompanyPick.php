<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

/**
 * The company chosen in the top bar picker ("All", Company A, Company B...).
 * Superadmin, admin and branch managers can pick one; every back-office page
 * then shows only that company's records. Kept in the session.
 */
class CompanyPick
{
    public const SESSION_KEY = 'picked_company_id';
    public const ROLES = [1, 2, 3];

    private static array $cache = [];

    /** Can the logged-in user use the picker? */
    public static function available(): bool
    {
        return in_array((int) Auth::user()?->role_id, self::ROLES, true);
    }

    /** Companies the user can pick from (a branch manager: their branch only). */
    public static function options()
    {
        $user = Auth::user();
        if (!self::available()) {
            return collect();
        }

        return Company::query()
            ->when($user->role_id == 3, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('company_name')
            ->get();
    }

    /** The picked company, or null for "All". Ignores a pick the user can no longer reach. */
    public static function company(): ?Company
    {
        if (!self::available()) {
            return null;
        }
        $id = session(self::SESSION_KEY);
        if (!$id) {
            return null;
        }

        $user = Auth::user();
        $key = $user->id . ':' . $id;
        if (!array_key_exists($key, self::$cache)) {
            $company = Company::find($id);
            if ($company && $user->role_id == 3 && (int) $company->branch_id !== (int) $user->branch_id) {
                $company = null;
            }
            self::$cache[$key] = $company;
        }

        return self::$cache[$key];
    }

    public static function id(): ?int
    {
        return self::company()?->id;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

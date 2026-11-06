<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

/**
 * Record-level access rules shared by the back-office controllers.
 *
 *   1, 2 (admins)       : every record
 *   3 (branch manager)  : records in their branch
 *   4 (company manager) : records in their company
 *   5 (staff)           : only records they own (e.g. their own orders/shifts)
 */
trait AuthorizesScope
{
    /** Can the logged-in user reach a record with these branch / company / owner ids? */
    protected function userCanAccess($branchId, $companyId, $ownerId = null): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return match ((int) $user->role_id) {
            1, 2    => true,
            3       => $branchId !== null && $user->branch_id !== null && $branchId == $user->branch_id,
            4       => $companyId !== null && $user->company_id !== null && $companyId == $user->company_id,
            5       => $ownerId !== null && $ownerId == $user->id,
            default => false,
        };
    }

    /**
     * Abort with 403 unless the record (which has branch_id and company_id
     * columns) is inside the user's scope. $ownerField names the column that
     * lets staff (role 5) see their own records; leave it null to block staff.
     */
    protected function authorizeRecord($record, ?string $ownerField = null): void
    {
        $owner = $ownerField ? $record->{$ownerField} : null;

        if (!$this->userCanAccess($record->branch_id, $record->company_id, $owner)) {
            abort(403, 'Access denied');
        }
    }

    /** Companies the logged-in user may create or move records into. */
    protected function scopedCompanies()
    {
        $user = Auth::user();

        return match ((int) $user->role_id) {
            1, 2    => Company::all(),
            3       => Company::where('branch_id', $user->branch_id)->get(),
            4       => Company::where('id', $user->company_id)->get(),
            default => collect(),
        };
    }

    /** Abort with 403 unless the given company id is one the user may use. */
    protected function authorizeCompanyId($companyId): Company
    {
        $company = $companyId ? Company::find($companyId) : null;

        if (!$company || !$this->userCanAccess($company->branch_id, $company->id)) {
            abort(403, 'Access denied');
        }

        return $company;
    }

    /** Abort with 403 unless the given category id is one the user may use. */
    protected function authorizeCategoryId($categoryId): Category
    {
        $category = $categoryId ? Category::find($categoryId) : null;

        if (!$category) {
            abort(403, 'Access denied');
        }
        $this->authorizeRecord($category);

        return $category;
    }
}

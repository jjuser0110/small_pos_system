<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffBorrow;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffBorrowController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        $query = StaffBorrow::with(['user', 'approver'])->orderBy('id', 'DESC');

        if ($login_user->role_id == 5) {
            $query->where('user_id', $login_user->id);          // staff: own records
        } elseif ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id); // manager: own branch
        }                                                       // others: everything

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $borrows = $query->get();

        return view('staff_borrow.index')->with('borrows', $borrows);
    }

    public function store(Request $request)
    {
        $login_user = Auth::user();
        if (!in_array($login_user->role_id, [1, 2, 5])) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'reason' => 'nullable|string|max:500',
        ]);

        StaffBorrow::create([
            'user_id'    => $login_user->id,
            'branch_id'  => $login_user->branch_id,
            'company_id' => $login_user->company_id,
            'amount'     => round($request->amount, 2),
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return redirect()->route('staff_borrow.index')->withSuccess('Borrow request submitted, waiting for approval');
    }

    public function approve(StaffBorrow $staff_borrow)
    {
        return $this->decide($staff_borrow, 'approved');
    }

    public function reject(Request $request, StaffBorrow $staff_borrow)
    {
        return $this->decide($staff_borrow, 'rejected', $request->reject_reason);
    }

    private function decide(StaffBorrow $borrow, $status, $reject_reason = null)
    {
        $login_user = Auth::user();

        // roles 1 and 2 can approve any branch; role 3 only their own branch
        $can_approve = in_array($login_user->role_id, [1, 2])
            || ($login_user->role_id == 3 && $borrow->branch_id == $login_user->branch_id);

        if (!$can_approve) {
            abort(403);
        }

        // only update if still pending (prevents double approve from two clicks/tabs)
        $updated = StaffBorrow::where('id', $borrow->id)
            ->where('status', 'pending')
            ->update([
                'status'        => $status,
                'approved_by'   => $login_user->id,
                'status_at'     => Carbon::now(),
                'reject_reason' => $status == 'rejected' ? $reject_reason : null,
            ]);

        if (!$updated) {
            return redirect()->back()->withErrors('This request was already processed');
        }

        return redirect()->back()->withSuccess('Request ' . $status);
    }
}
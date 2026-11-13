<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffBorrow;
use App\Models\ShiftClosing;
use Illuminate\Support\Facades\DB;
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

    /** Staff ask for an advance; it is tied to the shift they are working now. */
    public function store(Request $request)
    {
        $login_user = Auth::user();
        if (!$login_user->isStaff()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'reason' => 'nullable|string|max:500',
        ]);

        StaffBorrow::create([
            'user_id'          => $login_user->id,
            'branch_id'        => $login_user->workingCompany()?->branch_id ?? $login_user->branch_id,
            'company_id'       => $login_user->workingCompany()?->id ?? $login_user->company_id,
            'shift_closing_id' => ShiftClosing::openFor($login_user)?->id,
            'amount'           => round($request->amount, 2),
            'reason'           => $request->reason,
            'status'           => 'pending',
        ]);

        return redirect()->route('staff_borrow.index')->withSuccess('Advance request submitted, waiting for superadmin approval');
    }

    public function approve(StaffBorrow $staff_borrow)
    {
        return $this->decide($staff_borrow, 'approved');
    }

    public function reject(Request $request, StaffBorrow $staff_borrow)
    {
        return $this->decide($staff_borrow, 'rejected', $request->reject_reason);
    }

    /** Only the superadmin decides. Approval cuts the amount from the requesting shift's sales. */
    private function decide(StaffBorrow $borrow, $status, $reject_reason = null)
    {
        $login_user = Auth::user();
        if ((int) $login_user->role_id !== 1) {
            abort(403);
        }

        $updated = DB::transaction(function () use ($borrow, $status, $reject_reason, $login_user) {
            $locked = StaffBorrow::lockForUpdate()->find($borrow->id);
            if (!$locked || $locked->status !== 'pending') {
                return false;   // double click / another tab
            }

            $locked->update([
                'status'        => $status,
                'approved_by'   => $login_user->id,
                'status_at'     => Carbon::now(),
                'reject_reason' => $status == 'rejected' ? $reject_reason : null,
            ]);

            if ($status === 'approved' && ($shift = ShiftClosing::lockForUpdate()->find($locked->shift_closing_id))) {
                $shift->update(['advance_amount' => round($shift->advance_amount + $locked->amount, 2)]);
            }
            return true;
        });

        if (!$updated) {
            return redirect()->back()->withErrors('This request was already processed');
        }

        return redirect()->back()->withSuccess('Advance ' . $status);
    }
}

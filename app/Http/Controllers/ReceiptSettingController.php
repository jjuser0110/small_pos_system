<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ReceiptSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Receipt header and footer per branch. A branch manager edits their own
 * branch's; admins pick a branch, or the default used by branches without
 * their own.
 */
class ReceiptSettingController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->branchToEdit($request);

        return view('receipt_setting.index', [
            'branchId'        => $branchId,
            'branches'        => Auth::user()->role_id == 3 ? collect() : Branch::orderBy('branch_name')->get(),
            // the branch's own setting; if it has none yet, start from the default
            'own'             => ReceiptSetting::where('branch_id', $branchId)->first(),
            'receipt_setting' => ReceiptSetting::forBranch($branchId),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'header'    => 'required|string|max:2000',
            'footer'    => 'required|string|max:2000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        $branchId = $this->branchToEdit($request);

        ReceiptSetting::updateOrCreate(
            ['branch_id' => $branchId],
            ['header' => $request->header, 'footer' => $request->footer]
        );

        return redirect()->route('receipt_setting.index', $branchId ? ['branch_id' => $branchId] : [])->withSuccess('Data saved');
    }

    /** Branch manager: always their branch. Admin: the chosen branch, or null for the default. */
    private function branchToEdit(Request $request): ?int
    {
        $user = Auth::user();
        if ($user->role_id == 3) {
            return (int) $user->branch_id;
        }
        return $request->filled('branch_id') ? (int) $request->branch_id : null;
    }
}

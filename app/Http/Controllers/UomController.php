<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Units of measure belong to a branch. A branch manager sees and manages
 * their branch's units (plus shared ones, which only admins can change);
 * admins see all and choose the branch.
 */
class UomController extends Controller
{
    public function index(Request $request)
    {
        $query = Uom::with('branch')->orderBy('uom_name');
        if (Auth::user()->role_id == 3) {
            $query->forBranch(Auth::user()->branch_id);
        }

        return view('uom.index')->with('uom', $query->get());
    }

    public function create()
    {
        return view('uom.create')->with('branches', $this->branchesForForm());
    }

    public function store(Request $request)
    {
        Uom::create($this->validated($request));

        return redirect()->route('uom.index')->withSuccess('Data saved');
    }

    public function edit(Uom $uom)
    {
        $this->authorizeUom($uom);

        return view('uom.create')->with('uom', $uom)->with('branches', $this->branchesForForm());
    }

    public function update(Request $request, Uom $uom)
    {
        $this->authorizeUom($uom);
        $uom->update($this->validated($request));

        return redirect()->route('uom.index')->withSuccess('Data updated');
    }

    public function destroy(Uom $uom)
    {
        $this->authorizeUom($uom);

        if ($uom->products()->count() > 0) {
            return redirect()->route('uom.index')->withErrors('UOM has related items. You can not delete this.');
        }
        $uom->delete();

        return redirect()->route('uom.index')->withSuccess('Data deleted');
    }

    private function validated(Request $request): array
    {
        $user = Auth::user();
        $request->validate([
            'uom_name'  => 'required|string|max:100',
            'uom_unit'  => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        return [
            'uom_name'  => $request->uom_name,
            'uom_unit'  => $request->uom_unit,
            // a branch manager always works on their own branch; admins may leave it shared
            'branch_id' => $user->role_id == 3 ? $user->branch_id : ($request->branch_id ?: null),
        ];
    }

    private function branchesForForm()
    {
        return Auth::user()->role_id == 3 ? collect() : Branch::orderBy('branch_name')->get();
    }

    /** Branch managers may only change their own branch's units, not shared ones. */
    private function authorizeUom(Uom $uom): void
    {
        $user = Auth::user();
        if ($user->role_id == 3 && (int) $uom->branch_id !== (int) $user->branch_id) {
            abort(403, 'Shared units can only be changed by an admin.');
        }
    }
}

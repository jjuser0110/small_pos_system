<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Suppliers belong to a branch. A branch manager sees and manages only their
 * branch's suppliers; admins see all and choose the branch.
 */
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::with('branch')->orderBy('name');
        if (Auth::user()->role_id == 3) {
            $query->where('branch_id', Auth::user()->branch_id);
        }

        return view('supplier.index')->with('supplier', $query->get());
    }

    public function create()
    {
        return view('supplier.create')->with('branches', $this->branchesForForm());
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('supplier.index')->withSuccess('Data saved');
    }

    public function edit(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);

        return view('supplier.create')->with('supplier', $supplier)->with('branches', $this->branchesForForm());
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);
        $supplier->update($this->validated($request));

        return redirect()->route('supplier.index')->withSuccess('Data updated');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);

        if ($supplier->batches()->exists()) {
            return redirect()->route('supplier.index')->withErrors('This supplier is used on stock entries. You can not delete it.');
        }
        $supplier->delete();

        return redirect()->route('supplier.index')->withSuccess('Data deleted');
    }

    private function validated(Request $request): array
    {
        $user = Auth::user();
        $request->validate([
            'name'      => 'required|string|max:255',
            'branch_id' => $user->role_id == 3 ? 'nullable' : 'required|exists:branches,id',
        ]);

        return [
            'name'      => $request->name,
            // a branch manager always creates for their own branch
            'branch_id' => $user->role_id == 3 ? $user->branch_id : $request->branch_id,
        ];
    }

    private function branchesForForm()
    {
        return Auth::user()->role_id == 3 ? collect() : Branch::orderBy('branch_name')->get();
    }

    private function authorizeSupplier(Supplier $supplier): void
    {
        $user = Auth::user();
        if ($user->role_id == 3 && (int) $supplier->branch_id !== (int) $user->branch_id) {
            abort(403, 'Access denied');
        }
    }
}

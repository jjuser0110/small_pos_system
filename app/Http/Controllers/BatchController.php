<?php

namespace App\Http\Controllers;

use App\Exports\StockTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StockImport;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Batch;
use App\Models\RunningNumber;
use App\Models\Company;
use App\Models\Product;
use App\Models\BatchItem;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DB;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $batch = Batch::with('supplier')->where('branch_id',$login_user->branch_id)->orderBy('created_at','DESC')->get();
        }else if($login_user->role_id == 4){
            $batch = Batch::where('company_id',$login_user->company_id)->orderBy('created_at','DESC')->get();
        }else{
            $batch = Batch::with('supplier')->orderBy('created_at','DESC')->get();
        }
        return view('batch.index')->with('batch',$batch);
    }

    public function create()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
        }

        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        $check = RunningNumber::where('name', 'stock_batch')
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$check) {
            $check = RunningNumber::create([
                'code' => 'SB',
                'name' => 'stock_batch',
                'year' => $year,
                'month' => $month,
                'no_of_digit_behind' => 4,
                'running_no' => 1,
            ]);
        }

        $batch_no = $check->code .
            $check->year .
            sprintf('%02d', $check->month) .
            sprintf('%0' . $check->no_of_digit_behind . 'd', $check->running_no);

        return view('batch.create', [
            'batch_no' => $batch_no,
            'code' => $check->code,
            'year' => $check->year,
            'month' => $check->month,
            'company' => $company,
            'suppliers' => $this->suppliersForUser(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'batch_no'    => 'required|string',
            'batch_date'  => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id,deleted_at,NULL',
            'supplier_invoice' => 'nullable|string|max:100',
        ], [
            'supplier_id.required' => 'Choose which supplier this stock comes from.',
        ]);
        $company = $this->authorizeCompanyId($request->company_id);

        // the supplier must belong to the company's branch (or be shared)
        if (!\App\Models\Supplier::forBranch($company->branch_id)->whereKey($request->supplier_id)->exists()) {
            return back()->withErrors(['supplier_id' => 'This supplier is not for this branch.'])->withInput();
        }

        // status and totals are never taken from the request
        $batch = Batch::create([
            'branch_id'  => $company->branch_id,
            'company_id' => $company->id,
            'supplier_id' => $request->supplier_id,
            'supplier_invoice' => $request->supplier_invoice,
            'batch_no'   => $request->batch_no,
            'batch_date' => $request->batch_date,
        ]);
        $check = RunningNumber::where('code', $request->code)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();
        $check->increment('running_no');

        return redirect()->route('batch.edit',$batch)->withSuccess('Data saved');
    }

    public function edit(Batch $batch)
    {
        $this->authorizeRecord($batch);

        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
        }

        $product = Product::with(['units', 'uom_dt'])->where('company_id', $batch->company_id)->where('is_active', 1)->orderBy('product_name')->get();
        $batch->load(['batch_items.product.allUnits', 'batch_items.product.uom_dt', 'supplier']);

        $receipt = \App\Models\ReceiptSetting::first();

        return view('batch.create')->with('batch',$batch)->with('company',$company)->with('product',$product)
            ->with('receipt', $receipt)
            ->with('suppliers', \App\Models\Supplier::forBranch($batch->branch_id)->orderBy('name')->get());
    }

    public function update(Request $request, Batch $batch)
    {
        $this->authorizeRecord($batch);

        $request->validate([
            'batch_date'       => 'sometimes|date',
            'supplier_invoice' => 'nullable|string|max:100',
        ]);
        $batch->update($request->only('batch_date', 'supplier_invoice'));

        if ($request->input('back') === 'edit') {
            return redirect()->route('batch.edit', $batch)->withSuccess('Saved');
        }

        return redirect()->route('batch.index')->withSuccess('Data updated');
    }

    public function destroy(Batch $batch)
    {
        $this->authorizeRecord($batch);

        $batch->batch_items()->delete();
        $batch->delete();

        return redirect()->route('batch.index')->withSuccess('Data deleted');
    }

    public function complete(Batch $batch)
    {
        $this->authorizeRecord($batch);

        // completing twice would add the stock twice
        if ($batch->status !== 'Open') {
            return redirect()->route('batch.index')->withErrors('This stock entry is already completed.');
        }

        if($batch->batch_items->isEmpty()){
            return redirect()->back()->withErrors('No Batch Items to process');
        }
        $total_product = $batch->batch_items->count();
        $total_item = $batch->batch_items->sum('quantity');
        $total_cost = $batch->batch_items->sum('total_cost');
        // lock the batch so two clicks cannot both add the stock
        $done = DB::transaction(function () use ($batch, $total_product, $total_item, $total_cost) {
            $locked = Batch::lockForUpdate()->find($batch->id);
            if ($locked->status !== 'Open') {
                return false;
            }
            foreach($batch->batch_items as $item){
                $product = Product::lockForUpdate()->find($item->product_id);
                $before_stock = $product->stock_quantity;
                $quantity = $item->quantity;
                $after_stock = $before_stock + $quantity;
                $product->update(['stock_quantity'=>$after_stock]);
                $item->stock_logs()->create([
                    'branch_id' => $item->branch_id,
                    'company_id' => $item->company_id,
                    'category_id' => $item->category_id,
                    'product_id' => $item->product_id,
                    'type' => 'stock_in',
                    'description' => $batch->batch_no??'',
                    'before_stock' => $before_stock,
                    'quantity' => $quantity,
                    'after_stock' => $after_stock,
                ]);
            }
            $batch->update([
                'total_product' => $total_product,
                'total_item' => $total_item,
                'total_cost' => $total_cost,
                'status' => 'Completed',
            ]);
            return true;
        });

        if (!$done) {
            return redirect()->route('batch.edit', $batch)->withErrors('This stock entry is already completed.');
        }

        return redirect()->route('batch.edit', $batch)
            ->with('print', true)
            ->withSuccess('Stock added');
    }

    /**
     * Add an item to an open stock entry, by item id or by scanned barcode
     * (the item's own or one of its sell options'). Adding the same item and
     * unit again adds 1 to that row. Answers JSON for the stock page.
     */
    public function addBatchItem(Request $request, Batch $batch)
    {
        $this->authorizeRecord($batch);
        if ($batch->status !== 'Open') {
            return $this->batchItemReply($request, false, 'This stock entry is already completed.');
        }

        $product = null;
        $unit = null;
        if ($request->filled('barcode')) {
            $code = trim($request->barcode);
            $unit = \App\Models\ProductUnit::where('barcode', $code)->where('is_active', true)
                ->whereHas('product', fn ($q) => $q->where('company_id', $batch->company_id))->first();
            $product = $unit?->product ?? Product::where('company_id', $batch->company_id)->where('barcode', $code)->first();
        } elseif ($request->filled('product_id')) {
            $product = Product::where('id', $request->product_id)->where('company_id', $batch->company_id)->first();
            if ($product && $request->filled('unit_id')) {
                $unit = $product->allUnits()->where('id', $request->unit_id)->first();
            }
        }
        if (!$product) {
            return $this->batchItemReply($request, false, 'No item with that barcode in this company.');
        }

        $row = BatchItem::where('batch_id', $batch->id)->where('product_id', $product->id)
            ->where('entry_unit_id', $unit?->id)->first();

        if ($row) {
            $this->saveEntryRow($row, $unit, (float) $row->entry_qty + 1, (float) $row->entry_cost);
        } else {
            // start from what this item cost last time (per entry unit)
            $lastCostPerBase = (float) BatchItem::where('product_id', $product->id)->where('cost_per_unit', '>', 0)
                ->latest('id')->value('cost_per_unit');
            $row = new BatchItem([
                'batch_id' => $batch->id, 'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
                'category_id' => $product->category_id, 'product_id' => $product->id,
            ]);
            $this->saveEntryRow($row, $unit, 1, round($lastCostPerBase * ($unit ? $unit->base_qty : 1), 2));
        }

        return $this->batchItemReply($request, true, 'Added ' . $product->product_name . ($unit ? " ({$unit->name})" : ''), $row);
    }

    /**
     * Save a row of an open stock entry as keyed in: quantity in the chosen
     * unit and either the cost per unit or the total cost. Stored in base units.
     */
    public function updateOpenItem(Request $request, BatchItem $batch_item)
    {
        $this->authorizeRecord($batch_item);
        if ($batch_item->batch->status !== 'Open') {
            return response()->json(['ok' => false, 'message' => 'This stock entry is already completed.'], 422);
        }

        $request->validate([
            'unit_id'    => 'nullable|integer',
            'entry_qty'  => 'required|numeric|gt:0',
            'entry_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'changed'    => 'nullable|in:qty,cost,total,unit',
        ]);

        $unit = $request->filled('unit_id') ? $batch_item->product->allUnits()->where('id', $request->unit_id)->first() : null;
        $qty = (float) $request->entry_qty;
        // total edited: cost per unit = total / qty; otherwise total = qty x cost per unit
        $cost = $request->changed === 'total' && $qty > 0
            ? (float) $request->total_cost / $qty
            : (float) $request->entry_cost;

        $this->saveEntryRow($batch_item, $unit, $qty, $cost);
        $this->updateBatchTotals($batch_item->batch);

        return response()->json(['ok' => true, 'row' => $this->rowJson($batch_item->fresh())]);
    }

    /** Keyed-in quantity/cost -> base units. 10 Carton (24) at RM48 = 240 base at RM2.00. */
    private function saveEntryRow(BatchItem $row, $unit, float $entryQty, float $entryCost): void
    {
        $perUnit = $unit ? (float) $unit->base_qty : 1.0;
        $baseQty = round($entryQty * $perUnit, 4);
        $total = round($entryQty * $entryCost, 2);

        $row->fill([
            'entry_unit_id' => $unit?->id,
            'entry_unit'    => $unit?->name,
            'entry_qty'     => $entryQty,
            'entry_cost'    => round($entryCost, 4),
            'quantity'      => $baseQty,
            'balance'       => $baseQty,                      // open entry: nothing used yet
            'total_cost'    => $total,
            'cost_per_unit' => $baseQty > 0 ? round($total / $baseQty, 4) : 0,
        ])->save();
    }

    private function rowJson(BatchItem $row): array
    {
        return [
            'id' => $row->id, 'entry_qty' => (float) $row->entry_qty, 'entry_cost' => round((float) $row->entry_cost, 4),
            'total_cost' => (float) $row->total_cost, 'quantity' => (float) $row->quantity,
            'cost_per_unit' => (float) $row->cost_per_unit, 'unit_id' => $row->entry_unit_id,
        ];
    }

    private function batchItemReply(Request $request, bool $ok, string $message, ?BatchItem $row = null)
    {
        if ($request->expectsJson()) {
            $html = null;
            if ($row) {
                $row->load(['product.allUnits', 'product.uom_dt']);
                $batch = $row->batch;
                $index = BatchItem::where('batch_id', $batch->id)->where('id', '<', $row->id)->count();
                $html = view('batch._row', compact('row', 'index', 'batch'))->render();
            }
            return response()->json([
                'ok' => $ok, 'message' => $message,
                'row' => $row ? $this->rowJson($row) : null,
                'html' => $html,
            ], $ok ? 200 : 422);
        }
        return $ok ? back()->withSuccess($message) : back()->withErrors($message);
    }

    public function destroyItem(Request $request, BatchItem $batch_item)
    {
        $this->authorizeRecord($batch_item);
        $batch = $batch_item->batch;
        if ($batch->status !== 'Open') {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'This stock entry is already completed.'], 422)
                : back()->withErrors('This stock entry is already completed.');
        }

        $batch_item->delete();
        $this->updateBatchTotals($batch);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->back()->withSuccess('Data deleted');
    }

    public function updateBatchItem(Request $request, BatchItem $batchItem)
    {
        $this->authorizeRecord($batchItem);

        $request->validate([
            'quantity'      => 'required',
            'cost_per_unit' => 'required',
            'total_cost'    => 'required',
        ]);

        $total_cost = round($request->quantity * $request->cost_per_unit, 2);
        if ($total_cost <> $request->total_cost) {
            return back()->withErrors('Incorrect value. Please try again.');
        }

        return DB::transaction(function () use ($request, $batchItem) {

            $product = $batchItem->product;
            if (empty($product)) {
                return back()->withErrors('Product not found.');
            }

            $oldQty = $batchItem->quantity;
            $newQty = $request->quantity;
            $adjustment = $newQty - $oldQty;

            if ($adjustment < 0 && abs($adjustment) > $batchItem->balance) {
                return back()->withErrors('Unable to reduce quantity more than available balance.');
            }

            if ($request->cost_per_unit <> $batchItem->cost_per_unit) {
                foreach ($batchItem->profit_items as $profit_item) {
                    $profit_item->update([
                        'cost_price'        => $request->cost_per_unit,
                        'earning'           => round($profit_item->selling_price - $request->cost_per_unit, 2),
                        'total_cost_price'  => round($request->cost_per_unit * $profit_item->quantity, 2),
                        'total_earning'     => round(($profit_item->selling_price - $request->cost_per_unit) * $profit_item->quantity, 2),
                    ]);
                }
            }

            if ($adjustment === 0) {

                $batchItem->update([
                    'quantity'       => $request->quantity,
                    'cost_per_unit'  => $request->cost_per_unit,
                    'entry_unit_id' => null, 'entry_unit' => null, 'entry_qty' => $request->quantity, 'entry_cost' => $request->cost_per_unit,
                    'total_cost'     => $request->total_cost,
                ]);

                $this->updateBatchTotals($batchItem->batch);

                return back()->withSuccess('Data updated');
            }

            $type = $adjustment > 0 ? 'adjust_in' : 'adjust_out';
            $stockChange = abs($adjustment);

            $newStock = $type === 'adjust_in'
                ? $product->stock_quantity + $stockChange
                : $product->stock_quantity - $stockChange;

            $balance = $type === 'adjust_in'
                ? $batchItem->balance + $stockChange
                : $batchItem->balance - $stockChange;

            $batchItem->stock_logs()->create([
                'branch_id'     => $product->branch_id,
                'company_id'    => $product->company_id,
                'category_id'   => $product->category_id,
                'product_id'    => $product->id,
                'type'          => $type,
                'description'   => $batchItem->batch->batch_no ?? '',
                'before_stock'  => $product->stock_quantity,
                'quantity'      => $stockChange,
                'after_stock'   => $newStock,
            ]);

            $batchItem->product->update([
                'stock_quantity' => $newStock,
            ]);

            $batchItem->update([
                'quantity'       => $request->quantity,
                'cost_per_unit'  => $request->cost_per_unit,
                    'entry_unit_id' => null, 'entry_unit' => null, 'entry_qty' => $request->quantity, 'entry_cost' => $request->cost_per_unit,
                'total_cost'     => $request->total_cost,
                'balance'        => $balance,
            ]);

            $this->updateBatchTotals($batchItem->batch);

            return back()->withSuccess('Batch item updated successfully');
        });
    }

    private function updateBatchTotals($batch)
    {
        $batch->update([
            'total_product' => $batch->batch_items()->count(),
            'total_item'    => $batch->batch_items()->sum('quantity'),
            'total_cost'    => $batch->batch_items()->sum('total_cost'),
        ]);
    }

    public function downloadTemplate(Batch $batch)
    {
        $this->authorizeRecord($batch);

        return Excel::download(new StockTemplateExport($batch), 'stock_template.xlsx');
    }

    public function import(Request $request, Batch $batch)
    {
        $this->authorizeRecord($batch);

        $request->validate([
            'file'       => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new StockImport($batch), $request->file('file'));

        return back()->with('success', 'Batch items imported successfully');
    }

    /** Suppliers for the stock form: a branch manager sees their branch's (and shared) ones. */
    private function suppliersForUser()
    {
        $query = \App\Models\Supplier::with('branch')->orderBy('name');
        if (Auth::user()->role_id == 3) {
            $query->forBranch(Auth::user()->branch_id);
        }
        return $query->get();
    }
}

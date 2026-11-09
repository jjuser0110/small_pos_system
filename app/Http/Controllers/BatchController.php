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
            'suppliers' => \App\Models\Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'batch_no'    => 'required|string',
            'batch_date'  => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id,deleted_at,NULL',
        ], [
            'supplier_id.required' => 'Choose which supplier this stock comes from.',
        ]);
        $company = $this->authorizeCompanyId($request->company_id);

        // status and totals are never taken from the request
        $batch = Batch::create([
            'branch_id'  => $company->branch_id,
            'company_id' => $company->id,
            'supplier_id' => $request->supplier_id,
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

        $product = Product::where('branch_id', $batch->branch_id)->where('company_id', $batch->company_id)->get();

        $receipt = \App\Models\ReceiptSetting::first();

        return view('batch.create')->with('batch',$batch)->with('company',$company)->with('product',$product)
            ->with('receipt', $receipt)
            ->with('suppliers', \App\Models\Supplier::orderBy('name')->get());
    }

    public function update(Request $request, Batch $batch)
    {
        $this->authorizeRecord($batch);

        // the edit form has no editable fields; only the date may change here
        $request->validate(['batch_date' => 'sometimes|date']);
        $batch->update($request->only('batch_date'));

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

    public function addBatchItem(Request $request, Batch $batch)
    {
        $this->authorizeRecord($batch);

        $request->validate([
            'product_id'    => 'required',
            'quantity'      => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'total_cost'    => 'required|numeric|min:0',
        ]);

        // the product must belong to the same company as the batch
        $product = Product::where('id', $request->product_id)
            ->where('company_id', $batch->company_id)
            ->first();
        if (!$product) {
            return redirect()->back()->withErrors('Product does not belong to this batch\'s company.');
        }

        BatchItem::create([
            'batch_id'      => $batch->id,
            'branch_id'     => $product->branch_id,
            'company_id'    => $product->company_id,
            'category_id'   => $product->category_id,
            'product_id'    => $product->id,
            'quantity'      => $request->quantity,
            'cost_per_unit' => $request->cost_per_unit,
            'total_cost'    => $request->total_cost,
            'balance'       => $request->quantity,
        ]);
        return redirect()->back()->withSuccess('Data updated');
    }

    public function destroyItem(BatchItem $batch_item)
    {
        $this->authorizeRecord($batch_item);

        $batch_item->delete();

        return redirect()->back()->withSuccess('Data deleted');
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
}

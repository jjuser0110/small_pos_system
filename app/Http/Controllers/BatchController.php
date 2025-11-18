<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $batch = Batch::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $batch = Batch::where('company_id',$login_user->company_id)->get();
        }else{
            $batch = Batch::all();
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
        ]);
    }

    public function store(Request $request)
    {
        $company = Company::find($request->company_id);
        $request->merge(['branch_id'=>$company->branch_id,'company_id'=>$company->id]);
        $batch = Batch::create($request->all());
        $check = RunningNumber::where('code', $request->code)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();
        $check->increment('running_no');

        return redirect()->route('batch.edit',$batch)->withSuccess('Data saved');
    }

    public function edit(Batch $batch)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
            $product = Product::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
            $product = Product::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
            $product = Product::all();
        }

        return view('batch.create')->with('batch',$batch)->with('company',$company)->with('product',$product);
    }

    public function update(Request $request, Batch $batch)
    {
        $batch->update($request->all());
        return redirect()->route('batch.index')->withSuccess('Data updated');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();

        return redirect()->route('batch.index')->withSuccess('Data deleted');
    }

    public function complete(Batch $batch)
    {
        if($batch->batch_items->isEmpty()){
            return redirect()->back()->withErrors('No Batch Items to process');
        }
        $total_product = $batch->batch_items->count();
        $total_item = $batch->batch_items->sum('quantity');
        $total_cost = $batch->batch_items->sum('total_cost');
        foreach($batch->batch_items as $item){
            $product = Product::find($item->product_id);
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

        return redirect()->route('batch.index')->withSuccess('Data deleted');
    }
    
    public function addBatchItem(Request $request, Batch $batch)
    {
        $product = Product::find($request->product_id);
        $request->merge(['branch_id'=>$product->branch_id,'company_id'=>$product->company_id,'category_id'=>$product->category_id,'batch_id'=>$batch->id,'balance'=>$request->quantity]);
        // dd($request->all());
        $batch_items = BatchItem::create($request->all());
        return redirect()->back()->withSuccess('Data updated');
    }

    public function destroyItem(BatchItem $batch_item)
    {
        $batch_item->delete();

        return redirect()->back()->withSuccess('Data deleted');
    }

}

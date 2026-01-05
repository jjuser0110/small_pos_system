<?php

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Uom;
use App\Models\Product;
use App\Models\BatchItem;
use App\Models\Branch;
use App\Models\Company;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $product = Product::where('branch_id',$login_user->branch_id)->get();
            $companies = Company::where('branch_id', $login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $product = Product::where('company_id',$login_user->company_id)->get();
            $companies = Company::where('id', $login_user->company_id)->get();
        }else{
            $product = Product::all();
            $companies = Company::all();
        }

        return view('product.index', compact('product', 'companies'));
    }

    public function create()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $category = Category::where('branch_id',$login_user->branch_id)->get();
            $product_link = Product::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $category = Category::where('company_id',$login_user->company_id)->get();
            $product_link = Product::where('company_id',$login_user->company_id)->get();
        }else{
            $category = Category::all();
            $product_link = Product::all();
        }

        $uom = Uom::all();
        return view('product.create')->with('category',$category)->with('uom',$uom)->with('product_link',$product_link);
    }

    public function store(Request $request)
    {
        $login_user = Auth::user();
        $category = Category::find($request->category_id);
        $request->merge([
            'branch_id'         => $category->branch_id,
            'company_id'        => $category->company_id,
            'initial'           => $request->initial ?? 0,
            'stock_quantity'    => $request->initial ?? 0,
        ]);
        $product = Product::create($request->all());

        if ($product->initial <> 0) {
            $product->stockLogs()->create([
                'branch_id'     => $product->branch_id,
                'company_id'    => $product->company_id,
                'category_id'   => $product->category_id,
                'product_id'    => $product->id,
                'type'          => 'stock_in',
                'description'   => 'Initial',
                'before_stock'  => 0,
                'quantity'      => $product->initial,
                'after_stock'   => $product->initial,
            ]);
        }

        return redirect()->route('product.index')->withSuccess('Data saved');
    }

    public function edit(Product $product)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $category = Category::where('branch_id',$login_user->branch_id)->get();
            $product_link = Product::where('branch_id',$login_user->branch_id)->where('id','!=',$product->id)->get();
        }else if($login_user->role_id == 4){
            $category = Category::where('company_id',$login_user->company_id)->where('id','!=',$product->id)->get();
            $product_link = Product::where('company_id',$login_user->company_id)->where('id','!=',$product->id)->get();
        }else{
            $category = Category::all();
            $product_link = Product::where('id','!=',$product->id)->get();
        }
        $uom = Uom::all();
        return view('product.create')->with('product',$product)->with('category',$category)->with('uom',$uom)->with('product_link',$product_link);
    }

    public function update(Request $request, Product $product)
    {
        $adjustment = $request->initial - $product->initial;
        if ($adjustment <> 0) {
            $type = $adjustment > 0 ? 'adjust_in' : 'adjust_out';
            $stockChange = abs($adjustment);

            $newStock = $type === 'adjust_in'
                    ? $product->stock_quantity + $stockChange
                    : $product->stock_quantity - $stockChange;

            $product->stockLogs()->create([
                'branch_id'     => $product->branch_id,
                'company_id'    => $product->company_id,
                'category_id'   => $product->category_id,
                'product_id'    => $product->id,
                'type'          => $type,
                'description'   => 'Adjust Initial',
                'before_stock'  => $product->stock_quantity,
                'quantity'      => $stockChange,
                'after_stock'   => $newStock,
            ]);

            $request->merge([
                'stock_quantity' => $newStock,
            ]);
        }

        $product->update($request->all());

        return redirect()->route('product.index')->withSuccess('Data updated');
    }

    public function destroy(Product $product)
    {
        if($product->stock_logs()->count()>0){
            return redirect()->route('product.index')->withErrors('Product has related items. You can not delete this.');
        }
        $product->delete();

        return redirect()->route('product.index')->withSuccess('Data deleted');
    }

    public function viewlog(Product $product)
    {
        return view('product.viewlog')->with('product',$product);
    }

    public function convert(Product $product)
    {
        // dd($request->all());
        if($product->stock_quantity > 0){
            $batch = BatchItem::where('product_id', $product->id)
                ->whereHas('batch', function ($query) {
                    $query->where('status', 'Completed');
                })
                ->where('balance', '>', 0)
                ->orderBy('created_at', 'ASC')
                ->first();
            if(isset($batch)){
                $batch->stock_logs()->create([
                    'branch_id' => $product->branch_id,
                    'company_id' => $product->company_id,
                    'category_id' => $product->category_id,
                    'product_id' => $product->id,
                    'type' => 'convert_out',
                    'description' => $batch->batch->batch_no ?? '',
                    'before_stock' => $product->stock_quantity,
                    'quantity' => 1,
                    'after_stock' => $product->stock_quantity - 1,
                ]);
                $batch->update([
                    'balance'=>$batch->balance - 1,
                    'quantity'=>$batch->quantity - 1,
                    'total_cost'=>round($batch->cost_per_unit*($batch->quantity - 1),2),
                ]);
                $product->update(['stock_quantity' => $product->stock_quantity -1]);

                $connected_product = Product::find($product->connected_product_id);
                $total_cost = $batch->cost_per_unit;
                $cost_per_unit = round($total_cost / $product->connected_product_quantity,2);
                $batch_item = BatchItem::create([
                    'batch_id'=> $batch->batch_id,
                    'branch_id'=> $batch->branch_id,
                    'company_id'=> $batch->company_id,
                    'category_id'=> $batch->category_id,
                    'product_id'=> $product->connected_product_id,
                    'quantity'=> $product->connected_product_quantity,
                    'total_cost'=> $total_cost,
                    'cost_per_unit'=> $cost_per_unit,
                    'balance'=> $product->connected_product_quantity,
                ]);

                $batch_item->stock_logs()->create([
                    'branch_id' => $connected_product->branch_id,
                    'company_id' => $connected_product->company_id,
                    'category_id' => $connected_product->category_id,
                    'product_id' => $connected_product->id,
                    'type' => 'convert_in',
                    'description' => $batch->batch->batch_no ?? '',
                    'before_stock' => $connected_product->stock_quantity,
                    'quantity' => $product->connected_product_quantity,
                    'after_stock' => round($connected_product->stock_quantity + $product->connected_product_quantity),
                ]);
                $connected_product->update(['stock_quantity' => round($connected_product->stock_quantity + $product->connected_product_quantity)]);

            }else{
                return redirect()->route('product.index')->withError('No batch item found for this product');
            }
        }else{
            return redirect()->route('product.index')->withError('Stock Not Enough');
        }

        return redirect()->route('product.index')->withSuccess('Item converted');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'product_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'       => 'required|mimes:xlsx,csv',
            'company_id' => 'required',
        ]);

        $company = Company::findOrFail($request->company_id);
        if (!$company) {
            return back()->withErrors('Company not found');
        }

        Excel::import(new ProductImport($company->branch_id, $company->id), $request->file('file'));

        return back()->with('success', 'Products imported successfully');
    }
}

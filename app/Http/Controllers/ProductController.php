<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Uom;
use App\Models\Product;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $product = Product::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $product = Product::where('company_id',$login_user->company_id)->get();
        }else{
            $product = Product::all();
        }

        return view('product.index')->with('product',$product);
    }

    public function create()
    {
        $category = Category::all();
        $uom = Uom::all();
        return view('product.create')->with('category',$category)->with('uom',$uom);
    }

    public function store(Request $request)
    {
        $login_user = Auth::user();
        $category = Category::find($request->category_id);
        $request->merge(['branch_id'=>$category->branch_id,'company_id'=>$category->company_id]);
        $product = Product::create($request->all());

        return redirect()->route('product.index')->withSuccess('Data saved');
    }

    public function edit(Product $product)
    {
        $category = Category::all();
        $uom = Uom::all();
        return view('product.create')->with('product',$product)->with('category',$category)->with('uom',$uom);
    }

    public function update(Request $request, Product $product)
    {
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

}

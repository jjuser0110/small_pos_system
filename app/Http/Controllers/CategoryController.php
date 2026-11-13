<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Category;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $category = Category::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $category = Category::where('company_id',$login_user->company_id)->get();
        }else{
            $category = Category::all();
        }

        return view('category.index')->with('category',$category);
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
        return view('category.create')->with('company',$company);
    }

    public function store(Request $request)
    {
        $company = $this->authorizeCompanyId($request->company_id);

        Category::create(array_merge($this->categoryFields($request), [
            'branch_id'  => $company->branch_id,
            'company_id' => $company->id,
        ]));

        return redirect()->route('category.index')->withSuccess('Data saved');
    }

    public function edit(Category $category)
    {
        $this->authorizeRecord($category);

        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
        }
        return view('category.create')->with('category',$category)->with('company',$company);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeRecord($category);
        $company = $this->authorizeCompanyId($request->company_id ?? $category->company_id);

        $category->update(array_merge($this->categoryFields($request), [
            'branch_id'  => $company->branch_id,
            'company_id' => $company->id,
        ]));
        return redirect()->route('category.index')->withSuccess('Data updated');
    }

    public function destroy(Category $category)
    {
        $this->authorizeRecord($category);

        if($category->products()->count()>0){
            return redirect()->route('category.index')->withErrors('Category has related items. You can not delete this.');
        }
        $category->delete();

        return redirect()->route('category.index')->withSuccess('Data deleted');
    }

    /** Fields a user may set from the category form. */
    private function categoryFields(Request $request): array
    {
        // every item tracks stock and sells at a fixed price
        return $request->only(['category_name', 'arrangement']) + ['special' => 0, 'has_stock' => 1];
    }
}

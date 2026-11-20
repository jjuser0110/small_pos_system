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
        $query = Product::with(['category', 'company', 'branch', 'uom_dt', 'units']);
        if (Auth::user()->role_id == 3) {
            // branch manager: own branch only
            $query->where('branch_id', Auth::user()->branch_id);
        }
        $this->applyCompanyPick($query);

        return view('product.index', [
            'product'   => $query->orderBy('product_name')->get(),
            'companies' => $this->scopedCompanies(),   // for the Excel import
        ]);
    }

    public function create()
    {
        return view('product.create')
            ->with('category', $this->formCategories())
            ->with('uom', $this->formUoms())
            ->with('averageCost', null);
    }

    /** Units for the item form: a branch manager sees their branch's and shared ones. */
    private function formUoms($itemBranchId = null)
    {
        $branchId = Auth::user()->role_id == 3 ? Auth::user()->branch_id : $itemBranchId;
        return Uom::with('branch')->when($branchId, fn ($q) => $q->forBranch($branchId))->orderBy('uom_name')->get();
    }

    /**
     * Categories the user may put items in (their branch for a branch manager,
     * and only the company picked in the top bar). An item being edited keeps
     * its current category in the list.
     */
    private function formCategories($keepCategoryId = null)
    {
        $query = Category::with('company')->orderBy('category_name');
        if (Auth::user()->role_id == 3) {
            $query->where('branch_id', Auth::user()->branch_id);
        }
        if ($picked = $this->pickedCompanyId()) {
            $query->where(fn ($q) => $q->where('company_id', $picked)
                ->when($keepCategoryId, fn ($q) => $q->orWhere('id', $keepCategoryId)));
        }
        return $query->get();
    }

    /** Fields a user may set from the product form. */
    private const FORM_FIELDS = [
        'product_name', 'product_code', 'barcode', 'arrangement', 'uom',
        'selling_price', 'margin', 'is_active',
    ];

    public function store(Request $request)
    {
        $this->validateItem($request);
        $category = $this->authorizeCategoryId($request->category_id);
        $this->assertBarcodesFree($request, $category->company_id);

        $initial = $request->initial ?? 0;
        $product = Product::create(array_merge($request->only(self::FORM_FIELDS), [
            'category_id'    => $category->id,
            'branch_id'      => $category->branch_id,
            'company_id'     => $category->company_id,
            'initial'        => $initial,
            'stock_quantity' => $initial,
        ]));

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

        $this->saveUnits($product, $request->input('units', []));

        return redirect()->route('product.index')->withSuccess('Data saved');
    }

    public function edit(Product $product)
    {
        $this->authorizeRecord($product);

        return view('product.create')
            ->with('product', $product->load('allUnits'))
            ->with('category', $this->formCategories($product->category_id))
            ->with('uom', $this->formUoms($product->branch_id))
            ->with('averageCost', $product->averageCost());
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeRecord($product);
        $this->validateItem($request, $product);
        $category = $this->authorizeCategoryId($request->category_id ?? $product->category_id);
        $this->assertBarcodesFree($request, $category->company_id, $product);

        $data = array_merge($request->only(self::FORM_FIELDS), [
            'category_id' => $category->id,
            'branch_id'   => $category->branch_id,
            'company_id'  => $category->company_id,
        ]);
        if ($request->has('initial')) {
            $data['initial'] = $request->initial ?? 0;
        }

        $adjustment = $request->has('initial') ? ($request->initial - $product->initial) : 0;
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

            // stock only changes through this logged adjustment
            $data['stock_quantity'] = $newStock;
        }

        $product->update($data);
        $this->saveUnits($product, $request->input('units', []));

        return redirect()->route('product.index')->withSuccess('Data updated');
    }

    public function destroy(Product $product)
    {
        $this->authorizeRecord($product);

        if($product->stock_logs()->count()>0){
            return redirect()->route('product.index')->withErrors('Product has related items. You can not delete this.');
        }
        // still on a stock entry (even an open one), a sale, or a transfer: deleting would break those pages
        $inUse = \App\Models\BatchItem::where('product_id', $product->id)->exists()
            || \App\Models\OrderItem::where('product_id', $product->id)->exists()
            || \App\Models\StockTransferItem::where(fn ($q) => $q->where('from_product_id', $product->id)->orWhere('to_product_id', $product->id))
                ->whereHas('transfer', fn ($q) => $q->whereIn('status', [\App\Models\StockTransfer::PENDING_SENDER, \App\Models\StockTransfer::PENDING_RECEIVER]))
                ->exists();
        if ($inUse) {
            return redirect()->route('product.index')
                ->withErrors('This item is on a stock entry, a sale or a waiting transfer. Remove it there first, or set it inactive instead.');
        }
        $product->delete();
        // nobody can sell it any more: take it out of every cart
        \App\Models\Cart::where('product_id', $product->id)->delete();

        return redirect()->route('product.index')->withSuccess('Data deleted');
    }

    public function viewlog(Product $product)
    {
        $this->authorizeRecord($product);

        return view('product.viewlog')->with('product',$product);
    }

    /** A new in-store barcode (EAN-13 starting with 20) that no item or sell option uses yet. */
    public function newBarcode()
    {
        return response()->json(['barcode' => \App\Support\BarcodeGenerator::unique()]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'product_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'       => 'required|mimes:xlsx,csv|max:5120',   // 5 MB
            'company_id' => 'required',
        ]);

        $company = $this->authorizeCompanyId($request->company_id);

        Excel::import(new ProductImport($company->branch_id, $company->id), $request->file('file'));

        return back()->with('success', 'Products imported successfully');
    }

    private function validateItem(Request $request, ?Product $product = null): void
    {
        $request->validate([
            'product_name'     => 'required|string|max:255',
            'selling_price'    => 'required|numeric|min:0',
            'margin'           => 'nullable|numeric|min:-100|max:100000',
            'units'            => 'nullable|array',
            'units.*.name'     => 'required|string|max:100',
            'units.*.base_qty' => 'required|numeric|gt:0',
            'units.*.price'    => 'required|numeric|min:0',
            'units.*.barcode'  => 'nullable|string|max:100',
        ], [
            'units.*.name.required'     => 'Every sell option needs a name.',
            'units.*.base_qty.gt'       => 'Base units in a sell option must be more than 0.',
        ]);

        // a barcode must point to exactly one thing in the company, or scanning cannot work
        $codes = collect($request->input('units', []))->pluck('barcode')->push($request->barcode)
            ->map(fn ($c) => trim((string) $c))->filter();
        if ($codes->duplicates()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['barcode' => 'The same barcode is used twice on this item.']);
        }
    }

    /** Every barcode on the form must be unused by other items / sell options of the same company. */
    private function assertBarcodesFree(Request $request, $companyId, ?Product $product = null): void
    {
        $codes = collect($request->input('units', []))->pluck('barcode')->push($request->barcode)
            ->map(fn ($c) => trim((string) $c))->filter()->unique()->values();
        if ($codes->isEmpty()) {
            return;
        }

        $item = Product::where('company_id', $companyId)->whereIn('barcode', $codes)
            ->when($product, fn ($q) => $q->where('id', '!=', $product->id))->first(['product_name', 'barcode']);
        $unit = $item ? null : \App\Models\ProductUnit::whereIn('barcode', $codes)
            ->whereHas('product', fn ($q) => $q->where('company_id', $companyId)
                ->when($product, fn ($q) => $q->where('id', '!=', $product->id)))
            ->with('product:id,product_name')->first();

        if ($item || $unit) {
            $used = $item ? $item->product_name : ($unit->product->product_name . ' - ' . $unit->name);
            $code = $item ? $item->barcode : $unit->barcode;
            throw \Illuminate\Validation\ValidationException::withMessages([
                'barcode' => "Barcode $code is already used by $used in this company.",
            ]);
        }
    }

    /** Create, update and remove the item's sell options to match the form. */
    private function saveUnits(Product $product, array $rows): void
    {
        $keep = [];
        foreach ($rows as $row) {
            $data = [
                'name'      => trim($row['name']),
                'base_qty'  => (float) $row['base_qty'],
                'price'     => round((float) $row['price'], 2),
                'barcode'   => trim((string) ($row['barcode'] ?? '')) ?: null,
                'is_active' => true,
            ];
            $unit = !empty($row['id']) ? $product->allUnits()->where('id', $row['id'])->first() : null;
            $unit ? $unit->update($data) : $unit = $product->allUnits()->create($data);
            $keep[] = $unit->id;
        }
        // removed rows are soft-deleted so old orders still show what was sold
        $product->allUnits()->whereNotIn('id', $keep)->get()->each->delete();
    }
}

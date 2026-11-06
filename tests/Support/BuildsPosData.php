<?php

namespace Tests\Support;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Uom;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/** Small builders for POS records used by the database-backed tests. */
trait BuildsPosData
{
    protected function makeBranch(string $name = 'Main'): Branch
    {
        return Branch::create(['branch_name' => $name, 'branch_code' => strtoupper(substr($name, 0, 3))]);
    }

    protected function makeCompany(Branch $branch, string $code = 'A', ?string $domain = null): Company
    {
        return Company::create([
            'branch_id' => $branch->id, 'company_name' => "Company $code", 'company_code' => $code, 'domain' => $domain,
        ]);
    }

    protected function makeProduct(Company $company, string $name = 'Beer', float $price = 10, bool $hasStock = true): Product
    {
        $category = Category::firstOrCreate(
            ['company_id' => $company->id, 'category_name' => $hasStock ? 'Drinks' : 'Service'],
            ['branch_id' => $company->branch_id, 'special' => 0, 'has_stock' => $hasStock ? 1 : 0]
        );
        $uom = Uom::firstOrCreate(['uom_name' => 'Bottle'], ['uom_unit' => 'btl']);

        return Product::create([
            'branch_id' => $company->branch_id, 'company_id' => $company->id, 'category_id' => $category->id,
            'product_name' => $name, 'selling_price' => $price, 'uom' => $uom->id, 'stock_quantity' => 0, 'initial' => 0,
        ]);
    }

    /** A Completed batch with one item of $qty at $cost, and the product's stock raised. */
    protected function stockBatch(Product $product, float $qty, float $cost, string $batchNo = 'SB1'): BatchItem
    {
        $batch = Batch::create([
            'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'batch_no' => $batchNo, 'batch_date' => now()->toDateString(), 'status' => 'Completed',
        ]);
        $item = BatchItem::create([
            'batch_id' => $batch->id, 'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'category_id' => $product->category_id, 'product_id' => $product->id,
            'quantity' => $qty, 'balance' => $qty, 'cost_per_unit' => $cost, 'total_cost' => $qty * $cost,
        ]);
        $product->increment('stock_quantity', $qty);

        return $item;
    }

    protected function makeUser(int $role, ?Company $company = null, array $extra = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name' => "User $n", 'username' => "user$n", 'email' => "user$n@example.test",
            'password' => Hash::make('secret-test'), 'role_id' => $role, 'is_active' => 1,
            'branch_id' => $company?->branch_id, 'company_id' => $company?->id,
        ], $extra));
    }
}

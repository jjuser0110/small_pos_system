<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Uom;
use App\Models\User;
use App\Support\ShiftWindow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * LOCAL TESTING ONLY. Adds a separate "Demo Branch" with two companies, items
 * with stock, and test accounts, so every FsMart flow can be tried in the
 * browser. Real branches, companies and users are not touched.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Test accounts (all use the password below):
 *   demo_super    superadmin
 *   demo_manager  branch manager of Demo Branch
 *   demo_staff_a  staff of Demo A (domain = this local site), current shift
 *   demo_staff_b  staff of Demo B (domain demo-b.test), current shift
 *
 * To remove: delete the DEMO branch, its two companies and the demo_* users.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'fsmart-demo-2026';
    public const LOCAL_DOMAIN = 'small_pos_system.test';

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder is for local testing only.');
        }

        $branch = Branch::firstOrCreate(['branch_code' => 'DEMO'], ['branch_name' => 'Demo Branch']);
        $a = Company::firstOrCreate(['company_code' => 'DEMOA'], ['branch_id' => $branch->id, 'company_name' => 'Demo A', 'domain' => self::LOCAL_DOMAIN]);
        $b = Company::firstOrCreate(['company_code' => 'DEMOB'], ['branch_id' => $branch->id, 'company_name' => 'Demo B', 'domain' => 'demo-b.test']);

        foreach (['Cash', 'QR'] as $method) {
            PaymentMethod::firstOrCreate(['payment_method_name' => $method]);
        }
        $uom = Uom::firstOrCreate(['uom_name' => 'Bottle'], ['uom_unit' => 'btl']);

        $beerA = $this->product($a, 'Demo Beer 330ml', '9990001', 12.00, $uom);
        $this->product($a, 'Demo Cola', '9990002', 5.00, $uom);
        $this->product($b, 'Demo Beer Can', '9990101', 12.00, $uom);   // same goods as Demo A's beer, different name

        $this->stock($beerA, 24, 5.00);

        // whichever shift is running now, so the demo staff can log in straight away
        $shift = collect(ShiftWindow::shifts())->first(fn ($s) => ShiftWindow::current($s) !== null) ?? 'AM';

        $this->user('demo_super', 'Demo Superadmin', 1, null, null);
        $this->user('demo_manager', 'Demo Manager', 3, $branch, null);
        $this->user('demo_staff_a', 'Demo Staff A', 5, $branch, $a, $shift);
        $this->user('demo_staff_b', 'Demo Staff B', 5, $branch, $b, $shift);

        $this->command?->info("Demo data ready. Staff shift: $shift. Password is in DemoSeeder::PASSWORD.");
    }

    private function product(Company $company, string $name, string $barcode, float $price, Uom $uom): Product
    {
        $category = Category::firstOrCreate(
            ['company_id' => $company->id, 'category_name' => 'Drinks'],
            ['branch_id' => $company->branch_id, 'special' => 0, 'has_stock' => 1, 'arrangement' => 1]
        );

        return Product::firstOrCreate(
            ['company_id' => $company->id, 'product_name' => $name],
            ['branch_id' => $company->branch_id, 'category_id' => $category->id, 'barcode' => $barcode,
             'selling_price' => $price, 'uom' => $uom->id, 'initial' => 0, 'stock_quantity' => 0, 'is_active' => 1]
        );
    }

    /** A completed stock entry, the same way the Stock screen creates one. */
    private function stock(Product $product, float $qty, float $cost): void
    {
        if (BatchItem::where('product_id', $product->id)->exists()) {
            return; // already seeded
        }
        $batch = Batch::create([
            'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'batch_no' => 'DEMO-' . $product->id, 'batch_date' => now()->toDateString(), 'status' => 'Completed',
            'total_product' => 1, 'total_item' => $qty, 'total_cost' => $qty * $cost,
        ]);
        $item = BatchItem::create([
            'batch_id' => $batch->id, 'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'category_id' => $product->category_id, 'product_id' => $product->id,
            'quantity' => $qty, 'balance' => $qty, 'cost_per_unit' => $cost, 'total_cost' => $qty * $cost,
        ]);
        StockLog::create([
            'content_type' => $item->getMorphClass(), 'content_id' => $item->id,
            'branch_id' => $product->branch_id, 'company_id' => $product->company_id, 'category_id' => $product->category_id,
            'product_id' => $product->id, 'type' => 'stock_in', 'description' => $batch->batch_no,
            'before_stock' => $product->stock_quantity, 'quantity' => $qty, 'after_stock' => $product->stock_quantity + $qty,
        ]);
        $product->increment('stock_quantity', $qty);
    }

    private function user(string $username, string $name, int $role, ?Branch $branch, ?Company $company, ?string $shift = null): void
    {
        User::updateOrCreate(['username' => $username], [
            'name' => $name, 'email' => "$username@demo.test", 'password' => Hash::make(self::PASSWORD),
            'role_id' => $role, 'is_active' => 1, 'branch_id' => $branch?->id ?? $company?->branch_id,
            'company_id' => $company?->id, 'shift' => $shift,
        ]);
    }
}

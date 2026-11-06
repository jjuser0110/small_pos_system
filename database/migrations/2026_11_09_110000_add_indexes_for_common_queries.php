<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the lookups the app runs most. Most *_id columns had no index
 * at all (there are no foreign keys, so MySQL never created any).
 *
 * Each index is only added if an index on the same columns does not already
 * exist, so this is safe on databases that already have some of them.
 */
return new class extends Migration
{
    /** table => list of column sets; the comment says which screen/query uses it */
    private function indexes(): array
    {
        return [
            'users' => [
                ['username'],                         // every login
                ['role_id', 'branch_id'],             // staff / manager lists
                ['company_id'],
            ],
            'carts' => [
                ['user_id', 'product_id'],            // every tap on the counter
            ],
            'companies' => [
                ['branch_id'],
            ],
            'categories' => [
                ['company_id'],                       // counter, product form
                ['branch_id'],
            ],
            'products' => [
                ['company_id', 'is_active'],          // counter product grid
                ['branch_id'],
                ['category_id'],
                ['barcode'],                          // scanner, import, transfer matching
            ],
            'batches' => [
                ['company_id', 'created_at'],         // stock list
                ['branch_id', 'created_at'],
            ],
            'batch_items' => [
                ['product_id', 'created_at'],         // FIFO stock-out on every sale
                ['batch_id'],
            ],
            'stock_logs' => [
                ['product_id', 'created_at'],         // product log, newest first
                ['content_type', 'content_id'],       // "what caused this movement"
            ],
            'orders' => [
                ['created_at'],                       // order list / report date range (admins)
                ['branch_id', 'created_at'],          // branch manager
                ['company_id', 'created_at'],
                ['user_id'],
                ['order_no'],                         // receipt / search
            ],
            'order_items' => [
                ['order_id'],                         // every order's items
                ['product_id'],                       // sales report by item
            ],
            'order_item_profits' => [
                ['order_id'],
                ['order_item_id'],                    // refund / void put stock back
                ['batch_item_id'],                    // cost changes on a stock entry
            ],
            'shift_closings' => [
                ['work_date'],                        // shift report date range
                ['branch_id', 'work_date'],
                ['company_id', 'work_date'],
            ],
            'shift_method_closings' => [
                ['shift_closing_id', 'payment_method'],
            ],
            'shift_closing_details' => [
                ['shift_closing_id', 'category_id'],
            ],
            'running_numbers' => [
                ['name', 'year', 'month', 'day'],     // next order / stock number
            ],
            'refunds' => [
                ['order_id'],
                ['shift_closing_id'],
                ['requested_by'],
            ],
            'damages' => [
                ['status'],                           // superadmin's pending list
                ['product_id'],
            ],
            'staff_borrows' => [
                ['status'],                           // superadmin's pending list
                ['shift_closing_id'],
            ],
            'stock_transfers' => [
                ['to_branch_id', 'status'],           // branch manager's pending transfers
                ['from_branch_id'],
            ],
            'stock_adjustments' => [
                ['company_id'],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($sets as $columns) {
                if (Schema::hasIndex($table, $columns)) {
                    continue;
                }
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($sets as $columns) {
                $name = $this->name($table, $columns);
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    /** Short, stable index names (MySQL allows at most 64 characters). */
    private function name(string $table, array $columns): string
    {
        $name = 'idx_' . $table . '_' . implode('_', $columns);
        return strlen($name) <= 64 ? $name : 'idx_' . substr(md5($name), 0, 20);
    }
};

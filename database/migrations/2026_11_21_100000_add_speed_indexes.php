<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries that run most or read the biggest tables.
 * Each one is skipped if it already exists, so this is safe to run on any copy.
 */
return new class extends Migration
{
    private const INDEXES = [
        // every sale: oldest batch with stock left for this item (skips used-up batches, and locks fewer rows)
        'batch_items'          => [['product_id', 'balance'], ['company_id', 'balance']],
        // order list / category totals / profit by category: sale lines of a company or branch
        'order_items'          => [['company_id', 'order_id'], ['branch_id', 'order_id'], ['category_id']],
        'order_item_profits'   => [['company_id', 'order_id'], ['branch_id', 'order_id'], ['product_id']],
        // orders of a period that are not voided
        'orders'               => [['status', 'created_at']],
        // activity log narrowed by the top bar company
        'activity_logs'        => [['company_id', 'created_at']],
        // item delete check and transfer lines by item
        'stock_transfer_items' => [['from_product_id'], ['to_product_id']],
        // refunds of a sale line, damage / adjustments history per item
        'stock_adjustments'    => [['product_id', 'created_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $columns) {
                if (!$this->columnsExist($table, $columns) || $this->hasIndexOn($table, $columns)) {
                    continue;
                }
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                $name = $this->name($table, $columns);
                if (Schema::hasTable($table) && collect(Schema::getIndexes($table))->contains('name', $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    private function name(string $table, array $columns): string
    {
        return substr('speed_' . $table . '_' . implode('_', $columns), 0, 64);
    }

    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $c) {
            if (!Schema::hasColumn($table, $c)) {
                return false;
            }
        }
        return true;
    }

    /** An index that already starts with these columns does the same job. */
    private function hasIndexOn(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }
        return false;
    }
};

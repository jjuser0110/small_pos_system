<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for lookups added in round 2. Each is skipped if an index on the
 * same columns already exists.
 */
return new class extends Migration
{
    private function indexes(): array
    {
        return [
            'refunds'         => [['status']],                  // superadmin's pending badge, on every page
            'stock_transfers' => [['status']],                  // superadmin's pending badge, on every page
            'shift_closings'  => [['user_id', 'work_date']],    // a staff member's shifts by date (staff page, shift report)
            'damages'         => [['status', 'status_at']],     // approved damage by date (profit report)
        ];
    }

    public function up(): void
    {
        // every shift record now has a work date; fill any old ones so date
        // filters can use the work_date index without a fallback
        DB::table('shift_closings')->whereNull('work_date')->update(['work_date' => DB::raw('DATE(created_at)')]);

        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                if (!Schema::hasIndex($table, $columns)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $this->name($table, $columns)));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                $name = $this->name($table, $columns);
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    private function name(string $table, array $columns): string
    {
        return 'idx_' . $table . '_' . implode('_', $columns);
    }
};
